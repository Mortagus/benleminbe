import p5 from 'p5';
import { SCENE_SIZE_3D, createFlock, resizeFlock, stepFlock, updateDirections } from '../boids/flocking.js';
import { buildLandscape } from './landscape.js';
import { orientationMatrix, updateUps } from './orientation.js';
import { roostPosition } from './roost.js';

const CANVAS_RATIO = 9 / 16;
const MAX_CANVAS_HEIGHT = 620;
// Fraction of the remaining turn applied each frame: boids are drawn turning smoothly towards their heading.
const DIRECTION_SMOOTHING = 0.25;
// Fraction of the remaining roll applied each frame to the up of a boid: slower than the heading, so that a boid
// that goes nearly vertical rolls over several frames instead of snapping.
const UP_SMOOTHING = 0.1;
// Birds are the darkest, the scenery a little lighter and the ground lighter still, so that a bird in front of a tree
// or the ground can still be told apart. To be tuned by eye.
const BIRD_COLOR = '#140e1a';
// Birds are drawn at half the size of their model: a tree is then about ten times as long as a bird, which Benj chose
// by comparing 100, 50, 25 and 10 %. Below that the birds stop being visible; real proportions would be 50 to 100 times.
// It changes only how big a bird looks, not the simulation.
const DEFAULT_BIRD_SCALE = 0.5;
const SCENERY_COLOR = '#2a1e2b';
const GROUND_COLOR = '#43303a';
// The ground stretches to 6000 units, so the far plane of the camera has to reach beyond it.
const NEAR_PLANE = 20;
const FAR_PLANE = 15000;
// The camera looks at the centre of the scene, which is moved to the origin when drawing.
// Close to the ground (it is at y = 421, y pointing down) and looking up at the flock, so that trees and power lines
// stand out against the sky as in a photograph of a murmuration. The camera can still be moved by the user.
const CAMERA_POSITION = [0, 325, 1150];
const FIELD_OF_VIEW = Math.PI / 3.2;

/**
 * Runs the 3D boids simulation in a p5 instance (WEBGL mode) bound to `container`.
 * p5 only handles the canvas, the camera and the drawing; the flocking rules live in ../boids/flocking.js.
 * The canvas is transparent: the sky is a CSS background of the container.
 *
 * @param {HTMLElement} container
 * @param {typeof import('../boids/flocking.js').DEFAULT_PARAMETERS_3D} parameters Mutated live by the controls.
 * @param {{ paused: boolean }} initialState
 * @param {{ birdScale?: number }} [options] `birdScale` draws the birds at that fraction of their model size (half by
 *   default), without touching the simulation: it changes how big a bird looks next to a tree.
 */
export function startBoids3dSketch(container, parameters, initialState, options = {}) {
    const birdScale = options.birdScale ?? DEFAULT_BIRD_SCALE;
    const state = { paused: initialState.paused, flock: null, directions: new Float32Array(0), ups: new Float32Array(0), bird: null, ground: null, scenery: null, frame: 0 };
    const attitude = new Float64Array(16);
    const centre = SCENE_SIZE_3D.map((extent) => extent / 2);
    let instance = null;

    function canvasSize() {
        const width = Math.max(container.clientWidth, 200);

        return { width, height: Math.min(Math.round(width * CANVAS_RATIO), MAX_CANVAS_HEIGHT) };
    }

    /**
     * A small static bird with its nose along +x: a slim body (an elongated diamond) and two swept-back wings.
     * Built once into a p5.Geometry, then drawn with `model()` for every boid.
     */
    function buildBird(p) {
        return p.buildGeometry(() => {
            const nose = [11, 0, 0];
            const tail = [-10, 0, 0];
            const top = [0, -2.5, 0];
            const belly = [0, 2, 0];
            const left = [0, 0, -2];
            const right = [0, 0, 2];
            const triangles = [
                // Body.
                [nose, top, left], [nose, left, belly], [nose, belly, right], [nose, right, top],
                [tail, left, top], [tail, belly, left], [tail, right, belly], [tail, top, right],
                // Wings: flat, swept back.
                [[3, 0, -1], [-7, -1, -17], [-6, 0, -1]],
                [[3, 0, 1], [-6, 0, 1], [-7, -1, 17]],
            ];

            p.beginShape(p.TRIANGLES);

            for (const triangle of triangles) {
                for (const [x, y, z] of triangle) {
                    p.vertex(x, y, z);
                }
            }

            p.endShape();
        });
    }

    /**
     * Builds once a p5.Geometry from lists of triangles (nine numbers each), so drawing it takes a single call.
     * p5 keeps in the geometry the fill colour that is active while it is built (a later fill() does not change it),
     * hence the colour is given here.
     */
    function buildTriangles(p, color, ...lists) {
        p.fill(color);

        return p.buildGeometry(() => {
            p.beginShape(p.TRIANGLES);

            for (const triangles of lists) {
                for (let i = 0; i < triangles.length; i += 3) {
                    p.vertex(triangles[i], triangles[i + 1], triangles[i + 2]);
                }
            }

            p.endShape();
        });
    }

    function drawBoid(p, index) {
        const { pos } = state.flock;
        const { directions, ups } = state;

        orientationMatrix(directions.subarray(index * 3, index * 3 + 3), ups.subarray(index * 3, index * 3 + 3), attitude);

        if (birdScale !== 1) {
            // A uniform scale of the three rotated axes (the translation is applied separately).
            for (const column of [0, 4, 8]) {
                attitude[column] *= birdScale;
                attitude[column + 1] *= birdScale;
                attitude[column + 2] *= birdScale;
            }
        }

        p.push();
        p.translate(pos[index * 3] - centre[0], pos[index * 3 + 1] - centre[1], pos[index * 3 + 2] - centre[2]);
        p.applyMatrix(...attitude);
        p.model(state.bird);
        p.pop();
    }

    // The heading and the up of every boid, smoothed for drawing only.
    function updateAttitudes() {
        state.directions = updateDirections(state.directions, state.flock, DIRECTION_SMOOTHING);
        state.ups = updateUps(state.ups, state.directions, UP_SMOOTHING);
    }

    function sketch(p) {
        p.setup = () => {
            const { width, height } = canvasSize();
            const canvas = p.createCanvas(width, height, p.WEBGL);

            // Without antialiasing the silhouettes are jagged.
            p.setAttributes('antialias', true);
            canvas.attribute('role', 'img');
            canvas.attribute('aria-label', container.dataset.canvasLabel ?? 'Boids 3D simulation');
            p.noStroke();
            p.fill(BIRD_COLOR);
            p.camera(...CAMERA_POSITION, 0, 0, 0, 0, 1, 0);
            p.fill(BIRD_COLOR);
            state.bird = buildBird(p);

            const landscape = buildLandscape();

            state.ground = buildTriangles(p, GROUND_COLOR, landscape.ground);
            state.scenery = buildTriangles(p, SCENERY_COLOR, landscape.horizon, landscape.silhouettes);
            state.flock = createFlock(parameters.count, Math.random, SCENE_SIZE_3D);
            updateAttitudes();
        };

        p.draw = () => {
            if (!state.paused) {
                state.flock = resizeFlock(state.flock, parameters.count, Math.random, SCENE_SIZE_3D);
                // The roost pulls the whole flock gently along a path through the three dimensions.
                const roost = { position: roostPosition(state.frame, SCENE_SIZE_3D), mode: 'pull' };

                state.frame++;
                state.flock = stepFlock(state.flock, parameters, SCENE_SIZE_3D, roost);
                updateAttitudes();
            }

            // Transparent background: the CSS sky shows through.
            p.clear();
            p.perspective(FIELD_OF_VIEW, p.width / p.height, NEAR_PLANE, FAR_PLANE);
            p.orbitControl();
            p.noStroke();

            // The landscape is static: two draw calls, whatever the number of trees. Each geometry carries its colour.
            p.model(state.ground);
            p.model(state.scenery);

            for (let i = 0; i < state.flock.count; i++) {
                drawBoid(p, i);
            }
        };
    }

    instance = new p5(sketch, container);

    new ResizeObserver(() => {
        const { width, height } = canvasSize();

        if (instance.width !== width || instance.height !== height) {
            instance.resizeCanvas(width, height);
        }
    }).observe(container);

    return {
        setPaused(paused) {
            state.paused = paused;
        },
        isPaused() {
            return state.paused;
        },
        reset() {
            state.flock = createFlock(parameters.count, Math.random, SCENE_SIZE_3D);
            state.frame = 0;
            state.directions = new Float32Array(0);
            state.ups = new Float32Array(0);
            updateAttitudes();
        },
        /** Measured frame rate, for diagnostics. */
        frameRate() {
            return instance.frameRate();
        },
    };
}
