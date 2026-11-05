import { describe, expect, test } from 'vitest';
import {
    DEFAULT_PARAMETERS,
    computeAcceleration,
    createFlock,
    resizeFlock,
    stepFlock,
} from '../../../../assets/scripts/lab/simulations/boids/flocking.js';
import { magnitude } from '../../../../assets/scripts/lab/simulations/boids/vector.js';

const parameters = { ...DEFAULT_PARAMETERS };

function boid(x, y, vx = 0, vy = 0) {
    return { position: { x, y }, velocity: { x: vx, y: vy } };
}

describe('boids flocking rules', () => {
    test('a lone boid receives no acceleration', () => {
        const alone = boid(100, 100, 1, 0);

        expect(computeAcceleration(alone, [alone], parameters)).toEqual({ x: 0, y: 0 });
    });

    test('boids outside the perception radius are ignored', () => {
        const first = boid(0, 0, 1, 0);
        const far = boid(parameters.perceptionRadius + 10, 0, 0, 1);

        expect(computeAcceleration(first, [first, far], parameters)).toEqual({ x: 0, y: 0 });
    });

    test('separation pushes a boid away from a close neighbour', () => {
        const first = boid(100, 100);
        const neighbour = boid(110, 100);
        const acceleration = computeAcceleration(first, [first, neighbour], parameters);

        // Cohesion pulls towards the neighbour (+x) and separation pushes away (-x); both are capped by maxForce.
        expect(acceleration.y).toBeCloseTo(0);
        expect(magnitude(acceleration)).toBeLessThanOrEqual(parameters.maxForce * 3 + 1e-9);
    });

    test('alignment steers towards the heading of neighbours', () => {
        const first = boid(100, 100, 1, 0);
        const neighbour = boid(100, 130, 0, 3);
        const acceleration = computeAcceleration(first, [first, neighbour], parameters);

        expect(acceleration.y).toBeGreaterThan(0);
    });

    test('each steering contribution is capped by the maximum force', () => {
        const first = boid(100, 100, 5, 0);
        const flock = [first, boid(101, 100, -5, 0), boid(100, 101, 0, -5)];

        expect(magnitude(computeAcceleration(first, flock, parameters))).toBeLessThanOrEqual(parameters.maxForce * 3 + 1e-9);
    });

    test('does not produce NaN when two boids share the same position', () => {
        const first = boid(10, 10, 1, 0);
        const twin = boid(10, 10, 0, 1);
        const acceleration = computeAcceleration(first, [first, twin], parameters);

        expect(Number.isNaN(acceleration.x)).toBe(false);
        expect(Number.isNaN(acceleration.y)).toBe(false);
    });
});

describe('boids flock lifecycle', () => {
    test('stepFlock never exceeds the maximum speed', () => {
        let flock = createFlock(50, seededRandom(1), 400, 300);

        for (let frame = 0; frame < 100; frame++) {
            flock = stepFlock(flock, parameters, 400, 300);
        }

        for (const { velocity } of flock) {
            expect(magnitude(velocity)).toBeLessThanOrEqual(parameters.maxSpeed + 1e-9);
        }
    });

    test('stepFlock does not mutate the previous flock', () => {
        const flock = createFlock(5, seededRandom(2), 400, 300);
        const snapshot = JSON.parse(JSON.stringify(flock));

        stepFlock(flock, parameters, 400, 300);

        expect(flock).toEqual(snapshot);
    });

    test('boids wrap around the edges of the playfield', () => {
        const flock = [boid(399.5, 100, 2, 0), boid(100, 0.5, 0, -2)];
        const [right, top] = stepFlock(flock, parameters, 400, 300);

        expect(right.position.x).toBe(0);
        expect(top.position.y).toBe(300);
    });

    test('resizeFlock keeps existing boids when growing and truncates when shrinking', () => {
        const flock = createFlock(3, seededRandom(3), 400, 300);
        const grown = resizeFlock(flock, 5, seededRandom(4), 400, 300);
        const shrunk = resizeFlock(grown, 2, seededRandom(5), 400, 300);

        expect(grown).toHaveLength(5);
        expect(grown.slice(0, 3)).toEqual(flock);
        expect(shrunk).toEqual(flock.slice(0, 2));
    });
});

function seededRandom(seed) {
    let state = seed;

    return () => {
        state = (state * 1664525 + 1013904223) % 4294967296;

        return state / 4294967296;
    };
}
