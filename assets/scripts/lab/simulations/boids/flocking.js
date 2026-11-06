/**
 * Flocking rules (Reynolds, 1986) on plain numbers, with no dependency on p5 or the DOM so they can be unit tested.
 *
 * Each rule returns an acceleration whose length is at most `maxForce` and that fades smoothly with distance,
 * so a boid never gets a sudden kick when a neighbour crosses a threshold.
 *
 * @typedef {{ x: number, y: number, vx: number, vy: number, ax: number, ay: number }} Boid
 *   Position, velocity and the smoothed acceleration applied on the previous frame.
 * @typedef {{ x: number, y: number, mode: 'repel' | 'attract' }} Pointer
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

const NO_FORCE = Object.freeze({ x: 0, y: 0 });

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
        ax: 0,
        ay: 0,
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

function limitLength(x, y, maxLength) {
    const length = Math.hypot(x, y);

    return length > maxLength ? { x: (x / length) * maxLength, y: (y / length) * maxLength } : { x, y };
}

/**
 * The boids this one reacts to for alignment and cohesion: within the perception radius, inside the field of view,
 * and at most `maxNeighbours` of them, the nearest first.
 *
 * @param {Boid} boid
 * @param {Boid[]} flock
 * @returns {{ boid: Boid, distance: number }[]}
 */
function findNeighbours(boid, flock, parameters) {
    const heading = Math.hypot(boid.vx, boid.vy);
    const seesEverything = parameters.perceptionAngle >= 360;
    // The field of view is centred on the heading, so a neighbour is visible when the angle between the heading
    // and the direction to it is at most half the field of view.
    const cosHalfAngle = Math.cos((parameters.perceptionAngle * Math.PI) / 360);
    const visible = [];

    for (const other of flock) {
        if (other === boid) {
            continue;
        }

        const towardsX = other.x - boid.x;
        const towardsY = other.y - boid.y;
        const distance = Math.hypot(towardsX, towardsY);

        if (distance >= parameters.perceptionRadius) {
            continue;
        }

        // dot(velocity, towards) = |velocity| * |towards| * cos(angle between them), so comparing it with
        // cos(half angle) * |velocity| * |towards| tells whether the angle is small enough, without any trigonometry
        // per pair. A smaller field of view means a larger blind spot behind the boid.
        const dot = boid.vx * towardsX + boid.vy * towardsY;

        if (!seesEverything && dot < cosHalfAngle * heading * distance) {
            continue;
        }

        visible.push({ boid: other, distance });
    }

    return visible.sort((a, b) => a.distance - b.distance).slice(0, parameters.maxNeighbours);
}

/** Match the average velocity of the neighbours: the larger the difference, the stronger the correction. */
function alignmentForce(boid, neighbours, parameters) {
    if (neighbours.length === 0) {
        return NO_FORCE;
    }

    let velocityX = 0;
    let velocityY = 0;

    for (const { boid: other } of neighbours) {
        velocityX += other.vx;
        velocityY += other.vy;
    }

    return limitLength(
        velocityX / neighbours.length - boid.vx,
        velocityY / neighbours.length - boid.vy,
        parameters.maxForce,
    );
}

/** Head for the centre of the neighbours: the farther from it, the stronger the pull, so there is no overshoot. */
function cohesionForce(boid, neighbours, parameters) {
    if (neighbours.length === 0) {
        return NO_FORCE;
    }

    let centreX = 0;
    let centreY = 0;

    for (const { boid: other } of neighbours) {
        centreX += other.x;
        centreY += other.y;
    }

    const towardsX = centreX / neighbours.length - boid.x;
    const towardsY = centreY / neighbours.length - boid.y;
    const distance = Math.hypot(towardsX, towardsY);

    if (distance === 0) {
        return NO_FORCE;
    }

    const strength = Math.min(distance / parameters.perceptionRadius, 1) * parameters.maxForce;

    return { x: (towardsX / distance) * strength, y: (towardsY / distance) * strength };
}

/** Move away from boids inside the personal space: the push fades from full strength (touching) to nothing (edge). */
function separationForce(boid, flock, parameters) {
    const radius = Math.min(parameters.separationRadius, parameters.perceptionRadius);
    let pushX = 0;
    let pushY = 0;

    for (const other of flock) {
        const awayX = boid.x - other.x;
        const awayY = boid.y - other.y;
        const distance = Math.hypot(awayX, awayY);

        if (distance === 0 || distance >= radius) {
            continue;
        }

        const closeness = 1 - distance / radius;

        pushX += (awayX / distance) * closeness;
        pushY += (awayY / distance) * closeness;
    }

    const push = limitLength(pushX, pushY, 1);

    return { x: push.x * parameters.maxForce, y: push.y * parameters.maxForce };
}

/**
 * Gently brings the speed back to the cruise speed, along the heading. Without it the field of view makes the
 * flock accelerate (the visible neighbours are mostly ahead, so cohesion always pulls forwards) up to the maximum speed.
 */
function cruiseForce(boid, parameters) {
    const speed = Math.hypot(boid.vx, boid.vy);

    if (speed === 0) {
        return NO_FORCE;
    }

    const cruiseSpeed = parameters.maxSpeed * parameters.cruiseSpeedRatio;
    const strength = Math.max(-parameters.maxForce, Math.min((cruiseSpeed - speed) * CRUISE_GAIN, parameters.maxForce));

    return { x: (boid.vx / speed) * strength, y: (boid.vy / speed) * strength };
}

/**
 * Reynolds steering rules for one boid, each scaled by its weight, plus the cruise speed regulation.
 *
 * @param {Boid} boid
 * @param {Boid[]} flock
 * @returns {{ x: number, y: number }}
 */
export function computeAcceleration(boid, flock, parameters) {
    const neighbours = findNeighbours(boid, flock, parameters);
    const alignment = alignmentForce(boid, neighbours, parameters);
    const cohesion = cohesionForce(boid, neighbours, parameters);
    const separation = separationForce(boid, flock, parameters);
    const cruise = cruiseForce(boid, parameters);

    return {
        x:
            alignment.x * parameters.alignmentWeight +
            cohesion.x * parameters.cohesionWeight +
            separation.x * parameters.separationWeight +
            cruise.x,
        y:
            alignment.y * parameters.alignmentWeight +
            cohesion.y * parameters.cohesionWeight +
            separation.y * parameters.separationWeight +
            cruise.y,
    };
}

/**
 * Force exerted by the pointer: it pushes boids away or pulls them in, fading to nothing at `pointerRadius`.
 *
 * @param {Boid} boid
 * @param {Pointer | null} pointer
 * @returns {{ x: number, y: number }}
 */
export function computePointerAcceleration(boid, pointer, parameters) {
    if (!pointer) {
        return NO_FORCE;
    }

    const towardsX = pointer.x - boid.x;
    const towardsY = pointer.y - boid.y;
    const distance = Math.hypot(towardsX, towardsY);

    if (distance === 0 || distance >= parameters.pointerRadius) {
        return NO_FORCE;
    }

    const direction = pointer.mode === 'repel' ? -1 : 1;
    const strength = (1 - distance / parameters.pointerRadius) * parameters.maxForce * parameters.pointerWeight;

    return { x: (towardsX / distance) * strength * direction, y: (towardsY / distance) * strength * direction };
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
 * Advances the simulation by one frame and returns a new flock.
 * Every acceleration is computed from the same snapshot, so the result does not depend on boid order.
 * The flocking acceleration is blended with the previous one to avoid abrupt turns; the pointer is applied as is
 * so that clicks stay responsive.
 *
 * @param {Boid[]} flock
 * @param {Pointer | null} [pointer]
 * @returns {Boid[]}
 */
export function stepFlock(flock, parameters, width, height, pointer = null) {
    const responsiveness = 1 - parameters.smoothing;

    return flock.map((boid) => {
        const flocking = computeAcceleration(boid, flock, parameters);
        const fromPointer = computePointerAcceleration(boid, pointer, parameters);
        const ax = boid.ax + (flocking.x - boid.ax) * responsiveness;
        const ay = boid.ay + (flocking.y - boid.ay) * responsiveness;
        const velocity = limitLength(boid.vx + ax + fromPointer.x, boid.vy + ay + fromPointer.y, parameters.maxSpeed);
        const vx = velocity.x;
        const vy = velocity.y;

        return { x: wrap(boid.x + vx, width), y: wrap(boid.y + vy, height), vx, vy, ax, ay };
    });
}
