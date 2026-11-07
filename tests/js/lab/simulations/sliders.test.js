import { describe, expect, test } from 'vitest';
import { defaultRatio, parameterToSlider, sliderToParameter } from '../../../../assets/scripts/lab/simulations/boids3d/sliders.js';

describe('sliderToParameter', () => {
    test('is the identity for a scale of 1', () => {
        expect(sliderToParameter(200, 1)).toBe(200);
        expect(sliderToParameter('0.7', 1)).toBe(0.7);
    });

    test('turns a percentage into a fraction without floating-point noise', () => {
        // In floating point 35 * 0.01 is 0.35000000000000003, 70 * 0.01 is 0.7000000000000001 and 95 * 0.01 is 0.9500000000000001.
        expect(35 * 0.01).not.toBe(0.35);
        expect(sliderToParameter(35, 0.01)).toBe(0.35);
        expect(sliderToParameter(70, 0.01)).toBe(0.7);
        expect(sliderToParameter(95, 0.01)).toBe(0.95);
        expect(sliderToParameter(15, 0.01)).toBe(0.15);
        expect(sliderToParameter(5, 0.01)).toBe(0.05);
        expect(sliderToParameter(100, 0.01)).toBe(1);
    });

    test('accepts the string a slider gives', () => {
        expect(sliderToParameter('70', 0.01)).toBe(0.7);
    });
});

describe('parameterToSlider', () => {
    test('is the inverse of sliderToParameter', () => {
        for (const value of [5, 10, 15, 35, 70, 100]) {
            expect(parameterToSlider(sliderToParameter(value, 0.01), 0.01)).toBe(value);
        }
    });

    test('gives a whole number for the default share of leaders', () => {
        expect(parameterToSlider(0.15, 0.01)).toBe(15);
    });

    test('is the identity for a scale of 1', () => {
        expect(parameterToSlider(0.7, 1)).toBe(0.7);
    });
});

describe('defaultRatio', () => {
    test('is 0 at the minimum, 1 at the maximum, and linear between', () => {
        expect(defaultRatio(100, 100, 500)).toBe(0);
        expect(defaultRatio(500, 100, 500)).toBe(1);
        expect(defaultRatio(200, 100, 500)).toBe(0.25);
    });
});
