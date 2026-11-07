/**
 * Orbit camera of the 3D simulation: it turns around a fixed target, and can never go below the ground.
 *
 * p5's own `orbitControl` has no limit on the angle, so dragging up or down takes the camera under the ground, which
 * then hides the whole scene. Here the camera is an azimuth (yaw), an elevation (pitch) and a distance, and a limit
 * function brings them back into what is allowed after every move.
 *
 * Pure maths, kept apart from the renderer and the input events so it can be unit tested. The y axis of p5 points
 * down, so "above" means a smaller y; a positive pitch puts the camera above the target.
 *
 * @typedef {{ yaw: number, pitch: number, distance: number }} CameraState
 *   `yaw` turns around the vertical axis (0 puts the camera on the +z side of the target), `pitch` is the angle above
 *   the horizontal plane through the target, `distance` is the distance to the target.
 * @typedef {{ groundY: number, clearance: number, minDistance: number, maxDistance: number, maxPitch: number }} CameraLimits
 *   The camera stays at least `clearance` above the ground plane `groundY`, between the two distances, and never
 *   higher than `maxPitch` (below the pole, so that it never flips over the top).
 */

function clamp(value, min, max) {
    return Math.min(Math.max(value, min), max);
}

/**
 * @param {CameraState} state
 * @param {ArrayLike<number>} target The point the camera aims at.
 * @returns {number[]} The position of the camera.
 */
export function cameraPosition(state, target) {
    const { yaw, pitch, distance } = state;

    return [
        target[0] + distance * Math.cos(pitch) * Math.sin(yaw),
        target[1] - distance * Math.sin(pitch),
        target[2] + distance * Math.cos(pitch) * Math.cos(yaw),
    ];
}

/**
 * The state that puts the camera at a given position, to start from an existing camera.
 *
 * @param {ArrayLike<number>} position
 * @param {ArrayLike<number>} target
 * @returns {CameraState}
 */
export function stateFromPosition(position, target) {
    const dx = position[0] - target[0];
    const dy = position[1] - target[1];
    const dz = position[2] - target[2];
    const distance = Math.hypot(dx, dy, dz);

    return { yaw: Math.atan2(dx, dz), pitch: Math.asin(clamp(-dy / distance, -1, 1)), distance };
}

/**
 * Brings a camera back into what is allowed: not too close or too far, not over the pole, and above the ground.
 *
 * The height of the camera is `target y - distance * sin(pitch)`, so staying above the ground is a lower bound on the
 * pitch that depends on the distance: the further the camera is from the target, the less it can look up from below.
 *
 * @param {CameraState} state
 * @param {ArrayLike<number>} target
 * @param {CameraLimits} limits
 * @returns {CameraState}
 */
export function limitCamera(state, target, limits) {
    const distance = clamp(state.distance, limits.minDistance, limits.maxDistance);
    // sin(pitch) must be at least this for the camera to be above `groundY - clearance`.
    const lowestSine = (target[1] - (limits.groundY - limits.clearance)) / distance;
    const lowestPitch = Math.asin(clamp(lowestSine, -1, 1));
    const pitch = Math.max(Math.min(state.pitch, limits.maxPitch), lowestPitch);

    return { yaw: state.yaw, pitch, distance };
}

/**
 * Turns the camera for a drag of `dx` pixels sideways and `dy` pixels down. Dragging down lifts the camera, as in the
 * usual orbit controls. The result is not limited: pass it to `limitCamera`.
 *
 * @param {CameraState} state
 * @param {number} dx
 * @param {number} dy
 * @param {number} sensitivity Radians per pixel.
 * @returns {CameraState}
 */
export function orbit(state, dx, dy, sensitivity) {
    return { yaw: state.yaw - dx * sensitivity, pitch: state.pitch + dy * sensitivity, distance: state.distance };
}

/**
 * Moves the camera closer (a factor below one) or further (above one). The result is not limited.
 *
 * @param {CameraState} state
 * @param {number} factor
 * @returns {CameraState}
 */
export function zoom(state, factor) {
    return { yaw: state.yaw, pitch: state.pitch, distance: state.distance * factor };
}
