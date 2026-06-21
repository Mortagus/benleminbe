import {
    normalizeSimonAudioVolume,
    SIMON_DEFAULT_AUDIO_PREFERENCES,
} from './audio-preferences.js';
import {
    getSimonSoundPalette,
    normalizeSimonSoundPaletteId,
} from './sound-palettes.js';
import {
    getSimonSoundNoteSet,
    normalizeSimonSoundNoteSetId,
} from './sound-note-sets.js';

const MAX_TONE_VOLUME = 0.12;
const PATTERN_GAP = 40;

export class SimonAudio {
    constructor({
        contextFactory = createAudioContext,
        volume = SIMON_DEFAULT_AUDIO_PREFERENCES.volume,
        palette = SIMON_DEFAULT_AUDIO_PREFERENCES.palette,
        noteSet = SIMON_DEFAULT_AUDIO_PREFERENCES.noteSet,
    } = {}) {
        this.contextFactory = contextFactory;
        this.volume = normalizeSimonAudioVolume(volume);
        this.paletteId = normalizeSimonSoundPaletteId(palette);
        this.noteSetId = normalizeSimonSoundNoteSetId(noteSet);
        this.enabled = true;
        this.context = null;
        this.unlocked = false;
        this.previewToken = 0;
        this.previewing = false;
        this.previewNodes = new Set();
    }

    isSupported() {
        return Boolean(getAudioContextClass());
    }

    setEnabled(enabled) {
        this.enabled = enabled;

        if (!enabled) {
            this.unlocked = false;
            this.cancelPreview();
        }
    }

    setVolume(volume) {
        this.volume = normalizeSimonAudioVolume(volume);
        return this.volume;
    }

    getVolume() {
        return this.volume;
    }

    setPalette(palette) {
        const nextPaletteId = normalizeSimonSoundPaletteId(palette);

        if (nextPaletteId === this.paletteId) {
            return this.paletteId;
        }

        this.paletteId = nextPaletteId;
        this.cancelPreview();

        return this.paletteId;
    }

    setNoteSet(noteSet) {
        const nextNoteSetId = normalizeSimonSoundNoteSetId(noteSet);

        if (nextNoteSetId === this.noteSetId) {
            return this.noteSetId;
        }

        this.noteSetId = nextNoteSetId;
        this.cancelPreview();

        return this.noteSetId;
    }

    getPalette() {
        return this.paletteId;
    }

    getPaletteConfig() {
        return getSimonSoundPalette(this.paletteId);
    }

    getNoteSet() {
        return this.noteSetId;
    }

    getNoteSetConfig() {
        return getSimonSoundNoteSet(this.noteSetId);
    }

    isEnabled() {
        return this.enabled;
    }

    isPreviewing() {
        return this.previewing;
    }

    cancelPreview() {
        this.previewToken += 1;
        this.previewing = false;
        this.stopPreviewNodes();
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
        const palette = this.getPaletteConfig();
        const noteSet = this.getNoteSetConfig();
        const padStep = palette.pads[index] ?? palette.pads[0] ?? null;
        const frequency = noteSet.pads[index] ?? noteSet.pads[0] ?? padStep?.frequency ?? null;

        return this.playSoundStep(padStep, frequency);
    }

    playStart() {
        return this.playFeedbackSequence('start');
    }

    playSuccess() {
        return this.playFeedbackSequence('success');
    }

    playError() {
        return this.playFeedbackSequence('error');
    }

    async playPalettePreview() {
        if (!this.canPlay()) {
            return;
        }

        const palette = this.getPaletteConfig();
        const noteSet = this.getNoteSetConfig();
        const previewToken = this.beginPreview();

        try {
            for (let index = 0; index < palette.pads.length; index += 1) {
                if (this.isPreviewStale(previewToken)) {
                    return;
                }

                await this.playPreviewSoundStep(
                    palette.pads[index],
                    noteSet.pads[index] ?? noteSet.pads[0] ?? palette.pads[index]?.frequency ?? null,
                );

                if (this.isPreviewStale(previewToken) || index === palette.pads.length - 1) {
                    continue;
                }

                await delay(palette.previewGap ?? PATTERN_GAP);
            }
        } finally {
            if (!this.isPreviewStale(previewToken)) {
                this.previewing = false;
            }
        }
    }

    async playSequence(steps, frequencies = []) {
        if (!this.canPlay()) {
            return;
        }

        for (let index = 0; index < steps.length; index += 1) {
            await this.playSoundStep(steps[index], frequencies[index] ?? null);
            await delay(PATTERN_GAP);
        }
    }

    playFeedbackSequence(sequenceId) {
        const palette = this.getPaletteConfig();
        const noteSet = this.getNoteSetConfig();
        const feedbackTemplate = palette.feedback[sequenceId] ?? [];
        const noteIndexes = noteSet.feedback[sequenceId] ?? [];
        const frequencies = noteIndexes.map(noteIndex => noteSet.pads[noteIndex] ?? null);

        return this.playSequence(feedbackTemplate, frequencies);
    }

    playSoundStep(step, frequency = null) {
        if (!this.canPlay() || !step) {
            return Promise.resolve();
        }

        return this.playTone(
            frequency ?? step.frequency,
            step.duration,
            step.type ?? 'sine',
            step.gain ?? 1,
            step.attack ?? 0.008,
            step.release ?? 0.05,
        );
    }

    playPreviewSoundStep(step, frequency = null) {
        if (!this.canPlay() || !step) {
            return Promise.resolve();
        }

        return this.playTone(
            frequency ?? step.frequency,
            step.duration,
            step.type ?? 'sine',
            step.gain ?? 1,
            step.attack ?? 0.008,
            step.release ?? 0.05,
            true,
        );
    }

    playTone(frequency, duration, type = 'sine', volumeMultiplier = 1, attack = 0.008, release = 0.05, preview = false) {
        if (!this.canPlay() || frequency === null || frequency === undefined) {
            return Promise.resolve();
        }

        const oscillator = this.context.createOscillator();
        const gain = this.context.createGain();
        const currentTime = this.context.currentTime;
        const toneVolume = (this.volume / 100) * MAX_TONE_VOLUME * volumeMultiplier;

        oscillator.type = type;
        oscillator.frequency.value = frequency;

        gain.gain.setValueAtTime(0.0001, currentTime);
        gain.gain.linearRampToValueAtTime(toneVolume, currentTime + attack);
        gain.gain.linearRampToValueAtTime(0.0001, currentTime + duration);

        oscillator.connect(gain);
        gain.connect(this.context.destination);

        return new Promise(resolve => {
            let settled = false;
            const entry = preview
                ? {
                    oscillator,
                    gain,
                    settle: null,
                }
                : null;

            const cleanup = () => {
                oscillator.disconnect();
                gain.disconnect();
                if (preview && entry) {
                    this.previewNodes.delete(entry);
                }
            };

            const settle = () => {
                if (settled) {
                    return;
                }

                settled = true;
                cleanup();
                resolve();
            };

            if (entry) {
                entry.settle = settle;
            }

            if (entry) {
                this.previewNodes.add(entry);
            }

            oscillator.onended = settle;

            oscillator.start(currentTime);
            oscillator.stop(currentTime + duration + release);
        });
    }

    canPlay() {
        return this.enabled && this.unlocked && this.context && this.volume > 0;
    }

    beginPreview() {
        this.previewToken += 1;
        this.previewing = true;
        this.stopPreviewNodes();

        return this.previewToken;
    }

    isPreviewStale(previewToken) {
        return previewToken !== this.previewToken;
    }

    stopPreviewNodes() {
        for (const entry of this.previewNodes) {
            try {
                entry.oscillator.onended = null;
                entry.oscillator.stop();
            } catch {
                // Ignore stop failures when the node already completed.
            }

            try {
                entry.settle();
            } catch {
                // Ignore cleanup failures to keep the audio engine resilient.
            }
        }

        this.previewNodes.clear();
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
