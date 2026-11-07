import { DEFAULT_PARAMETERS_3D } from '../boids/flocking.js';
import { startBoids3dSketch } from './sketch.js';
import { defaultRatio, parameterToSlider, sliderToParameter } from './sliders.js';

const root = document.querySelector('[data-boids3d-simulation]');

if (root) {
    const container = root.querySelector('[data-boids3d-canvas]');
    const toggleButton = root.querySelector('[data-boids3d-toggle]');
    const resetButton = root.querySelector('[data-boids3d-reset]');
    const parameters = { ...DEFAULT_PARAMETERS_3D };
    const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    // The simulation reads `parameters` at every frame, so changing a slider acts at once.
    const simulation = startBoids3dSketch(container, parameters, { paused: prefersReducedMotion });

    const syncToggleLabel = () => {
        toggleButton.textContent = simulation.isPaused() ? toggleButton.dataset.labelResume : toggleButton.dataset.labelPause;
        toggleButton.setAttribute('aria-pressed', String(simulation.isPaused()));
    };

    toggleButton.addEventListener('click', () => {
        simulation.setPaused(!simulation.isPaused());
        syncToggleLabel();
    });

    resetButton.addEventListener('click', () => simulation.reset());

    root.querySelectorAll('[data-boids3d-parameter]').forEach((input) => {
        const name = input.dataset.boids3dParameter;
        const scale = Number(input.dataset.scale ?? 1);
        const output = root.querySelector(`[data-boids3d-output="${name}"]`);
        const apply = () => {
            parameters[name] = sliderToParameter(input.value, scale);
            // The unit comes translated from the template and already carries any space it needs ("15 %", "15%").
            output.textContent = input.value + (output.dataset.unit ?? '');
        };

        // Lets the stylesheet draw a tick at the default value, as a fraction of the slider range.
        const defaultValue = parameterToSlider(parameters[name], scale);

        input.closest('.boids-slider').style.setProperty('--default-ratio', defaultRatio(defaultValue, Number(input.min), Number(input.max)));
        input.value = defaultValue;
        apply();
        input.addEventListener('input', apply);
    });

    syncToggleLabel();
}
