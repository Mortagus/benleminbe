import { describe, expect, test } from 'vitest';
import {
    SIMON_DEFAULT_SOUND_NOTE_SET_ID,
    SIMON_SOUND_NOTE_SET_IDS,
    getSimonSoundNoteSet,
    getSimonSoundNoteSetOptions,
    normalizeSimonSoundNoteSetId,
} from '../../../assets/scripts/games/simon/sound-note-sets.js';

describe('Simon sound note sets', () => {
    test('exposes the expected note set identifiers and options', () => {
        expect(SIMON_DEFAULT_SOUND_NOTE_SET_ID).toBe('major');
        expect(SIMON_SOUND_NOTE_SET_IDS).toEqual([
            'major',
            'minor',
            'pentatonic',
            'dorian',
            'blues',
        ]);

        expect(getSimonSoundNoteSetOptions().map(option => option.id)).toEqual(SIMON_SOUND_NOTE_SET_IDS);
    });

    test('normalizes unknown note set identifiers to the default note set', () => {
        expect(normalizeSimonSoundNoteSetId('unknown')).toBe(SIMON_DEFAULT_SOUND_NOTE_SET_ID);
        expect(normalizeSimonSoundNoteSetId('blues')).toBe('blues');
        expect(getSimonSoundNoteSet('unknown')).toBe(getSimonSoundNoteSet(SIMON_DEFAULT_SOUND_NOTE_SET_ID));
    });
});
