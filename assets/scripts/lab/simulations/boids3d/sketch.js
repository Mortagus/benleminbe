import p5 from 'p5';
import { SCENE_SIZE_3D, createFlock, resizeFlock, stepFlock, updateDirections } from '../boids/flocking.js';
import { orientationMatrix, updateUps } from './orientation.js';
import { roostPosition } from './roost.js';

const CANVAS_RATIO = 9 / 16;
const MAX_CANVAS_HEIGHT = 620;
// Fraction of the remaining turn applied each frame: boids are drawn turning smoothly towards their heading.
const DIRECTION_SMOOTHING = 0.25;
// Fraction of the remaining roll applied each frame to the up of a boid: slower than the heading, so that a boid
// that goes nearly vertical rolls over several frames instead of snapping.
const UP_SMOOTHING = 0.1;
const BIRD_COLOR = '#1b1424';
// The camera looks at the centre of the scene, which is moved to the origin when drawing.
const CAMERA_POSITION = [0, -60, 1050];
const FIELD_OF_VIEW = Math.PI / 3.2;

/**
 * Runs the 3D boids simulation in a p5 instance (WEBGL mode) bound to `container`.
 * p5 only handles the canvas, the camera and the drawing; the flocking rules live in ../boids/flocking.js.
 * The canvas is transparent: the sky is a CSS background of the container.
 *
 * @param {HTMLElement} container
 * @param {typeof import('../boids/flocking.js').DEFAULT_PARAMETERS_3D} parameters Mutated live by the controls.
 * @param {{ paused: boolean }} initialState
 */
export function startBoids3dSketch(container, parameters, initialState) {
    const state = { paused: initialState.paused, flock: null, directions: new Float32Array(0), ups: new Float32Array(0), bird: null, frame: 0 };
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

    function drawBoid(p, index) {
        const { pos } = state.flock;
        const { directions, ups } = state;

        orientationMatrix(directions.subarray(index * 3, index * 3 + 3), ups.subarray(index * 3, index * 3 + 3), attitude);

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
            state.bird = buildBird(p);
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
            p.perspective(FIELD_OF_VIEW, p.width / p.height, 10, 5000);
            p.orbitControl();
            p.noStroke();
            p.fill(BIRD_COLOR);

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
