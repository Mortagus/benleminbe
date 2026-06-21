import { describe, expect, test, vi } from 'vitest';
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

describe('Simon audio preferences', () => {
    test('uses the default muted false and 75% volume state', () => {
        const preferences = new SimonAudioPreferences({ storage: null });

        expect(preferences.getPreferences()).toEqual(SIMON_DEFAULT_AUDIO_PREFERENCES);
        expect(preferences.isMuted()).toBe(false);
        expect(preferences.getVolume()).toBe(75);
        expect(preferences.getEffectiveVolume()).toBe(75);
    });

    test('persists mute and volume independently and restores the last chosen volume', () => {
        const storage = createLocalStorageMock();
        const preferences = new SimonAudioPreferences({ storage });

        preferences.toggleMuted();
        preferences.setVolume(55);
        preferences.toggleMuted();

        expect(preferences.getPreferences()).toEqual({
            muted: false,
            volume: 55,
        });
        expect(JSON.parse(storage.getItem(SIMON_AUDIO_STORAGE_KEY))).toEqual({
            version: 1,
            muted: false,
            volume: 55,
        });
    });

    test('loads legacy boolean enablement data and validates stored values', () => {
        const storage = createLocalStorageMock();
        storage.setItem(
            SIMON_AUDIO_STORAGE_KEY,
            JSON.stringify({
                enabled: false,
                volume: 80,
            }),
        );

        expect(loadSimonAudioPreferences(storage)).toEqual({
            muted: true,
            volume: 80,
        });
        expect(validateSimonAudioPreferences({
            soundEnabled: true,
            volume: 120,
        })).toBeNull();
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
        }, null)).toBe(false);
    });
});

describe('Simon audio engine', () => {
    test('uses the configured volume and stops playing while muted', async () => {
        const context = createAudioContextDouble();
        const audio = new SimonAudio({
            contextFactory: () => context,
            volume: 55,
        });

        expect(audio.getVolume()).toBe(55);

        await audio.unlock();
        await audio.playTone(440, 0.1);

        expect(context.createOscillator).toHaveBeenCalledOnce();
        expect(context.createGain).toHaveBeenCalledOnce();
        expect(context.gain.gain.setValueAtTime).toHaveBeenCalledWith(0.0001, 10);
        expect(context.gain.gain.linearRampToValueAtTime.mock.calls[0][0]).toBeCloseTo(0.066, 3);

        audio.setEnabled(false);
        await expect(audio.playPad(0)).resolves.toBeUndefined();
        expect(context.createOscillator).toHaveBeenCalledOnce();
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
    const gain = {
        gain: {
            setValueAtTime: vi.fn(),
            linearRampToValueAtTime: vi.fn(),
        },
        connect: vi.fn(),
        disconnect: vi.fn(),
    };

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

    return {
        state: 'running',
        currentTime: 10,
        destination: {},
        gain,
        oscillator,
        createGain: vi.fn(() => gain),
        createOscillator: vi.fn(() => oscillator),
        resume: vi.fn(() => Promise.resolve()),
    };
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
