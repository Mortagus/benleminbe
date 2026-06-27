import { describe, expect, test } from 'vitest';
import {
    SIMON_DEFAULT_SOUND_PALETTE_ID,
    SIMON_SOUND_PALETTE_IDS,
    getSimonSoundPalette,
    getSimonSoundPaletteOptions,
    normalizeSimonSoundPaletteId,
} from '../../../assets/scripts/games/simon/sound-palettes.js';

describe('Simon sound palettes', () => {
    test('exposes the expected palette identifiers and options', () => {
        expect(SIMON_DEFAULT_SOUND_PALETTE_ID).toBe('classic');
        expect(SIMON_SOUND_PALETTE_IDS).toEqual([
            'classic',
            'arcade',
            'crystal',
            'synthwave',
            'percussion',
        ]);

        expect(getSimonSoundPaletteOptions().map(option => option.id)).toEqual(SIMON_SOUND_PALETTE_IDS);
    });

    test('normalizes unknown palette identifiers to the default palette', () => {
        expect(normalizeSimonSoundPaletteId('unknown')).toBe(SIMON_DEFAULT_SOUND_PALETTE_ID);
        expect(normalizeSimonSoundPaletteId('arcade')).toBe('arcade');
        expect(getSimonSoundPalette('unknown')).toBe(getSimonSoundPalette(SIMON_DEFAULT_SOUND_PALETTE_ID));
    });
});
