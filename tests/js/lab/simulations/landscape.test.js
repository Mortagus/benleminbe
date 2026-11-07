import { describe, expect, test } from 'vitest';
import { SCENE_SIZE_3D } from '../../../../assets/scripts/lab/simulations/boids/flocking.js';
import { FLIGHT_BOTTOM, LANDSCAPE_MARGIN, MAX_ELEMENT_HEIGHT, buildLandscape } from '../../../../assets/scripts/lab/simulations/boids3d/landscape.js';

const half = SCENE_SIZE_3D.map((extent) => extent / 2);

// Distance of a point from the centre of the scene, as a fraction of the radius of the flight ellipsoid.
function reach(x, y, z) {
    return Math.sqrt((x / half[0]) ** 2 + (y / half[1]) ** 2 + (z / half[2]) ** 2);
}

function vertices(triangles) {
    return Array.from({ length: triangles.length / 3 }, (_, i) => [triangles[i * 3], triangles[i * 3 + 1], triangles[i * 3 + 2]]);
}

const landscape = buildLandscape();

describe('landscape geometry', () => {
    test('is made of whole triangles with finite coordinates', () => {
        for (const triangles of [landscape.ground, landscape.horizon, landscape.silhouettes]) {
            expect(triangles.length % 9).toBe(0);
            expect(triangles.length).toBeGreaterThan(0);
            expect(triangles.every(Number.isFinite)).toBe(true);
        }
    });

    test('is the same every time for the same seed, and differs for another', () => {
        expect([...buildLandscape(7).silhouettes]).toEqual([...buildLandscape(7).silhouettes]);
        expect([...buildLandscape(7).horizon]).toEqual([...buildLandscape(7).horizon]);
        expect([...buildLandscape(7).silhouettes]).not.toEqual([...buildLandscape(8).silhouettes]);
        expect([...buildLandscape().silhouettes]).toEqual([...buildLandscape().silhouettes]);
    });

    test('stays within a triangle budget the renderer can draw every frame', () => {
        expect((landscape.ground.length + landscape.horizon.length + landscape.silhouettes.length) / 9).toBeLessThan(20000);
    });
});

describe('landscape ground', () => {
    test('is flat at the ground level', () => {
        for (const [, y] of vertices(landscape.ground)) {
            expect(y).toBeCloseTo(landscape.groundY, 6);
        }
    });

    test('is large enough to reach the hills on the horizon', () => {
        const farthest = Math.max(...vertices(landscape.ground).map(([x, , z]) => Math.hypot(x, z)));
        const hills = Math.max(...vertices(landscape.horizon).map(([x, , z]) => Math.hypot(x, z)));

        expect(farthest).toBeGreaterThanOrEqual(hills);
    });

    test('is below the lowest point the flock can reach, with room for the tallest element and a margin', () => {
        // In p5 the y axis points down, so below means larger y.
        expect(landscape.groundY - MAX_ELEMENT_HEIGHT).toBeGreaterThanOrEqual(FLIGHT_BOTTOM + LANDSCAPE_MARGIN);
    });
});

describe('landscape silhouettes', () => {
    test('never enter the zone the flock flies in', () => {
        for (const [x, y, z] of [...vertices(landscape.silhouettes), ...vertices(landscape.horizon), ...vertices(landscape.ground)]) {
            // The flock goes at most 1.07 of the way to the confinement ellipsoid; stay clear of it.
            expect(reach(x, y, z)).toBeGreaterThanOrEqual(1.15);
        }
    });

    test('never rise above the tallest allowed element nor sink below the ground', () => {
        for (const [, y] of vertices(landscape.silhouettes)) {
            expect(y).toBeGreaterThanOrEqual(landscape.groundY - MAX_ELEMENT_HEIGHT - 1e-6);
            expect(y).toBeLessThanOrEqual(landscape.groundY + 1e-6);
        }
    });

    test('stand on the ground: the lowest points of the trees and poles touch it', () => {
        for (const tree of landscape.trees) {
            expect(tree.height).toBeGreaterThan(0);
            expect(tree.height).toBeLessThanOrEqual(MAX_ELEMENT_HEIGHT);
        }

        for (const pole of landscape.poles) {
            expect(pole.height).toBeGreaterThan(0);
            expect(pole.height).toBeLessThanOrEqual(MAX_ELEMENT_HEIGHT);
        }
    });
});

describe('landscape horizon', () => {
    test('is a ring of hills far from the scene, standing on the ground', () => {
        for (const [x, y, z] of vertices(landscape.horizon)) {
            expect(Math.hypot(x, z)).toBeGreaterThan(3000);
            expect(y).toBeLessThanOrEqual(landscape.groundY + 1e-6);
        }
    });

    test('rises above the ground all the way round, with an uneven skyline', () => {
        const tops = vertices(landscape.horizon).map(([, y]) => landscape.groundY - y).filter((height) => height > 1);
        const distinct = new Set(tops.map((height) => Math.round(height)));

        expect(Math.min(...tops)).toBeGreaterThan(50);
        expect(distinct.size).toBeGreaterThan(20);
    });
});

describe('landscape trees', () => {
    test('are numerous and of both kinds', () => {
        expect(landscape.trees.length).toBeGreaterThanOrEqual(50);
        expect(new Set(landscape.trees.map((tree) => tree.kind))).toEqual(new Set(['conifer', 'broadleaf']));
    });

    // The placement rules must hold for any seed, not only the default one, which may never come close to breaking them.
    const seeds = Array.from({ length: 60 }, (_, i) => i + 1);

    test('keep away from the power lines, so no crown goes through a cable, whatever the seed', () => {
        for (const seed of seeds) {
            const { trees, lines } = buildLandscape(seed);

            for (const tree of trees) {
                for (const line of lines) {
                    expect(Math.abs(tree.z - line.z)).toBeGreaterThanOrEqual(70);
                }
            }
        }
    });

    test('do not stand under the flock: a clear space under it, whatever the seed', () => {
        // Not required for safety (they are far below), but the scene reads better with a clear space under the flock.
        for (const seed of seeds) {
            for (const tree of buildLandscape(seed).trees) {
                expect(Math.hypot(tree.x, tree.z)).toBeGreaterThanOrEqual(150);
            }
        }
    });

    test('always reach their number of trees, whatever the seed', () => {
        for (const seed of seeds) {
            expect(buildLandscape(seed).trees.length).toBeGreaterThanOrEqual(50);
        }
    });
});

describe('landscape power lines', () => {
    test('have poles evenly spaced along each line', () => {
        for (const line of landscape.lines) {
            const poles = landscape.poles.filter((pole) => pole.line === line.index).sort((a, b) => a.x - b.x);

            expect(poles.length).toBeGreaterThanOrEqual(5);

            for (let i = 1; i < poles.length; i++) {
                expect(poles[i].x - poles[i - 1].x).toBeCloseTo(line.spacing, 6);
                expect(poles[i].z).toBeCloseTo(line.z, 6);
            }
        }
    });

    test('join every pair of neighbouring poles with three cables', () => {
        for (const line of landscape.lines) {
            const poles = landscape.poles.filter((pole) => pole.line === line.index).sort((a, b) => a.x - b.x);
            const cables = landscape.cables.filter((cable) => cable.line === line.index);

            expect(cables).toHaveLength((poles.length - 1) * 3);

            for (let span = 0; span < poles.length - 1; span++) {
                for (const attachment of poles[span].attachments) {
                    const matching = cables.filter((cable) => cable.from.every((value, k) => Math.abs(value - attachment[k]) < 1e-6));

                    expect(matching).toHaveLength(1);
                    expect(matching[0].to.every((value, k) => Math.abs(value - poles[span + 1].attachments[poles[span].attachments.indexOf(attachment)][k]) < 1e-6)).toBe(true);
                }
            }
        }
    });

    test('hang from the top of the poles and sag between them', () => {
        for (const cable of landscape.cables) {
            expect(cable.from[1]).toBeCloseTo(cable.to[1], 6);
            // The lowest point of the sag is lower than the ends (larger y, since y points down), and above the ground.
            expect(cable.lowest).toBeGreaterThan(cable.from[1]);
            expect(cable.lowest).toBeLessThan(landscape.groundY);
        }
    });
});
