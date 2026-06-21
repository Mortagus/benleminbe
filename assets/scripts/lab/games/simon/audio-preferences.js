export const SIMON_AUDIO_STORAGE_KEY = 'benleminbe-lab-simon-audio-preferences';

export const SIMON_DEFAULT_AUDIO_PREFERENCES = Object.freeze({
    muted: false,
    volume: 75,
});

const SIMON_AUDIO_PREFERENCES_VERSION = 1;

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
            const normalizedPreferences = validateSimonAudioPreferences(preferences);

            if (normalizedPreferences) {
                return normalizedPreferences;
            }
        }

        return loadSimonAudioPreferences(this.storage);
    }

    persist() {
        saveSimonAudioPreferences(this.preferences, this.storage);
    }
}

export function loadSimonAudioPreferences(storage = globalThis.localStorage ?? null) {
    if (!storage) {
        return { ...SIMON_DEFAULT_AUDIO_PREFERENCES };
    }

    try {
        const rawValue = storage.getItem(SIMON_AUDIO_STORAGE_KEY);

        if (rawValue === null) {
            return { ...SIMON_DEFAULT_AUDIO_PREFERENCES };
        }

        const parsedValue = JSON.parse(rawValue);
        const preferences = coerceSimonAudioPreferences(parsedValue);

        if (preferences) {
            return preferences;
        }

        storage.removeItem?.(SIMON_AUDIO_STORAGE_KEY);

        return { ...SIMON_DEFAULT_AUDIO_PREFERENCES };
    } catch {
        storage.removeItem?.(SIMON_AUDIO_STORAGE_KEY);

        return { ...SIMON_DEFAULT_AUDIO_PREFERENCES };
    }
}

export function saveSimonAudioPreferences(preferences, storage = globalThis.localStorage ?? null) {
    if (!storage) {
        return false;
    }

    const normalizedPreferences = validateSimonAudioPreferences(preferences);

    if (!normalizedPreferences) {
        return false;
    }

    try {
        storage.setItem(
            SIMON_AUDIO_STORAGE_KEY,
            JSON.stringify({
                version: SIMON_AUDIO_PREFERENCES_VERSION,
                muted: normalizedPreferences.muted,
                volume: normalizedPreferences.volume,
            }),
        );

        return true;
    } catch {
        return false;
    }
}

export function resetSimonAudioPreferences(storage = globalThis.localStorage ?? null) {
    const preferences = { ...SIMON_DEFAULT_AUDIO_PREFERENCES };

    saveSimonAudioPreferences(preferences, storage);

    return preferences;
}

export function validateSimonAudioPreferences(preferences) {
    if (!preferences || typeof preferences !== 'object' || Array.isArray(preferences)) {
        return null;
    }

    const muted = readMutedPreference(preferences);
    const volume = readVolumePreference(preferences);

    if (muted === null || volume === null) {
        return null;
    }

    return {
        muted,
        volume,
    };
}

export function normalizeSimonAudioVolume(rawVolume) {
    const numericVolume = Number(rawVolume);

    if (!Number.isFinite(numericVolume)) {
        return SIMON_DEFAULT_AUDIO_PREFERENCES.volume;
    }

    return Math.min(100, Math.max(0, Math.round(numericVolume)));
}

function coerceSimonAudioPreferences(value) {
    if (!value || typeof value !== 'object') {
        return null;
    }

    if (Array.isArray(value)) {
        return coerceSimonAudioArrayPreferences(value);
    }

    if (value.version === SIMON_AUDIO_PREFERENCES_VERSION && value.preferences) {
        return validateSimonAudioPreferences(value.preferences);
    }

    if (value.version === SIMON_AUDIO_PREFERENCES_VERSION) {
        return validateSimonAudioPreferences(value);
    }

    if (value.preferences && typeof value.preferences === 'object') {
        return validateSimonAudioPreferences(value.preferences);
    }

    return validateSimonAudioPreferences(value);
}

function coerceSimonAudioArrayPreferences(values) {
    if (values.length < 2) {
        return null;
    }

    const preferences = {
        muted: values[0],
        volume: values[1],
    };

    return validateSimonAudioPreferences(preferences);
}

function readMutedPreference(preferences) {
    if (Object.hasOwn(preferences, 'muted')) {
        const muted = normalizeSimonAudioBoolean(preferences.muted);

        return muted;
    }

    if (Object.hasOwn(preferences, 'soundEnabled')) {
        const soundEnabled = normalizeSimonAudioBoolean(preferences.soundEnabled);

        return soundEnabled === null ? null : !soundEnabled;
    }

    if (Object.hasOwn(preferences, 'enabled')) {
        const enabled = normalizeSimonAudioBoolean(preferences.enabled);

        return enabled === null ? null : !enabled;
    }

    return SIMON_DEFAULT_AUDIO_PREFERENCES.muted;
}

function readVolumePreference(preferences) {
    if (Object.hasOwn(preferences, 'volume')) {
        const volume = coerceSimonAudioVolume(preferences.volume);

        return volume;
    }

    return SIMON_DEFAULT_AUDIO_PREFERENCES.volume;
}

function coerceSimonAudioVolume(rawVolume) {
    if (typeof rawVolume === 'string' && rawVolume.trim() === '') {
        return null;
    }

    const numericVolume = Number(rawVolume);

    if (!Number.isInteger(numericVolume) || numericVolume < 0 || numericVolume > 100) {
        return null;
    }

    return numericVolume;
}

function normalizeSimonAudioBoolean(rawValue) {
    if (typeof rawValue === 'boolean') {
        return rawValue;
    }

    if (typeof rawValue === 'number') {
        if (rawValue === 1) {
            return true;
        }

        if (rawValue === 0) {
            return false;
        }
    }

    if (typeof rawValue === 'string') {
        const normalizedValue = rawValue.trim().toLowerCase();

        if (normalizedValue === 'true' || normalizedValue === '1' || normalizedValue === 'yes' || normalizedValue === 'on') {
            return true;
        }

        if (normalizedValue === 'false' || normalizedValue === '0' || normalizedValue === 'no' || normalizedValue === 'off') {
            return false;
        }
    }

    return null;
}
