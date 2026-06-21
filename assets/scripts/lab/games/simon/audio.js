const PAD_FREQUENCIES = [246.94, 329.63, 392.0, 523.25];
const START_PATTERN = [261.63, 329.63];
const SUCCESS_PATTERN = [392.0, 523.25, 659.25];
const ERROR_PATTERN = [220.0, 174.61];

export class SimonAudio {
    constructor({ contextFactory = createAudioContext, volume = 0.08 } = {}) {
        this.contextFactory = contextFactory;
        this.volume = volume;
        this.enabled = true;
        this.context = null;
        this.unlocked = false;
    }

    setEnabled(enabled) {
        this.enabled = enabled;

        if (!enabled) {
            this.unlocked = false;
        }
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
        const toneVolume = this.volume * volumeMultiplier;

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
        return this.enabled && this.unlocked && this.context;
    }
}

function createAudioContext() {
    const AudioContextClass = globalThis.AudioContext ?? globalThis.webkitAudioContext ?? null;

    return AudioContextClass ? new AudioContextClass() : null;
}

function delay(duration) {
    return new Promise(resolve => {
        globalThis.setTimeout(resolve, duration);
    });
}
