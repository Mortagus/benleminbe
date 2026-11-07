import { describe, expect, test } from 'vitest';
import { roostPosition } from '../../../../assets/scripts/lab/simulations/boids3d/roost.js';
import {
    BOUNDARY_MARGIN,
    DEFAULT_PARAMETERS,
    DEFAULT_PARAMETERS_3D,
    SCENE_SIZE_3D,
    allocateFlock,
    buildGrid,
    computeAcceleration,
    computePointerAcceleration,
    createFlock,
    resizeFlock,
    smoothAngle,
    stepFlock,
    updateDirections,
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

function readFlockAcceleration(flock) {
    return [...flock.acc];
}

// Distance of each boid from the centre of the playfield, as a fraction of the radius of the inscribed ellipsoid.
function reachOfEach(flock, field) {
    return Array.from({ length: flock.count }, (_, i) =>
        Math.sqrt(field.reduce((sum, extent, d) => sum + ((flock.pos[i * flock.dim + d] - extent / 2) / (extent / 2)) ** 2, 0)),
    );
}

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

    test('a pulling point does nothing at the point itself', () => {
        expect(pointerAccelerationOn(boid(100, 100), at('pull'))).toEqual({ x: 0, y: 0 });
    });

    test('a pulling point draws a boid in, harder the further away it is', () => {
        const near = pointerAccelerationOn(boid(150, 100), at('pull'));
        const far = pointerAccelerationOn(boid(100 + parameters.pointerRadius / 2, 100), at('pull'));

        expect(near.x).toBeLessThan(0);
        expect(Math.abs(far.x)).toBeGreaterThan(Math.abs(near.x));
    });

    test('a pulling point keeps its full strength beyond the pointer radius instead of fading', () => {
        const cap = parameters.maxForce * parameters.pointerWeight;

        for (const distance of [parameters.pointerRadius, parameters.pointerRadius * 2, parameters.pointerRadius * 10]) {
            expect(pointerAccelerationOn(boid(100 + distance, 100), at('pull')).x).toBeCloseTo(-cap, 6);
        }
    });

    test('a pulling point does not squeeze boids together the way an attracting one does', () => {
        // Two boids on either side of the point: the attraction makes them rush towards each other, the pull barely does.
        const left = pointerAccelerationOn(boid(95, 100), at('attract'));
        const right = pointerAccelerationOn(boid(105, 100), at('attract'));
        const pullLeft = pointerAccelerationOn(boid(95, 100), at('pull'));
        const pullRight = pointerAccelerationOn(boid(105, 100), at('pull'));

        expect(Math.abs(right.x - left.x)).toBeGreaterThan(Math.abs(pullRight.x - pullLeft.x) * 10);
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

describe('boids spatial grid', () => {
    // A flock spread over the playfield and its margin, plus a few boids well outside it.
    function scatteredFlock(count, field, seed) {
        const random = seededRandom(seed);
        const flock = allocateFlock(count, field.length);

        for (let i = 0; i < count; i++) {
            for (let d = 0; d < field.length; d++) {
                const outside = i % 10 === 0 ? 120 : 0;

                flock.pos[i * field.length + d] = random() * (field[d] + 2 * BOUNDARY_MARGIN + 2 * outside) - BOUNDARY_MARGIN - outside;
                flock.vel[i * field.length + d] = (random() - 0.5) * 6;
            }
        }

        return flock;
    }

    function expectSameAccelerations(flock, field, testParameters) {
        const grid = buildGrid(flock, testParameters.perceptionRadius, field);

        for (let i = 0; i < flock.count; i++) {
            const naive = computeAcceleration(flock, i, testParameters);
            const gridded = computeAcceleration(flock, i, testParameters, grid);

            gridded.forEach((value, d) => expect(value).toBeCloseTo(naive[d], 9));
        }
    }

    test('puts every boid in exactly one cell, the one that contains it', () => {
        const flock = scatteredFlock(300, size, 11);
        const grid = buildGrid(flock, 50, size);
        const seen = new Set();

        for (let cell = 0; cell < grid.cellStart.length - 1; cell++) {
            for (let k = grid.cellStart[cell]; k < grid.cellStart[cell + 1]; k++) {
                expect(grid.cellOf[grid.items[k]]).toBe(cell);
                seen.add(grid.items[k]);
            }
        }

        expect(seen.size).toBe(flock.count);
        expect(grid.cellStart[grid.cellStart.length - 1]).toBe(flock.count);
    });

    test('covers the playfield and its margin with cells of the requested size', () => {
        const grid = buildGrid(allocateFlock(0, 2), 50, size);

        expect([...grid.cells]).toEqual([Math.ceil((400 + 2 * BOUNDARY_MARGIN) / 50), Math.ceil((300 + 2 * BOUNDARY_MARGIN) / 50)]);
    });

    test('gives the same accelerations as testing every boid, for a dense flock', () => {
        expectSameAccelerations(scatteredFlock(400, size, 21), size, parameters);
    });

    test('gives the same accelerations when boids are outside the covered area', () => {
        // Every tenth boid of the scattered flock sits up to 120px beyond the margin.
        expectSameAccelerations(scatteredFlock(300, [200, 150], 22), [200, 150], parameters);
    });

    test('gives the same accelerations for other perception settings', () => {
        const flock = scatteredFlock(300, size, 23);

        expectSameAccelerations(flock, size, { ...parameters, perceptionRadius: 10, separationRadius: 8, maxNeighbours: 3 });
        expectSameAccelerations(flock, size, { ...parameters, perceptionRadius: 150, perceptionAngle: 360, maxNeighbours: 20 });
        expectSameAccelerations(flock, size, { ...parameters, perceptionAngle: 90 });
    });

    test('gives the same accelerations in three dimensions', () => {
        const field = [300, 200, 100];

        expectSameAccelerations(scatteredFlock(300, field, 24), field, parameters);
    });

    test('finds a neighbour across a cell boundary', () => {
        // 49.5 and 50.5 are in different cells of a 50px grid but only 1px apart.
        const flock = flockOf(boid(49.5 - BOUNDARY_MARGIN, 100), boid(50.5 - BOUNDARY_MARGIN, 100));
        const grid = buildGrid(flock, 50, size);
        const separationOnly = { ...parameters, alignmentWeight: 0, cohesionWeight: 0, cruiseSpeedRatio: 0 };

        expect(grid.cellOf[0]).not.toBe(grid.cellOf[1]);
        expect(computeAcceleration(flock, 0, separationOnly, grid)[0]).toBeLessThan(0);
    });

    test('stepFlock keeps the same behaviour as testing every boid', () => {
        const flock = scatteredFlock(200, size, 25);
        const next = stepFlock(flock, parameters, size);

        // Rebuild the first step by hand from naive accelerations.
        for (let i = 0; i < flock.count; i++) {
            const naive = computeAcceleration(flock, i, parameters);
            const responsiveness = 1 - parameters.smoothing;

            for (let d = 0; d < 2; d++) {
                expect(next.acc[i * 2 + d]).toBeCloseTo(naive[d] * responsiveness, 5);
            }
        }
    });
});

describe('boids 2D behaviour is unchanged by the 3D work', () => {
    test('confinement is off by default, so boids wrap around', () => {
        expect(DEFAULT_PARAMETERS.confinementWeight).toBe(0);
    });

    // Reference values recorded from the implementation validated before the 3D work (commit 4e8bce9).
    test('a seeded 2D flock follows the same trajectories as before', () => {
        const field = [900, 506];
        let flock = createFlock(100, seededRandom(31), field);

        for (let frame = 0; frame < 120; frame++) {
            flock = stepFlock(flock, parameters, field);
        }

        const expected = [
            [0, 123.260666, 322.196716, 1.856991, 3.046019],
            [1, 491.726318, 517.40686, -3.443644, 0.52571],
            [50, 129.108932, 296.722595, 1.787505, 3.14028],
            [99, 816.757874, 273.19931, 1.430175, 3.245034],
        ];

        for (const [index, x, y, vx, vy] of expected) {
            const actual = readBoid(flock, index);

            expect(actual.x).toBeCloseTo(x, 4);
            expect(actual.y).toBeCloseTo(y, 4);
            expect(actual.vx).toBeCloseTo(vx, 4);
            expect(actual.vy).toBeCloseTo(vy, 4);
        }
    });
});

describe('boids soft confinement', () => {
    const confining = { ...parameters, confinementWeight: 4, smoothing: 0 };
    const alone = (member, testParameters = confining, field = size) => stepFlock(flockOf(member), testParameters, field);

    test('does not touch a boid inside the inner part of the ellipsoid', () => {
        const confined = readBoid(alone(boid(200, 150, cruising, 0)), 0);
        const free = readBoid(alone(boid(200, 150, cruising, 0), { ...confining, confinementWeight: 0 }), 0);

        expect(confined.vx).toBeCloseTo(free.vx);
        expect(confined.vy).toBeCloseTo(free.vy);
    });

    test('pushes a boid back towards the centre once it goes beyond the start radius', () => {
        // 190 of 200 px from the centre along x: 0.95 of the radius, past the 0.7 start.
        const pushed = readBoid(alone(boid(390, 150, cruising, 0)), 0);

        expect(pushed.ax).toBeLessThan(0);
        expect(pushed.vx).toBeLessThan(cruising);
        expect(pushed.ay).toBeCloseTo(0);
    });

    test('pushes harder the further the boid is from the centre', () => {
        // 150 and 195 px from the centre along x: 0.75 and 0.975 of the radius, both past the 0.7 start.
        const near = readBoid(alone(boid(350, 150)), 0);
        const far = readBoid(alone(boid(395, 150)), 0);

        expect(Math.abs(far.ax)).toBeGreaterThan(Math.abs(near.ax));
        expect(near.ax).toBeLessThan(0);
    });

    test('never pushes harder than maxForce times its weight, however far the boid is', () => {
        const cap = parameters.maxForce * confining.confinementWeight;

        for (const x of [400, 600, 5000]) {
            expect(Math.hypot(...[readBoid(alone(boid(x, 150)), 0).ax])).toBeCloseTo(cap, 5);
        }
    });

    test('does not wrap boids that leave the playfield, it brings them back instead', () => {
        // Without confinement a boid this far out would reappear on the other side.
        const confined = readBoid(alone(boid(500, 150)), 0);
        const wrapped = readBoid(alone(boid(500, 150), { ...confining, confinementWeight: 0 }), 0);

        expect(confined.x).toBeGreaterThan(400);
        // 500 is past the wrap limit of 425 on a 400 px wide playfield: it comes back 450 px lower.
        expect(wrapped.x).toBeCloseTo(50);
    });

    test('points towards the centre on every axis in three dimensions', () => {
        const flock = allocateFlock(1, 3);
        const field = [400, 300, 200];

        flock.pos.set([380, 20, 190]);

        const pushed = readFlockAcceleration(stepFlock(flock, confining, field));

        expect(pushed[0]).toBeLessThan(0);
        expect(pushed[1]).toBeGreaterThan(0);
        expect(pushed[2]).toBeLessThan(0);
    });

    test('a flat playfield gives a flat ellipsoid', () => {
        // 100 px from the centre is 0.2 of the radius along x but 0.9 along the thin y axis.
        const flat = [1000, 100];
        const alongX = readBoid(alone(boid(600, 50), confining, flat), 0);
        const alongY = readBoid(alone(boid(500, 95), confining, flat), 0);

        expect(alongX.ax).toBeCloseTo(0);
        expect(alongY.ay).toBeLessThan(0);
    });

    test('a 3D flock stays inside the scene and does not overlap', () => {
        const field = SCENE_SIZE_3D;
        let flock = createFlock(DEFAULT_PARAMETERS_3D.count, seededRandom(5), field);
        let farthest = 0;

        for (let frame = 0; frame < 800; frame++) {
            flock = stepFlock(flock, DEFAULT_PARAMETERS_3D, field);

            if (frame >= 200 && frame % 20 === 0) {
                farthest = Math.max(farthest, ...reachOfEach(flock, field));
            }
        }

        expect(farthest).toBeLessThan(1.1);

        let overlapping = 0;

        for (let i = 0; i < flock.count; i++) {
            for (let j = i + 1; j < flock.count; j++) {
                if (Math.hypot(flock.pos[i * 3] - flock.pos[j * 3], flock.pos[i * 3 + 1] - flock.pos[j * 3 + 1], flock.pos[i * 3 + 2] - flock.pos[j * 3 + 2]) < 8) {
                    overlapping++;
                }
            }
        }

        expect(overlapping).toBe(0);
    });

    test('a 3D flock stays together as a group', () => {
        const field = SCENE_SIZE_3D;
        let flock = createFlock(DEFAULT_PARAMETERS_3D.count, seededRandom(6), field);

        for (let frame = 0; frame < 600; frame++) {
            flock = stepFlock(flock, DEFAULT_PARAMETERS_3D, field);
        }

        // Every boid has a good number of others within perception range, so nobody is flying alone.
        let alone = 0;

        for (let i = 0; i < flock.count; i++) {
            let near = 0;

            for (let j = 0; j < flock.count; j++) {
                if (i !== j && Math.hypot(flock.pos[i * 3] - flock.pos[j * 3], flock.pos[i * 3 + 1] - flock.pos[j * 3 + 1], flock.pos[i * 3 + 2] - flock.pos[j * 3 + 2]) < DEFAULT_PARAMETERS_3D.perceptionRadius) {
                    near++;
                }
            }

            if (near === 0) {
                alone++;
            }
        }

        expect(alone).toBe(0);
    });
});

describe('boids following the roost in 3D', () => {
    // How a 3D flock behaves over time with the default settings and the roost pulling it along.
    function measure(count, seed) {
        const field = SCENE_SIZE_3D;
        const half = field.map((extent) => extent / 2);
        let flock = createFlock(count, seededRandom(seed), field);
        let vertical = 0;
        let rotation = 0;
        let nearest = 0;
        let nearestSamples = 0;
        let lowest = Infinity;
        let highest = -Infinity;
        let farthest = 0;
        let alone = 0;
        let samples = 0;

        for (let frame = 0; frame < 1500; frame++) {
            flock = stepFlock(flock, { ...DEFAULT_PARAMETERS_3D, count }, field, { position: roostPosition(frame, field), mode: 'pull' });

            if (frame < 400 || frame % 10) {
                continue;
            }

            const { pos, vel } = flock;
            const centre = [0, 1, 2].map((d) => Array.from({ length: count }, (_, i) => pos[i * 3 + d]).reduce((a, b) => a + b, 0) / count);
            const angular = [0, 0, 0];
            let denominator = 0;
            let share = 0;

            for (let i = 0; i < count; i++) {
                const [rx, ry, rz] = [0, 1, 2].map((d) => pos[i * 3 + d] - centre[d]);
                const [vx, vy, vz] = [vel[i * 3], vel[i * 3 + 1], vel[i * 3 + 2]];
                const speed = Math.hypot(vx, vy, vz) || 1;

                share += Math.abs(vy) / speed;
                angular[0] += ry * vz - rz * vy;
                angular[1] += rz * vx - rx * vz;
                angular[2] += rx * vy - ry * vx;
                denominator += Math.hypot(rx, ry, rz) * speed;
                farthest = Math.max(farthest, Math.sqrt([0, 1, 2].reduce((sum, d) => sum + ((pos[i * 3 + d] - half[d]) / half[d]) ** 2, 0)));
            }

            vertical += share / count;
            rotation += Math.max(...angular.map((value) => Math.abs(value) / denominator));
            lowest = Math.min(lowest, centre[1]);
            highest = Math.max(highest, centre[1]);
            samples++;

            if (frame % 100 === 0) {
                const distances = [];

                for (let i = 0; i < count; i++) {
                    let closest = Infinity;
                    let neighbours = 0;

                    for (let j = 0; j < count; j++) {
                        if (i !== j) {
                            const distance = Math.hypot(pos[i * 3] - pos[j * 3], pos[i * 3 + 1] - pos[j * 3 + 1], pos[i * 3 + 2] - pos[j * 3 + 2]);

                            closest = Math.min(closest, distance);
                            neighbours += distance < DEFAULT_PARAMETERS_3D.perceptionRadius ? 1 : 0;
                        }
                    }

                    distances.push(closest);
                    alone += neighbours === 0 ? 1 : 0;
                }

                distances.sort((a, b) => a - b);
                nearest += distances[Math.floor(count * 0.05)];
                nearestSamples++;
            }
        }

        return { vertical: vertical / samples, rotation: rotation / samples, travel: (highest - lowest) / half[1], nearest: nearest / nearestSamples, farthest, alone };
    }

    test.each([
        [200, 1],
        [200, 2],
        [500, 1],
    ])('a flock of %d boids (seed %d) climbs and dives, keeps its distance and stays together', (count, seed) => {
        const result = measure(count, seed);

        // Measured over many seeds: vertical share between 0.3 and 0.57, median about 0.45 (0.04 without the roost),
        // rotation 0.14 at most, vertical travel 0.55 or more. The thresholds keep a margin under the lowest values seen.
        expect(result.vertical).toBeGreaterThan(0.27);
        expect(result.rotation).toBeLessThan(0.3);
        expect(result.travel).toBeGreaterThan(0.4);
        expect(result.nearest).toBeGreaterThan(26);
        expect(result.alone).toBe(0);
        expect(result.farthest).toBeLessThan(1.15);
    });
});

describe('boid directions for drawing', () => {
    const unit = (directions, index, dim) => Math.hypot(...directions.subarray(index * dim, index * dim + dim));

    test('a new boid takes the direction of its velocity at once', () => {
        const flock = flockOf(boid(0, 0, 3, 4));
        const directions = updateDirections(new Float32Array(2), flock, 0.25);

        expect(directions[0]).toBeCloseTo(0.6);
        expect(directions[1]).toBeCloseTo(0.8);
    });

    test('turns part of the way towards the new velocity and stays a unit vector', () => {
        const flock = flockOf(boid(0, 0, 0, 5));
        const directions = updateDirections(Float32Array.of(1, 0), flock, 0.5);

        // (1, 0) + ((0, 1) - (1, 0)) * 0.5 = (0.5, 0.5), brought back to length 1.
        expect(directions[0]).toBeCloseTo(Math.SQRT1_2);
        expect(directions[1]).toBeCloseTo(Math.SQRT1_2);
        expect(unit(directions, 0, 2)).toBeCloseTo(1);
    });

    test('converges to the velocity direction over several calls', () => {
        const flock = flockOf(boid(0, 0, 0, 5));
        let directions = Float32Array.of(1, 0);

        for (let call = 0; call < 60; call++) {
            directions = updateDirections(directions, flock, 0.25);
        }

        expect(directions[0]).toBeCloseTo(0, 3);
        expect(directions[1]).toBeCloseTo(1, 3);
    });

    test('keeps the direction of a boid that does not move', () => {
        const flock = flockOf(boid(0, 0, 0, 0));
        const directions = updateDirections(Float32Array.of(0, -1), flock, 0.5);

        expect([...directions]).toEqual([0, -1]);
    });

    test('gives a motionless new boid a default axis instead of a zero vector', () => {
        const directions = updateDirections(new Float32Array(2), flockOf(boid(0, 0, 0, 0)), 0.5);

        expect(unit(directions, 0, 2)).toBeCloseTo(1);
    });

    test('does not produce NaN when the boid turns exactly the opposite way', () => {
        const flock = flockOf(boid(0, 0, -5, 0));
        const directions = updateDirections(Float32Array.of(1, 0), flock, 0.5);

        expect(Number.isNaN(directions[0])).toBe(false);
        expect(directions[0]).toBeCloseTo(-1);
        expect(unit(directions, 0, 2)).toBeCloseTo(1);
    });

    test('works in three dimensions', () => {
        const flock = allocateFlock(1, 3);

        flock.vel.set([0, 0, 2]);

        const directions = updateDirections(Float32Array.of(1, 0, 0), flock, 0.5);

        expect(unit(directions, 0, 3)).toBeCloseTo(1);
        expect(directions[0]).toBeCloseTo(Math.SQRT1_2);
        expect(directions[2]).toBeCloseTo(Math.SQRT1_2);
    });

    test('updates the array in place when the flock size did not change', () => {
        const flock = flockOf(boid(0, 0, 0, 5), boid(0, 0, 5, 0));
        const directions = new Float32Array(4);

        expect(updateDirections(directions, flock, 0.5)).toBe(directions);
    });

    test('keeps the directions of existing boids when the flock grows or shrinks', () => {
        const flock = flockOf(boid(0, 0, 0, 5), boid(0, 0, 5, 0), boid(0, 0, 0, -5));
        const directions = Float32Array.of(1, 0, 0, 1);

        const grown = updateDirections(directions, flock, 0);

        expect(grown).toHaveLength(6);
        expect([...grown.subarray(0, 4)]).toEqual([1, 0, 0, 1]);
        expect(grown[5]).toBeCloseTo(-1);

        const shrunk = updateDirections(grown, flockOf(boid(0, 0, 0, 5)), 0);

        expect(shrunk).toHaveLength(2);
        expect([...shrunk]).toEqual([1, 0]);
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
