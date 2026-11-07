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
            // The unit comes translated from the template and already carries any space it needs ("300°", "50 px").
            output.textContent = input.value + (output.dataset.unit ?? '');
        };

        // Lets the stylesheet draw a tick at the default value, as a fraction of the slider range.
        const range = Number(input.max) - Number(input.min);
        input.closest('.boids-slider').style.setProperty('--default-ratio', (parameters[name] - Number(input.min)) / range);

        input.value = parameters[name];
        apply();
        input.addEventListener('input', apply);
    });

    // Left button repels boids, right button attracts them, for as long as it is held.
    const POINTER_MODES = { 0: 'repel', 2: 'attract' };
    let activeButton = null;

    const updatePointer = (event) => {
        const rect = container.getBoundingClientRect();

        simulation.setPointer({
            position: [event.clientX - rect.left, event.clientY - rect.top],
            mode: POINTER_MODES[activeButton],
        });
    };

    const releasePointer = () => {
        activeButton = null;
        simulation.setPointer(null);
    };

    container.addEventListener('pointerdown', (event) => {
        if (!(event.button in POINTER_MODES)) {
            return;
        }

        activeButton = event.button;
        container.setPointerCapture(event.pointerId);
        updatePointer(event);
    });

    container.addEventListener('pointermove', (event) => {
        if (activeButton !== null) {
            updatePointer(event);
        }
    });

    container.addEventListener('pointerup', releasePointer);
    container.addEventListener('pointercancel', releasePointer);
    container.addEventListener('contextmenu', (event) => event.preventDefault());

    syncToggleLabel();
}
