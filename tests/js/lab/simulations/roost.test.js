import { describe, expect, test } from 'vitest';
import { SCENE_SIZE_3D } from '../../../../assets/scripts/lab/simulations/boids/flocking.js';
import { roostPosition } from '../../../../assets/scripts/lab/simulations/boids3d/roost.js';

describe('roostPosition', () => {
    test('has one coordinate per axis of the scene', () => {
        expect(roostPosition(0, SCENE_SIZE_3D)).toHaveLength(3);
        expect(roostPosition(0, [400, 300])).toHaveLength(2);
    });

    test('gives the same position for the same frame, without any state', () => {
        const first = roostPosition(1234, SCENE_SIZE_3D);

        roostPosition(1, SCENE_SIZE_3D);
        roostPosition(99999, SCENE_SIZE_3D);

        expect(roostPosition(1234, SCENE_SIZE_3D)).toEqual(first);
    });

    test('always stays inside the ellipsoid the flock is confined to, with room to spare', () => {
        for (let frame = 0; frame < 20000; frame += 7) {
            const position = roostPosition(frame, SCENE_SIZE_3D);
            const reach = Math.sqrt(position.reduce((sum, value, axis) => sum + ((value - SCENE_SIZE_3D[axis] / 2) / (SCENE_SIZE_3D[axis] / 2)) ** 2, 0));

            expect(reach).toBeLessThan(0.95);
        }
    });

    test('moves smoothly, by a small step from one frame to the next', () => {
        let largest = 0;
        let previous = roostPosition(0, SCENE_SIZE_3D);

        for (let frame = 1; frame < 5000; frame++) {
            const position = roostPosition(frame, SCENE_SIZE_3D);

            largest = Math.max(largest, Math.hypot(...position.map((value, axis) => value - previous[axis])));
            previous = position;
        }

        // Slower than the cruising speed of a boid (2.8 px per frame), so the flock can keep up with it.
        expect(largest).toBeLessThan(2.8);
    });

    test('uses the whole height of the scene, not just the horizontal plane', () => {
        const heights = Array.from({ length: 3000 }, (_, frame) => roostPosition(frame * 3, SCENE_SIZE_3D)[1]);
        const range = Math.max(...heights) - Math.min(...heights);

        // At least half of the full height.
        expect(range).toBeGreaterThan(SCENE_SIZE_3D[1] / 2);
    });

    test('does not repeat itself after a short time', () => {
        const start = roostPosition(0, SCENE_SIZE_3D);

        for (const frame of [200, 500, 1000, 2000, 3000]) {
            const position = roostPosition(frame, SCENE_SIZE_3D);

            expect(Math.hypot(...position.map((value, axis) => value - start[axis]))).toBeGreaterThan(5);
        }
    });
});
