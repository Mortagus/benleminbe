import p5 from 'p5';
import { createFlock, resizeFlock, stepFlock } from './flocking.js';

const CANVAS_RATIO = 9 / 16;
const MAX_CANVAS_HEIGHT = 520;
const THEME_REFRESH_FRAMES = 30;

/**
 * Runs the boids simulation in a p5 instance bound to `container`.
 * p5 only handles the canvas and the draw loop; the flocking rules live in flocking.js.
 *
 * @param {HTMLElement} container
 * @param {typeof import('./flocking.js').DEFAULT_PARAMETERS} parameters Mutated live by the controls.
 * @param {{ paused: boolean }} initialState
 */
export function startBoidsSketch(container, parameters, initialState) {
    const state = { paused: initialState.paused, flock: [], colors: null, pointer: null };
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

    function drawBoid(p, { x, y, vx, vy }) {
        p.push();
        p.translate(x, y);
        p.rotate(Math.atan2(vy, vx));
        p.triangle(0, 0, -15, 5, -15, -5);
        p.pop();
    }

    // Faint ring showing the area of influence of the pointer while a button is held.
    function drawPointer(p) {
        if (!state.pointer) {
            return;
        }

        p.drawingContext.globalAlpha = 0.35;
        p.circle(state.pointer.x, state.pointer.y, parameters.pointerRadius * 2);
        p.drawingContext.globalAlpha = 1;
    }

    function sketch(p) {
        p.setup = () => {
            const { width, height } = canvasSize();
            const canvas = p.createCanvas(width, height);

            canvas.attribute('role', 'img');
            canvas.attribute('aria-label', container.dataset.canvasLabel ?? 'Boids simulation');
            state.flock = createFlock(parameters.count, Math.random, width, height);
            state.colors = readColors();
        };

        p.draw = () => {
            if (p.frameCount % THEME_REFRESH_FRAMES === 0) {
                state.colors = readColors();
            }

            if (!state.paused) {
                state.flock = resizeFlock(state.flock, parameters.count, Math.random, p.width, p.height);
                state.flock = stepFlock(state.flock, parameters, p.width, p.height, state.pointer);
            }

            p.background(state.colors.background);
            p.stroke(state.colors.boid);
            p.strokeWeight(1);
            p.noFill();
            state.flock.forEach((boid) => drawBoid(p, boid));
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
            state.flock = createFlock(parameters.count, Math.random, instance.width, instance.height);
        },
    };
}
