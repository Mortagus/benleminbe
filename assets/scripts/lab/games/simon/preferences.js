import {
    SIMON_DEFAULT_SOUND_PALETTE_ID,
    normalizeSimonSoundPaletteId,
} from './sound-palettes.js';
import {
    SIMON_DEFAULT_SOUND_NOTE_SET_ID,
    normalizeSimonSoundNoteSetId,
} from './sound-note-sets.js';

export const SIMON_PREFERENCES_STORAGE_KEY = 'benleminbe-lab-simon-preferences';
export const SIMON_LEGACY_KEYBOARD_STORAGE_KEY = 'benleminbe-lab-simon-keyboard-bindings';
export const SIMON_LEGACY_AUDIO_STORAGE_KEY = 'benleminbe-lab-simon-audio-preferences';

export const SIMON_PREFERENCES_VERSION = 1;

export const SIMON_DEFAULT_KEYBOARD_BINDINGS = Object.freeze({
    'top-left': 'A',
    'top-right': 'Z',
    'bottom-left': 'Q',
    'bottom-right': 'S',
});

export const SIMON_DEFAULT_AUDIO_PREFERENCES = Object.freeze({
    muted: false,
    volume: 75,
    palette: SIMON_DEFAULT_SOUND_PALETTE_ID,
    noteSet: SIMON_DEFAULT_SOUND_NOTE_SET_ID,
});

export const SIMON_DEFAULT_PREFERENCES = Object.freeze({
    version: SIMON_PREFERENCES_VERSION,
    keyboard: Object.freeze({
        bindings: SIMON_DEFAULT_KEYBOARD_BINDINGS,
    }),
    audio: SIMON_DEFAULT_AUDIO_PREFERENCES,
});

const SIMON_KEYBOARD_ZONES = Object.freeze([
    'top-left',
    'top-right',
    'bottom-left',
    'bottom-right',
]);

const SIMON_KEYBOARD_ZONE_ALIASES = Object.freeze({
    'top-left': ['top-left', 'topLeft', 'top_left'],
    'top-right': ['top-right', 'topRight', 'top_right'],
    'bottom-left': ['bottom-left', 'bottomLeft', 'bottom_left'],
    'bottom-right': ['bottom-right', 'bottomRight', 'bottom_right'],
});

export function createSimonDefaultPreferences() {
    return {
        version: SIMON_PREFERENCES_VERSION,
        keyboard: {
            bindings: { ...SIMON_DEFAULT_KEYBOARD_BINDINGS },
        },
        audio: { ...SIMON_DEFAULT_AUDIO_PREFERENCES },
    };
}

export function loadSimonPreferences(storage = globalThis.localStorage ?? null) {
    if (!storage) {
        return createSimonDefaultPreferences();
    }

    const currentPreferences = readSimonStoredPreferences(storage, SIMON_PREFERENCES_STORAGE_KEY);

    if (currentPreferences) {
        return normalizeSimonPreferences(currentPreferences);
    }

    const legacyPreferences = loadSimonLegacyPreferences(storage);

    if (legacyPreferences) {
        if (saveSimonPreferences(legacyPreferences, storage)) {
            removeSimonLegacyPreferenceKeys(storage);
        }

        return legacyPreferences;
    }

    return createSimonDefaultPreferences();
}

export function saveSimonPreferences(preferences, storage = globalThis.localStorage ?? null) {
    if (!storage) {
        return false;
    }

    const normalizedPreferences = validateSimonPreferences(preferences);

    if (!normalizedPreferences) {
        return false;
    }

    try {
        storage.setItem(
            SIMON_PREFERENCES_STORAGE_KEY,
            JSON.stringify(normalizedPreferences),
        );

        return true;
    } catch {
        return false;
    }
}

export function updateSimonPreferences(patch, storage = globalThis.localStorage ?? null) {
    const currentPreferences = loadSimonPreferences(storage);
    const nextPreferences = mergeSimonPreferences(currentPreferences, patch);
    const saved = saveSimonPreferences(nextPreferences, storage);

    return {
        preferences: saved ? nextPreferences : currentPreferences,
        saved,
    };
}

export function resetSimonPreferences(storage = globalThis.localStorage ?? null, scope = 'all') {
    const currentPreferences = loadSimonPreferences(storage);
    const nextPreferences = mergeSimonPreferences(currentPreferences, buildResetPatch(scope));
    const saved = saveSimonPreferences(nextPreferences, storage);

    return {
        preferences: saved ? nextPreferences : currentPreferences,
        saved,
    };
}

export function validateSimonPreferences(preferences) {
    if (!preferences || typeof preferences !== 'object' || Array.isArray(preferences)) {
        return null;
    }

    return normalizeSimonPreferences(preferences);
}

export function normalizeSimonPreferences(preferences) {
    if (!preferences || typeof preferences !== 'object' || Array.isArray(preferences)) {
        return createSimonDefaultPreferences();
    }

    const keyboardSource = readSimonKeyboardSource(preferences);
    const audioSource = readSimonAudioSource(preferences);

    return {
        version: SIMON_PREFERENCES_VERSION,
        keyboard: {
            bindings: normalizeSimonKeyboardBindings(keyboardSource),
        },
        audio: normalizeSimonAudioPreferences(audioSource),
    };
}

export function normalizeSimonKeyboardBindings(bindings) {
    if (Array.isArray(bindings)) {
        return coerceSimonKeyboardArrayBindings(bindings) ?? { ...SIMON_DEFAULT_KEYBOARD_BINDINGS };
    }

    if (!bindings || typeof bindings !== 'object') {
        return { ...SIMON_DEFAULT_KEYBOARD_BINDINGS };
    }

    const usedKeys = new Set();
    const normalizedBindings = {};

    for (const zone of SIMON_KEYBOARD_ZONES) {
        const rawKey = readBindingForZone(bindings, zone);
        const key = normalizeSimonKeyboardKey(rawKey);

        if (key && !usedKeys.has(key)) {
            normalizedBindings[zone] = key;
            usedKeys.add(key);
            continue;
        }

        const fallbackKey = findAvailableDefaultKeyboardKey(usedKeys);
        normalizedBindings[zone] = fallbackKey;
        usedKeys.add(fallbackKey);
    }

    return normalizedBindings;
}

export function validateSimonKeyboardBindings(bindings) {
    const normalizedBindings = coerceSimonKeyboardBindings(bindings);

    return normalizedBindings ? { ...normalizedBindings } : null;
}

export function normalizeSimonKeyboardKey(rawKey) {
    if (typeof rawKey !== 'string' || rawKey.length === 0) {
        return null;
    }

    if (rawKey.length !== 1) {
        return null;
    }

    if (rawKey === ' ' || rawKey === '\t' || rawKey === '\n' || rawKey === '\r' || rawKey === '\u00A0') {
        return null;
    }

    const normalizedKey = rawKey.toUpperCase();

    return normalizedKey.trim() === '' ? null : normalizedKey;
}

export function normalizeSimonAudioVolume(rawVolume) {
    if (rawVolume === null || rawVolume === undefined) {
        return SIMON_DEFAULT_AUDIO_PREFERENCES.volume;
    }

    if (typeof rawVolume === 'string' && rawVolume.trim() === '') {
        return SIMON_DEFAULT_AUDIO_PREFERENCES.volume;
    }

    const numericVolume = Number(rawVolume);

    if (!Number.isFinite(numericVolume)) {
        return SIMON_DEFAULT_AUDIO_PREFERENCES.volume;
    }

    return Math.min(100, Math.max(0, Math.round(numericVolume)));
}

export function normalizeSimonAudioPreferences(preferences) {
    if (Array.isArray(preferences)) {
        return coerceSimonAudioArrayPreferences(preferences) ?? { ...SIMON_DEFAULT_AUDIO_PREFERENCES };
    }

    if (!preferences || typeof preferences !== 'object' || Array.isArray(preferences)) {
        return { ...SIMON_DEFAULT_AUDIO_PREFERENCES };
    }

    return {
        muted: normalizeSimonAudioMuted(readMutedPreference(preferences)),
        volume: normalizeSimonAudioVolume(readVolumePreference(preferences)),
        palette: normalizeSimonSoundPaletteId(readPalettePreference(preferences)),
        noteSet: normalizeSimonSoundNoteSetId(readNoteSetPreference(preferences)),
    };
}

export function validateSimonAudioPreferences(preferences) {
    const normalizedPreferences = coerceSimonAudioPreferences(preferences);

    return normalizedPreferences ? { ...normalizedPreferences } : null;
}

function mergeSimonPreferences(currentPreferences, patch) {
    if (!patch || typeof patch !== 'object' || Array.isArray(patch)) {
        return currentPreferences;
    }

    const mergedKeyboardBindings = mergeSimonKeyboardBindings(currentPreferences.keyboard.bindings, patch.keyboard ?? null);
    const mergedAudioPreferences = mergeSimonAudioPreferencePatch(currentPreferences.audio, patch.audio ?? null);

    return {
        version: SIMON_PREFERENCES_VERSION,
        keyboard: {
            bindings: mergedKeyboardBindings,
        },
        audio: mergedAudioPreferences,
    };
}

function buildResetPatch(scope) {
    if (scope === 'keyboard') {
        return {
            keyboard: {
                bindings: { ...SIMON_DEFAULT_KEYBOARD_BINDINGS },
            },
        };
    }

    if (scope === 'audio') {
        return {
            audio: { ...SIMON_DEFAULT_AUDIO_PREFERENCES },
        };
    }

    return createSimonDefaultPreferences();
}

function mergeSimonKeyboardBindings(currentBindings, patch) {
    if (!patch || typeof patch !== 'object' || Array.isArray(patch)) {
        return { ...currentBindings };
    }

    const candidate = Object.hasOwn(patch, 'bindings')
        ? patch.bindings
        : patch;
    const normalizedBindings = validateSimonKeyboardBindings({
        ...currentBindings,
        ...candidate,
    });

    return normalizedBindings ?? { ...currentBindings };
}

function mergeSimonAudioPreferencePatch(currentAudioPreferences, patch) {
    if (!patch || typeof patch !== 'object' || Array.isArray(patch)) {
        return { ...currentAudioPreferences };
    }

    const candidate = Object.hasOwn(patch, 'preferences')
        ? patch.preferences
        : patch;
    const normalizedPreferences = normalizeSimonAudioPreferences({
        ...currentAudioPreferences,
        ...candidate,
    });

    return normalizedPreferences ?? { ...currentAudioPreferences };
}

function readSimonStoredPreferences(storage, key) {
    try {
        const rawValue = storage.getItem(key);

        if (rawValue === null) {
            return null;
        }

        return JSON.parse(rawValue);
    } catch {
        try {
            storage.removeItem?.(key);
        } catch {
            return null;
        }

        return null;
    }
}

function loadSimonLegacyPreferences(storage) {
    const legacyKeyboard = readSimonStoredPreferences(storage, SIMON_LEGACY_KEYBOARD_STORAGE_KEY);
    const legacyAudio = readSimonStoredPreferences(storage, SIMON_LEGACY_AUDIO_STORAGE_KEY);

    if (!legacyKeyboard && !legacyAudio) {
        return null;
    }

    return {
        version: SIMON_PREFERENCES_VERSION,
        keyboard: {
            bindings: normalizeSimonKeyboardBindings(readSimonKeyboardSource(legacyKeyboard ?? null)),
        },
        audio: normalizeSimonAudioPreferences(readSimonAudioSource(legacyAudio ?? null)),
    };
}

function removeSimonLegacyPreferenceKeys(storage) {
    safeRemoveStoredPreference(storage, SIMON_LEGACY_KEYBOARD_STORAGE_KEY);
    safeRemoveStoredPreference(storage, SIMON_LEGACY_AUDIO_STORAGE_KEY);
}

function safeRemoveStoredPreference(storage, key) {
    try {
        storage.removeItem?.(key);
    } catch {
        // Ignore removal failures and keep the migrated data.
    }
}

function readSimonKeyboardSource(preferences) {
    if (!preferences || typeof preferences !== 'object' || Array.isArray(preferences)) {
        return preferences;
    }

    if (Object.hasOwn(preferences, 'keyboard')) {
        return readSimonKeyboardSource(preferences.keyboard);
    }

    if (Object.hasOwn(preferences, 'bindings')) {
        return preferences.bindings;
    }

    return preferences;
}

function readSimonAudioSource(preferences) {
    if (!preferences || typeof preferences !== 'object' || Array.isArray(preferences)) {
        return preferences;
    }

    if (Object.hasOwn(preferences, 'audio')) {
        return readSimonAudioSource(preferences.audio);
    }

    if (Object.hasOwn(preferences, 'preferences')) {
        return readSimonAudioSource(preferences.preferences);
    }

    return preferences;
}

function coerceSimonKeyboardBindings(bindings) {
    if (Array.isArray(bindings)) {
        return coerceSimonKeyboardArrayBindings(bindings);
    }

    if (!bindings || typeof bindings !== 'object') {
        return null;
    }

    if (bindings.version === SIMON_PREFERENCES_VERSION && bindings.bindings) {
        return validateSimonKeyboardBindingsFromObject(bindings.bindings);
    }

    if (bindings.bindings && typeof bindings.bindings === 'object') {
        return validateSimonKeyboardBindingsFromObject(bindings.bindings);
    }

    return validateSimonKeyboardBindingsFromObject(bindings);
}

function coerceSimonKeyboardArrayBindings(values) {
    if (values.length < SIMON_KEYBOARD_ZONES.length) {
        return null;
    }

    const bindings = {};

    SIMON_KEYBOARD_ZONES.forEach((zone, index) => {
        bindings[zone] = values[index];
    });

    return validateSimonKeyboardBindings(bindings);
}

function validateSimonKeyboardBindingsFromObject(bindings) {
    const normalizedBindings = {};

    for (const zone of SIMON_KEYBOARD_ZONES) {
        const rawKey = readBindingForZone(bindings, zone);
        const key = normalizeSimonKeyboardKey(rawKey);

        if (!key || Object.values(normalizedBindings).includes(key)) {
            return null;
        }

        normalizedBindings[zone] = key;
    }

    return normalizedBindings;
}

function readBindingForZone(bindings, zoneId) {
    const aliases = SIMON_KEYBOARD_ZONE_ALIASES[zoneId] ?? [zoneId];

    for (const alias of aliases) {
        if (Object.hasOwn(bindings, alias)) {
            return bindings[alias];
        }
    }

    return null;
}

function coerceSimonAudioPreferences(preferences) {
    if (Array.isArray(preferences)) {
        return coerceSimonAudioArrayPreferences(preferences);
    }

    if (!preferences || typeof preferences !== 'object') {
        return null;
    }

    if (preferences.version === SIMON_PREFERENCES_VERSION && preferences.preferences) {
        return validateSimonAudioPreferencesFromObject(preferences.preferences);
    }

    if (preferences.version === SIMON_PREFERENCES_VERSION) {
        return validateSimonAudioPreferencesFromObject(preferences);
    }

    if (Object.hasOwn(preferences, 'preferences')) {
        return validateSimonAudioPreferencesFromObject(preferences.preferences);
    }

    return validateSimonAudioPreferencesFromObject(preferences);
}

function coerceSimonAudioArrayPreferences(values) {
    if (values.length < 2) {
        return null;
    }

    return validateSimonAudioPreferences({
        muted: values[0],
        volume: values[1],
    });
}

function validateSimonAudioPreferencesFromObject(preferences) {
    const muted = readMutedPreference(preferences);
    const volume = readVolumePreference(preferences);

    if (muted === null || volume === null) {
        return null;
    }

    return {
        muted,
        volume,
        palette: normalizeSimonSoundPaletteId(readPalettePreference(preferences)),
        noteSet: normalizeSimonSoundNoteSetId(readNoteSetPreference(preferences)),
    };
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
        return coerceSimonAudioVolume(preferences.volume);
    }

    return SIMON_DEFAULT_AUDIO_PREFERENCES.volume;
}

function readPalettePreference(preferences) {
    if (Object.hasOwn(preferences, 'palette')) {
        return preferences.palette;
    }

    return SIMON_DEFAULT_AUDIO_PREFERENCES.palette;
}

function readNoteSetPreference(preferences) {
    if (Object.hasOwn(preferences, 'noteSet')) {
        return preferences.noteSet;
    }

    return SIMON_DEFAULT_AUDIO_PREFERENCES.noteSet;
}

function coerceSimonAudioVolume(rawVolume) {
    if (typeof rawVolume === 'string' && rawVolume.trim() === '') {
        return null;
    }

    const numericVolume = Number(rawVolume);

    if (!Number.isFinite(numericVolume)) {
        return null;
    }

    return Math.min(100, Math.max(0, Math.round(numericVolume)));
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

function findAvailableDefaultKeyboardKey(usedKeys) {
    for (const key of Object.values(SIMON_DEFAULT_KEYBOARD_BINDINGS)) {
        if (!usedKeys.has(key)) {
            return key;
        }
    }

    return SIMON_DEFAULT_KEYBOARD_BINDINGS['top-left'];
}

function normalizeSimonAudioMuted(rawMuted) {
    const muted = normalizeSimonAudioBoolean(rawMuted);

    return muted ?? SIMON_DEFAULT_AUDIO_PREFERENCES.muted;
}

export {
    normalizeSimonSoundPaletteId,
    normalizeSimonSoundNoteSetId,
};
