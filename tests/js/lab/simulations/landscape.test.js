import { describe, expect, test } from 'vitest';
import { DEFAULT_PARAMETERS_3D, SCENE_SIZE_3D, confinementReach } from '../../../../assets/scripts/lab/simulations/boids/flocking.js';
import { FLIGHT_BOTTOM, GROUND_CLEARANCE, MAX_ELEMENT_HEIGHT, buildEnvironment, buildLandscape } from '../../../../assets/scripts/lab/simulations/boids3d/landscape.js';

const half = SCENE_SIZE_3D.map((extent) => extent / 2);

// Distance of a point from the centre of the scene, as a fraction of the half extent of the zone the flock is confined to,
// measured with the shape of that zone (a rounded box): 1 on its surface.
const exponent = DEFAULT_PARAMETERS_3D.confinementExponent;

function reach(x, y, z) {
    return (Math.abs(x / half[0]) ** exponent + Math.abs(y / half[1]) ** exponent + Math.abs(z / half[2]) ** exponent) ** (1 / exponent);
}

function vertices(triangles) {
    return Array.from({ length: triangles.length / 3 }, (_, i) => [triangles[i * 3], triangles[i * 3 + 1], triangles[i * 3 + 2]]);
}

const landscape = buildLandscape();
// The obstacles of the landscape, for tests that read them often.
const obstacles = () => landscape.obstacles;

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

    test('is just below the lowest point the flock can reach, so the flock can come close to it', () => {
        // In p5 the y axis points down, so below means larger y.
        expect(landscape.groundY).toBeCloseTo(FLIGHT_BOTTOM + GROUND_CLEARANCE, 9);
        expect(landscape.groundY).toBeGreaterThan(FLIGHT_BOTTOM);
    });

    test('is out of the reach of the flock, which goes at most 1.07 of the way to the confinement ellipsoid', () => {
        expect(landscape.groundY / half[1]).toBeGreaterThan(1.1);
    });

    test('is low enough for the tallest trees to stand up into the zone the flock flies in', () => {
        expect(landscape.groundY - MAX_ELEMENT_HEIGHT).toBeLessThan(FLIGHT_BOTTOM);
    });
});

describe('landscape silhouettes', () => {
    test('never enter the zone the flock flies in, except the trees, which the flock goes round', () => {
        const points = [
            ...vertices(landscape.horizon),
            ...vertices(landscape.ground),
            ...landscape.poles.flatMap((pole) => [[pole.x, landscape.groundY - pole.height, pole.z], ...pole.attachments]),
            ...landscape.cables.flatMap((cable) => [cable.from, cable.to, [(cable.from[0] + cable.to[0]) / 2, cable.lowest, cable.from[2]]]),
        ];

        for (const [x, y, z] of points) {
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

    test('are numerous in the corridor the flock flies along, whatever the seed', () => {
        for (const seed of seeds) {
            const { trees } = buildLandscape(seed);
            const inCorridor = trees.filter((tree) => Math.abs(tree.x) <= 800 && Math.abs(tree.z) <= 350);

            expect(inCorridor.length).toBeGreaterThanOrEqual(30);
        }
    });

    test('always reach their number of trees, whatever the seed', () => {
        for (const seed of seeds) {
            expect(buildLandscape(seed).trees.length).toBeGreaterThanOrEqual(50);
        }
    });
});

describe('landscape obstacles', () => {
    const { obstacles, trees, groundY } = landscape;
    const sphere = (k) => ({ x: obstacles.centres[k * 3], y: obstacles.centres[k * 3 + 1], z: obstacles.centres[k * 3 + 2], radius: obstacles.radii[k] });

    test('are made of three spheres for a conifer and one for a broadleaf', () => {
        const expected = trees.reduce((sum, tree) => sum + (tree.kind === 'conifer' ? 3 : 1), 0);

        expect(obstacles.count).toBe(expected);
        expect(obstacles.centres).toHaveLength(obstacles.count * 3);
        expect(obstacles.radii).toHaveLength(obstacles.count);
    });

    test('stand on the axis of a tree and stay inside its extent', () => {
        for (let k = 0; k < obstacles.count; k++) {
            const { x, y, z, radius } = sphere(k);
            const owner = trees.find((tree) => Math.abs(tree.x - x) < 1e-3 && Math.abs(tree.z - z) < 1e-3);

            expect(owner).toBeDefined();
            expect(radius).toBeGreaterThan(0);
            // The sphere does not go above the top of its tree, nor below the ground.
            expect(y - radius).toBeGreaterThanOrEqual(groundY - owner.height - 1e-3);
            expect(y + radius).toBeLessThanOrEqual(groundY + 1e-3);
        }
    });

    test('are the same every time for the same seed', () => {
        expect([...buildLandscape(7).obstacles.centres]).toEqual([...buildLandscape(7).obstacles.centres]);
        expect([...buildLandscape(7).obstacles.radii]).toEqual([...buildLandscape(7).obstacles.radii]);
    });

    test('reach into the zone the flock flies in, otherwise it would never have to avoid anything', () => {
        let inside = 0;

        for (let k = 0; k < obstacles.count; k++) {
            const { x, y, z } = sphere(k);

            if (reach(x, y, z) < 1) {
                inside++;
            }
        }

        // 17 at the time of writing: only the crowns of the tall trees (their centre must be above the lowest the flock goes)
        // and the top sphere of the conifers are in the zone. What matters is that there are some.
        expect(inside).toBeGreaterThanOrEqual(10);
    });
});

describe('landscape environment for the flock', () => {
    const environment = buildEnvironment(landscape);

    test('moves the obstacles from the centred coordinates of the landscape to those of the flock', () => {
        const { obstacles } = landscape;

        for (let k = 0; k < obstacles.count; k++) {
            for (let axis = 0; axis < 3; axis++) {
                expect(environment.obstacles.centres[k * 3 + axis]).toBeCloseTo(obstacles.centres[k * 3 + axis] + half[axis], 3);
            }

            expect(environment.obstacles.radii[k]).toBe(obstacles.radii[k]);
        }

        expect(environment.obstacles.count).toBe(obstacles.count);
    });

    test('keeps the shape of the landscape: an obstacle is as far from the middle of the zone in either coordinates', () => {
        for (let k = 0; k < obstacles().count; k += 7) {
            const centred = [obstacles().centres[k * 3], obstacles().centres[k * 3 + 1], obstacles().centres[k * 3 + 2]];
            const moved = [environment.obstacles.centres[k * 3], environment.obstacles.centres[k * 3 + 1], environment.obstacles.centres[k * 3 + 2]];

            expect(confinementReach(moved, SCENE_SIZE_3D, exponent)).toBeCloseTo(reach(...centred), 3);
        }
    });

    test('puts the floor just above the ground, in the coordinates of the flock, below the whole zone', () => {
        expect(environment.floorLevel).toBeCloseTo(landscape.groundY + half[1] - 8, 6);
        // The flock cannot get below the bottom of its zone (y = SCENE_SIZE_3D[1]) by more than 7%: the floor is further down.
        expect(environment.floorLevel).toBeGreaterThan(SCENE_SIZE_3D[1] * (1 + 0.07 / 2));
    });

    test('can be given another margin above the ground', () => {
        expect(buildEnvironment(landscape, 20).floorLevel).toBeCloseTo(landscape.groundY + half[1] - 20, 6);
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
