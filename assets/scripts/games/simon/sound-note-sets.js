export const SIMON_DEFAULT_SOUND_NOTE_SET_ID = 'major';

export const SIMON_SOUND_NOTE_SET_IDS = Object.freeze([
    'major',
    'minor',
    'pentatonic',
    'dorian',
    'blues',
]);

export const SIMON_SOUND_NOTE_SETS = Object.freeze({
    major: Object.freeze({
        pads: Object.freeze([261.63, 329.63, 392.0, 523.25]),
        feedback: Object.freeze({
            start: Object.freeze([0, 1]),
            success: Object.freeze([1, 2, 3]),
            error: Object.freeze([3, 2]),
        }),
    }),
    minor: Object.freeze({
        pads: Object.freeze([220.0, 261.63, 311.13, 392.0]),
        feedback: Object.freeze({
            start: Object.freeze([0, 2]),
            success: Object.freeze([1, 2, 3]),
            error: Object.freeze([3, 1]),
        }),
    }),
    pentatonic: Object.freeze({
        pads: Object.freeze([261.63, 293.66, 329.63, 392.0]),
        feedback: Object.freeze({
            start: Object.freeze([0, 1]),
            success: Object.freeze([1, 3, 2]),
            error: Object.freeze([3, 1]),
        }),
    }),
    dorian: Object.freeze({
        pads: Object.freeze([293.66, 349.23, 392.0, 440.0]),
        feedback: Object.freeze({
            start: Object.freeze([0, 1]),
            success: Object.freeze([1, 2, 3]),
            error: Object.freeze([3, 2]),
        }),
    }),
    blues: Object.freeze({
        pads: Object.freeze([261.63, 311.13, 349.23, 415.3]),
        feedback: Object.freeze({
            start: Object.freeze([0, 1]),
            success: Object.freeze([1, 2, 3]),
            error: Object.freeze([3, 1]),
        }),
    }),
});

export function normalizeSimonSoundNoteSetId(rawNoteSetId) {
    if (typeof rawNoteSetId !== 'string') {
        return SIMON_DEFAULT_SOUND_NOTE_SET_ID;
    }

    return Object.hasOwn(SIMON_SOUND_NOTE_SETS, rawNoteSetId)
        ? rawNoteSetId
        : SIMON_DEFAULT_SOUND_NOTE_SET_ID;
}

export function getSimonSoundNoteSet(noteSetId) {
    return SIMON_SOUND_NOTE_SETS[normalizeSimonSoundNoteSetId(noteSetId)] ?? SIMON_SOUND_NOTE_SETS[SIMON_DEFAULT_SOUND_NOTE_SET_ID];
}

export function getSimonSoundNoteSetOptions() {
    return SIMON_SOUND_NOTE_SET_IDS.map(id => ({
        id,
        noteSet: SIMON_SOUND_NOTE_SETS[id],
    }));
}
