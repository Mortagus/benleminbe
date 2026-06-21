import { describe, expect, test, vi } from 'vitest';
import {
    createSimonDefaultPreferences,
    loadSimonPreferences,
    resetSimonPreferences,
    saveSimonPreferences,
    SIMON_DEFAULT_AUDIO_PREFERENCES,
    SIMON_DEFAULT_KEYBOARD_BINDINGS,
    SIMON_LEGACY_AUDIO_STORAGE_KEY,
    SIMON_LEGACY_KEYBOARD_STORAGE_KEY,
    SIMON_PREFERENCES_STORAGE_KEY,
    updateSimonPreferences,
} from '../../../../assets/scripts/lab/games/simon/preferences.js';

describe('Simon preference storage', () => {
    test('loads the default preferences when nothing is stored', () => {
        expect(loadSimonPreferences(createLocalStorageMock())).toEqual(createSimonDefaultPreferences());
    });

    test('loads a valid consolidated preference structure', () => {
        const storage = createLocalStorageMock();

        storage.setItem(
            SIMON_PREFERENCES_STORAGE_KEY,
            JSON.stringify({
                version: 1,
                keyboard: {
                    bindings: {
                        'top-left': 'M',
                        'top-right': 'P',
                        'bottom-left': 'L',
                        'bottom-right': 'K',
                    },
                },
                audio: {
                    muted: true,
                    volume: 64,
                    palette: 'arcade',
                    noteSet: 'minor',
                    noteDuration: 110,
                    reverb: 24,
                },
            }),
        );

        expect(loadSimonPreferences(storage)).toEqual({
            version: 1,
            keyboard: {
                bindings: {
                    'top-left': 'M',
                    'top-right': 'P',
                    'bottom-left': 'L',
                    'bottom-right': 'K',
                },
            },
            audio: {
                muted: true,
                volume: 64,
                palette: 'arcade',
                noteSet: 'minor',
                noteDuration: 110,
                reverb: 24,
            },
        });
    });

    test('falls back on invalid JSON and clears the broken storage entry', () => {
        const storage = createLocalStorageMock();

        storage.setItem(SIMON_PREFERENCES_STORAGE_KEY, '{"broken"');

        expect(loadSimonPreferences(storage)).toEqual(createSimonDefaultPreferences());
        expect(storage.getItem(SIMON_PREFERENCES_STORAGE_KEY)).toBeNull();
    });

    test('preserves a valid category when the other one is corrupted', () => {
        const storage = createLocalStorageMock();

        storage.setItem(
            SIMON_PREFERENCES_STORAGE_KEY,
            JSON.stringify({
                version: 1,
                keyboard: {
                    bindings: {
                        'top-left': 'M',
                        'top-right': 'P',
                        'bottom-left': 'L',
                        'bottom-right': 'K',
                    },
                },
                audio: {
                    muted: 'nope',
                    volume: 88,
                    palette: 'crystal',
                    noteSet: 'pentatonic',
                    noteDuration: 'bad',
                    reverb: 14,
                },
            }),
        );

        expect(loadSimonPreferences(storage)).toEqual({
            version: 1,
            keyboard: {
                bindings: {
                    'top-left': 'M',
                    'top-right': 'P',
                    'bottom-left': 'L',
                    'bottom-right': 'K',
                },
            },
            audio: {
                muted: false,
                volume: 88,
                palette: 'crystal',
                noteSet: 'pentatonic',
                noteDuration: SIMON_DEFAULT_AUDIO_PREFERENCES.noteDuration,
                reverb: 14,
            },
        });
    });

    test('normalizes a volume outside the accepted range', () => {
        const storage = createLocalStorageMock();

        storage.setItem(
            SIMON_PREFERENCES_STORAGE_KEY,
            JSON.stringify({
                version: 1,
                keyboard: {
                    bindings: SIMON_DEFAULT_KEYBOARD_BINDINGS,
                },
                audio: {
                    muted: false,
                    volume: 130,
                    palette: 'percussion',
                    noteSet: 'dorian',
                    noteDuration: 140,
                    reverb: 28,
                },
            }),
        );

        expect(loadSimonPreferences(storage).audio).toEqual({
            muted: false,
            volume: 100,
            palette: 'percussion',
            noteSet: 'dorian',
            noteDuration: 140,
            reverb: 28,
        });
    });

    test('falls back to the default palette when the stored value is invalid', () => {
        const storage = createLocalStorageMock();

        storage.setItem(
            SIMON_PREFERENCES_STORAGE_KEY,
            JSON.stringify({
                version: 1,
                keyboard: {
                    bindings: SIMON_DEFAULT_KEYBOARD_BINDINGS,
                },
                audio: {
                    muted: false,
                    volume: 82,
                    palette: 'unknown',
                    noteSet: 'unknown',
                    noteDuration: 17,
                    reverb: 200,
                },
            }),
        );

        expect(loadSimonPreferences(storage).audio).toEqual({
            muted: false,
            volume: 82,
            palette: SIMON_DEFAULT_AUDIO_PREFERENCES.palette,
            noteSet: SIMON_DEFAULT_AUDIO_PREFERENCES.noteSet,
            noteDuration: 50,
            reverb: 100,
        });
    });

    test('updates one category without overwriting the other', () => {
        const storage = createLocalStorageMock();

        storage.setItem(
            SIMON_PREFERENCES_STORAGE_KEY,
            JSON.stringify({
                version: 1,
                keyboard: {
                    bindings: {
                        'top-left': 'M',
                        'top-right': 'P',
                        'bottom-left': 'L',
                        'bottom-right': 'K',
                    },
                },
                audio: {
                    muted: false,
                    volume: 70,
                    palette: 'synthwave',
                    noteSet: 'blues',
                    noteDuration: 125,
                    reverb: 42,
                },
            }),
        );

        const result = updateSimonPreferences({
            audio: {
                muted: true,
            },
        }, storage);

        expect(result.saved).toBe(true);
        expect(result.preferences).toEqual({
            version: 1,
            keyboard: {
                bindings: {
                    'top-left': 'M',
                    'top-right': 'P',
                    'bottom-left': 'L',
                    'bottom-right': 'K',
                },
            },
            audio: {
                muted: true,
                volume: 70,
                palette: 'synthwave',
                noteSet: 'blues',
                noteDuration: 125,
                reverb: 42,
            },
        });
    });

    test('resets a single category without touching the other', () => {
        const storage = createLocalStorageMock();

        storage.setItem(
            SIMON_PREFERENCES_STORAGE_KEY,
            JSON.stringify({
                version: 1,
                keyboard: {
                    bindings: {
                        'top-left': 'M',
                        'top-right': 'P',
                        'bottom-left': 'L',
                        'bottom-right': 'K',
                    },
                },
                audio: {
                    muted: true,
                    volume: 40,
                    palette: 'synthwave',
                    noteSet: 'blues',
                    noteDuration: 130,
                    reverb: 36,
                },
            }),
        );

        expect(resetSimonPreferences(storage, 'keyboard')).toEqual({
            preferences: {
                version: 1,
                keyboard: {
                    bindings: SIMON_DEFAULT_KEYBOARD_BINDINGS,
                },
                audio: {
                    muted: true,
                    volume: 40,
                    palette: 'synthwave',
                    noteSet: 'blues',
                    noteDuration: 130,
                    reverb: 36,
                },
            },
            saved: true,
        });
    });

    test('resets preferences without affecting the storage contract', () => {
        const storage = createLocalStorageMock();

        storage.setItem(
            SIMON_PREFERENCES_STORAGE_KEY,
            JSON.stringify({
                version: 1,
                keyboard: {
                    bindings: {
                        'top-left': 'M',
                        'top-right': 'P',
                        'bottom-left': 'L',
                        'bottom-right': 'K',
                    },
                },
                audio: {
                    muted: true,
                    volume: 40,
                    palette: 'arcade',
                    noteSet: 'minor',
                    noteDuration: 100,
                    reverb: 12,
                },
            }),
        );

        expect(resetSimonPreferences(storage)).toEqual({
            preferences: createSimonDefaultPreferences(),
            saved: true,
        });
        expect(JSON.parse(storage.getItem(SIMON_PREFERENCES_STORAGE_KEY))).toEqual(createSimonDefaultPreferences());
    });

    test('migrates legacy keys once and stays idempotent afterwards', () => {
        const storage = createLocalStorageMock();

        storage.setItem(
            SIMON_LEGACY_KEYBOARD_STORAGE_KEY,
            JSON.stringify(['M', 'P', 'L', 'K']),
        );
        storage.setItem(
            SIMON_LEGACY_AUDIO_STORAGE_KEY,
            JSON.stringify({
                enabled: false,
                volume: 80,
            }),
        );

        expect(loadSimonPreferences(storage)).toEqual({
            version: 1,
            keyboard: {
                bindings: {
                    'top-left': 'M',
                    'top-right': 'P',
                    'bottom-left': 'L',
                    'bottom-right': 'K',
                },
            },
                audio: {
                    muted: true,
                    volume: 80,
                    palette: SIMON_DEFAULT_AUDIO_PREFERENCES.palette,
                    noteSet: SIMON_DEFAULT_AUDIO_PREFERENCES.noteSet,
                    noteDuration: SIMON_DEFAULT_AUDIO_PREFERENCES.noteDuration,
                    reverb: SIMON_DEFAULT_AUDIO_PREFERENCES.reverb,
                },
            });
        expect(storage.getItem(SIMON_LEGACY_KEYBOARD_STORAGE_KEY)).toBeNull();
        expect(storage.getItem(SIMON_LEGACY_AUDIO_STORAGE_KEY)).toBeNull();
        expect(storage.getItem(SIMON_PREFERENCES_STORAGE_KEY)).not.toBeNull();

        expect(loadSimonPreferences(storage)).toEqual({
            version: 1,
            keyboard: {
                bindings: {
                    'top-left': 'M',
                    'top-right': 'P',
                    'bottom-left': 'L',
                    'bottom-right': 'K',
                },
            },
            audio: {
                muted: true,
                volume: 80,
                palette: SIMON_DEFAULT_AUDIO_PREFERENCES.palette,
                noteSet: SIMON_DEFAULT_AUDIO_PREFERENCES.noteSet,
                noteDuration: SIMON_DEFAULT_AUDIO_PREFERENCES.noteDuration,
                reverb: SIMON_DEFAULT_AUDIO_PREFERENCES.reverb,
            },
        });
        expect(storage.setItem).toHaveBeenCalledTimes(3);
    });

    test('returns defaults when localStorage throws', () => {
        const storage = createThrowingStorageMock();

        expect(() => loadSimonPreferences(storage)).not.toThrow();
        expect(loadSimonPreferences(storage)).toEqual(createSimonDefaultPreferences());
        expect(saveSimonPreferences(createSimonDefaultPreferences(), storage)).toBe(false);
    });
});

function createLocalStorageMock() {
    const entries = new Map();

    return {
        getItem: vi.fn(key => entries.get(key) ?? null),
        setItem: vi.fn((key, value) => {
            entries.set(key, String(value));
        }),
        removeItem: vi.fn(key => {
            entries.delete(key);
        }),
    };
}

function createThrowingStorageMock() {
    return {
        getItem: vi.fn(() => {
            throw new Error('blocked');
        }),
        setItem: vi.fn(() => {
            throw new Error('blocked');
        }),
        removeItem: vi.fn(() => {
            throw new Error('blocked');
        }),
    };
}
