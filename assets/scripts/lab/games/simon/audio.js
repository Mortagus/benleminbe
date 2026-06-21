import {
    normalizeSimonAudioVolume,
    SIMON_DEFAULT_AUDIO_PREFERENCES,
} from './audio-preferences.js';

const PAD_FREQUENCIES = [246.94, 329.63, 392.0, 523.25];
const START_PATTERN = [261.63, 329.63];
const SUCCESS_PATTERN = [392.0, 523.25, 659.25];
const ERROR_PATTERN = [220.0, 174.61];
const MAX_TONE_VOLUME = 0.12;

export class SimonAudio {
    constructor({ contextFactory = createAudioContext, volume = SIMON_DEFAULT_AUDIO_PREFERENCES.volume } = {}) {
        this.contextFactory = contextFactory;
        this.volume = normalizeSimonAudioVolume(volume);
        this.enabled = true;
        this.context = null;
        this.unlocked = false;
    }

    isSupported() {
        return Boolean(getAudioContextClass());
    }

    setEnabled(enabled) {
        this.enabled = enabled;

        if (!enabled) {
            this.unlocked = false;
        }
    }

    setVolume(volume) {
        this.volume = normalizeSimonAudioVolume(volume);
        return this.volume;
    }

    getVolume() {
        return this.volume;
    }

    isEnabled() {
        return this.enabled;
    }

    async unlock() {
        if (!this.enabled) {
            return false;
        }

        if (!this.context) {
            this.context = this.contextFactory();
        }

        if (!this.context) {
            return false;
        }

        if (typeof this.context.resume === 'function' && this.context.state === 'suspended') {
            await this.context.resume().catch(() => {});
        }

        this.unlocked = true;

        return true;
    }

    playPad(index) {
        return this.playTone(PAD_FREQUENCIES[index] ?? PAD_FREQUENCIES[0], 0.11, 'sine', 1);
    }

    playStart() {
        return this.playPattern(START_PATTERN, 'triangle', 0.08);
    }

    playSuccess() {
        return this.playPattern(SUCCESS_PATTERN, 'sine', 0.08);
    }

    playError() {
        return this.playPattern(ERROR_PATTERN, 'sawtooth', 0.12);
    }

    async playPattern(frequencies, type, duration) {
        if (!this.canPlay()) {
            return;
        }

        for (const frequency of frequencies) {
            await this.playTone(frequency, duration, type);
            await delay(40);
        }
    }

    playTone(frequency, duration, type = 'sine', volumeMultiplier = 1) {
        if (!this.canPlay()) {
            return Promise.resolve();
        }

        const oscillator = this.context.createOscillator();
        const gain = this.context.createGain();
        const currentTime = this.context.currentTime;
        const attack = 0.008;
        const release = 0.05;
        const toneVolume = (this.volume / 100) * MAX_TONE_VOLUME * volumeMultiplier;

        oscillator.type = type;
        oscillator.frequency.value = frequency;

        gain.gain.setValueAtTime(0.0001, currentTime);
        gain.gain.linearRampToValueAtTime(toneVolume, currentTime + attack);
        gain.gain.linearRampToValueAtTime(0.0001, currentTime + duration);

        oscillator.connect(gain);
        gain.connect(this.context.destination);

        return new Promise(resolve => {
            oscillator.onended = () => {
                oscillator.disconnect();
                gain.disconnect();
                resolve();
            };

            oscillator.start(currentTime);
            oscillator.stop(currentTime + duration + release);
        });
    }

    canPlay() {
        return this.enabled && this.unlocked && this.context && this.volume > 0;
    }
}

function createAudioContext() {
    const AudioContextClass = getAudioContextClass();

    return AudioContextClass ? new AudioContextClass() : null;
}

function getAudioContextClass() {
    return globalThis.AudioContext ?? globalThis.webkitAudioContext ?? null;
}

function delay(duration) {
    return new Promise(resolve => {
        globalThis.setTimeout(resolve, duration);
    });
}
