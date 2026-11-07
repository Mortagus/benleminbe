import { describe, expect, test } from 'vitest';
import { cameraPosition, limitCamera, orbit, stateFromPosition, zoom } from '../../../../assets/scripts/lab/simulations/boids3d/camera.js';

// The scene as the renderer sees it: the camera aims at `target`, the ground is a plane at y = groundY, and y points down.
const target = [0, 200, 0];
const limits = { groundY: 575, clearance: 20, minDistance: 300, maxDistance: 4000, maxPitch: (85 * Math.PI) / 180 };

const distanceBetween = (a, b) => Math.hypot(a[0] - b[0], a[1] - b[1], a[2] - b[2]);

describe('cameraPosition', () => {
    test('is always at the given distance from the target', () => {
        for (const [yaw, pitch, distance] of [[0, 0, 500], [1, 0.4, 1200], [-2, -0.3, 300], [3, 1.2, 4000]]) {
            expect(distanceBetween(cameraPosition({ yaw, pitch, distance }, target), target)).toBeCloseTo(distance, 6);
        }
    });

    test('is on the +z side of the target at yaw 0 and level with it at pitch 0', () => {
        const [x, y, z] = cameraPosition({ yaw: 0, pitch: 0, distance: 1000 }, target);

        expect(x).toBeCloseTo(0, 6);
        expect(y).toBeCloseTo(target[1], 6);
        expect(z).toBeCloseTo(1000, 6);
    });

    test('goes up (a smaller y) when the pitch is positive, since y points down', () => {
        expect(cameraPosition({ yaw: 0, pitch: 0.5, distance: 1000 }, target)[1]).toBeLessThan(target[1]);
        expect(cameraPosition({ yaw: 0, pitch: -0.5, distance: 1000 }, target)[1]).toBeGreaterThan(target[1]);
    });

    test('turns around the target as the yaw changes, keeping the same height', () => {
        const a = cameraPosition({ yaw: 0, pitch: 0.2, distance: 1000 }, target);
        const b = cameraPosition({ yaw: Math.PI / 2, pitch: 0.2, distance: 1000 }, target);

        expect(b[1]).toBeCloseTo(a[1], 6);
        expect(b[0]).toBeCloseTo(Math.cos(0.2) * 1000, 6);
        expect(b[2]).toBeCloseTo(0, 6);
    });
});

describe('stateFromPosition', () => {
    test('gives back the position it was made from', () => {
        const position = [0, 484, 1150];
        const state = stateFromPosition(position, target);

        cameraPosition(state, target).forEach((value, i) => expect(value).toBeCloseTo(position[i], 6));
    });

    test('reads the distance, the yaw and the pitch of a position', () => {
        const state = stateFromPosition([300, target[1] - 400, 0], target);

        expect(state.distance).toBeCloseTo(500, 6);
        expect(state.pitch).toBeCloseTo(Math.asin(400 / 500), 6);
        expect(state.yaw).toBeCloseTo(Math.PI / 2, 6);
    });
});

describe('limitCamera', () => {
    test('never lets the camera go below the ground, whatever the angle and the distance', () => {
        for (let yaw = -3; yaw <= 3; yaw += 0.5) {
            for (let pitch = -1.5; pitch <= 1.5; pitch += 0.1) {
                for (const distance of [100, 300, 700, 1500, 4000, 9000]) {
                    const limited = limitCamera({ yaw, pitch, distance }, target, limits);

                    expect(cameraPosition(limited, target)[1]).toBeLessThanOrEqual(limits.groundY - limits.clearance + 1e-6);
                }
            }
        }
    });

    test('leaves a camera that is already acceptable unchanged', () => {
        const state = { yaw: 0.3, pitch: 0.2, distance: 1200 };

        expect(limitCamera(state, target, limits)).toEqual(state);
    });

    test('keeps the distance between its bounds', () => {
        expect(limitCamera({ yaw: 0, pitch: 0.1, distance: 10 }, target, limits).distance).toBe(limits.minDistance);
        expect(limitCamera({ yaw: 0, pitch: 0.1, distance: 99999 }, target, limits).distance).toBe(limits.maxDistance);
    });

    test('keeps the pitch below the pole, so the camera never flips over the top', () => {
        expect(limitCamera({ yaw: 0, pitch: 3, distance: 1000 }, target, limits).pitch).toBeCloseTo(limits.maxPitch, 6);
    });

    test('lowers the camera as far as the ground allows, and no further', () => {
        const limited = limitCamera({ yaw: 0, pitch: -1.4, distance: 1000 }, target, limits);

        // It is brought up to just above the ground, not dropped to the horizon.
        expect(cameraPosition(limited, target)[1]).toBeCloseTo(limits.groundY - limits.clearance, 6);
    });

    test('can go lower the closer it is to the target, as the ground is below the target', () => {
        const near = limitCamera({ yaw: 0, pitch: -1.4, distance: 400 }, target, limits);
        const far = limitCamera({ yaw: 0, pitch: -1.4, distance: 2000 }, target, limits);

        // Both end at the same height above the ground, so the limit is on the height and not on the angle.
        expect(cameraPosition(near, target)[1]).toBeCloseTo(cameraPosition(far, target)[1], 6);
        expect(near.pitch).toBeLessThan(far.pitch);
    });

    test('does not produce NaN when the target is far above the ground and the distance is short', () => {
        const limited = limitCamera({ yaw: 0, pitch: -1, distance: 300 }, [0, -500, 0], limits);

        expect(Object.values(limited).every(Number.isFinite)).toBe(true);
    });
});

describe('orbit', () => {
    test('turns the camera about the target when dragging sideways, and up or down when dragging vertically', () => {
        const start = { yaw: 0, pitch: 0, distance: 1000 };

        expect(orbit(start, 100, 0, 0.005).yaw).not.toBe(0);
        expect(orbit(start, 100, 0, 0.005).pitch).toBe(0);
        // Dragging down lifts the camera, as in the usual orbit controls.
        expect(orbit(start, 0, 100, 0.005).pitch).toBeGreaterThan(0);
        expect(orbit(start, 0, -100, 0.005).pitch).toBeLessThan(0);
    });

    test('does not change the distance', () => {
        expect(orbit({ yaw: 0, pitch: 0.2, distance: 800 }, 40, -30, 0.005).distance).toBe(800);
    });

    test('dragging down then back up by the same amount comes back to the start', () => {
        const start = { yaw: 0.4, pitch: 0.2, distance: 800 };
        const back = orbit(orbit(start, 30, 50, 0.005), -30, -50, 0.005);

        expect(back.yaw).toBeCloseTo(start.yaw, 9);
        expect(back.pitch).toBeCloseTo(start.pitch, 9);
    });
});

describe('zoom', () => {
    test('brings the camera closer for a factor below one and further for a factor above', () => {
        expect(zoom({ yaw: 0, pitch: 0, distance: 1000 }, 0.5).distance).toBe(500);
        expect(zoom({ yaw: 0, pitch: 0, distance: 1000 }, 2).distance).toBe(2000);
    });

    test('keeps the angles', () => {
        const zoomed = zoom({ yaw: 0.7, pitch: 0.3, distance: 1000 }, 0.8);

        expect(zoomed.yaw).toBe(0.7);
        expect(zoomed.pitch).toBe(0.3);
    });
});

describe('a whole drag session', () => {
    test('a camera dragged and zoomed at random, then limited at every step, stays above the ground and within bounds', () => {
        let state = limitCamera(stateFromPosition([0, 484, 1150], target), target, limits);
        let seed = 7;
        const random = () => {
            seed = (seed * 1664525 + 1013904223) % 4294967296;

            return seed / 4294967296;
        };

        for (let step = 0; step < 5000; step++) {
            state = random() < 0.7 ? orbit(state, (random() - 0.5) * 400, (random() - 0.5) * 400, 0.005) : zoom(state, Math.exp((random() - 0.5) * 1.5));
            state = limitCamera(state, target, limits);

            expect(cameraPosition(state, target)[1]).toBeLessThanOrEqual(limits.groundY - limits.clearance + 1e-6);
            expect(state.distance).toBeGreaterThanOrEqual(limits.minDistance);
            expect(state.distance).toBeLessThanOrEqual(limits.maxDistance);
        }
    });
});
