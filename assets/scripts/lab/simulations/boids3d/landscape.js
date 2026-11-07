/**
 * The landscape of the 3D simulation: a ground, trees, power lines and hills on the horizon, as flat dark silhouettes.
 *
 * Everything is generated here as plain triangles, from a seeded random generator, so the result is always the same,
 * has no dependency on p5 or the DOM and can be unit tested. The renderer turns the triangles into two p5 geometries,
 * built once, so the whole landscape costs two draw calls per frame whatever the number of trees.
 *
 * Coordinates are those of the scene centred on its middle, as the renderer draws it. The y axis of p5 points down, so
 * "below" means a larger y, and the ground is at a positive y.
 *
 * Triangles are flat arrays of numbers: nine per triangle (three vertices of x, y, z).
 */
import { SCENE_SIZE_3D } from '../boids/flocking.js';

/** The tallest tree or pole, in scene units. A boid is about 20 units long, so these are stylised, not to scale. */
export const MAX_ELEMENT_HEIGHT = 150;
/** Empty space between the lowest point the flock reaches and the ground: the flock comes close to the ground. */
export const GROUND_CLEARANCE = 25;
/**
 * The lowest the flock goes: it can slightly overshoot the confinement ellipsoid (by 7% at most in the measurements), so
 * 1.1 times its half height.
 */
export const FLIGHT_BOTTOM = (SCENE_SIZE_3D[1] / 2) * 1.1;

const DEFAULT_SEED = 20261107;
const GROUND_RADIUS = 6000;
const HORIZON_RADIUS = 4800;
const TREE_COUNT = 78;
// More trees in the corridor the flock flies along (the whole width of its sweep, a little beyond its depth), and tall, so
// that their crowns reach into the zone the flock flies in and it has to go round them.
const CORRIDOR_TREE_COUNT = 40;
const CORRIDOR_HALF_WIDTH = 800;
const CORRIDOR_HALF_DEPTH = 350;
const CORRIDOR_MIN_HEIGHT = 90;
const TREE_SPACING = 30;
// Trees keep this far from the power lines, so that no crown goes through a cable.
const LINE_CLEARANCE = 70;
const CLUSTER_COUNT = 7;
// Two lines of poles, one behind the other, so that the cables overlap in layers when seen from the middle.
const LINES = Object.freeze([
    { z: -520, spacing: 300, height: 130, count: 11 },
    { z: -1150, spacing: 380, height: 110, count: 11 },
]);
const CABLE_OFFSETS = Object.freeze([-36, 0, 36]);
const CABLE_SEGMENTS = 10;
const CABLE_WIDTH = 1.8;
const SAG_RATIO = 0.05;

// Small deterministic random generator (mulberry32): the same seed always gives the same landscape.
function createRandom(seed) {
    let state = seed >>> 0;

    return () => {
        state = (state + 0x6d2b79f5) >>> 0;

        let t = Math.imul(state ^ (state >>> 15), 1 | state);

        t = (t + Math.imul(t ^ (t >>> 7), 61 | t)) ^ t;

        return ((t ^ (t >>> 14)) >>> 0) / 4294967296;
    };
}

function triangle(out, a, b, c) {
    out.push(a[0], a[1], a[2], b[0], b[1], b[2], c[0], c[1], c[2]);
}

function quad(out, a, b, c, d) {
    triangle(out, a, b, c);
    triangle(out, a, c, d);
}

/** An axis-aligned box, as twelve triangles. */
function box(out, [x0, y0, z0], [x1, y1, z1]) {
    quad(out, [x0, y0, z0], [x1, y0, z0], [x1, y1, z0], [x0, y1, z0]);
    quad(out, [x0, y0, z1], [x0, y1, z1], [x1, y1, z1], [x1, y0, z1]);
    quad(out, [x0, y0, z0], [x0, y1, z0], [x0, y1, z1], [x0, y0, z1]);
    quad(out, [x1, y0, z0], [x1, y0, z1], [x1, y1, z1], [x1, y1, z0]);
    quad(out, [x0, y0, z0], [x0, y0, z1], [x1, y0, z1], [x1, y0, z0]);
    quad(out, [x0, y1, z0], [x1, y1, z0], [x1, y1, z1], [x0, y1, z1]);
}

/** A cone standing on the plane `baseY`, narrowing to its apex at `topY`. */
function cone(out, x, z, baseY, topY, radius, sides) {
    for (let i = 0; i < sides; i++) {
        const a = (i / sides) * Math.PI * 2;
        const b = ((i + 1) / sides) * Math.PI * 2;

        triangle(out, [x + Math.cos(a) * radius, baseY, z + Math.sin(a) * radius], [x + Math.cos(b) * radius, baseY, z + Math.sin(b) * radius], [x, topY, z]);
    }
}

/** An ellipsoid, as bands of triangles between rings of latitude. */
function ellipsoid(out, [cx, cy, cz], [rx, ry, rz], segments, rings) {
    const point = (ring, segment) => {
        const polar = (ring / rings) * Math.PI;
        const azimuth = (segment / segments) * Math.PI * 2;

        return [cx + rx * Math.sin(polar) * Math.cos(azimuth), cy - ry * Math.cos(polar), cz + rz * Math.sin(polar) * Math.sin(azimuth)];
    };

    for (let ring = 0; ring < rings; ring++) {
        for (let segment = 0; segment < segments; segment++) {
            const a = point(ring, segment);
            const b = point(ring, segment + 1);
            const c = point(ring + 1, segment + 1);
            const d = point(ring + 1, segment);

            if (ring > 0) {
                triangle(out, a, b, c);
            }

            if (ring < rings - 1) {
                triangle(out, a, c, d);
            }
        }
    }
}

/** A thin ribbon between two points, made of two crossed quads (one vertical, one horizontal) so that it shows from any side. */
function ribbon(out, a, b, width) {
    const half = width / 2;
    const dx = b[0] - a[0];
    const dz = b[2] - a[2];
    const length = Math.hypot(dx, dz) || 1;
    const sideX = (-dz / length) * half;
    const sideZ = (dx / length) * half;

    quad(out, [a[0], a[1] - half, a[2]], [b[0], b[1] - half, b[2]], [b[0], b[1] + half, b[2]], [a[0], a[1] + half, a[2]]);
    quad(out, [a[0] - sideX, a[1], a[2] - sideZ], [b[0] - sideX, b[1], b[2] - sideZ], [b[0] + sideX, b[1], b[2] + sideZ], [a[0] + sideX, a[1], a[2] + sideZ]);
}

function buildGround(groundY) {
    const out = [];
    const sides = 48;

    for (let i = 0; i < sides; i++) {
        const a = (i / sides) * Math.PI * 2;
        const b = ((i + 1) / sides) * Math.PI * 2;

        triangle(out, [0, groundY, 0], [Math.cos(a) * GROUND_RADIUS, groundY, Math.sin(a) * GROUND_RADIUS], [Math.cos(b) * GROUND_RADIUS, groundY, Math.sin(b) * GROUND_RADIUS]);
    }

    return out;
}

/** A ring of low hills far away: a wall that rises from the ground and follows a smooth, uneven ridge line. */
function buildHorizon(groundY, random) {
    const out = [];
    const segments = 96;
    const phase = [random() * 6.28, random() * 6.28, random() * 6.28];
    const ridge = (index) => {
        const angle = (index / segments) * Math.PI * 2;

        return groundY - (260 + 90 * Math.sin(3 * angle + phase[0]) + 60 * Math.sin(7 * angle + phase[1]) + 30 * Math.sin(13 * angle + phase[2]));
    };

    for (let i = 0; i < segments; i++) {
        const a = (i / segments) * Math.PI * 2;
        const b = ((i + 1) / segments) * Math.PI * 2;
        const baseA = [Math.cos(a) * HORIZON_RADIUS, groundY, Math.sin(a) * HORIZON_RADIUS];
        const baseB = [Math.cos(b) * HORIZON_RADIUS, groundY, Math.sin(b) * HORIZON_RADIUS];

        quad(out, baseA, baseB, [baseB[0], ridge(i + 1), baseB[2]], [baseA[0], ridge(i), baseA[2]]);
    }

    return out;
}

function buildTree(out, tree, groundY) {
    const { x, z, height, kind } = tree;

    if (kind === 'conifer') {
        box(out, [x - 3, groundY - height * 0.22, z - 3], [x + 3, groundY, z + 3]);

        for (let level = 0; level < 3; level++) {
            const baseY = groundY - height * 0.18 - level * height * 0.2;

            cone(out, x, z, baseY, baseY - height * 0.42, height * 0.26 * (1 - 0.22 * level), 8);
        }

        return;
    }

    box(out, [x - 3.5, groundY - height * 0.42, z - 3.5], [x + 3.5, groundY, z + 3.5]);
    ellipsoid(out, [x, groundY - height * 0.62, z], [height * 0.34, height * 0.32, height * 0.34], 8, 5);
}

/** Trees in clumps with a few on their own, away from the power lines, and more in the corridor the flock flies along. */
function placeTrees(random) {
    const clusters = Array.from({ length: CLUSTER_COUNT }, () => {
        const angle = random() * Math.PI * 2;
        const distance = 400 + random() * 1100;

        return [Math.cos(angle) * distance, Math.sin(angle) * distance];
    });
    const trees = [];

    for (let attempts = 0; trees.length < TREE_COUNT && attempts < TREE_COUNT * 50; attempts++) {
        let x;
        let z;

        if (random() < 0.7) {
            // Clustered: the sum of two uniform numbers gives a bell-shaped spread around the centre of the clump.
            const [cx, cz] = clusters[Math.floor(random() * CLUSTER_COUNT)];

            x = cx + (random() + random() - 1) * 260;
            z = cz + (random() + random() - 1) * 260;
        } else {
            const angle = random() * Math.PI * 2;
            const distance = 200 + Math.sqrt(random()) * 1800;

            x = Math.cos(angle) * distance;
            z = Math.sin(angle) * distance;
        }

        const farFromLines = LINES.every((line) => Math.abs(z - line.z) >= LINE_CLEARANCE);

        if (farFromLines) {
            trees.push({ x, z, height: 60 + random() * (MAX_ELEMENT_HEIGHT - 10 - 60), kind: random() < 0.55 ? 'conifer' : 'broadleaf' });
        }
    }

    for (let attempts = 0, corridor = 0; corridor < CORRIDOR_TREE_COUNT && attempts < CORRIDOR_TREE_COUNT * 50; attempts++) {
        const x = (random() * 2 - 1) * CORRIDOR_HALF_WIDTH;
        const z = (random() * 2 - 1) * CORRIDOR_HALF_DEPTH;
        const height = CORRIDOR_MIN_HEIGHT + random() * (MAX_ELEMENT_HEIGHT - 10 - CORRIDOR_MIN_HEIGHT);
        const kind = random() < 0.55 ? 'conifer' : 'broadleaf';

        // Not on top of another tree of the corridor, so that each one stands out.
        if (trees.every((tree) => Math.hypot(tree.x - x, tree.z - z) >= TREE_SPACING)) {
            trees.push({ x, z, height, kind });
            corridor++;
        }
    }

    return trees;
}

/**
 * The trees as obstacles for the flock: spheres standing in for their crowns. A broadleaf is one sphere. A conifer is three
 * stacked cones and so three spheres, each at the lower third of its cone: a single cylinder would make boids avoid the
 * empty air around the pointed top. They are approximations of the shapes, good enough to pass close without touching.
 */
function buildObstacles(trees, groundY) {
    const centres = [];
    const radii = [];

    for (const { x, z, height, kind } of trees) {
        if (kind === 'broadleaf') {
            centres.push(x, groundY - height * 0.62, z);
            radii.push(height * 0.34);

            continue;
        }

        for (let level = 0; level < 3; level++) {
            const baseY = groundY - height * 0.18 - level * height * 0.2;
            const coneHeight = height * 0.42;
            const baseRadius = height * 0.26 * (1 - 0.22 * level);

            centres.push(x, baseY - coneHeight * 0.3, z);
            radii.push(baseRadius * 0.62);
        }
    }

    return { count: radii.length, centres: Float32Array.from(centres), radii: Float32Array.from(radii) };
}

/**
 * What the flock must avoid, in the coordinates of the flock. The landscape is centred on the middle of the scene (that is
 * how it is drawn), whereas the positions of the flock run from 0 to the size of the playfield, so everything is moved by half
 * the size of the playfield.
 *
 * @param {ReturnType<typeof buildLandscape>} landscape
 * @param {number} [floorMargin] How far above the ground the boids are kept: about the height of a bird.
 * @returns {{ obstacles: { count: number, centres: Float32Array, radii: Float32Array }, floorLevel: number }}
 */
export function buildEnvironment(landscape, floorMargin = 8) {
    const half = SCENE_SIZE_3D.map((extent) => extent / 2);
    const { count, centres, radii } = landscape.obstacles;
    const moved = new Float32Array(centres.length);

    for (let i = 0; i < centres.length; i++) {
        moved[i] = centres[i] + half[i % 3];
    }

    return { obstacles: { count, centres: moved, radii }, floorLevel: landscape.groundY + half[1] - floorMargin };
}

/**
 * @param {number} [seed] Seed of the random generator; the default gives the landscape of the site.
 * @returns {{
 *   groundY: number,
 *   ground: number[],
 *   horizon: number[],
 *   silhouettes: number[],
 *   trees: { x: number, z: number, height: number, kind: 'conifer' | 'broadleaf' }[],
 *   obstacles: { count: number, centres: Float32Array, radii: Float32Array },
 *   lines: { index: number, z: number, spacing: number, height: number }[],
 *   poles: { line: number, x: number, z: number, height: number, attachments: number[][] }[],
 *   cables: { line: number, from: number[], to: number[], lowest: number }[],
 * }} The ground and the hills on the horizon (each a list of triangles), the trees, poles and cables (one list of
 *   triangles) and a description of what they are made of.
 */
export function buildLandscape(seed = DEFAULT_SEED) {
    const random = createRandom(seed);
    // Just below the lowest point the flock reaches. The trees stand up into the zone the flock flies in: it goes round them.
    const groundY = FLIGHT_BOTTOM + GROUND_CLEARANCE;
    const silhouettes = [];
    const trees = placeTrees(random);

    for (const tree of trees) {
        buildTree(silhouettes, tree, groundY);
    }

    const lines = LINES.map((line, index) => ({ index, z: line.z, spacing: line.spacing, height: line.height }));
    const poles = [];
    const cables = [];

    LINES.forEach((line, index) => {
        const start = -((line.count - 1) * line.spacing) / 2;

        for (let i = 0; i < line.count; i++) {
            const x = start + i * line.spacing;
            const top = groundY - line.height;

            poles.push({ line: index, x, z: line.z, height: line.height, attachments: CABLE_OFFSETS.map((offset) => [x, top + 4, line.z + offset]) });
            box(silhouettes, [x - 3, top, line.z - 3], [x + 3, groundY, line.z + 3]);
            box(silhouettes, [x - 2.5, top + 1, line.z - 40], [x + 2.5, top + 5, line.z + 40]);
        }

        const linePoles = poles.filter((pole) => pole.line === index);

        for (let span = 0; span < linePoles.length - 1; span++) {
            CABLE_OFFSETS.forEach((offset, k) => {
                const from = linePoles[span].attachments[k];
                const to = linePoles[span + 1].attachments[k];
                const sag = SAG_RATIO * line.spacing;
                const at = (t) => [from[0] + (to[0] - from[0]) * t, from[1] + (to[1] - from[1]) * t + sag * 4 * t * (1 - t), from[2] + (to[2] - from[2]) * t];

                cables.push({ line: index, from, to, lowest: from[1] + sag });

                for (let segment = 0; segment < CABLE_SEGMENTS; segment++) {
                    ribbon(silhouettes, at(segment / CABLE_SEGMENTS), at((segment + 1) / CABLE_SEGMENTS), CABLE_WIDTH);
                }
            });
        }
    });

    return {
        groundY,
        ground: Float32Array.from(buildGround(groundY)),
        horizon: Float32Array.from(buildHorizon(groundY, random)),
        silhouettes: Float32Array.from(silhouettes),
        trees,
        obstacles: buildObstacles(trees, groundY),
        lines,
        poles,
        cables,
    };
}
