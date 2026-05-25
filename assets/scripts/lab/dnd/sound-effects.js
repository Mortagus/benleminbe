// Reusable sound effects for the DnD initiative tracker.
// Playback is best-effort: browser audio errors should never block gameplay.
export const SOUND_EFFECTS = {
    monsterInitiativeRoll: {
        sources: [
            '/files/media/sounds/dice_roll.mp3',
            '/files/media/sounds/dice_roll_2.mp3',
        ],
        volume: 0.45,
    },
};

const audioCache = new Map();

export function playSoundEffect(soundId, options = {}) {
    const soundEffect = SOUND_EFFECTS[soundId];

    if (!soundEffect) {
        return Promise.resolve();
    }

    const source = chooseSoundSource(soundEffect.sources, options.random ?? Math.random);
    const audio = getAudio(source, options.audioFactory ?? createAudio);

    audio.volume = soundEffect.volume;
    audio.currentTime = 0;

    return Promise.resolve(audio.play())
        .catch(() => {});
}

export function clearSoundEffectCache() {
    audioCache.clear();
}

function chooseSoundSource(sources, random) {
    const sourceIndex = Math.floor(random() * sources.length);

    return sources[sourceIndex];
}

function getAudio(source, audioFactory) {
    if (!audioCache.has(source)) {
        audioCache.set(source, audioFactory(source));
    }

    return audioCache.get(source);
}

function createAudio(source) {
    return new window.Audio(source);
}
