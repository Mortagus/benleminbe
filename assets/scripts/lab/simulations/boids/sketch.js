import p5 from 'p5';
import { createFlock, resizeFlock, smoothAngle, stepFlock } from './flocking.js';

const CANVAS_RATIO = 9 / 16;
const MAX_CANVAS_HEIGHT = 520;
const THEME_REFRESH_FRAMES = 30;
// Fraction of the remaining rotation applied each frame: boids are drawn turning smoothly towards their heading.
const HEADING_SMOOTHING = 0.25;

/**
 * Runs the boids simulation in a p5 instance bound to `container`.
 * p5 only handles the canvas and the draw loop; the flocking rules live in flocking.js.
 *
 * @param {HTMLElement} container
 * @param {typeof import('./flocking.js').DEFAULT_PARAMETERS} parameters Mutated live by the controls.
 * @param {{ paused: boolean }} initialState
 */
export function startBoidsSketch(container, parameters, initialState) {
    const state = { paused: initialState.paused, flock: null, headings: new Float32Array(0), colors: null, pointer: null };
    let instance = null;

    function canvasSize() {
        const width = Math.max(container.clientWidth, 200);

        return { width, height: Math.min(Math.round(width * CANVAS_RATIO), MAX_CANVAS_HEIGHT) };
    }

    function readColors() {
        const styles = getComputedStyle(container);

        return {
            background: styles.getPropertyValue('--card').trim() || '#ffffff',
            boid: styles.getPropertyValue('--accent-text').trim() || '#2b6f94',
        };
    }

    // Drawn angles are kept per boid (same index as in the flock) and only affect rendering, not the simulation.
    // A boid without a previous angle (new, or after a reset) starts directly at its heading.
    function updateHeadings() {
        const { count, vel } = state.flock;
        const headings = new Float32Array(count);

        for (let i = 0; i < count; i++) {
            const target = Math.atan2(vel[i * 2 + 1], vel[i * 2]);

            headings[i] = i < state.headings.length ? smoothAngle(state.headings[i], target, HEADING_SMOOTHING) : target;
        }

        state.headings = headings;
    }

    function drawBoid(p, x, y, heading) {
        p.push();
        p.translate(x, y);
        p.rotate(heading);
        p.triangle(0, 0, -15, 5, -15, -5);
        p.pop();
    }

    // Faint ring showing the area of influence of the pointer while a button is held.
    function drawPointer(p) {
        if (!state.pointer) {
            return;
        }

        p.drawingContext.globalAlpha = 0.35;
        p.circle(state.pointer.position[0], state.pointer.position[1], parameters.pointerRadius * 2);
        p.drawingContext.globalAlpha = 1;
    }

    function sketch(p) {
        p.setup = () => {
            const { width, height } = canvasSize();
            const canvas = p.createCanvas(width, height);

            canvas.attribute('role', 'img');
            canvas.attribute('aria-label', container.dataset.canvasLabel ?? 'Boids simulation');
            state.flock = createFlock(parameters.count, Math.random, [width, height]);
            updateHeadings();
            state.colors = readColors();
        };

        p.draw = () => {
            if (p.frameCount % THEME_REFRESH_FRAMES === 0) {
                state.colors = readColors();
            }

            if (!state.paused) {
                const size = [p.width, p.height];

                state.flock = resizeFlock(state.flock, parameters.count, Math.random, size);
                state.flock = stepFlock(state.flock, parameters, size, state.pointer);
                updateHeadings();
            }

            p.background(state.colors.background);
            p.stroke(state.colors.boid);
            p.strokeWeight(1);
            p.noFill();

            const { count, pos } = state.flock;

            for (let i = 0; i < count; i++) {
                drawBoid(p, pos[i * 2], pos[i * 2 + 1], state.headings[i]);
            }

            drawPointer(p);
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
        /** @param {import('./flocking.js').Pointer | null} pointer Position in canvas coordinates, or null when released. */
        setPointer(pointer) {
            state.pointer = pointer;
        },
        reset() {
            state.flock = createFlock(parameters.count, Math.random, [instance.width, instance.height]);
            state.headings = new Float32Array(0);
            updateHeadings();
        },
    };
}
