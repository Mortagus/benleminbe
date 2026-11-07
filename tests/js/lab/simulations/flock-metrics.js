/**
 * Measures of the shape and the motion of a 3D flock, for tests and analysis. They turn words such as "a ball", "a sheet"
 * or "moving as one block" into numbers.
 */

// Eigenvalues of a symmetric 3x3 matrix, largest first.
function eigenvalues(a) {
    const offDiagonal = a[0][1] ** 2 + a[0][2] ** 2 + a[1][2] ** 2;
    const mean = (a[0][0] + a[1][1] + a[2][2]) / 3;

    if (offDiagonal < 1e-12) {
        return [a[0][0], a[1][1], a[2][2]].sort((x, y) => y - x);
    }

    const spread = Math.sqrt(((a[0][0] - mean) ** 2 + (a[1][1] - mean) ** 2 + (a[2][2] - mean) ** 2 + 2 * offDiagonal) / 6);
    const b = a.map((row, i) => row.map((value, j) => (value - (i === j ? mean : 0)) / spread));
    const determinant =
        b[0][0] * (b[1][1] * b[2][2] - b[1][2] * b[2][1]) - b[0][1] * (b[1][0] * b[2][2] - b[1][2] * b[2][0]) + b[0][2] * (b[1][0] * b[2][1] - b[1][1] * b[2][0]);
    const angle = Math.acos(Math.max(-1, Math.min(1, determinant / 2))) / 3;
    const largest = mean + 2 * spread * Math.cos(angle);
    const smallest = mean + 2 * spread * Math.cos(angle + (2 * Math.PI) / 3);

    return [largest, 3 * mean - largest - smallest, smallest];
}

const speedOf = (flock, i) => Math.hypot(flock.vel[i * 3], flock.vel[i * 3 + 1], flock.vel[i * 3 + 2]) || 1;

/** The shortest axis of the cloud of boids over its longest: 1 for a ball, small for a sheet or a thread. */
export function thicknessRatio(flock) {
    const centre = [0, 1, 2].map((d) => Array.from({ length: flock.count }, (_, i) => flock.pos[i * 3 + d]).reduce((a, b) => a + b, 0) / flock.count);
    const covariance = [[0, 0, 0], [0, 0, 0], [0, 0, 0]];

    for (let i = 0; i < flock.count; i++) {
        const offset = [0, 1, 2].map((d) => flock.pos[i * 3 + d] - centre[d]);

        for (let a = 0; a < 3; a++) {
            for (let b = 0; b < 3; b++) {
                covariance[a][b] += (offset[a] * offset[b]) / flock.count;
            }
        }
    }

    const [longest, , shortest] = eigenvalues(covariance);

    return Math.sqrt(Math.max(shortest, 0) / longest);
}

/** Length of the mean of the unit velocities: 1 when every boid goes the same way, near 0 when they go every way. */
export function polarization(flock) {
    const mean = [0, 0, 0];

    for (let i = 0; i < flock.count; i++) {
        for (let d = 0; d < 3; d++) {
            mean[d] += flock.vel[i * 3 + d] / speedOf(flock, i) / flock.count;
        }
    }

    return Math.hypot(...mean);
}

/** The share of boids that move up or down clearly (more than 25% of their speed) against the vertical motion of the flock. */
export function counterMovingShare(flock) {
    let meanVertical = 0;

    for (let i = 0; i < flock.count; i++) {
        meanVertical += flock.vel[i * 3 + 1] / speedOf(flock, i) / flock.count;
    }

    let against = 0;

    for (let i = 0; i < flock.count; i++) {
        const vertical = flock.vel[i * 3 + 1] / speedOf(flock, i);

        if (Math.abs(vertical) > 0.25 && vertical * meanVertical < 0) {
            against++;
        }
    }

    return against / flock.count;
}

/** The share of boids in the biggest group, where two boids are linked when they are closer than `link`. */
export function biggestGroupShare(flock, link) {
    const parent = Array.from({ length: flock.count }, (_, i) => i);
    const find = (i) => {
        while (parent[i] !== i) {
            parent[i] = parent[parent[i]];
            i = parent[i];
        }

        return i;
    };

    for (let i = 0; i < flock.count; i++) {
        for (let j = i + 1; j < flock.count; j++) {
            if (Math.hypot(flock.pos[i * 3] - flock.pos[j * 3], flock.pos[i * 3 + 1] - flock.pos[j * 3 + 1], flock.pos[i * 3 + 2] - flock.pos[j * 3 + 2]) < link) {
                parent[find(i)] = find(j);
            }
        }
    }

    const sizes = new Map();

    for (let i = 0; i < flock.count; i++) {
        sizes.set(find(i), (sizes.get(find(i)) ?? 0) + 1);
    }

    return Math.max(...sizes.values()) / flock.count;
}

/** The indices of the boids with no other boid within `radius`. */
export function lonelyBoids(flock, radius) {
    const lonely = [];

    for (let i = 0; i < flock.count; i++) {
        let neighbours = 0;

        for (let j = 0; j < flock.count && neighbours === 0; j++) {
            if (i !== j && Math.hypot(flock.pos[i * 3] - flock.pos[j * 3], flock.pos[i * 3 + 1] - flock.pos[j * 3 + 1], flock.pos[i * 3 + 2] - flock.pos[j * 3 + 2]) < radius) {
                neighbours++;
            }
        }

        if (neighbours === 0) {
            lonely.push(i);
        }
    }

    return lonely;
}
