import { describe, expect, test } from 'vitest';
import {
    BOUNDARY_MARGIN,
    DEFAULT_PARAMETERS,
    computeAcceleration,
    computePointerAcceleration,
    createFlock,
    resizeFlock,
    stepFlock,
} from '../../../../assets/scripts/lab/simulations/boids/flocking.js';

const parameters = { ...DEFAULT_PARAMETERS };
const totalWeight = parameters.alignmentWeight + parameters.separationWeight + parameters.cohesionWeight;

function boid(x, y, vx = 0, vy = 0) {
    return { x, y, vx, vy };
}

function speed({ vx, vy }) {
    return Math.hypot(vx, vy);
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
        const acceleration = computeAcceleration(first, [first, neighbour], { ...parameters, alignmentWeight: 0, cohesionWeight: 0 });

        expect(acceleration.x).toBeLessThan(0);
        expect(acceleration.y).toBeCloseTo(0);
    });

    test('separation outweighs cohesion when a neighbour is very close', () => {
        const first = boid(100, 100);
        const neighbour = boid(105, 100);

        expect(computeAcceleration(first, [first, neighbour], parameters).x).toBeLessThan(0);
    });

    test('separation ignores neighbours beyond the separation radius', () => {
        const first = boid(100, 100);
        const neighbour = boid(100 + parameters.separationRadius + 5, 100);
        const acceleration = computeAcceleration(first, [first, neighbour], { ...parameters, alignmentWeight: 0, cohesionWeight: 0 });

        expect(acceleration).toEqual({ x: 0, y: 0 });
    });

    test('the closest neighbour dominates the separation direction', () => {
        const first = boid(100, 100);
        const flock = [first, boid(95, 100), boid(115, 100)];
        const acceleration = computeAcceleration(first, flock, { ...parameters, alignmentWeight: 0, cohesionWeight: 0 });

        // The neighbour 5px to the left pushes harder than the one 15px to the right.
        expect(acceleration.x).toBeGreaterThan(0);
    });

    test('cohesion pulls a boid towards its neighbours', () => {
        const first = boid(100, 100);
        const neighbour = boid(100 + parameters.separationRadius + 10, 100);
        const acceleration = computeAcceleration(first, [first, neighbour], { ...parameters, alignmentWeight: 0 });

        expect(acceleration.x).toBeGreaterThan(0);
    });

    test('alignment steers towards the heading of neighbours', () => {
        const first = boid(100, 100, 1, 0);
        const neighbour = boid(100, 130, 0, 3);
        const acceleration = computeAcceleration(first, [first, neighbour], parameters);

        expect(acceleration.y).toBeGreaterThan(0);
    });

    test('a weight of zero disables its rule', () => {
        const first = boid(100, 100, 1, 0);
        const neighbour = boid(100, 130, 0, 3);
        const none = { ...parameters, alignmentWeight: 0, separationWeight: 0, cohesionWeight: 0 };

        expect(computeAcceleration(first, [first, neighbour], none)).toEqual({ x: 0, y: 0 });
    });

    test('each steering contribution is capped by the maximum force times its weight', () => {
        const first = boid(100, 100, 5, 0);
        const flock = [first, boid(101, 100, -5, 0), boid(100, 101, 0, -5)];
        const acceleration = computeAcceleration(first, flock, parameters);

        expect(Math.hypot(acceleration.x, acceleration.y)).toBeLessThanOrEqual(parameters.maxForce * totalWeight + 1e-9);
    });

    test('does not produce NaN when two boids share the same position', () => {
        const first = boid(10, 10, 1, 0);
        const twin = boid(10, 10, 0, 1);
        const acceleration = computeAcceleration(first, [first, twin], parameters);

        expect(Number.isNaN(acceleration.x)).toBe(false);
        expect(Number.isNaN(acceleration.y)).toBe(false);
    });
});

describe('boids pointer interaction', () => {
    const at = (mode, x = 100, y = 100) => ({ x, y, mode });

    test('without a pointer there is no extra force', () => {
        expect(computePointerAcceleration(boid(50, 50), null, parameters)).toEqual({ x: 0, y: 0 });
    });

    test('a repelling pointer pushes a boid away', () => {
        const acceleration = computePointerAcceleration(boid(120, 100), at('repel'), parameters);

        expect(acceleration.x).toBeGreaterThan(0);
    });

    test('an attracting pointer pulls a boid in', () => {
        const acceleration = computePointerAcceleration(boid(120, 100), at('attract'), parameters);

        expect(acceleration.x).toBeLessThan(0);
    });

    test('the pointer has no effect beyond its radius', () => {
        const far = boid(100 + parameters.pointerRadius + 1, 100);

        expect(computePointerAcceleration(far, at('repel'), parameters)).toEqual({ x: 0, y: 0 });
    });

    test('stepFlock moves boids away from a repelling pointer', () => {
        const [moved] = stepFlock([boid(120, 100)], parameters, 400, 300, at('repel'));

        expect(moved.vx).toBeGreaterThan(0);
        expect(moved.x).toBeGreaterThan(120);
    });
});

describe('boids flock lifecycle', () => {
    test('stepFlock never exceeds the maximum speed', () => {
        let flock = createFlock(50, seededRandom(1), 400, 300);

        for (let frame = 0; frame < 100; frame++) {
            flock = stepFlock(flock, parameters, 400, 300, { x: 200, y: 150, mode: 'attract' });
        }

        for (const member of flock) {
            expect(speed(member)).toBeLessThanOrEqual(parameters.maxSpeed + 1e-9);
        }
    });

    test('stepFlock does not mutate the previous flock', () => {
        const flock = createFlock(5, seededRandom(2), 400, 300);
        const snapshot = JSON.parse(JSON.stringify(flock));

        stepFlock(flock, parameters, 400, 300);

        expect(flock).toEqual(snapshot);
    });

    test('a boid keeps flying past the visible edge instead of wrapping immediately', () => {
        const [right] = stepFlock([boid(399.5, 100, 2, 0)], parameters, 400, 300);

        expect(right.x).toBeCloseTo(401.5);
    });

    test('boids wrap around once they are fully outside the margin', () => {
        const flock = [boid(400 + BOUNDARY_MARGIN - 0.5, 100, 2, 0), boid(100, -BOUNDARY_MARGIN + 0.5, 0, -2)];
        const [right, top] = stepFlock(flock, parameters, 400, 300);

        expect(right.x).toBeCloseTo(-BOUNDARY_MARGIN + 1.5);
        expect(top.y).toBeCloseTo(300 + BOUNDARY_MARGIN - 1.5);
    });

    test('resizeFlock keeps existing boids when growing and truncates when shrinking', () => {
        const flock = createFlock(3, seededRandom(3), 400, 300);
        const grown = resizeFlock(flock, 5, seededRandom(4), 400, 300);
        const shrunk = resizeFlock(grown, 2, seededRandom(5), 400, 300);

        expect(grown).toHaveLength(5);
        expect(grown.slice(0, 3)).toEqual(flock);
        expect(shrunk).toEqual(flock.slice(0, 2));
    });

    test('with default parameters a settled flock does not visibly overlap', () => {
        let flock = createFlock(100, seededRandom(7), 900, 506);

        for (let frame = 0; frame < 600; frame++) {
            flock = stepFlock(flock, parameters, 900, 506);
        }

        let overlapping = 0;

        for (let i = 0; i < flock.length; i++) {
            for (let j = i + 1; j < flock.length; j++) {
                if (Math.hypot(flock[i].x - flock[j].x, flock[i].y - flock[j].y) < 8) {
                    overlapping++;
                }
            }
        }

        expect(overlapping).toBe(0);
    });
});

function seededRandom(seed) {
    let state = seed;

    return () => {
        state = (state * 1664525 + 1013904223) % 4294967296;

        return state / 4294967296;
    };
}
