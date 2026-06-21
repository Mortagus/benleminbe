export const SIMON_DEFAULT_SOUND_PALETTE_ID = 'classic';

export const SIMON_SOUND_PALETTE_IDS = Object.freeze([
    'classic',
    'arcade',
    'crystal',
    'synthwave',
    'percussion',
]);

export const SIMON_SOUND_PALETTES = Object.freeze({
    classic: Object.freeze({
        pads: Object.freeze([
            Object.freeze({ frequency: 246.94, type: 'sine', gain: 1, duration: 0.11, attack: 0.008, release: 0.05 }),
            Object.freeze({ frequency: 329.63, type: 'sine', gain: 1, duration: 0.11, attack: 0.008, release: 0.05 }),
            Object.freeze({ frequency: 392.0, type: 'sine', gain: 1, duration: 0.11, attack: 0.008, release: 0.05 }),
            Object.freeze({ frequency: 523.25, type: 'sine', gain: 1, duration: 0.11, attack: 0.008, release: 0.05 }),
        ]),
        feedback: Object.freeze({
            start: Object.freeze([
                Object.freeze({ frequency: 261.63, type: 'triangle', gain: 0.95, duration: 0.08, attack: 0.008, release: 0.05 }),
                Object.freeze({ frequency: 329.63, type: 'triangle', gain: 0.95, duration: 0.08, attack: 0.008, release: 0.05 }),
            ]),
            success: Object.freeze([
                Object.freeze({ frequency: 392.0, type: 'sine', gain: 0.95, duration: 0.08, attack: 0.008, release: 0.05 }),
                Object.freeze({ frequency: 523.25, type: 'sine', gain: 0.95, duration: 0.08, attack: 0.008, release: 0.05 }),
                Object.freeze({ frequency: 659.25, type: 'sine', gain: 0.95, duration: 0.08, attack: 0.008, release: 0.05 }),
            ]),
            error: Object.freeze([
                Object.freeze({ frequency: 220.0, type: 'sawtooth', gain: 0.85, duration: 0.12, attack: 0.008, release: 0.05 }),
                Object.freeze({ frequency: 174.61, type: 'sawtooth', gain: 0.85, duration: 0.12, attack: 0.008, release: 0.05 }),
            ]),
        }),
        previewGap: 45,
    }),
    arcade: Object.freeze({
        pads: Object.freeze([
            Object.freeze({ frequency: 220.0, type: 'square', gain: 0.82, duration: 0.08, attack: 0.004, release: 0.03 }),
            Object.freeze({ frequency: 277.18, type: 'square', gain: 0.82, duration: 0.08, attack: 0.004, release: 0.03 }),
            Object.freeze({ frequency: 329.63, type: 'square', gain: 0.82, duration: 0.08, attack: 0.004, release: 0.03 }),
            Object.freeze({ frequency: 415.3, type: 'square', gain: 0.82, duration: 0.08, attack: 0.004, release: 0.03 }),
        ]),
        feedback: Object.freeze({
            start: Object.freeze([
                Object.freeze({ frequency: 440.0, type: 'square', gain: 0.8, duration: 0.05, attack: 0.004, release: 0.03 }),
                Object.freeze({ frequency: 659.25, type: 'square', gain: 0.8, duration: 0.05, attack: 0.004, release: 0.03 }),
            ]),
            success: Object.freeze([
                Object.freeze({ frequency: 523.25, type: 'square', gain: 0.8, duration: 0.05, attack: 0.004, release: 0.03 }),
                Object.freeze({ frequency: 659.25, type: 'square', gain: 0.8, duration: 0.05, attack: 0.004, release: 0.03 }),
                Object.freeze({ frequency: 783.99, type: 'square', gain: 0.8, duration: 0.05, attack: 0.004, release: 0.03 }),
            ]),
            error: Object.freeze([
                Object.freeze({ frequency: 196.0, type: 'square', gain: 0.75, duration: 0.08, attack: 0.004, release: 0.03 }),
                Object.freeze({ frequency: 146.83, type: 'square', gain: 0.75, duration: 0.08, attack: 0.004, release: 0.03 }),
            ]),
        }),
        previewGap: 35,
    }),
    crystal: Object.freeze({
        pads: Object.freeze([
            Object.freeze({ frequency: 392.0, type: 'triangle', gain: 0.68, duration: 0.1, attack: 0.012, release: 0.08 }),
            Object.freeze({ frequency: 493.88, type: 'triangle', gain: 0.68, duration: 0.1, attack: 0.012, release: 0.08 }),
            Object.freeze({ frequency: 587.33, type: 'triangle', gain: 0.68, duration: 0.1, attack: 0.012, release: 0.08 }),
            Object.freeze({ frequency: 783.99, type: 'triangle', gain: 0.68, duration: 0.1, attack: 0.012, release: 0.08 }),
        ]),
        feedback: Object.freeze({
            start: Object.freeze([
                Object.freeze({ frequency: 523.25, type: 'triangle', gain: 0.62, duration: 0.07, attack: 0.012, release: 0.08 }),
                Object.freeze({ frequency: 659.25, type: 'triangle', gain: 0.62, duration: 0.07, attack: 0.012, release: 0.08 }),
            ]),
            success: Object.freeze([
                Object.freeze({ frequency: 659.25, type: 'triangle', gain: 0.62, duration: 0.07, attack: 0.012, release: 0.08 }),
                Object.freeze({ frequency: 783.99, type: 'triangle', gain: 0.62, duration: 0.07, attack: 0.012, release: 0.08 }),
                Object.freeze({ frequency: 1046.5, type: 'triangle', gain: 0.62, duration: 0.07, attack: 0.012, release: 0.08 }),
            ]),
            error: Object.freeze([
                Object.freeze({ frequency: 311.13, type: 'triangle', gain: 0.58, duration: 0.1, attack: 0.012, release: 0.08 }),
                Object.freeze({ frequency: 233.08, type: 'triangle', gain: 0.58, duration: 0.1, attack: 0.012, release: 0.08 }),
            ]),
        }),
        previewGap: 55,
    }),
    synthwave: Object.freeze({
        pads: Object.freeze([
            Object.freeze({ frequency: 174.61, type: 'sawtooth', gain: 0.72, duration: 0.12, attack: 0.01, release: 0.06 }),
            Object.freeze({ frequency: 220.0, type: 'sawtooth', gain: 0.72, duration: 0.12, attack: 0.01, release: 0.06 }),
            Object.freeze({ frequency: 261.63, type: 'sawtooth', gain: 0.72, duration: 0.12, attack: 0.01, release: 0.06 }),
            Object.freeze({ frequency: 349.23, type: 'sawtooth', gain: 0.72, duration: 0.12, attack: 0.01, release: 0.06 }),
        ]),
        feedback: Object.freeze({
            start: Object.freeze([
                Object.freeze({ frequency: 174.61, type: 'sawtooth', gain: 0.68, duration: 0.08, attack: 0.01, release: 0.06 }),
                Object.freeze({ frequency: 261.63, type: 'sawtooth', gain: 0.68, duration: 0.08, attack: 0.01, release: 0.06 }),
            ]),
            success: Object.freeze([
                Object.freeze({ frequency: 349.23, type: 'sawtooth', gain: 0.68, duration: 0.08, attack: 0.01, release: 0.06 }),
                Object.freeze({ frequency: 523.25, type: 'sawtooth', gain: 0.68, duration: 0.08, attack: 0.01, release: 0.06 }),
                Object.freeze({ frequency: 698.46, type: 'sawtooth', gain: 0.68, duration: 0.08, attack: 0.01, release: 0.06 }),
            ]),
            error: Object.freeze([
                Object.freeze({ frequency: 130.81, type: 'sawtooth', gain: 0.62, duration: 0.12, attack: 0.01, release: 0.06 }),
                Object.freeze({ frequency: 98.0, type: 'sawtooth', gain: 0.62, duration: 0.12, attack: 0.01, release: 0.06 }),
            ]),
        }),
        previewGap: 50,
    }),
    percussion: Object.freeze({
        pads: Object.freeze([
            Object.freeze({ frequency: 196.0, type: 'square', gain: 0.7, duration: 0.07, attack: 0.002, release: 0.02 }),
            Object.freeze({ frequency: 246.94, type: 'square', gain: 0.7, duration: 0.07, attack: 0.002, release: 0.02 }),
            Object.freeze({ frequency: 293.66, type: 'square', gain: 0.7, duration: 0.07, attack: 0.002, release: 0.02 }),
            Object.freeze({ frequency: 392.0, type: 'square', gain: 0.7, duration: 0.07, attack: 0.002, release: 0.02 }),
        ]),
        feedback: Object.freeze({
            start: Object.freeze([
                Object.freeze({ frequency: 246.94, type: 'square', gain: 0.66, duration: 0.05, attack: 0.002, release: 0.02 }),
                Object.freeze({ frequency: 329.63, type: 'square', gain: 0.66, duration: 0.05, attack: 0.002, release: 0.02 }),
            ]),
            success: Object.freeze([
                Object.freeze({ frequency: 293.66, type: 'square', gain: 0.66, duration: 0.05, attack: 0.002, release: 0.02 }),
                Object.freeze({ frequency: 392.0, type: 'square', gain: 0.66, duration: 0.05, attack: 0.002, release: 0.02 }),
                Object.freeze({ frequency: 523.25, type: 'square', gain: 0.66, duration: 0.05, attack: 0.002, release: 0.02 }),
            ]),
            error: Object.freeze([
                Object.freeze({ frequency: 220.0, type: 'square', gain: 0.62, duration: 0.08, attack: 0.002, release: 0.02 }),
                Object.freeze({ frequency: 174.61, type: 'square', gain: 0.62, duration: 0.08, attack: 0.002, release: 0.02 }),
            ]),
        }),
        previewGap: 30,
    }),
});

export function normalizeSimonSoundPaletteId(rawPaletteId) {
    if (typeof rawPaletteId !== 'string') {
        return SIMON_DEFAULT_SOUND_PALETTE_ID;
    }

    return Object.hasOwn(SIMON_SOUND_PALETTES, rawPaletteId)
        ? rawPaletteId
        : SIMON_DEFAULT_SOUND_PALETTE_ID;
}

export function getSimonSoundPalette(paletteId) {
    return SIMON_SOUND_PALETTES[normalizeSimonSoundPaletteId(paletteId)] ?? SIMON_SOUND_PALETTES[SIMON_DEFAULT_SOUND_PALETTE_ID];
}

export function getSimonSoundPaletteOptions() {
    return SIMON_SOUND_PALETTE_IDS.map(id => ({
        id,
        palette: SIMON_SOUND_PALETTES[id],
    }));
}
