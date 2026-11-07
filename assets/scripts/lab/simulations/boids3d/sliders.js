/**
 * Conversion between what a slider shows and the parameter of the simulation it drives. They differ when the slider is
 * easier to read in other units: the share of leaders is shown as a percentage (5 to 100) while the parameter is a fraction
 * (0.05 to 1), so the slider has a `scale` of 0.01.
 *
 * Pure, so it can be unit tested without a DOM. The rounding keeps floating-point noise out (15 * 0.01 is not exactly 0.15).
 */

const PRECISION = 1e6;

/**
 * @param {number|string} sliderValue The value of the slider.
 * @param {number} scale Parameter units per slider unit.
 * @returns {number} The parameter.
 */
export function sliderToParameter(sliderValue, scale) {
    return Math.round(Number(sliderValue) * scale * PRECISION) / PRECISION;
}

/**
 * @param {number} parameter
 * @param {number} scale Parameter units per slider unit.
 * @returns {number} The value to give the slider.
 */
export function parameterToSlider(parameter, scale) {
    return Math.round((parameter / scale) * PRECISION) / PRECISION;
}

/**
 * Where the default value sits along a slider, from 0 to 1, for the tick the stylesheet draws under it.
 *
 * @param {number} value Default value, in slider units.
 * @param {number} min
 * @param {number} max
 * @returns {number}
 */
export function defaultRatio(value, min, max) {
    return (value - min) / (max - min);
}
