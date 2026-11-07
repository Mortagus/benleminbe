import { describe, expect, test } from 'vitest';
import { DEFAULT_PARAMETERS, DEFAULT_PARAMETERS_3D, SCENE_SIZE_3D, allocateFlock, computeAvoidance, createFlock, stepFlock } from '../../../../assets/scripts/lab/simulations/boids/flocking.js';
import { buildEnvironment, buildLandscape } from '../../../../assets/scripts/lab/simulations/boids3d/landscape.js';
import { roostPosition } from '../../../../assets/scripts/lab/simulations/boids3d/roost.js';

// Parameters that isolate the avoidance: no other rule acts on a lone boid, and nothing smooths its reaction.
const parameters = { ...DEFAULT_PARAMETERS_3D, avoidanceWeight: 4, avoidanceLookahead: 30, avoidanceClearance: 12, smoothing: 0, confinementWeight: 0 };
const size = [4000, 4000, 4000];

function spheres(...list) {
    return { count: list.length, centres: Float32Array.from(list.flatMap(([x, y, z]) => [x, y, z])), radii: Float32Array.from(list.map(([, , , radius]) => radius)) };
}

// One boid at `position` flying at `velocity`.
function lone(position, velocity) {
    const flock = allocateFlock(1, 3);

    flock.pos.set(position);
    flock.vel.set(velocity);

    return flock;
}

const dot = (a, b) => a[0] * b[0] + a[1] * b[1] + a[2] * b[2];
const length = (a) => Math.hypot(a[0], a[1], a[2]);

describe('avoidance of obstacles', () => {
    test('does nothing when the way is clear', () => {
        const flock = lone([0, 0, 0], [2.8, 0, 0]);

        // Beside the path, behind the boid, and too far ahead to be reached within the look-ahead.
        for (const obstacle of [[60, 0, 80, 20], [-80, 0, 0, 20], [500, 0, 0, 20]]) {
            expect([...computeAvoidance(flock, 0, parameters, { obstacles: spheres(obstacle) })]).toEqual([0, 0, 0]);
        }
    });

    test('does nothing at all when its weight is zero', () => {
        const flock = lone([0, 0, 0], [2.8, 0, 0]);
        const environment = { obstacles: spheres([50, 0, 0, 20]) };

        expect([...computeAvoidance(flock, 0, { ...parameters, avoidanceWeight: 0 }, environment)]).toEqual([0, 0, 0]);
    });

    test('is off by default, so the 2D simulation is unchanged', () => {
        expect(DEFAULT_PARAMETERS.avoidanceWeight).toBe(0);
    });

    test('turns a boid that heads at an obstacle, sideways and not backwards', () => {
        const flock = lone([0, 0, 0], [2.8, 0, 0]);
        const force = computeAvoidance(flock, 0, parameters, { obstacles: spheres([60, 0, 5, 20]) });

        expect(length(force)).toBeGreaterThan(0);
        // Perpendicular to the velocity: it steers, it does not brake or accelerate.
        expect(Math.abs(dot(force, [1, 0, 0]))).toBeLessThan(1e-9);
        // The obstacle is slightly to the +z side, so the boid is pushed to -z.
        expect(force[2]).toBeLessThan(0);
    });

    test('steers away from the side the obstacle is on', () => {
        const flock = lone([0, 0, 0], [2.8, 0, 0]);
        const toTheLeft = computeAvoidance(flock, 0, parameters, { obstacles: spheres([60, 0, -5, 20]) });
        const toTheRight = computeAvoidance(flock, 0, parameters, { obstacles: spheres([60, 0, 5, 20]) });

        expect(toTheLeft[2]).toBeGreaterThan(0);
        expect(toTheRight[2]).toBeLessThan(0);
    });

    test('can steer over an obstacle when it is above or below the path', () => {
        const flock = lone([0, 0, 0], [2.8, 0, 0]);
        const obstacleAbove = computeAvoidance(flock, 0, parameters, { obstacles: spheres([60, -8, 0, 20]) });
        const obstacleBelow = computeAvoidance(flock, 0, parameters, { obstacles: spheres([60, 8, 0, 20]) });

        // y points down: an obstacle slightly above pushes the boid down, one slightly below pushes it up.
        expect(obstacleAbove[1]).toBeGreaterThan(0);
        expect(obstacleBelow[1]).toBeLessThan(0);
    });

    test('pushes harder as the obstacle gets closer', () => {
        const flock = lone([0, 0, 0], [2.8, 0, 0]);
        const far = computeAvoidance(flock, 0, parameters, { obstacles: spheres([80, 0, 5, 20]) });
        const near = computeAvoidance(flock, 0, parameters, { obstacles: spheres([40, 0, 5, 20]) });

        expect(length(near)).toBeGreaterThan(length(far));
    });

    test('never pushes harder than maxForce times its weight', () => {
        const flock = lone([0, 0, 0], [2.8, 0, 0]);
        const force = computeAvoidance(flock, 0, parameters, { obstacles: spheres([22, 0, 1, 20]) });

        expect(length(force)).toBeLessThanOrEqual(parameters.maxForce * parameters.avoidanceWeight + 1e-9);
    });

    test('only reacts to the most urgent obstacle', () => {
        const flock = lone([0, 0, 0], [2.8, 0, 0]);
        const both = computeAvoidance(flock, 0, parameters, { obstacles: spheres([70, 0, -4, 20], [35, 0, 4, 20]) });
        const nearest = computeAvoidance(flock, 0, parameters, { obstacles: spheres([35, 0, 4, 20]) });

        expect([...both]).toEqual([...nearest]);
    });
});

describe('a boid flying at an obstacle', () => {
    // Away from the edges of the playfield (4000 wide), where boids wrap around and the obstacle would not be seen.
    function fly(withAvoidance, obstacle = [2000, 2000, 2000, 30]) {
        const stats = { obstacles: 0, floor: 0 };
        let flock = lone([1750, 2000, 2002], [2.8, 0, 0]);
        let closest = Infinity;

        for (let frame = 0; frame < 220; frame++) {
            flock = stepFlock(flock, { ...parameters, avoidanceWeight: withAvoidance ? 4 : 0 }, size, null, { obstacles: spheres(obstacle), stats });
            closest = Math.min(closest, Math.hypot(flock.pos[0] - obstacle[0], flock.pos[1] - obstacle[1], flock.pos[2] - obstacle[2]) - obstacle[3]);
        }

        return { closest, finalX: flock.pos[0], stats };
    }

    test('goes round it without ever touching it', () => {
        const { closest, finalX, stats } = fly(true);

        expect(closest).toBeGreaterThan(0);
        expect(stats.obstacles).toBe(0);
        // It has carried on past the obstacle.
        expect(finalX).toBeGreaterThan(2100);
    });

    test('passes close to it: it deviates only as much as needed, it is not repelled', () => {
        const { closest } = fly(true);

        // Within a few clearances of the surface, not kept at a distance.
        expect(closest).toBeLessThan(parameters.avoidanceClearance * 3);
    });

    test('would have gone through it without avoidance, where the safety net has to step in', () => {
        const { stats } = fly(false);

        expect(stats.obstacles).toBeGreaterThan(0);
    });
});

describe('the ground', () => {
    test('a boid diving at the ground levels out before reaching it', () => {
        const stats = { obstacles: 0, floor: 0 };
        let flock = lone([0, 0, 0], [1.5, 2.4, 0]);
        let lowest = -Infinity;

        for (let frame = 0; frame < 300; frame++) {
            flock = stepFlock(flock, parameters, size, null, { floorLevel: 300, stats });
            lowest = Math.max(lowest, flock.pos[1]);
        }

        expect(lowest).toBeLessThan(300);
        expect(stats.floor).toBe(0);
    });

    test('a boid that is already below the ground is brought back to its level, and stops sinking', () => {
        const stats = { obstacles: 0, floor: 0 };
        const flock = lone([0, 350, 0], [0, 2, 0]);
        const next = stepFlock(flock, { ...parameters, avoidanceWeight: 0 }, size, null, { floorLevel: 300, stats });

        expect(next.pos[1]).toBeLessThanOrEqual(300);
        expect(next.vel[1]).toBeLessThanOrEqual(0);
        expect(stats.floor).toBe(1);
    });
});

describe('the safety net', () => {
    test('brings a boid that ends inside an obstacle back to its surface and drops its inward speed', () => {
        const stats = { obstacles: 0, floor: 0 };
        const flock = lone([-5, 0, 0], [2.8, 0, 0]);
        const next = stepFlock(flock, { ...parameters, avoidanceWeight: 0, cruiseSpeedRatio: 0 }, size, null, { obstacles: spheres([0, 0, 0, 30]), stats });

        expect(Math.hypot(next.pos[0], next.pos[1], next.pos[2])).toBeGreaterThanOrEqual(30 - 1e-3);
        expect(stats.obstacles).toBe(1);
        // It was heading into the sphere: no inward speed is left.
        expect(next.vel[0]).toBeLessThanOrEqual(1e-6);
    });

    test('copes with a boid exactly at the centre of an obstacle', () => {
        const flock = lone([0, 0, 0], [0, 0, 0]);
        const next = stepFlock(flock, { ...parameters, avoidanceWeight: 0 }, size, null, { obstacles: spheres([0, 0, 0, 30]) });

        expect([...next.pos].every(Number.isFinite)).toBe(true);
        expect(Math.hypot(next.pos[0], next.pos[1], next.pos[2])).toBeGreaterThanOrEqual(30 - 1e-3);
    });
});

describe('the shortcut for boids flying high above the obstacles', () => {
    // The environment of the real scene, and the same one with a dummy obstacle far above and far aside: it can touch no boid
    // but it raises the highest point of the obstacles, which turns the shortcut off. Both must give exactly the same flock.
    function run(withDummy) {
        const base = buildEnvironment(buildLandscape());
        const { count, centres, radii } = base.obstacles;
        const environment = withDummy
            ? { ...base, obstacles: { count: count + 1, centres: Float32Array.from([...centres, 5000, -5000, 5000]), radii: Float32Array.from([...radii, 10]) } }
            : base;
        let flock = createFlock(120, (() => { let state = 11; return () => { state = (state * 1664525 + 1013904223) % 4294967296; return state / 4294967296; }; })(), SCENE_SIZE_3D);

        for (let frame = 0; frame < 700; frame++) {
            flock = stepFlock(flock, { ...DEFAULT_PARAMETERS_3D, count: 120 }, SCENE_SIZE_3D, { position: roostPosition(frame, SCENE_SIZE_3D), mode: 'pull' }, environment);
        }

        return flock;
    }

    test('gives exactly the same flock as testing every obstacle for every boid', () => {
        const shortcut = run(false);
        const everything = run(true);

        expect([...shortcut.pos]).toEqual([...everything.pos]);
        expect([...shortcut.vel]).toEqual([...everything.vel]);
    });
});

describe('a flock among obstacles', () => {
    // Large obstacles scattered through a box (1400 x 500 x 700), and a flock released inside it.
    function scatter(seed) {
        let state = seed;
        const random = () => {
            state = (state * 1664525 + 1013904223) % 4294967296;

            return state / 4294967296;
        };

        return spheres(...Array.from({ length: 25 }, () => [100 + random() * 1200, 50 + random() * 400, 50 + random() * 600, 60 + random() * 30]));
    }

    function interventions(withAvoidance, seed) {
        const stats = { obstacles: 0, floor: 0 };
        const box = [1400, 500, 700];
        let state = seed;
        const random = () => {
            state = (state * 1664525 + 1013904223) % 4294967296;

            return state / 4294967296;
        };
        let flock = createFlock(150, random, box);

        for (let frame = 0; frame < 900; frame++) {
            flock = stepFlock(flock, { ...DEFAULT_PARAMETERS_3D, count: 150, avoidanceWeight: withAvoidance ? DEFAULT_PARAMETERS_3D.avoidanceWeight : 0 }, box, null, { obstacles: scatter(seed), stats });
        }

        return stats.obstacles;
    }

    test('almost never needs the safety net when it avoids, and often does when it does not', () => {
        for (const seed of [1, 2, 3]) {
            const without = interventions(false, seed);
            const withAvoidance = interventions(true, seed);

            expect(without).toBeGreaterThan(1000);
            // Measured: about a hundred times fewer last-minute corrections. Allow a wide margin.
            expect(withAvoidance).toBeLessThan(without / 20);
        }
    });
});
