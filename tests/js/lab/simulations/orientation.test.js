import { describe, expect, test } from 'vitest';
import { WORLD_UP, orientationMatrix, updateUps } from '../../../../assets/scripts/lab/simulations/boids3d/orientation.js';

const dot = (a, b) => a[0] * b[0] + a[1] * b[1] + a[2] * b[2];
const length = (a) => Math.hypot(a[0], a[1], a[2]);

function unitVector(x, y, z) {
    const norm = Math.hypot(x, y, z);

    return [x / norm, y / norm, z / norm];
}

// A direction that points `pitch` radians above the horizon (towards the world up) and `yaw` radians around it.
function direction(yaw, pitch) {
    return unitVector(Math.cos(pitch) * Math.cos(yaw), -Math.sin(pitch), Math.cos(pitch) * Math.sin(yaw));
}

function directionsOf(...vectors) {
    return Float32Array.from(vectors.flat());
}

const upOf = (array, index) => [array[index * 3], array[index * 3 + 1], array[index * 3 + 2]];

// Runs a boid through a list of directions, as the renderer does frame after frame, and returns the up of each frame.
function followUps(path, factor = 0.25) {
    let current = new Float32Array(3);
    const result = [];

    for (const heading of path) {
        current = updateUps(current, directionsOf(heading), factor);
        result.push(upOf(current, 0));
    }

    return result;
}

describe('world up', () => {
    test('is the negative y axis, because the y axis of p5 points down', () => {
        expect(WORLD_UP).toEqual([0, -1, 0]);
    });
});

describe('updateUps', () => {
    test('a new boid takes the world up seen from its direction', () => {
        const result = updateUps(new Float32Array(3), directionsOf(unitVector(1, 0, 0)), 0.25);

        expect(upOf(result, 0)).toEqual([0, -1, 0]);
    });

    test('keeps the up perpendicular to the direction and of unit length', () => {
        const heading = direction(0.7, 0.4);
        const result = updateUps(new Float32Array(3), directionsOf(heading), 0.25);

        expect(dot(upOf(result, 0), heading)).toBeCloseTo(0, 5);
        expect(length(upOf(result, 0))).toBeCloseTo(1, 5);
    });

    test('keeps the belly down in level flight, all the way round a full circle', () => {
        const path = Array.from({ length: 360 }, (_, degrees) => direction((degrees * Math.PI) / 180, 0));

        for (const up of followUps(path)) {
            expect(dot(up, WORLD_UP)).toBeGreaterThan(0.999);
        }
    });

    test('does not flip when the boid turns right round to fly the other way', () => {
        const path = [direction(0, 0), direction(Math.PI, 0), direction(Math.PI, 0), direction(0, 0)];

        for (const up of followUps(path)) {
            expect(dot(up, WORLD_UP)).toBeGreaterThan(0.999);
        }
    });

    test('keeps the belly down when climbing or diving at a normal angle', () => {
        for (const pitch of [-1, -0.6, 0.6, 1]) {
            const path = Array.from({ length: 30 }, () => direction(1.2, pitch));

            for (const up of followUps(path)) {
                expect(dot(up, WORLD_UP)).toBeGreaterThan(0.5);
            }
        }
    });

    test('rolls gradually, never by a jump, while the boid pitches up through the vertical', () => {
        // From level flight, up past the vertical and over the top, 2 degrees per frame.
        const path = Array.from({ length: 100 }, (_, step) => direction(0.3, (step * 2 * Math.PI) / 180));
        const result = followUps(path, 0.1);
        let largestStep = 0;

        for (let i = 1; i < result.length; i++) {
            largestStep = Math.max(largestStep, Math.acos(Math.min(1, dot(result[i - 1], result[i]))));
        }

        // The up has to turn half way round as the direction crosses the vertical. It does it over several frames:
        // a tenth of the remaining half turn (0.31 rad) at most per frame, where a flip would be 3.14 rad at once.
        expect(largestStep).toBeLessThan(0.35);
    });

    test('keeps the previous up when the direction is exactly vertical', () => {
        const before = direction(0, 0.3);
        let current = updateUps(new Float32Array(3), directionsOf(before), 0.25);
        const upBefore = upOf(current, 0);

        current = updateUps(current, directionsOf(unitVector(0, -1, 0)), 0.25);

        const upAfter = upOf(current, 0);

        expect(Number.isNaN(upAfter[0])).toBe(false);
        expect(length(upAfter)).toBeCloseTo(1, 5);
        expect(dot(upAfter, [0, -1, 0])).toBeCloseTo(0, 4);
        // Climbing at 0.3 rad the up was nearly vertical; now it must be horizontal. The closest horizontal vector
        // shares exactly the horizontal part of the old one, which is sin(0.3).
        expect(dot(upAfter, upBefore)).toBeCloseTo(Math.sin(0.3), 3);
    });

    test('a vertical boid keeps its own up, whatever the boids before it did', () => {
        // The first boid has a target; the second flies exactly vertically and has none. It must not borrow the first one's.
        const level = direction(0, 0);
        const vertical = unitVector(0, -1, 0);
        const settled = updateUps(new Float32Array(6), directionsOf(level, direction(0.5, 0.4)), 0.25);
        const expected = upOf(updateUps(settled.slice(), directionsOf(level, vertical), 0.25), 1);
        const alone = upOf(updateUps(Float32Array.from(upOf(settled, 1)), directionsOf(vertical), 0.25), 0);

        expected.forEach((value, i) => expect(value).toBeCloseTo(alone[i], 6));
    });

    test('gives a boid that is new and exactly vertical a valid up', () => {
        for (const vertical of [unitVector(0, -1, 0), unitVector(0, 1, 0)]) {
            const result = updateUps(new Float32Array(3), directionsOf(vertical), 0.25);
            const up = upOf(result, 0);

            expect(length(up)).toBeCloseTo(1, 5);
            expect(dot(up, vertical)).toBeCloseTo(0, 5);
        }
    });

    test('recovers a belly-down attitude after flying over the top', () => {
        // Over the top of a loop the up ends up pointing back, then the boid flies level the other way.
        const loop = Array.from({ length: 90 }, (_, step) => direction(0, (step * 2 * Math.PI) / 180));
        const levelAgain = Array.from({ length: 80 }, () => direction(Math.PI, 0));
        const result = followUps([...loop, ...levelAgain]);

        expect(dot(result[result.length - 1], WORLD_UP)).toBeGreaterThan(0.99);
    });

    test('updates the array in place when the flock size did not change', () => {
        const current = new Float32Array(6);

        expect(updateUps(current, directionsOf(direction(0, 0), direction(1, 0)), 0.25)).toBe(current);
    });

    test('keeps the ups of existing boids when the flock grows or shrinks', () => {
        const headings = [direction(0, 0), direction(1, 0.2), direction(2, -0.2)];
        const existing = updateUps(new Float32Array(6), directionsOf(headings[0], headings[1]), 0.25);

        const grown = updateUps(existing, directionsOf(...headings), 0);

        expect(grown).toHaveLength(9);
        grown.subarray(0, 6).forEach((value, i) => expect(value).toBeCloseTo(existing[i], 6));
        expect(length(upOf(grown, 2))).toBeCloseTo(1, 5);

        const shrunk = updateUps(grown, directionsOf(headings[0]), 0);

        expect(shrunk).toHaveLength(3);
    });
});

describe('orientationMatrix', () => {
    // Applies a column-major 4x4 matrix to a vector (without translation).
    const apply = (matrix, [x, y, z]) => [
        matrix[0] * x + matrix[4] * y + matrix[8] * z,
        matrix[1] * x + matrix[5] * y + matrix[9] * z,
        matrix[2] * x + matrix[6] * y + matrix[10] * z,
    ];

    test('turns the nose (+x) onto the direction and the back (-y) onto the up', () => {
        const heading = direction(0.9, 0.3);
        const up = upOf(updateUps(new Float32Array(3), directionsOf(heading), 0.25), 0);
        const matrix = orientationMatrix(heading, up, new Float64Array(16));

        apply(matrix, [1, 0, 0]).forEach((value, i) => expect(value).toBeCloseTo(heading[i], 6));
        apply(matrix, [0, -1, 0]).forEach((value, i) => expect(value).toBeCloseTo(up[i], 6));
    });

    test('is a proper rotation: orthonormal and not mirrored', () => {
        const heading = direction(-2, -0.5);
        const up = upOf(updateUps(new Float32Array(3), directionsOf(heading), 0.25), 0);
        const matrix = orientationMatrix(heading, up, new Float64Array(16));
        const x = apply(matrix, [1, 0, 0]);
        const y = apply(matrix, [0, 1, 0]);
        const z = apply(matrix, [0, 0, 1]);

        expect(length(x)).toBeCloseTo(1, 6);
        expect(length(y)).toBeCloseTo(1, 6);
        expect(length(z)).toBeCloseTo(1, 6);
        expect(dot(x, y)).toBeCloseTo(0, 6);
        expect(dot(x, z)).toBeCloseTo(0, 6);
        expect(dot(y, z)).toBeCloseTo(0, 6);
        // x cross y = z: a rotation, not a reflection.
        const cross = [x[1] * y[2] - x[2] * y[1], x[2] * y[0] - x[0] * y[2], x[0] * y[1] - x[1] * y[0]];

        cross.forEach((value, i) => expect(value).toBeCloseTo(z[i], 6));
    });

    test('keeps the wings horizontal in level flight', () => {
        for (const yaw of [0, 1, 2, 3, 4, 5]) {
            const heading = direction(yaw, 0);
            const up = upOf(updateUps(new Float32Array(3), directionsOf(heading), 0.25), 0);
            const wing = apply(orientationMatrix(heading, up, new Float64Array(16)), [0, 0, 1]);

            expect(Math.abs(dot(wing, WORLD_UP))).toBeLessThan(1e-6);
        }
    });

    test('has the identity attitude for a boid flying along +x with the world up', () => {
        const matrix = orientationMatrix([1, 0, 0], [0, -1, 0], new Float64Array(16));

        [1, 0, 0, 0, 0, 1, 0, 0, 0, 0, 1, 0, 0, 0, 0, 1].forEach((value, i) => expect(matrix[i]).toBeCloseTo(value, 9));
    });
});
