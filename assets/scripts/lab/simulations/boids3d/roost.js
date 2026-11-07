/**
 * The roost: an invisible point that wanders along a smooth path in 3D and weakly attracts the flock.
 *
 * Left to itself, a flock settles into circling like a school of fish and hardly uses the height of the scene. A real
 * flock climbs, dives and turns together; a point that moves slowly through all three dimensions makes the whole flock
 * follow a varied path, while the usual rules (separation, alignment, cohesion) still shape the group.
 *
 * The path is a sum of sines on each axis with different frequencies, so it does not repeat for a long time and has no
 * random state: the same frame always gives the same position. Pure maths, kept apart from the renderer.
 */

// Radians per frame on each axis (x, y, z). Different values keep the path from settling into a simple loop. They are low
// so that the point never goes faster than the cruising speed of a boid (2.8 units per frame): the speed of the point
// is the amplitude times the frequency, and the scene is wide, so across it the point is slow (a full lateral sweep
// takes about 30 seconds at 60 frames per second).
const FREQUENCIES = Object.freeze([0.0035, 0.0085, 0.0058]);
// How far the point goes from the centre on each axis, as a fraction of the half extent of the scene. Kept small enough
// for the point to stay inside the confinement ellipsoid (the root of the sum of squares must stay below 1: 0.91 here).
const AMPLITUDES = Object.freeze([0.6, 0.55, 0.4]);
// Offsets that keep the three axes from starting in step.
const PHASES = Object.freeze([0, 2.1, 4.2]);

/**
 * @param {number} frame Number of frames since the start of the simulation.
 * @param {ArrayLike<number>} size Extent of the playfield on each axis.
 * @returns {number[]} Position of the roost, one value per axis.
 */
export function roostPosition(frame, size) {
    return size.map((extent, axis) => extent / 2 + AMPLITUDES[axis] * (extent / 2) * Math.sin(FREQUENCIES[axis] * frame + PHASES[axis]));
}
