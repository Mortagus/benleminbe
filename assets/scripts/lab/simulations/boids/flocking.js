/**
 * Flocking rules (Reynolds, 1986) on plain numbers, with no dependency on p5 or the DOM so they can be unit tested.
 *
 * Each rule returns an acceleration whose length is at most `maxForce` and that fades smoothly with distance,
 * so a boid never gets a sudden kick when a neighbour crosses a threshold.
 *
 * The flock is stored as flat typed arrays, `dim` numbers per boid (2 for the 2D simulation): boid `i` owns the
 * slots `i * dim` to `i * dim + dim - 1` of each array. The rules are written for any number of dimensions.
 *
 * @typedef {object} Flock
 * @property {number} count Number of boids.
 * @property {number} dim Number of coordinates per boid.
 * @property {Float32Array} pos Positions.
 * @property {Float32Array} vel Velocities.
 * @property {Float32Array} acc Smoothed accelerations applied on the previous frame.
 * @typedef {object} Grid
 * @property {number} cellSize Side of a cell.
 * @property {Int32Array} cells Number of cells along each axis.
 * @property {Int32Array} cellOf Cell index of each boid.
 * @property {Int32Array} cellStart Boids of cell `c` are `items[cellStart[c]]` to `items[cellStart[c + 1] - 1]`.
 * @property {Int32Array} items Boid indices grouped by cell.
 * @typedef {{ position: ArrayLike<number>, mode: 'repel' | 'attract' }} Pointer
 *   Position of the pointer, with as many coordinates as the flock has dimensions.
 */

export const DEFAULT_PARAMETERS = Object.freeze({
    count: 100,
    // Perception: neighbours must be within `perceptionRadius`, inside the field of view (in degrees,
    // centred on the heading), and only the `maxNeighbours` nearest ones are taken into account.
    perceptionRadius: 50,
    perceptionAngle: 300,
    maxNeighbours: 7,
    // Personal space: separation sees every boid within this radius, whatever the field of view.
    separationRadius: 22,
    maxSpeed: 5,
    // Boids like to fly at this fraction of the maximum speed (the cap is only reached when pushed).
    cruiseSpeedRatio: 0.7,
    maxForce: 0.3,
    alignmentWeight: 1,
    separationWeight: 2.5,
    cohesionWeight: 1,
    // 0 applies the rules instantly; closer to 1 reacts more slowly, so turns are smoother.
    smoothing: 0.6,
    pointerRadius: 150,
    pointerWeight: 3,
});

/**
 * Boids may travel this far outside the visible area before wrapping to the opposite side,
 * so the teleport happens off-screen. It must be larger than the drawn boid.
 */
export const BOUNDARY_MARGIN = 25;

// Acceleration per unit of speed difference with the cruise speed, before it is capped by maxForce.
const CRUISE_GAIN = 0.5;

/**
 * @param {number} count
 * @param {number} dim
 * @returns {Flock} A flock of `count` boids with every value set to zero.
 */
export function allocateFlock(count, dim) {
    return {
        count,
        dim,
        pos: new Float32Array(count * dim),
        vel: new Float32Array(count * dim),
        acc: new Float32Array(count * dim),
    };
}

/**
 * Fills the boids from `first` (included) to `flock.count` with a random position inside `size`
 * and a random velocity of 0.5 to 1.5 in a uniformly random direction.
 */
function randomiseBoids(flock, first, random, size) {
    const { count, dim, pos, vel } = flock;

    for (let i = first; i < count; i++) {
        const base = i * dim;

        for (let d = 0; d < dim; d++) {
            pos[base + d] = random() * size[d];
        }

        // Rejection sampling in the unit ball gives a uniform direction whatever the number of dimensions.
        let lengthSquared = 0;

        do {
            lengthSquared = 0;

            for (let d = 0; d < dim; d++) {
                vel[base + d] = random() * 2 - 1;
                lengthSquared += vel[base + d] * vel[base + d];
            }
        } while (lengthSquared === 0 || lengthSquared > 1);

        const scale = (0.5 + random()) / Math.sqrt(lengthSquared);

        for (let d = 0; d < dim; d++) {
            vel[base + d] *= scale;
        }
    }
}

/**
 * @param {() => number} random Returns a number in [0, 1).
 * @param {ArrayLike<number>} size Extent of the playfield on each axis, e.g. `[width, height]`; it sets the dimension.
 * @returns {Flock}
 */
export function createFlock(count, random, size) {
    const flock = allocateFlock(count, size.length);

    randomiseBoids(flock, 0, random, size);

    return flock;
}

/**
 * Adds or removes boids so the flock matches the requested size, keeping existing boids untouched.
 * Returns the same flock when its size is already right.
 *
 * @param {Flock} flock
 * @returns {Flock}
 */
export function resizeFlock(flock, count, random, size) {
    if (count === flock.count) {
        return flock;
    }

    const resized = allocateFlock(count, flock.dim);
    const kept = Math.min(count, flock.count) * flock.dim;

    resized.pos.set(flock.pos.subarray(0, kept));
    resized.vel.set(flock.vel.subarray(0, kept));
    resized.acc.set(flock.acc.subarray(0, kept));
    randomiseBoids(resized, flock.count, random, size);

    return resized;
}

/**
 * Sorts the boids into square cells of side `cellSize` covering the playfield and its margin, with a counting sort
 * (no array per cell). With `cellSize` at least the perception radius, every boid a given boid can react to lies in
 * its own cell or one of the adjacent ones. Boids outside the covered area are clamped to the nearest edge cell:
 * clamping never moves two boids further apart in cell units, so no neighbour is missed.
 *
 * @param {Flock} flock
 * @param {number} cellSize
 * @param {ArrayLike<number>} size Extent of the playfield on each axis.
 * @returns {Grid}
 */
export function buildGrid(flock, cellSize, size) {
    const { count, dim, pos } = flock;
    const cells = new Int32Array(dim);
    let total = 1;

    for (let d = 0; d < dim; d++) {
        cells[d] = Math.max(1, Math.ceil((size[d] + 2 * BOUNDARY_MARGIN) / cellSize));
        total *= cells[d];
    }

    const cellOf = new Int32Array(count);
    const cellStart = new Int32Array(total + 1);
    const items = new Int32Array(count);

    for (let i = 0; i < count; i++) {
        let cell = 0;

        for (let d = 0; d < dim; d++) {
            const coordinate = Math.floor((pos[i * dim + d] + BOUNDARY_MARGIN) / cellSize);

            cell = cell * cells[d] + Math.min(Math.max(coordinate, 0), cells[d] - 1);
        }

        cellOf[i] = cell;
        cellStart[cell + 1]++;
    }

    for (let c = 0; c < total; c++) {
        cellStart[c + 1] += cellStart[c];
    }

    // `cellStart` is used as a write cursor while filling, then restored by shifting it back.
    for (let i = 0; i < count; i++) {
        items[cellStart[cellOf[i]]++] = i;
    }

    for (let c = total; c > 0; c--) {
        cellStart[c] = cellStart[c - 1];
    }

    cellStart[0] = 0;

    return { cellSize, cells, cellOf, cellStart, items };
}

/**
 * Scratch memory reused for every boid of a frame, so the rules allocate nothing per boid.
 * `candidates` lists the boids worth testing for the current boid; `neighbourIds` and `neighbourDistances` hold
 * the nearest visible neighbours, sorted by distance.
 */
function createWorkspace(dim, maxNeighbours, count) {
    const capacity = Math.max(0, Math.floor(maxNeighbours));

    return {
        candidateCount: 0,
        candidates: new Int32Array(count),
        cellCoordinates: new Int32Array(dim),
        neighbourCount: 0,
        neighbourIds: new Int32Array(capacity),
        neighbourDistances: new Float64Array(capacity),
        vector: new Float64Array(dim),
        flocking: new Float64Array(dim),
        pointer: new Float64Array(dim),
    };
}

/**
 * Adds `vector * scale` to `out`, after shortening `vector` to `maxLength` if it is longer.
 */
function addLimited(out, vector, maxLength, scale) {
    let lengthSquared = 0;

    for (let d = 0; d < vector.length; d++) {
        lengthSquared += vector[d] * vector[d];
    }

    const length = Math.sqrt(lengthSquared);
    const factor = (length > maxLength ? maxLength / length : 1) * scale;

    for (let d = 0; d < vector.length; d++) {
        out[d] += vector[d] * factor;
    }
}

/**
 * Lists in the workspace the boids that may be within the perception radius of this one: all the others without a
 * grid, otherwise those in its cell and the adjacent ones (3^dim cells).
 */
function gatherCandidates(flock, index, grid, workspace) {
    const { count, dim } = flock;
    const { candidates } = workspace;
    let found = 0;

    if (!grid) {
        for (let other = 0; other < count; other++) {
            if (other !== index) {
                candidates[found++] = other;
            }
        }

        workspace.candidateCount = found;

        return;
    }

    const { cells, cellOf, cellStart, items } = grid;
    const coordinates = workspace.cellCoordinates;
    let rest = cellOf[index];
    let combinations = 1;

    for (let d = dim - 1; d >= 0; d--) {
        coordinates[d] = rest % cells[d];
        rest = Math.floor(rest / cells[d]);
        combinations *= 3;
    }

    // Each combination is a base-3 number whose digits (minus one) are the offsets along each axis.
    for (let combination = 0; combination < combinations; combination++) {
        let digits = combination;
        let cell = 0;
        let inside = true;

        for (let d = 0; d < dim; d++) {
            const coordinate = coordinates[d] + (digits % 3) - 1;

            digits = Math.floor(digits / 3);

            if (coordinate < 0 || coordinate >= cells[d]) {
                inside = false;
                break;
            }

            cell = cell * cells[d] + coordinate;
        }

        if (!inside) {
            continue;
        }

        for (let k = cellStart[cell]; k < cellStart[cell + 1]; k++) {
            if (items[k] !== index) {
                candidates[found++] = items[k];
            }
        }
    }

    workspace.candidateCount = found;
}

/**
 * The boids this one reacts to for alignment and cohesion: within the perception radius, inside the field of view,
 * and at most `maxNeighbours` of them, the nearest first. The result is left in the workspace.
 */
function findNeighbours(flock, index, parameters, workspace) {
    const { dim, pos, vel } = flock;
    const { candidates, candidateCount } = workspace;
    const base = index * dim;
    const capacity = workspace.neighbourIds.length;
    const ids = workspace.neighbourIds;
    const distances = workspace.neighbourDistances;
    const radiusSquared = parameters.perceptionRadius * parameters.perceptionRadius;
    const seesEverything = parameters.perceptionAngle >= 360;
    // The field of view is centred on the heading, so a neighbour is visible when the angle between the heading
    // and the direction to it is at most half the field of view.
    const cosHalfAngle = Math.cos((parameters.perceptionAngle * Math.PI) / 360);
    let headingSquared = 0;
    let found = 0;

    for (let d = 0; d < dim; d++) {
        headingSquared += vel[base + d] * vel[base + d];
    }

    const heading = Math.sqrt(headingSquared);

    for (let k = 0; k < candidateCount && capacity > 0; k++) {
        const other = candidates[k];
        const otherBase = other * dim;
        let distanceSquared = 0;
        let dot = 0;

        for (let d = 0; d < dim; d++) {
            const towards = pos[otherBase + d] - pos[base + d];

            distanceSquared += towards * towards;
            dot += vel[base + d] * towards;
        }

        if (distanceSquared >= radiusSquared) {
            continue;
        }

        const distance = Math.sqrt(distanceSquared);

        // dot(velocity, towards) = |velocity| * |towards| * cos(angle between them), so comparing it with
        // cos(half angle) * |velocity| * |towards| tells whether the angle is small enough, without any trigonometry
        // per pair. A smaller field of view means a larger blind spot behind the boid.
        if (!seesEverything && dot < cosHalfAngle * heading * distance) {
            continue;
        }

        // Insertion into the short sorted list: keeps the `capacity` nearest, earlier boids first on ties.
        let slot;

        if (found < capacity) {
            slot = found++;
        } else if (distance < distances[capacity - 1]) {
            slot = capacity - 1;
        } else {
            continue;
        }

        while (slot > 0 && distances[slot - 1] > distance) {
            distances[slot] = distances[slot - 1];
            ids[slot] = ids[slot - 1];
            slot--;
        }

        distances[slot] = distance;
        ids[slot] = other;
    }

    workspace.neighbourCount = found;
}

/** Match the average velocity of the neighbours: the larger the difference, the stronger the correction. */
function addAlignment(out, flock, index, parameters, workspace) {
    const { dim, vel } = flock;
    const { neighbourCount, neighbourIds, vector } = workspace;

    if (neighbourCount === 0) {
        return;
    }

    vector.fill(0);

    for (let k = 0; k < neighbourCount; k++) {
        for (let d = 0; d < dim; d++) {
            vector[d] += vel[neighbourIds[k] * dim + d];
        }
    }

    for (let d = 0; d < dim; d++) {
        vector[d] = vector[d] / neighbourCount - vel[index * dim + d];
    }

    addLimited(out, vector, parameters.maxForce, parameters.alignmentWeight);
}

/** Head for the centre of the neighbours: the farther from it, the stronger the pull, so there is no overshoot. */
function addCohesion(out, flock, index, parameters, workspace) {
    const { dim, pos } = flock;
    const { neighbourCount, neighbourIds, vector } = workspace;

    if (neighbourCount === 0) {
        return;
    }

    vector.fill(0);

    for (let k = 0; k < neighbourCount; k++) {
        for (let d = 0; d < dim; d++) {
            vector[d] += pos[neighbourIds[k] * dim + d];
        }
    }

    let distanceSquared = 0;

    for (let d = 0; d < dim; d++) {
        vector[d] = vector[d] / neighbourCount - pos[index * dim + d];
        distanceSquared += vector[d] * vector[d];
    }

    if (distanceSquared === 0) {
        return;
    }

    const distance = Math.sqrt(distanceSquared);
    const strength = Math.min(distance / parameters.perceptionRadius, 1) * parameters.maxForce;

    for (let d = 0; d < dim; d++) {
        out[d] += (vector[d] / distance) * strength * parameters.cohesionWeight;
    }
}

/** Move away from boids inside the personal space: the push fades from full strength (touching) to nothing (edge). */
function addSeparation(out, flock, index, parameters, workspace) {
    const { dim, pos } = flock;
    const { candidates, candidateCount, vector } = workspace;
    const base = index * dim;
    const radius = Math.min(parameters.separationRadius, parameters.perceptionRadius);
    const radiusSquared = radius * radius;

    vector.fill(0);

    for (let k = 0; k < candidateCount; k++) {
        const other = candidates[k];
        const otherBase = other * dim;
        let distanceSquared = 0;

        for (let d = 0; d < dim; d++) {
            const away = pos[base + d] - pos[otherBase + d];

            distanceSquared += away * away;
        }

        if (distanceSquared === 0 || distanceSquared >= radiusSquared) {
            continue;
        }

        const distance = Math.sqrt(distanceSquared);
        const closeness = 1 - distance / radius;

        for (let d = 0; d < dim; d++) {
            vector[d] += ((pos[base + d] - pos[otherBase + d]) / distance) * closeness;
        }
    }

    addLimited(out, vector, 1, parameters.maxForce * parameters.separationWeight);
}

/**
 * Gently brings the speed back to the cruise speed, along the heading. Without it the field of view makes the
 * flock accelerate (the visible neighbours are mostly ahead, so cohesion always pulls forwards) up to the maximum speed.
 */
function addCruise(out, flock, index, parameters) {
    const { dim, vel } = flock;
    const base = index * dim;
    let speedSquared = 0;

    for (let d = 0; d < dim; d++) {
        speedSquared += vel[base + d] * vel[base + d];
    }

    if (speedSquared === 0) {
        return;
    }

    const speed = Math.sqrt(speedSquared);
    const cruiseSpeed = parameters.maxSpeed * parameters.cruiseSpeedRatio;
    const strength = Math.max(-parameters.maxForce, Math.min((cruiseSpeed - speed) * CRUISE_GAIN, parameters.maxForce));

    for (let d = 0; d < dim; d++) {
        out[d] += (vel[base + d] / speed) * strength;
    }
}

/** Writes into `out` the steering rules for one boid, each scaled by its weight, plus the cruise speed regulation. */
function accelerationInto(out, flock, index, parameters, workspace, grid) {
    out.fill(0);
    gatherCandidates(flock, index, grid, workspace);
    findNeighbours(flock, index, parameters, workspace);
    addAlignment(out, flock, index, parameters, workspace);
    addCohesion(out, flock, index, parameters, workspace);
    addSeparation(out, flock, index, parameters, workspace);
    addCruise(out, flock, index, parameters);
}

/** Writes into `out` the force exerted by the pointer: it fades to nothing at `pointerRadius`. */
function pointerAccelerationInto(out, flock, index, pointer, parameters) {
    out.fill(0);

    if (!pointer) {
        return;
    }

    const { dim, pos } = flock;
    const base = index * dim;
    let distanceSquared = 0;

    for (let d = 0; d < dim; d++) {
        const towards = pointer.position[d] - pos[base + d];

        distanceSquared += towards * towards;
    }

    if (distanceSquared === 0 || distanceSquared >= parameters.pointerRadius * parameters.pointerRadius) {
        return;
    }

    const distance = Math.sqrt(distanceSquared);
    const direction = pointer.mode === 'repel' ? -1 : 1;
    const strength = (1 - distance / parameters.pointerRadius) * parameters.maxForce * parameters.pointerWeight;

    for (let d = 0; d < dim; d++) {
        out[d] = ((pointer.position[d] - pos[base + d]) / distance) * strength * direction;
    }
}

/**
 * Steering acceleration of one boid: the Reynolds rules, each scaled by its weight, plus the cruise speed regulation.
 *
 * @param {Flock} flock
 * @param {number} index Index of the boid in the flock.
 * @param {Grid | null} [grid] Grid built from this flock with `buildGrid`. Without one, every boid of the flock is
 *   tested, which gives the same result more slowly.
 * @returns {Float64Array} One value per dimension.
 */
export function computeAcceleration(flock, index, parameters, grid = null) {
    const out = new Float64Array(flock.dim);

    accelerationInto(out, flock, index, parameters, createWorkspace(flock.dim, parameters.maxNeighbours, flock.count), grid);

    return out;
}

/**
 * Force exerted on one boid by the pointer: it pushes boids away or pulls them in, fading to nothing at `pointerRadius`.
 *
 * @param {Flock} flock
 * @param {number} index
 * @param {Pointer | null} pointer
 * @returns {Float64Array} One value per dimension.
 */
export function computePointerAcceleration(flock, index, pointer, parameters) {
    const out = new Float64Array(flock.dim);

    pointerAccelerationInto(out, flock, index, pointer, parameters);

    return out;
}

/**
 * Wraps a coordinate around the playfield extended by BOUNDARY_MARGIN on each side.
 */
function wrap(value, size) {
    const min = -BOUNDARY_MARGIN;
    const max = size + BOUNDARY_MARGIN;
    const period = max - min;

    if (value > max) {
        return value - period;
    }

    return value < min ? value + period : value;
}

/**
 * Moves an angle towards a target by a fraction of the shortest way round, so the result never spins the long way.
 * Meant for drawing: it smooths how a boid is rotated without affecting the simulation.
 */
export function smoothAngle(current, target, factor) {
    const difference = Math.atan2(Math.sin(target - current), Math.cos(target - current));

    return current + difference * factor;
}

/**
 * Advances the simulation by one frame and returns a new flock; the previous one is left untouched.
 * Every acceleration is computed from the same snapshot, so the result does not depend on boid order.
 * Neighbours are looked up through a grid rebuilt each frame, so the cost grows roughly linearly with the flock size.
 * The flocking acceleration is blended with the previous one to avoid abrupt turns; the pointer is applied as is
 * so that clicks stay responsive.
 *
 * @param {Flock} flock
 * @param {ArrayLike<number>} size Extent of the playfield on each axis.
 * @param {Pointer | null} [pointer]
 * @returns {Flock}
 */
export function stepFlock(flock, parameters, size, pointer = null) {
    const { count, dim } = flock;
    const next = allocateFlock(count, dim);
    const workspace = createWorkspace(dim, parameters.maxNeighbours, count);
    const grid = buildGrid(flock, Math.max(parameters.perceptionRadius, 1), size);
    const responsiveness = 1 - parameters.smoothing;

    for (let i = 0; i < count; i++) {
        const base = i * dim;
        let speedSquared = 0;

        accelerationInto(workspace.flocking, flock, i, parameters, workspace, grid);
        pointerAccelerationInto(workspace.pointer, flock, i, pointer, parameters);

        for (let d = 0; d < dim; d++) {
            const acceleration = flock.acc[base + d] + (workspace.flocking[d] - flock.acc[base + d]) * responsiveness;

            next.acc[base + d] = acceleration;
            // Temporarily holds the new velocity, before it is limited to maxSpeed.
            next.vel[base + d] = flock.vel[base + d] + acceleration + workspace.pointer[d];
            speedSquared += next.vel[base + d] * next.vel[base + d];
        }

        const speed = Math.sqrt(speedSquared);
        const limit = speed > parameters.maxSpeed ? parameters.maxSpeed / speed : 1;

        for (let d = 0; d < dim; d++) {
            next.vel[base + d] *= limit;
            next.pos[base + d] = wrap(flock.pos[base + d] + next.vel[base + d], size[d]);
        }
    }

    return next;
}
