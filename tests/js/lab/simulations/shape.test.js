import { describe, expect, test } from 'vitest';
import { DEFAULT_PARAMETERS_3D, SCENE_SIZE_3D, createFlock, stepFlock } from '../../../../assets/scripts/lab/simulations/boids/flocking.js';
import { buildEnvironment, buildLandscape } from '../../../../assets/scripts/lab/simulations/boids3d/landscape.js';
import { roostPosition } from '../../../../assets/scripts/lab/simulations/boids3d/roost.js';
import { biggestGroupShare, counterMovingShare, lonelyBoids, polarization, thicknessRatio } from './flock-metrics.js';

function seededRandom(seed) {
    let state = seed;

    return () => {
        state = (state * 1664525 + 1013904223) % 4294967296;

        return state / 4294967296;
    };
}

const environment = buildEnvironment(buildLandscape());

/** Flies a flock of 200 boids through the real scene and returns the averages of the shape measures over time. */
function fly(seed, overrides) {
    const parameters = { ...DEFAULT_PARAMETERS_3D, count: 200, ...overrides };
    let flock = createFlock(200, seededRandom(seed), SCENE_SIZE_3D);
    const thickness = [];
    const polar = [];
    const counter = [];
    const groups = [];
    const lonelyShare = [];
    const lonelyRuns = new Map();
    let longestLonely = 0;

    for (let frame = 0; frame < 2400; frame++) {
        flock = stepFlock(flock, parameters, SCENE_SIZE_3D, { position: roostPosition(frame, SCENE_SIZE_3D), mode: 'pull' }, environment, frame);

        if (frame >= 400 && frame % 20 === 0) {
            const lonely = lonelyBoids(flock, 100);

            thickness.push(thicknessRatio(flock));
            polar.push(polarization(flock));
            counter.push(counterMovingShare(flock));
            groups.push(biggestGroupShare(flock, 100));
            lonelyShare.push(lonely.length / flock.count);

            for (const [boid, samples] of lonelyRuns) {
                if (!lonely.includes(boid)) {
                    lonelyRuns.delete(boid);
                } else {
                    lonelyRuns.set(boid, samples + 1);
                    longestLonely = Math.max(longestLonely, (samples + 1) * 20);
                }
            }

            for (const boid of lonely) {
                if (!lonelyRuns.has(boid)) {
                    lonelyRuns.set(boid, 1);
                    longestLonely = Math.max(longestLonely, 20);
                }
            }
        }
    }

    const mean = (values) => values.reduce((a, b) => a + b, 0) / values.length;

    return { thickness: mean(thickness), polarization: mean(polar), counterMoving: mean(counter), groupMin: Math.min(...groups), groupMax: Math.max(...groups), lonelyShare: mean(lonelyShare), longestLonely };
}

describe('the default 3D flock is a sheet that ripples, not a ball that moves as one block', () => {
    test('has the default settings for the leaders and the wave', () => {
        expect(DEFAULT_PARAMETERS_3D.pointerFraction).toBe(0.15);
        expect(DEFAULT_PARAMETERS_3D.waveWeight).toBe(0.7);
    });

    test.each([1, 2])('with the default settings (seed %d)', (seed) => {
        const result = fly(seed);

        // Measured: thickness 0.43, polarization 0.81, counter-moving 0.17, biggest group between 0.54 and 1, nobody lonely for long.
        // A ball would be 0.67, 0.98, 0.00 and 1.00.
        expect(result.thickness).toBeLessThan(0.55);
        expect(result.polarization).toBeLessThan(0.92);
        expect(result.counterMoving).toBeGreaterThan(0.05);
        // The cohesion comes and goes: the flock is whole at times and splits at others.
        expect(result.groupMax).toBeGreaterThan(0.9);
        expect(result.groupMin).toBeLessThan(0.9);
        // But nobody is lost: few lonely boids, and none for long (measured 1.3 seconds at most; 400 frames is 6.7 seconds).
        expect(result.lonelyShare).toBeLessThan(0.02);
        expect(result.longestLonely).toBeLessThanOrEqual(400);
    });

    test.each([1, 2])('without the leaders and the wave it is a ball again (seed %d), which shows they are what makes the difference', (seed) => {
        const result = fly(seed, { pointerFraction: 1, waveWeight: 0 });

        expect(result.thickness).toBeGreaterThan(0.58);
        expect(result.polarization).toBeGreaterThan(0.96);
        expect(result.counterMoving).toBeLessThan(0.02);
        expect(result.groupMin).toBeGreaterThan(0.95);
    });
});
