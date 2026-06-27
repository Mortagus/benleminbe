import { describe, expect, test } from 'vitest';
import {
    describeSimonKeyboardBindings,
    isSimonKeyboardModifierShortcut,
    loadSimonKeyboardBindings,
    normalizeSimonKeyboardKey,
    resetSimonKeyboardBindings,
    saveSimonKeyboardBindings,
    SimonKeyboardSettings,
    SIMON_DEFAULT_KEYBOARD_BINDINGS,
    SIMON_KEYBOARD_STORAGE_KEY,
    validateSimonKeyboardBindings,
} from '../../../assets/scripts/games/simon/keyboard.js';
import {
    SIMON_DEFAULT_AUDIO_PREFERENCES,
    SIMON_LEGACY_KEYBOARD_STORAGE_KEY,
} from '../../../assets/scripts/games/simon/preferences.js';

describe('Simon keyboard bindings', () => {
    test('uses the AZQS mapping by default', () => {
        const settings = new SimonKeyboardSettings({ storage: null });

        expect(settings.getBindings()).toEqual(SIMON_DEFAULT_KEYBOARD_BINDINGS);
        expect(settings.resolvePadIndexForKey('A')).toBe(0);
        expect(settings.resolvePadIndexForKey('Z')).toBe(1);
        expect(settings.resolvePadIndexForKey('Q')).toBe(2);
        expect(settings.resolvePadIndexForKey('S')).toBe(3);
        expect(settings.resolvePadIndexForKey('1')).toBeNull();
        expect(describeSimonKeyboardBindings(settings.getBindings())).toBe('A / Z / Q / S');
    });

    test('normalizes usable keys and rejects duplicates or disallowed keys', () => {
        expect(normalizeSimonKeyboardKey('a')).toBe('A');
        expect(normalizeSimonKeyboardKey(' ')).toBeNull();
        expect(normalizeSimonKeyboardKey('Enter')).toBeNull();
        expect(normalizeSimonKeyboardKey('Tab')).toBeNull();
        expect(normalizeSimonKeyboardKey('Escape')).toBeNull();
        expect(validateSimonKeyboardBindings({
            'top-left': 'A',
            'top-right': 'A',
            'bottom-left': 'Q',
            'bottom-right': 'S',
        })).toBeNull();
        expect(validateSimonKeyboardBindings({
            'top-left': 'A',
            'top-right': 'Enter',
            'bottom-left': 'Q',
            'bottom-right': 'S',
        })).toBeNull();
        expect(isSimonKeyboardModifierShortcut({ altKey: true, ctrlKey: false, metaKey: false })).toBe(true);
        expect(isSimonKeyboardModifierShortcut({ altKey: false, ctrlKey: true, metaKey: false })).toBe(true);
        expect(isSimonKeyboardModifierShortcut({ altKey: false, ctrlKey: false, metaKey: true })).toBe(true);
        expect(isSimonKeyboardModifierShortcut({ altKey: false, ctrlKey: false, metaKey: false })).toBe(false);
    });

    test('saves and reloads a valid custom mapping', () => {
        const storage = createLocalStorageMock();
        const settings = new SimonKeyboardSettings({ storage });

        settings.startCapture('top-left');
        expect(settings.applyCapturedKey('M')).toMatchObject({
            status: 'applied',
            zoneId: 'top-left',
            key: 'M',
        });
        expect(storage.getItem(SIMON_KEYBOARD_STORAGE_KEY)).not.toBeNull();

        const stored = JSON.parse(storage.getItem(SIMON_KEYBOARD_STORAGE_KEY));
        expect(stored).toEqual({
            version: 1,
            keyboard: {
                bindings: {
                    'top-left': 'M',
                    'top-right': 'Z',
                    'bottom-left': 'Q',
                    'bottom-right': 'S',
                },
            },
            audio: SIMON_DEFAULT_AUDIO_PREFERENCES,
        });

        expect(loadSimonKeyboardBindings(storage)).toEqual({
            'top-left': 'M',
            'top-right': 'Z',
            'bottom-left': 'Q',
            'bottom-right': 'S',
        });
    });

    test('loads legacy array storage formats', () => {
        const storage = createLocalStorageMock();

        storage.setItem(
            SIMON_LEGACY_KEYBOARD_STORAGE_KEY,
            JSON.stringify(['M', 'P', 'L', 'K']),
        );

        expect(loadSimonKeyboardBindings(storage)).toEqual({
            'top-left': 'M',
            'top-right': 'P',
            'bottom-left': 'L',
            'bottom-right': 'K',
        });
        expect(JSON.parse(storage.getItem(SIMON_KEYBOARD_STORAGE_KEY))).toEqual({
            version: 1,
            keyboard: {
                bindings: {
                    'top-left': 'M',
                    'top-right': 'P',
                    'bottom-left': 'L',
                    'bottom-right': 'K',
                },
            },
            audio: SIMON_DEFAULT_AUDIO_PREFERENCES,
        });
    });

    test('falls back to the default mapping when the stored configuration is invalid', () => {
        const storage = createLocalStorageMock();

        storage.setItem(SIMON_KEYBOARD_STORAGE_KEY, '{"broken"');

        expect(loadSimonKeyboardBindings(storage)).toEqual(SIMON_DEFAULT_KEYBOARD_BINDINGS);
        expect(storage.getItem(SIMON_KEYBOARD_STORAGE_KEY)).toBeNull();

        storage.setItem(
            SIMON_KEYBOARD_STORAGE_KEY,
            JSON.stringify({
                version: 1,
                keyboard: {
                    bindings: {
                        'top-left': 'A',
                        'top-right': 'A',
                        'bottom-left': 'Q',
                        'bottom-right': 'S',
                    },
                },
                audio: SIMON_DEFAULT_AUDIO_PREFERENCES,
            }),
        );

        expect(loadSimonKeyboardBindings(storage)).toEqual(SIMON_DEFAULT_KEYBOARD_BINDINGS);
        expect(storage.getItem(SIMON_KEYBOARD_STORAGE_KEY)).not.toBeNull();
    });

    test('resets to the default mapping', () => {
        const storage = createLocalStorageMock();
        const settings = new SimonKeyboardSettings({ storage });

        settings.startCapture('top-left');
        settings.applyCapturedKey('M');

        expect(settings.resetToDefault()).toMatchObject({
            status: 'reset',
            bindings: SIMON_DEFAULT_KEYBOARD_BINDINGS,
        });
        expect(settings.getBindings()).toEqual(SIMON_DEFAULT_KEYBOARD_BINDINGS);
        expect(settings.getCapturingZone()).toBeNull();
        expect(loadSimonKeyboardBindings(storage)).toEqual(SIMON_DEFAULT_KEYBOARD_BINDINGS);
        expect(resetSimonKeyboardBindings(storage)).toEqual(SIMON_DEFAULT_KEYBOARD_BINDINGS);
    });

    test('persists only valid mappings', () => {
        const storage = createLocalStorageMock();

        expect(saveSimonKeyboardBindings({
            'top-left': 'A',
            'top-right': 'Z',
            'bottom-left': 'Q',
            'bottom-right': 'S',
        }, storage)).toBe(true);
        expect(saveSimonKeyboardBindings({
            'top-left': 'A',
            'top-right': 'A',
            'bottom-left': 'Q',
            'bottom-right': 'S',
        }, storage)).toBe(false);
    });
});

function createLocalStorageMock() {
    const entries = new Map();

    return {
        getItem: key => entries.get(key) ?? null,
        setItem: (key, value) => {
            entries.set(key, String(value));
        },
        removeItem: key => {
            entries.delete(key);
        },
    };
}
