import { afterEach, describe, expect, test, vi } from 'vitest';
import { SimonAudio } from '../../../../assets/scripts/lab/games/simon/audio.js';
import {
    loadSimonAudioPreferences,
    normalizeSimonAudioVolume,
    resetSimonAudioPreferences,
    saveSimonAudioPreferences,
    SimonAudioPreferences,
    SIMON_AUDIO_STORAGE_KEY,
    SIMON_DEFAULT_AUDIO_PREFERENCES,
    validateSimonAudioPreferences,
} from '../../../../assets/scripts/lab/games/simon/audio-preferences.js';
import {
    SIMON_DEFAULT_KEYBOARD_BINDINGS,
    SIMON_LEGACY_AUDIO_STORAGE_KEY,
} from '../../../../assets/scripts/lab/games/simon/preferences.js';
import {
    SIMON_DEFAULT_SOUND_PALETTE_ID,
    SIMON_SOUND_PALETTE_IDS,
    getSimonSoundPalette,
} from '../../../../assets/scripts/lab/games/simon/sound-palettes.js';
import {
    SIMON_DEFAULT_SOUND_NOTE_SET_ID,
    SIMON_SOUND_NOTE_SET_IDS,
    getSimonSoundNoteSet,
} from '../../../../assets/scripts/lab/games/simon/sound-note-sets.js';

describe('Simon audio preferences', () => {
    afterEach(() => {
        vi.useRealTimers();
    });

    test('uses the default muted false, 75% volume, default palette and default note set state', () => {
        const preferences = new SimonAudioPreferences({ storage: null });

        expect(preferences.getPreferences()).toEqual(SIMON_DEFAULT_AUDIO_PREFERENCES);
        expect(preferences.isMuted()).toBe(false);
        expect(preferences.getVolume()).toBe(75);
        expect(preferences.getPalette()).toBe(SIMON_DEFAULT_SOUND_PALETTE_ID);
        expect(preferences.getNoteSet()).toBe(SIMON_DEFAULT_SOUND_NOTE_SET_ID);
        expect(preferences.getEffectiveVolume()).toBe(75);
    });

    test('persists mute, volume, palette and note set independently and restores the last chosen volume', () => {
        const storage = createLocalStorageMock();
        const preferences = new SimonAudioPreferences({ storage });

        preferences.setPalette('arcade');
        preferences.setNoteSet('blues');
        preferences.toggleMuted();
        preferences.setVolume(55);
        preferences.toggleMuted();

        expect(preferences.getPreferences()).toEqual({
            muted: false,
            volume: 55,
            palette: 'arcade',
            noteSet: 'blues',
        });
        expect(JSON.parse(storage.getItem(SIMON_AUDIO_STORAGE_KEY))).toEqual({
            version: 1,
            keyboard: {
                bindings: SIMON_DEFAULT_KEYBOARD_BINDINGS,
            },
            audio: {
                muted: false,
                volume: 55,
                palette: 'arcade',
                noteSet: 'blues',
            },
        });
    });

    test('loads legacy boolean enablement data and validates stored values', () => {
        const storage = createLocalStorageMock();
        storage.setItem(
            SIMON_LEGACY_AUDIO_STORAGE_KEY,
            JSON.stringify({
                enabled: false,
                volume: 80,
            }),
        );

        expect(loadSimonAudioPreferences(storage)).toEqual({
            muted: true,
            volume: 80,
            palette: SIMON_DEFAULT_SOUND_PALETTE_ID,
            noteSet: SIMON_DEFAULT_SOUND_NOTE_SET_ID,
        });
        expect(JSON.parse(storage.getItem(SIMON_AUDIO_STORAGE_KEY))).toEqual({
            version: 1,
            keyboard: {
                bindings: SIMON_DEFAULT_KEYBOARD_BINDINGS,
            },
            audio: {
                muted: true,
                volume: 80,
                palette: SIMON_DEFAULT_SOUND_PALETTE_ID,
                noteSet: SIMON_DEFAULT_SOUND_NOTE_SET_ID,
            },
        });
        expect(storage.getItem(SIMON_LEGACY_AUDIO_STORAGE_KEY)).toBeNull();
        expect(validateSimonAudioPreferences({
            soundEnabled: true,
            volume: 120,
            palette: 'unknown',
            noteSet: 'unknown',
        })).toEqual({
            muted: false,
            volume: 100,
            palette: SIMON_DEFAULT_SOUND_PALETTE_ID,
            noteSet: SIMON_DEFAULT_SOUND_NOTE_SET_ID,
        });
        expect(normalizeSimonAudioVolume(124)).toBe(100);
        expect(normalizeSimonAudioVolume(-10)).toBe(0);
    });

    test('falls back to the default preferences when the stored data is invalid', () => {
        const storage = createLocalStorageMock();
        storage.setItem(SIMON_AUDIO_STORAGE_KEY, '{"broken"');

        expect(loadSimonAudioPreferences(storage)).toEqual(SIMON_DEFAULT_AUDIO_PREFERENCES);
        expect(storage.getItem(SIMON_AUDIO_STORAGE_KEY)).toBeNull();
        expect(resetSimonAudioPreferences(storage)).toEqual(SIMON_DEFAULT_AUDIO_PREFERENCES);
        expect(saveSimonAudioPreferences({
            muted: false,
            volume: 75,
            palette: SIMON_DEFAULT_SOUND_PALETTE_ID,
            noteSet: SIMON_DEFAULT_SOUND_NOTE_SET_ID,
        }, null)).toBe(false);
    });
});

describe('Simon sound palettes', () => {
    test('exposes the expected palette identifiers', () => {
        expect(SIMON_SOUND_PALETTE_IDS).toEqual([
            'classic',
            'arcade',
            'crystal',
            'synthwave',
            'percussion',
        ]);

        for (const paletteId of SIMON_SOUND_PALETTE_IDS) {
            expect(getSimonSoundPalette(paletteId)).toBeDefined();
        }
    });

    test('exposes the expected note set identifiers', () => {
        expect(SIMON_SOUND_NOTE_SET_IDS).toEqual([
            'major',
            'minor',
            'pentatonic',
            'dorian',
            'blues',
        ]);

        for (const noteSetId of SIMON_SOUND_NOTE_SET_IDS) {
            expect(getSimonSoundNoteSet(noteSetId)).toBeDefined();
        }
    });
});

describe('Simon audio engine', () => {
    test('uses the configured palette and note set for pad and feedback tones', async () => {
        const context = createAudioContextDouble();
        const audio = new SimonAudio({
            contextFactory: () => context,
            volume: 55,
            palette: 'arcade',
            noteSet: 'blues',
        });

        expect(audio.getPalette()).toBe('arcade');
        expect(audio.getPaletteConfig()).toBe(getSimonSoundPalette('arcade'));
        expect(audio.getNoteSet()).toBe('blues');
        expect(audio.getNoteSetConfig()).toBe(getSimonSoundNoteSet('blues'));

        await audio.unlock();
        await audio.playPad(0);
        await audio.playStart();

        expect(context.createOscillator).toHaveBeenCalledTimes(3);
        expect(context.oscillators[0].type).toBe('square');
        expect(context.oscillators[0].frequency.value).toBeCloseTo(261.63, 2);
        expect(context.oscillators[1].type).toBe('square');
        expect(context.oscillators[1].frequency.value).toBeCloseTo(261.63, 2);
        expect(context.oscillators[2].type).toBe('square');
        expect(context.oscillators[2].frequency.value).toBeCloseTo(311.13, 2);
        expect(context.gains[0].gain.linearRampToValueAtTime.mock.calls[0][0]).toBeCloseTo(0.05412, 5);

        audio.setEnabled(false);
        await expect(audio.playPad(0)).resolves.toBeUndefined();
        expect(context.createOscillator).toHaveBeenCalledTimes(3);
    });

    test('previews the selected palette in order and cancels stale previews', async () => {
        vi.useFakeTimers();

        const audio = new SimonAudio({
            contextFactory: () => createAudioContextDouble(),
            volume: 75,
            palette: 'synthwave',
            noteSet: 'pentatonic',
        });

        await audio.unlock();

        const previewSpy = vi.spyOn(audio, 'playPreviewSoundStep')
            .mockResolvedValueOnce(undefined)
            .mockResolvedValueOnce(undefined)
            .mockResolvedValueOnce(undefined)
            .mockResolvedValueOnce(undefined);

        const previewPromise = audio.playPalettePreview();

        expect(audio.isPreviewing()).toBe(true);
        expect(previewSpy).toHaveBeenCalledTimes(1);
        expect(previewSpy.mock.calls[0][0]).toBe(getSimonSoundPalette('synthwave').pads[0]);

        audio.cancelPreview();

        await vi.runAllTimersAsync();
        await previewPromise;

        expect(audio.isPreviewing()).toBe(false);
        expect(previewSpy).toHaveBeenCalledTimes(1);
    });

    test('returns safely when Web Audio is unavailable', async () => {
        const audio = new SimonAudio({
            contextFactory: () => null,
            volume: 75,
        });

        await expect(audio.unlock()).resolves.toBe(false);
        await expect(audio.playSuccess()).resolves.toBeUndefined();
    });
});

function createAudioContextDouble() {
    const context = {
        state: 'running',
        currentTime: 10,
        destination: {},
        oscillators: [],
        gains: [],
        createGain: vi.fn(() => {
            const gain = {
                gain: {
                    setValueAtTime: vi.fn(),
                    linearRampToValueAtTime: vi.fn(),
                },
                connect: vi.fn(),
                disconnect: vi.fn(),
            };

            context.gains.push(gain);

            return gain;
        }),
        createOscillator: vi.fn(() => {
            const oscillator = {
                type: null,
                frequency: { value: 0 },
                connect: vi.fn(),
                disconnect: vi.fn(),
                start: vi.fn(),
                stop: vi.fn(function stop() {
                    this.onended?.();
                }),
                onended: null,
            };

            context.oscillators.push(oscillator);

            return oscillator;
        }),
        resume: vi.fn(() => Promise.resolve()),
    };

    return context;
}

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
