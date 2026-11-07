/**
 * Orientation of a drawn boid in 3D: where its nose points, and which way is its up.
 *
 * Turning the nose onto the direction by the shortest rotation is not enough: it ignores the roll around the nose,
 * so the wings flip suddenly as the direction passes near the back. A bird has a belly and a back, and keeps its
 * belly down. Here the up of a boid is the world up with its component along the direction removed, smoothed
 * from frame to frame so that it never jumps, even when the boid is nearly vertical and the world up is ambiguous.
 *
 * Pure maths, kept apart from the renderer so it can be unit tested.
 */

/** The world up. The y axis of p5 in WEBGL mode points down, so up is the negative y axis. */
export const WORLD_UP = Object.freeze([0, -1, 0]);

// Below this length a vector is considered to be zero.
const EPSILON = 1e-6;

/**
 * Writes into `out` the part of `vector` that is perpendicular to the unit vector `axis`, as a unit vector.
 * Returns false, leaving `out` unspecified, when there is no such part (the two are parallel).
 */
function perpendicularUnit(out, vector, axis) {
    const along = vector[0] * axis[0] + vector[1] * axis[1] + vector[2] * axis[2];
    const x = vector[0] - along * axis[0];
    const y = vector[1] - along * axis[1];
    const z = vector[2] - along * axis[2];
    const length = Math.hypot(x, y, z);

    if (length < EPSILON) {
        return false;
    }

    out[0] = x / length;
    out[1] = y / length;
    out[2] = z / length;

    return true;
}

/**
 * Rolls a unit up vector per boid towards the world up seen from its direction, by a fraction of the remaining
 * angle each call. Meant for drawing: it does not affect the simulation.
 *
 * A boid without an up yet takes its target at once. When the direction is exactly vertical there is no target (every
 * horizontal vector is as good as another), so the previous up is kept, made perpendicular to the direction. The up is
 * always unit length and perpendicular to the direction.
 *
 * @param {Float32Array} ups Three values per boid; may be shorter or longer than the flock.
 * @param {Float32Array} directions Three values per boid: the unit direction of each boid, from `updateDirections`.
 * @param {number} factor Fraction of the remaining turn applied per call, from 0 to 1.
 * @returns {Float32Array} The same array when its length already matches the flock, otherwise a new one that keeps
 *   the ups of the boids still present.
 */
export function updateUps(ups, directions, factor) {
    const count = directions.length / 3;
    let result = ups;

    if (ups.length !== directions.length) {
        result = new Float32Array(directions.length);
        result.set(ups.subarray(0, Math.min(ups.length, directions.length)));
    }

    const heading = [0, 0, 0];
    const previous = [0, 0, 0];
    const target = [0, 0, 0];

    for (let i = 0; i < count; i++) {
        const base = i * 3;

        heading[0] = directions[base];
        heading[1] = directions[base + 1];
        heading[2] = directions[base + 2];

        const hasTarget = perpendicularUnit(target, WORLD_UP, heading);
        const hasPrevious = perpendicularUnit(previous, [result[base], result[base + 1], result[base + 2]], heading);

        if (!hasTarget && !hasPrevious) {
            // Vertical direction and no usable previous up (a new boid): any horizontal vector will do.
            perpendicularUnit(target, [1, 0, 0], heading);
            result[base] = target[0];
            result[base + 1] = target[1];
            result[base + 2] = target[2];

            continue;
        }

        if (!hasTarget) {
            result[base] = previous[0];
            result[base + 1] = previous[1];
            result[base + 2] = previous[2];

            continue;
        }

        if (!hasPrevious) {
            result[base] = target[0];
            result[base + 1] = target[1];
            result[base + 2] = target[2];

            continue;
        }

        // Both vectors are perpendicular to the direction, so the way from one to the other is a rotation about it.
        // Turn the previous up by a fraction of the signed angle left. Unlike blending the two vectors, this has no
        // dead point: a boid that is exactly upside down still rolls back up instead of staying stuck.
        const cross = [
            previous[1] * target[2] - previous[2] * target[1],
            previous[2] * target[0] - previous[0] * target[2],
            previous[0] * target[1] - previous[1] * target[0],
        ];
        const cosine = previous[0] * target[0] + previous[1] * target[1] + previous[2] * target[2];
        const sine = cross[0] * heading[0] + cross[1] * heading[1] + cross[2] * heading[2];
        const angle = Math.atan2(sine, cosine) * factor;
        // previous rotated about the direction: previous * cos + (direction cross previous) * sin.
        const side = [
            heading[1] * previous[2] - heading[2] * previous[1],
            heading[2] * previous[0] - heading[0] * previous[2],
            heading[0] * previous[1] - heading[1] * previous[0],
        ];

        result[base] = previous[0] * Math.cos(angle) + side[0] * Math.sin(angle);
        result[base + 1] = previous[1] * Math.cos(angle) + side[1] * Math.sin(angle);
        result[base + 2] = previous[2] * Math.cos(angle) + side[2] * Math.sin(angle);
    }

    return result;
}

/**
 * Writes into `out` the 4x4 matrix, in column-major order, that orients the model of a boid: its nose (+x) onto
 * `direction`, its back (-y) onto `up`, and its right wing (+z) onto `up` cross `direction`. The columns are the three
 * rotated axes, so with the world up and a direction along +x it is the identity.
 *
 * @param {ArrayLike<number>} direction Unit vector.
 * @param {ArrayLike<number>} up Unit vector perpendicular to `direction`.
 * @param {ArrayLike<number>} out Array of 16 numbers to fill.
 * @returns {ArrayLike<number>} `out`
 */
export function orientationMatrix(direction, up, out) {
    const [fx, fy, fz] = direction;
    const [ux, uy, uz] = up;

    // First column: the nose. Second: the belly, opposite the up (written as 0 - x so that 0 does not become -0).
    out[0] = fx;
    out[1] = fy;
    out[2] = fz;
    out[3] = 0;
    out[4] = 0 - ux;
    out[5] = 0 - uy;
    out[6] = 0 - uz;
    out[7] = 0;
    // Third column: the right wing, up cross direction.
    out[8] = uy * fz - uz * fy;
    out[9] = uz * fx - ux * fz;
    out[10] = ux * fy - uy * fx;
    out[11] = 0;
    out[12] = 0;
    out[13] = 0;
    out[14] = 0;
    out[15] = 1;

    return out;
}
