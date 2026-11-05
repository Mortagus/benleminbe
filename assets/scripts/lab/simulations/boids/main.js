import { DEFAULT_PARAMETERS } from './flocking.js';
import { startBoidsSketch } from './sketch.js';

const root = document.querySelector('[data-boids-simulation]');

if (root) {
    const container = root.querySelector('[data-boids-canvas]');
    const toggleButton = root.querySelector('[data-boids-toggle]');
    const resetButton = root.querySelector('[data-boids-reset]');
    const parameters = { ...DEFAULT_PARAMETERS };
    const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    const simulation = startBoidsSketch(container, parameters, { paused: prefersReducedMotion });

    const syncToggleLabel = () => {
        toggleButton.textContent = simulation.isPaused() ? toggleButton.dataset.labelResume : toggleButton.dataset.labelPause;
        toggleButton.setAttribute('aria-pressed', String(simulation.isPaused()));
    };

    toggleButton.addEventListener('click', () => {
        simulation.setPaused(!simulation.isPaused());
        syncToggleLabel();
    });

    resetButton.addEventListener('click', () => simulation.reset());

    root.querySelectorAll('[data-boids-parameter]').forEach((input) => {
        const name = input.dataset.boidsParameter;
        const output = root.querySelector(`[data-boids-output="${name}"]`);
        const apply = () => {
            parameters[name] = Number(input.value);
            output.textContent = input.value;
        };

        input.value = parameters[name];
        apply();
        input.addEventListener('input', apply);
    });

    syncToggleLabel();
}
