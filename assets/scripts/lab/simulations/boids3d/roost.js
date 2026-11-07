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
// so that the point stays about as fast as a boid: across a wide scene it is slow (a full lateral sweep takes about 30
// seconds at 60 frames per second), and the flock, whose top speed is 4 units per frame, can always keep up.
const FREQUENCIES = Object.freeze([0.0035, 0.004, 0.0058]);
// How far the point goes from where it is centred on each axis, as a fraction of the half extent of the scene. Kept small
// enough for the point to stay inside the confined zone (a rounded box, see `confinementExponent`): 0.95 of the way to its
// surface at the very most.
const AMPLITUDES = Object.freeze([0.6, 0.8, 0.35]);
// Where each axis is centred, as a fraction of the half extent. The y axis points down, so +0.1 puts the path a little low:
// the flock goes further down, towards the trees, than up.
const OFFSETS = Object.freeze([0, 0.1, 0]);
// Flattening of each sine: 0 is a plain sine, larger values make the point linger near the two ends of its swing and move
// quickly between them. On the height it makes the flock stay a while at tree height before climbing again, instead of just
// brushing it for an instant; a plain sine hardly meets the trees at all.
const FLATTENING = Object.freeze([0, 2.5, 0]);
// Offsets that keep the three axes from starting in step.
const PHASES = Object.freeze([0, 2.1, 4.2]);

function swing(axis, frame) {
    const sine = Math.sin(FREQUENCIES[axis] * frame + PHASES[axis]);
    const k = FLATTENING[axis];

    return k === 0 ? sine : Math.tanh(k * sine) / Math.tanh(k);
}

/**
 * @param {number} frame Number of frames since the start of the simulation.
 * @param {ArrayLike<number>} size Extent of the playfield on each axis.
 * @returns {number[]} Position of the roost, one value per axis.
 */
export function roostPosition(frame, size) {
    return size.map((extent, axis) => extent / 2 + (OFFSETS[axis] + AMPLITUDES[axis] * swing(axis, frame)) * (extent / 2));
}
