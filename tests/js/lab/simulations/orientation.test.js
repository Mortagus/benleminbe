import { describe, expect, test } from 'vitest';
import { rotationFromXAxis } from '../../../../assets/scripts/lab/simulations/boids3d/orientation.js';

// Rodrigues' rotation formula: rotates a vector by `angle` around the unit `axis`.
function rotate([x, y, z], { angle, axis: [ax, ay, az] }) {
    const cos = Math.cos(angle);
    const sin = Math.sin(angle);
    const dot = ax * x + ay * y + az * z;
    const cross = [ay * z - az * y, az * x - ax * z, ax * y - ay * x];

    return [x * cos + cross[0] * sin + ax * dot * (1 - cos), y * cos + cross[1] * sin + ay * dot * (1 - cos), z * cos + cross[2] * sin + az * dot * (1 - cos)];
}

function unitVector(x, y, z) {
    const length = Math.hypot(x, y, z);

    return [x / length, y / length, z / length];
}

describe('rotationFromXAxis', () => {
    test('does not rotate a boid that already points along x', () => {
        expect(rotationFromXAxis(1, 0, 0).angle).toBe(0);
    });

    test('turns a boid exactly around when it points backwards', () => {
        const rotation = rotationFromXAxis(-1, 0, 0);

        expect(rotation.angle).toBeCloseTo(Math.PI);
        rotate([1, 0, 0], rotation).forEach((value, i) => expect(value).toBeCloseTo([-1, 0, 0][i]));
    });

    test.each([
        [0, 1, 0],
        [0, -1, 0],
        [0, 0, 1],
        [0, 0, -1],
        [1, 1, 0],
        [-1, 2, -3],
        [3, -1, 2],
        [-0.2, 0.1, 0.97],
    ])('turns the nose onto the direction (%d, %d, %d)', (x, y, z) => {
        const direction = unitVector(x, y, z);
        const turned = rotate([1, 0, 0], rotationFromXAxis(...direction));

        turned.forEach((value, i) => expect(value).toBeCloseTo(direction[i], 6));
    });

    test('turns the nose onto many random directions', () => {
        let state = 12345;
        const random = () => {
            state = (state * 1664525 + 1013904223) % 4294967296;

            return state / 4294967296 - 0.5;
        };

        for (let i = 0; i < 200; i++) {
            const direction = unitVector(random(), random(), random());
            const turned = rotate([1, 0, 0], rotationFromXAxis(...direction));

            turned.forEach((value, k) => expect(value).toBeCloseTo(direction[k], 6));
        }
    });

    test('uses a unit axis', () => {
        const { axis } = rotationFromXAxis(...unitVector(0.3, -0.5, 0.8));

        expect(Math.hypot(...axis)).toBeCloseTo(1);
    });
});
