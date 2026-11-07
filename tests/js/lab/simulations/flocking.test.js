import { describe, expect, test } from 'vitest';
import {
    BOUNDARY_MARGIN,
    DEFAULT_PARAMETERS,
    allocateFlock,
    computeAcceleration,
    computePointerAcceleration,
    createFlock,
    resizeFlock,
    smoothAngle,
    stepFlock,
} from '../../../../assets/scripts/lab/simulations/boids/flocking.js';

const parameters = { ...DEFAULT_PARAMETERS };
const cruising = parameters.maxSpeed * parameters.cruiseSpeedRatio;
const totalWeight = parameters.alignmentWeight + parameters.separationWeight + parameters.cohesionWeight;

// A boid described as [x, y, vx, vy]; velocity defaults to zero.
function boid(x, y, vx = 0, vy = 0) {
    return [x, y, vx, vy];
}

function flockOf(...boids) {
    const flock = allocateFlock(boids.length, 2);

    boids.forEach(([x, y, vx, vy], i) => {
        flock.pos.set([x, y], i * 2);
        flock.vel.set([vx, vy], i * 2);
    });

    return flock;
}

function readBoid(flock, index) {
    const [x, y] = flock.pos.subarray(index * 2, index * 2 + 2);
    const [vx, vy] = flock.vel.subarray(index * 2, index * 2 + 2);
    const [ax, ay] = flock.acc.subarray(index * 2, index * 2 + 2);

    return { x, y, vx, vy, ax, ay };
}

function speed({ vx, vy }) {
    return Math.hypot(vx, vy);
}

// Acceleration of the first boid of the flock, as { x, y }.
function accelerationOf(boids, testParameters, index = 0) {
    const [x, y] = computeAcceleration(flockOf(...boids), index, testParameters);

    return { x, y };
}

function pointerAccelerationOn(member, pointer) {
    const [x, y] = computePointerAcceleration(flockOf(member), 0, pointer, parameters);

    return { x, y };
}

const size = [400, 300];

describe('boids flocking rules', () => {
    test('a lone boid receives no acceleration', () => {
        const alone = boid(100, 100, cruising, 0);

        expect(accelerationOf([alone], parameters)).toEqual({ x: 0, y: 0 });
    });

    test('boids outside the perception radius are ignored', () => {
        const first = boid(0, 0, cruising, 0);
        const far = boid(parameters.perceptionRadius + 10, 0, 0, 1);

        expect(accelerationOf([first, far], parameters)).toEqual({ x: 0, y: 0 });
    });

    test('separation pushes a boid away from a close neighbour', () => {
        const first = boid(100, 100);
        const neighbour = boid(110, 100);
        const acceleration = accelerationOf([first, neighbour], { ...parameters, alignmentWeight: 0, cohesionWeight: 0, cruiseSpeedRatio: 0 });

        expect(acceleration.x).toBeLessThan(0);
        expect(acceleration.y).toBeCloseTo(0);
    });

    test('separation outweighs cohesion when a neighbour is very close', () => {
        const first = boid(100, 100);
        const neighbour = boid(105, 100);

        expect(accelerationOf([first, neighbour], parameters).x).toBeLessThan(0);
    });

    test('separation ignores neighbours beyond the separation radius', () => {
        const first = boid(100, 100);
        const neighbour = boid(100 + parameters.separationRadius + 5, 100);
        const acceleration = accelerationOf([first, neighbour], { ...parameters, alignmentWeight: 0, cohesionWeight: 0 });

        expect(acceleration).toEqual({ x: 0, y: 0 });
    });

    test('the closest neighbour dominates the separation direction', () => {
        const first = boid(100, 100);
        const flock = [first, boid(95, 100), boid(115, 100)];
        const acceleration = accelerationOf(flock, { ...parameters, alignmentWeight: 0, cohesionWeight: 0, cruiseSpeedRatio: 0 });

        // The neighbour 5px to the left pushes harder than the one 15px to the right.
        expect(acceleration.x).toBeGreaterThan(0);
    });

    test('cohesion pulls a boid towards its neighbours', () => {
        const first = boid(100, 100);
        const neighbour = boid(100 + parameters.separationRadius + 10, 100);
        const acceleration = accelerationOf([first, neighbour], { ...parameters, alignmentWeight: 0 });

        expect(acceleration.x).toBeGreaterThan(0);
    });

    test('alignment steers towards the heading of neighbours', () => {
        const first = boid(100, 100, 1, 0);
        const neighbour = boid(100, 130, 0, 3);
        const acceleration = accelerationOf([first, neighbour], parameters);

        expect(acceleration.y).toBeGreaterThan(0);
    });

    test('a weight of zero disables its rule', () => {
        const first = boid(100, 100, 1, 0);
        const neighbour = boid(100, 130, 0, 3);
        const none = { ...parameters, alignmentWeight: 0, separationWeight: 0, cohesionWeight: 0, cruiseSpeedRatio: 0.2 };
        const acceleration = accelerationOf([first, neighbour], none);

        expect(acceleration.x).toBeCloseTo(0);
        expect(acceleration.y).toBeCloseTo(0);
    });

    test('each steering contribution is capped by the maximum force times its weight', () => {
        const first = boid(100, 100, 5, 0);
        const flock = [first, boid(101, 100, -5, 0), boid(100, 101, 0, -5)];
        const acceleration = accelerationOf(flock, parameters);

        // Three weighted rules plus the cruise speed regulation, each at most maxForce long.
        expect(Math.hypot(acceleration.x, acceleration.y)).toBeLessThanOrEqual(parameters.maxForce * (totalWeight + 1) + 1e-9);
    });

    test('separation fades to nothing at the edge of the personal space', () => {
        const separationOnly = { ...parameters, alignmentWeight: 0, cohesionWeight: 0 };
        const first = boid(100, 100);
        const almostTouching = accelerationOf([first, boid(101, 100)], separationOnly);
        const nearTheEdge = accelerationOf([first, boid(100 + parameters.separationRadius - 0.5, 100)], separationOnly);

        expect(Math.abs(nearTheEdge.x)).toBeLessThan(Math.abs(almostTouching.x) / 10);
    });

    test('cohesion gets weaker as the boid reaches the centre of its neighbours', () => {
        const cohesionOnly = { ...parameters, alignmentWeight: 0, separationWeight: 0, cruiseSpeedRatio: 0 };
        const first = boid(100, 100);
        const far = accelerationOf([first, boid(100 + parameters.separationRadius + 20, 100)], cohesionOnly);
        const near = accelerationOf([first, boid(100 + parameters.separationRadius + 2, 100)], cohesionOnly);

        expect(far.x).toBeGreaterThan(near.x);
    });

    test('neighbours in the blind spot behind the boid are ignored', () => {
        const heading = boid(100, 100, 3, 0);
        const behind = boid(70, 100, 0, 3);
        const alignmentOnly = { ...parameters, separationWeight: 0, cohesionWeight: 0, cruiseSpeedRatio: 0.6 };

        expect(accelerationOf([heading, behind], { ...alignmentOnly, perceptionAngle: 300 })).toEqual({ x: 0, y: 0 });
        expect(accelerationOf([heading, behind], { ...alignmentOnly, perceptionAngle: 360 }).y).toBeGreaterThan(0);
    });

    test('neighbours ahead are seen even with a narrow field of view', () => {
        const heading = boid(100, 100, 3, 0);
        const ahead = boid(130, 100, 3, 3);
        const alignmentOnly = { ...parameters, separationWeight: 0, cohesionWeight: 0, perceptionAngle: 60 };

        expect(accelerationOf([heading, ahead], alignmentOnly).y).toBeGreaterThan(0);
    });

    test('only the nearest neighbours are taken into account', () => {
        const first = boid(100, 100, 3, 0);
        const flock = [first, boid(120, 100, 3, 3), boid(140, 100, 3, -3), boid(140, 105, 3, -3)];
        const alignmentOnly = { ...parameters, separationWeight: 0, cohesionWeight: 0 };

        expect(accelerationOf(flock, { ...alignmentOnly, maxNeighbours: 1 }).y).toBeGreaterThan(0);
        expect(accelerationOf(flock, { ...alignmentOnly, maxNeighbours: 3 }).y).toBeLessThan(0);
    });

    test('a boid slower than the cruise speed accelerates along its heading', () => {
        const slow = boid(100, 100, 1, 0);

        expect(accelerationOf([slow], parameters).x).toBeGreaterThan(0);
    });

    test('does not produce NaN when two boids share the same position', () => {
        const first = boid(10, 10, 1, 0);
        const twin = boid(10, 10, 0, 1);
        const acceleration = accelerationOf([first, twin], parameters);

        expect(Number.isNaN(acceleration.x)).toBe(false);
        expect(Number.isNaN(acceleration.y)).toBe(false);
    });
});

describe('boids pointer interaction', () => {
    const at = (mode, x = 100, y = 100) => ({ position: [x, y], mode });

    test('without a pointer there is no extra force', () => {
        expect(pointerAccelerationOn(boid(50, 50), null)).toEqual({ x: 0, y: 0 });
    });

    test('a repelling pointer pushes a boid away', () => {
        expect(pointerAccelerationOn(boid(120, 100), at('repel')).x).toBeGreaterThan(0);
    });

    test('an attracting pointer pulls a boid in', () => {
        expect(pointerAccelerationOn(boid(120, 100), at('attract')).x).toBeLessThan(0);
    });

    test('the pointer has no effect beyond its radius', () => {
        const far = boid(100 + parameters.pointerRadius + 1, 100);

        expect(pointerAccelerationOn(far, at('repel'))).toEqual({ x: 0, y: 0 });
    });

    test('stepFlock moves boids away from a repelling pointer', () => {
        const moved = readBoid(stepFlock(flockOf(boid(120, 100)), parameters, size, at('repel')), 0);

        expect(moved.vx).toBeGreaterThan(0);
        expect(moved.x).toBeGreaterThan(120);
    });
});

describe('boids flock lifecycle', () => {
    test('createFlock builds one boid per count with the dimension of the playfield', () => {
        const flock = createFlock(7, seededRandom(1), size);

        expect(flock.count).toBe(7);
        expect(flock.dim).toBe(2);
        expect(flock.pos).toHaveLength(14);
        expect(flock.vel).toHaveLength(14);
        expect(flock.acc).toHaveLength(14);
    });

    test('created boids start inside the playfield with a speed between 0.5 and 1.5', () => {
        const flock = createFlock(200, seededRandom(9), size);

        for (let i = 0; i < flock.count; i++) {
            const member = readBoid(flock, i);

            expect(member.x).toBeGreaterThanOrEqual(0);
            expect(member.x).toBeLessThan(size[0]);
            expect(member.y).toBeGreaterThanOrEqual(0);
            expect(member.y).toBeLessThan(size[1]);
            expect(speed(member)).toBeGreaterThanOrEqual(0.5 - 1e-6);
            expect(speed(member)).toBeLessThanOrEqual(1.5 + 1e-6);
        }
    });

    test('the rules work in any number of dimensions', () => {
        const flock = allocateFlock(2, 3);

        flock.pos.set([10, 10, 10, 15, 10, 10]);

        const acceleration = computeAcceleration(flock, 0, { ...parameters, alignmentWeight: 0, cohesionWeight: 0, cruiseSpeedRatio: 0 });

        expect(acceleration).toHaveLength(3);
        expect(acceleration[0]).toBeLessThan(0);
        expect(acceleration[1]).toBeCloseTo(0);
        expect(acceleration[2]).toBeCloseTo(0);
    });

    test('stepFlock never exceeds the maximum speed', () => {
        let flock = createFlock(50, seededRandom(1), size);

        for (let frame = 0; frame < 100; frame++) {
            flock = stepFlock(flock, parameters, size, { position: [200, 150], mode: 'attract' });
        }

        for (let i = 0; i < flock.count; i++) {
            expect(speed(readBoid(flock, i))).toBeLessThanOrEqual(parameters.maxSpeed + 1e-6);
        }
    });

    test('stepFlock does not mutate the previous flock', () => {
        const flock = createFlock(5, seededRandom(2), size);
        const snapshot = { pos: [...flock.pos], vel: [...flock.vel], acc: [...flock.acc] };

        const next = stepFlock(flock, parameters, size);

        expect(next).not.toBe(flock);
        expect([...flock.pos]).toEqual(snapshot.pos);
        expect([...flock.vel]).toEqual(snapshot.vel);
        expect([...flock.acc]).toEqual(snapshot.acc);
    });

    test('a boid keeps flying past the visible edge instead of wrapping immediately', () => {
        const right = readBoid(stepFlock(flockOf(boid(399.5, 100, cruising, 0)), parameters, size), 0);

        expect(right.x).toBeCloseTo(399.5 + cruising);
    });

    test('boids wrap around once they are fully outside the margin', () => {
        const flock = flockOf(boid(400 + BOUNDARY_MARGIN - 0.5, 100, cruising, 0), boid(100, -BOUNDARY_MARGIN + 0.5, 0, -cruising));
        const next = stepFlock(flock, parameters, size);

        expect(readBoid(next, 0).x).toBeCloseTo(-BOUNDARY_MARGIN - 0.5 + cruising);
        expect(readBoid(next, 1).y).toBeCloseTo(300 + BOUNDARY_MARGIN + 0.5 - cruising);
    });

    test('smoothing makes a boid react more slowly to a new situation', () => {
        const crowded = () => flockOf(boid(100, 100, 3.5, 0), boid(110, 100, 3.5, 0));
        const instant = readBoid(stepFlock(crowded(), { ...parameters, smoothing: 0 }, size), 0);
        const smooth = readBoid(stepFlock(crowded(), { ...parameters, smoothing: 0.9 }, size), 0);

        expect(Math.abs(smooth.ax)).toBeLessThan(Math.abs(instant.ax));
    });

    test('resizeFlock keeps existing boids when growing and truncates when shrinking', () => {
        const flock = createFlock(3, seededRandom(3), size);
        const grown = resizeFlock(flock, 5, seededRandom(4), size);
        const shrunk = resizeFlock(grown, 2, seededRandom(5), size);

        expect(grown.count).toBe(5);
        expect([...grown.pos.subarray(0, 6)]).toEqual([...flock.pos]);
        expect([...grown.vel.subarray(0, 6)]).toEqual([...flock.vel]);
        expect(shrunk.count).toBe(2);
        expect([...shrunk.pos]).toEqual([...flock.pos.subarray(0, 4)]);
    });

    test('resizeFlock returns the same flock when the size already matches', () => {
        const flock = createFlock(3, seededRandom(3), size);

        expect(resizeFlock(flock, 3, seededRandom(4), size)).toBe(flock);
    });

    test('with default parameters a settled flock does not visibly overlap', () => {
        const field = [900, 506];
        let flock = createFlock(100, seededRandom(7), field);

        for (let frame = 0; frame < 600; frame++) {
            flock = stepFlock(flock, parameters, field);
        }

        let overlapping = 0;

        for (let i = 0; i < flock.count; i++) {
            for (let j = i + 1; j < flock.count; j++) {
                const a = readBoid(flock, i);
                const b = readBoid(flock, j);

                if (Math.hypot(a.x - b.x, a.y - b.y) < 8) {
                    overlapping++;
                }
            }
        }

        expect(overlapping).toBe(0);
    });
});

describe('smoothAngle', () => {
    test('moves part of the way towards the target', () => {
        expect(smoothAngle(0, 1, 0.25)).toBeCloseTo(0.25);
    });

    test('takes the shortest way round the circle', () => {
        const next = smoothAngle(Math.PI - 0.1, -Math.PI + 0.1, 0.5);

        expect(Math.cos(next)).toBeLessThan(-0.99);
    });
});

function seededRandom(seed) {
    let state = seed;

    return () => {
        state = (state * 1664525 + 1013904223) % 4294967296;

        return state / 4294967296;
    };
}
