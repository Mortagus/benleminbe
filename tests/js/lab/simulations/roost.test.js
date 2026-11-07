import { describe, expect, test } from 'vitest';
import { DEFAULT_PARAMETERS_3D, SCENE_SIZE_3D, confinementReach } from '../../../../assets/scripts/lab/simulations/boids/flocking.js';
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

    test('always stays inside the zone the flock is confined to, with room to spare', () => {
        for (let frame = 0; frame < 20000; frame += 7) {
            const reach = confinementReach(roostPosition(frame, SCENE_SIZE_3D), SCENE_SIZE_3D, DEFAULT_PARAMETERS_3D.confinementExponent);

            // At the very most 0.944 when every axis is at the end of its swing at once.
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

        // Measured at 3.47 at the quickest. The flock can keep up with it: its top speed is 4 units per frame.
        expect(largest).toBeLessThan(DEFAULT_PARAMETERS_3D.maxSpeed * 0.9);
    });

    test('uses the whole height of the scene, not just the horizontal plane', () => {
        const heights = Array.from({ length: 3000 }, (_, frame) => roostPosition(frame * 3, SCENE_SIZE_3D)[1]);
        const range = Math.max(...heights) - Math.min(...heights);

        // At least half of the full height.
        expect(range).toBeGreaterThan(SCENE_SIZE_3D[1] / 2);
    });

    test('lingers near the lowest point of its swing, at tree height, instead of only brushing it', () => {
        const heights = Array.from({ length: 40000 }, (_, frame) => roostPosition(frame, SCENE_SIZE_3D)[1]);
        const highest = Math.max(...heights);
        const range = highest - Math.min(...heights);
        const share = heights.filter((y) => y >= highest - 0.15 * range).length / heights.length;

        // A plain sine spends 25% of its time in the lowest 15% of its range; this one 39%.
        expect(share).toBeGreaterThan(0.33);
    });

    test('goes lower than it goes high: the path is a little low, towards the trees', () => {
        const heights = Array.from({ length: 40000 }, (_, frame) => roostPosition(frame, SCENE_SIZE_3D)[1] - SCENE_SIZE_3D[1] / 2);

        // y points down: positive is below the middle of the scene.
        expect(Math.max(...heights)).toBeGreaterThan(-Math.min(...heights));
    });

    test('does not repeat itself after a short time', () => {
        const start = roostPosition(0, SCENE_SIZE_3D);

        for (const frame of [200, 500, 1000, 2000, 3000]) {
            const position = roostPosition(frame, SCENE_SIZE_3D);

            expect(Math.hypot(...position.map((value, axis) => value - start[axis]))).toBeGreaterThan(5);
        }
    });
});
