/**
 * Flocking rules (Reynolds, 1986) on plain numbers, with no dependency on p5 or the DOM so they can be unit tested.
 *
 * @typedef {{ x: number, y: number, vx: number, vy: number }} Boid Position and velocity.
 * @typedef {{ x: number, y: number, mode: 'repel' | 'attract' }} Pointer
 */

export const DEFAULT_PARAMETERS = Object.freeze({
    count: 100,
    perceptionRadius: 50,
    separationRadius: 22,
    maxSpeed: 5,
    maxForce: 0.3,
    alignmentWeight: 1,
    separationWeight: 2.5,
    cohesionWeight: 1,
    pointerRadius: 150,
    pointerWeight: 3,
});

/**
 * Boids may travel this far outside the visible area before wrapping to the opposite side,
 * so the teleport happens off-screen. It must be larger than the drawn boid.
 */
export const BOUNDARY_MARGIN = 25;

const NO_ACCELERATION = Object.freeze({ x: 0, y: 0 });

/**
 * @param {() => number} random Returns a number in [0, 1).
 * @returns {Boid}
 */
export function createBoid(random, width, height) {
    const angle = random() * Math.PI * 2;
    const speed = 0.5 + random();

    return {
        x: random() * width,
        y: random() * height,
        vx: Math.cos(angle) * speed,
        vy: Math.sin(angle) * speed,
    };
}

export function createFlock(count, random, width, height) {
    return Array.from({ length: count }, () => createBoid(random, width, height));
}

/**
 * Adds or removes boids so the flock matches the requested size, keeping existing boids untouched.
 */
export function resizeFlock(flock, count, random, width, height) {
    if (count < flock.length) {
        return flock.slice(0, count);
    }

    return [...flock, ...createFlock(count - flock.length, random, width, height)];
}

/**
 * Steering force that turns the boid's current velocity towards the desired direction.
 * Only the direction of (desiredX, desiredY) matters: the boid wants to travel that way at full speed.
 * The result is capped by maxForce, then multiplied by the rule's weight.
 */
function steer(boid, desiredX, desiredY, weight, parameters) {
    const length = Math.hypot(desiredX, desiredY);

    if (length === 0) {
        return { x: 0, y: 0 };
    }

    let steerX = (desiredX / length) * parameters.maxSpeed - boid.vx;
    let steerY = (desiredY / length) * parameters.maxSpeed - boid.vy;
    const steerLength = Math.hypot(steerX, steerY);

    if (steerLength > parameters.maxForce) {
        steerX *= parameters.maxForce / steerLength;
        steerY *= parameters.maxForce / steerLength;
    }

    return { x: steerX * weight, y: steerY * weight };
}

/**
 * Reynolds steering rules for one boid, each scaled by its weight:
 * - alignment: match the average velocity of neighbours within perceptionRadius;
 * - cohesion: head for the average position of those neighbours;
 * - separation: move away from neighbours within separationRadius, the closer the harder.
 *
 * @param {Boid} boid
 * @param {Boid[]} flock
 * @returns {{ x: number, y: number }}
 */
export function computeAcceleration(boid, flock, parameters) {
    const separationRadius = Math.min(parameters.separationRadius, parameters.perceptionRadius);
    let neighbours = 0;
    let velocitySumX = 0;
    let velocitySumY = 0;
    let positionSumX = 0;
    let positionSumY = 0;
    let pushX = 0;
    let pushY = 0;

    for (const other of flock) {
        if (other === boid) {
            continue;
        }

        const awayX = boid.x - other.x;
        const awayY = boid.y - other.y;
        const distance = Math.hypot(awayX, awayY);

        if (distance >= parameters.perceptionRadius) {
            continue;
        }

        neighbours++;
        velocitySumX += other.vx;
        velocitySumY += other.vy;
        positionSumX += other.x;
        positionSumY += other.y;

        if (distance > 0 && distance < separationRadius) {
            // (awayX, awayY) / distance is the unit direction away from the neighbour;
            // dividing by distance once more makes the push grow as the neighbour gets closer.
            pushX += awayX / (distance * distance);
            pushY += awayY / (distance * distance);
        }
    }

    if (neighbours === 0) {
        return { ...NO_ACCELERATION };
    }

    const alignment = steer(boid, velocitySumX / neighbours, velocitySumY / neighbours, parameters.alignmentWeight, parameters);
    const cohesion = steer(
        boid,
        positionSumX / neighbours - boid.x,
        positionSumY / neighbours - boid.y,
        parameters.cohesionWeight,
        parameters,
    );
    const separation = steer(boid, pushX, pushY, parameters.separationWeight, parameters);
    const noPush = pushX === 0 && pushY === 0;

    return {
        x: alignment.x + cohesion.x + (noPush ? 0 : separation.x),
        y: alignment.y + cohesion.y + (noPush ? 0 : separation.y),
    };
}

/**
 * Force exerted by the pointer: it pushes boids away or pulls them in when they are within pointerRadius.
 *
 * @param {Boid} boid
 * @param {Pointer | null} pointer
 * @returns {{ x: number, y: number }}
 */
export function computePointerAcceleration(boid, pointer, parameters) {
    if (!pointer) {
        return { ...NO_ACCELERATION };
    }

    const towardsX = pointer.x - boid.x;
    const towardsY = pointer.y - boid.y;

    if (Math.hypot(towardsX, towardsY) >= parameters.pointerRadius) {
        return { ...NO_ACCELERATION };
    }

    const direction = pointer.mode === 'repel' ? -1 : 1;

    return steer(boid, towardsX * direction, towardsY * direction, parameters.pointerWeight, parameters);
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

function limitSpeed(vx, vy, maxSpeed) {
    const speed = Math.hypot(vx, vy);

    return speed > maxSpeed ? { vx: (vx / speed) * maxSpeed, vy: (vy / speed) * maxSpeed } : { vx, vy };
}

/**
 * Advances the simulation by one frame and returns a new flock.
 * Every acceleration is computed from the same snapshot, so the result does not depend on boid order.
 *
 * @param {Boid[]} flock
 * @param {Pointer | null} [pointer]
 * @returns {Boid[]}
 */
export function stepFlock(flock, parameters, width, height, pointer = null) {
    return flock.map((boid) => {
        const flocking = computeAcceleration(boid, flock, parameters);
        const fromPointer = computePointerAcceleration(boid, pointer, parameters);
        const { vx, vy } = limitSpeed(
            boid.vx + flocking.x + fromPointer.x,
            boid.vy + flocking.y + fromPointer.y,
            parameters.maxSpeed,
        );

        return {
            x: wrap(boid.x + vx, width),
            y: wrap(boid.y + vy, height),
            vx,
            vy,
        };
    });
}
