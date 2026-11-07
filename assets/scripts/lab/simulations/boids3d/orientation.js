/**
 * Rotation that turns the +x axis, the nose of a drawn boid, onto a given direction, as an axis and an angle.
 * It is the shortest rotation, so a boid has no roll of its own: its wings stay as level as the direction allows.
 * Pure maths, kept apart from the renderer so it can be unit tested.
 *
 * @param {number} dx
 * @param {number} dy
 * @param {number} dz
 * @returns {{ angle: number, axis: [number, number, number] }} Rotation turning (1, 0, 0) onto the unit vector
 *   (dx, dy, dz), the angle in radians.
 */
export function rotationFromXAxis(dx, dy, dz) {
    // The axis is x cross direction = (0, -dz, dy), whose length is sin(angle) for a unit direction.
    const sideways = Math.hypot(dy, dz);
    const angle = Math.acos(Math.max(-1, Math.min(1, dx)));

    // Already pointing forwards or exactly backwards: any axis perpendicular to x works.
    if (sideways < 1e-6) {
        return { angle: dx > 0 ? 0 : Math.PI, axis: [0, 1, 0] };
    }

    return { angle, axis: [0, -dz / sideways, dy / sideways] };
}
