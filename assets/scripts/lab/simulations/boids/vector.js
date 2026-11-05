/**
 * Minimal immutable 2D vector helpers.
 *
 * The flocking rules are kept free of p5 so they can be unit tested without a DOM or a canvas.
 */

export const ZERO = Object.freeze({ x: 0, y: 0 });

export function add(a, b) {
    return { x: a.x + b.x, y: a.y + b.y };
}

export function sub(a, b) {
    return { x: a.x - b.x, y: a.y - b.y };
}

export function scale(vector, factor) {
    return { x: vector.x * factor, y: vector.y * factor };
}

export function magnitude(vector) {
    return Math.hypot(vector.x, vector.y);
}

export function setMagnitude(vector, length) {
    const current = magnitude(vector);

    if (current === 0) {
        return { x: 0, y: 0 };
    }

    return scale(vector, length / current);
}

export function limit(vector, maxLength) {
    return magnitude(vector) > maxLength ? setMagnitude(vector, maxLength) : { ...vector };
}
