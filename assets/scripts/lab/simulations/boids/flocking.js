import { ZERO, add, limit, magnitude, scale, setMagnitude, sub } from './vector.js';

export const DEFAULT_PARAMETERS = Object.freeze({
    count: 100,
    perceptionRadius: 50,
    maxSpeed: 5,
    maxForce: 0.3,
});

/**
 * @typedef {{ x: number, y: number }} Vec
 * @typedef {{ position: Vec, velocity: Vec }} Boid
 */

/**
 * @param {() => number} random Returns a number in [0, 1).
 * @returns {Boid}
 */
export function createBoid(random, width, height) {
    const angle = random() * Math.PI * 2;
    const speed = 0.5 + random();

    return {
        position: { x: random() * width, y: random() * height },
        velocity: { x: Math.cos(angle) * speed, y: Math.sin(angle) * speed },
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

function steerTowards(desired, boid, parameters) {
    const steering = sub(setMagnitude(desired, parameters.maxSpeed), boid.velocity);

    return limit(steering, parameters.maxForce);
}

/**
 * Reynolds steering rules: alignment, separation and cohesion, summed with equal weight.
 *
 * @param {Boid} boid
 * @param {Boid[]} flock
 * @returns {Vec}
 */
export function computeAcceleration(boid, flock, parameters) {
    let alignment = ZERO;
    let separation = ZERO;
    let centre = ZERO;
    let neighbours = 0;

    for (const other of flock) {
        if (other === boid) {
            continue;
        }

        const offset = sub(boid.position, other.position);
        const distance = magnitude(offset);

        if (distance >= parameters.perceptionRadius) {
            continue;
        }

        alignment = add(alignment, other.velocity);
        centre = add(centre, other.position);
        // Closer neighbours push harder: weight the repulsion by 1 / distance.
        separation = add(separation, distance === 0 ? ZERO : scale(offset, 1 / distance));
        neighbours++;
    }

    if (neighbours === 0) {
        return { x: 0, y: 0 };
    }

    const towardsCentre = sub(scale(centre, 1 / neighbours), boid.position);

    return add(
        add(
            steerTowards(scale(alignment, 1 / neighbours), boid, parameters),
            steerTowards(scale(separation, 1 / neighbours), boid, parameters),
        ),
        steerTowards(towardsCentre, boid, parameters),
    );
}

function wrap(value, size) {
    if (value > size) {
        return 0;
    }

    return value < 0 ? size : value;
}

/**
 * Advances the simulation by one frame and returns a new flock.
 * Every acceleration is computed from the same snapshot, so the result does not depend on boid order.
 *
 * @param {Boid[]} flock
 * @returns {Boid[]}
 */
export function stepFlock(flock, parameters, width, height) {
    const accelerations = flock.map((boid) => computeAcceleration(boid, flock, parameters));

    return flock.map((boid, index) => {
        const velocity = limit(add(boid.velocity, accelerations[index]), parameters.maxSpeed);
        const moved = add(boid.position, velocity);

        return {
            position: { x: wrap(moved.x, width), y: wrap(moved.y, height) },
            velocity,
        };
    });
}
