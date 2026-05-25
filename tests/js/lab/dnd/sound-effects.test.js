import { afterEach, describe, expect, test, vi } from 'vitest';
import {
    clearSoundEffectCache,
    playSoundEffect,
    SOUND_EFFECTS,
} from '../../../../assets/scripts/lab/dnd/sound-effects.js';

describe('DND sound effects', () => {
    afterEach(() => {
        clearSoundEffectCache();
    });

    test('plays a random source for a known sound effect', async () => {
        const play = vi.fn(() => Promise.resolve());
        const audioFactory = vi.fn(source => createAudioDouble(source, play));

        await playSoundEffect('monsterInitiativeRoll', {
            audioFactory,
            random: () => 0.99,
        });

        expect(audioFactory).toHaveBeenCalledWith(SOUND_EFFECTS.monsterInitiativeRoll.sources[1]);
        expect(audioFactory.mock.results[0].value.volume).toBe(0.45);
        expect(audioFactory.mock.results[0].value.currentTime).toBe(0);
        expect(play).toHaveBeenCalledOnce();
    });

    test('caches audio instances by source', async () => {
        const play = vi.fn(() => Promise.resolve());
        const audioFactory = vi.fn(source => createAudioDouble(source, play));

        await playSoundEffect('monsterInitiativeRoll', {
            audioFactory,
            random: () => 0,
        });
        await playSoundEffect('monsterInitiativeRoll', {
            audioFactory,
            random: () => 0,
        });

        expect(audioFactory).toHaveBeenCalledOnce();
        expect(play).toHaveBeenCalledTimes(2);
    });

    test('absorbs browser playback errors', async () => {
        const play = vi.fn(() => Promise.reject(new Error('blocked')));
        const audioFactory = vi.fn(source => createAudioDouble(source, play));

        await expect(playSoundEffect('monsterInitiativeRoll', {
            audioFactory,
            random: () => 0,
        })).resolves.toBeUndefined();
    });

    test('ignores unknown sound effects', async () => {
        await expect(playSoundEffect('unknown')).resolves.toBeUndefined();
    });
});

function createAudioDouble(source, play) {
    return {
        source,
        volume: 1,
        currentTime: 12,
        play,
    };
}
