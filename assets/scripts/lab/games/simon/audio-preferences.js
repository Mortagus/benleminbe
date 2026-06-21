import {
    loadSimonPreferences,
    normalizeSimonAudioPreferences,
    normalizeSimonAudioVolume,
    normalizeSimonSoundPaletteId,
    normalizeSimonSoundNoteSetId,
    SIMON_DEFAULT_AUDIO_PREFERENCES,
    SIMON_PREFERENCES_STORAGE_KEY,
    validateSimonAudioPreferences,
    updateSimonPreferences,
} from './preferences.js';

export const SIMON_AUDIO_STORAGE_KEY = SIMON_PREFERENCES_STORAGE_KEY;

export class SimonAudioPreferences {
    constructor({ storage = globalThis.localStorage ?? null, preferences = null } = {}) {
        this.storage = storage;
        this.preferences = this.resolveInitialPreferences(preferences);
    }

    isMuted() {
        return this.preferences.muted;
    }

    getVolume() {
        return this.preferences.volume;
    }

    getPalette() {
        return this.preferences.palette;
    }

    getNoteSet() {
        return this.preferences.noteSet;
    }

    getEffectiveVolume() {
        return this.isMuted() ? 0 : this.getVolume();
    }

    setMuted(muted) {
        this.preferences = {
            ...this.preferences,
            muted: Boolean(muted),
        };
        this.persist();

        return this.isMuted();
    }

    setVolume(volume) {
        const normalizedVolume = normalizeSimonAudioVolume(volume);

        this.preferences = {
            ...this.preferences,
            volume: normalizedVolume,
        };
        this.persist();

        return this.getVolume();
    }

    setPalette(palette) {
        this.preferences = {
            ...this.preferences,
            palette: normalizeSimonSoundPaletteId(palette),
        };
        this.persist();

        return this.getPalette();
    }

    setNoteSet(noteSet) {
        this.preferences = {
            ...this.preferences,
            noteSet: normalizeSimonSoundNoteSetId(noteSet),
        };
        this.persist();

        return this.getNoteSet();
    }

    toggleMuted() {
        return this.setMuted(!this.isMuted());
    }

    resetToDefault() {
        this.preferences = { ...SIMON_DEFAULT_AUDIO_PREFERENCES };
        this.persist();

        return this.getPreferences();
    }

    getPreferences() {
        return { ...this.preferences };
    }

    resolveInitialPreferences(preferences) {
        if (preferences) {
            return normalizeSimonAudioPreferences(preferences);
        }

        return loadSimonAudioPreferences(this.storage);
    }

    persist() {
        saveSimonAudioPreferences(this.preferences, this.storage);
    }
}

export function loadSimonAudioPreferences(storage = globalThis.localStorage ?? null) {
    return { ...loadSimonPreferences(storage).audio };
}

export function saveSimonAudioPreferences(preferences, storage = globalThis.localStorage ?? null) {
    const normalizedPreferences = normalizeSimonAudioPreferences(preferences);

    const result = updateSimonPreferences({
        audio: normalizedPreferences,
    }, storage);

    return result.saved;
}

export function resetSimonAudioPreferences(storage = globalThis.localStorage ?? null) {
    const preferences = { ...SIMON_DEFAULT_AUDIO_PREFERENCES };

    updateSimonPreferences({
        audio: preferences,
    }, storage);

    return preferences;
}

export {
    normalizeSimonAudioVolume,
    SIMON_DEFAULT_AUDIO_PREFERENCES,
    validateSimonAudioPreferences,
};
