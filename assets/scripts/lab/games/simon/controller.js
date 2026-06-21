import { SimonAudio } from './audio.js';
import {
    SimonAudioPreferences,
} from './audio-preferences.js';
import { SimonGame, SIMON_PHASE } from './game.js';
import {
    describeSimonKeyboardBindings,
    SIMON_KEYBOARD_ZONES,
    SimonKeyboardSettings,
    isSimonKeyboardModifierShortcut,
} from './keyboard.js';
import {
    loadSimonBestScore,
    saveSimonBestScore,
} from './storage.js';

export class SimonGameController {
    constructor(root, options = {}) {
        this.root = root;
        this.random = options.random ?? Math.random;
        this.storage = options.storage ?? globalThis.localStorage ?? null;
        this.motionReduced = globalThis.matchMedia?.('(prefers-reduced-motion: reduce)')?.matches ?? false;
        this.timing = this.createTiming();

        this.config = this.readConfig();
        this.game = new SimonGame({
            random: this.random,
            bestScore: loadSimonBestScore(this.storage),
        });
        this.audio = options.audio ?? new SimonAudio();
        this.audioPreferences = options.audioPreferences ?? new SimonAudioPreferences({
            storage: this.storage,
        });
        this.keyboard = options.keyboard ?? new SimonKeyboardSettings({
            storage: this.storage,
        });
        this.audioAvailable = typeof this.audio.isSupported === 'function'
            ? this.audio.isSupported()
            : true;
        this.soundEnabled = !this.audioPreferences.isMuted();
        this.audioVolume = this.audioPreferences.getVolume();
        this.sessionId = 0;
        this.activePadIndex = null;
        this.elements = this.findElements();
        this.boundHandleKeydown = event => this.handleKeydown(event);
    }

    start() {
        if (!this.elements) {
            return;
        }

        this.bindEvents();
        this.applyAudioPreferences();
        this.renderIdleState();
    }

    bindEvents() {
        this.elements.startButton?.addEventListener('click', () => {
            void this.startNewGame();
        });

        this.elements.soundButton?.addEventListener('click', () => {
            void this.toggleSound();
        });

        this.elements.audioVolumeInput?.addEventListener('input', () => {
            this.handleAudioVolumeInput();
        });

        this.elements.keyboardResetButton?.addEventListener('click', () => {
            this.resetKeyboardBindings();
        });

        this.elements.keyboardEditButtons.forEach(button => {
            button.addEventListener('click', () => {
                const zoneId = button.dataset.simonKeyboardEdit;
                if (!zoneId) {
                    return;
                }

                this.beginKeyboardCapture(zoneId);
            });
        });

        this.elements.padButtons.forEach(button => {
            button.addEventListener('click', () => {
                const index = Number.parseInt(button.dataset.simonPad ?? '', 10);
                if (Number.isNaN(index)) {
                    return;
                }

                this.handlePlayerInput(index);
            });
        });

        globalThis.document?.addEventListener('keydown', this.boundHandleKeydown);
    }

    findElements() {
        const startButton = this.root.querySelector('[data-simon-start]');
        const soundButton = this.root.querySelector('[data-simon-sound]');
        const statusText = this.root.querySelector('[data-simon-status]');
        const currentLevel = this.root.querySelector('[data-simon-level]');
        const bestScore = this.root.querySelector('[data-simon-best]');
        const board = this.root.querySelector('[data-simon-board]');
        const padButtons = Array.from(this.root.querySelectorAll('[data-simon-pad]'));
        const keyboardFeedback = this.root.querySelector('[data-simon-keyboard-feedback]');
        const keyboardResetButton = this.root.querySelector('[data-simon-keyboard-reset]');
        const keyboardEditButtons = Array.from(this.root.querySelectorAll('[data-simon-keyboard-edit]'));
        const keyboardBindingValues = Array.from(this.root.querySelectorAll('[data-simon-keyboard-key]'));
        const keyboardPadBindings = Array.from(this.root.querySelectorAll('[data-simon-keyboard-zone]'));
        const audioVolumeInput = this.root.querySelector('[data-simon-volume]');
        const audioVolumeValue = this.root.querySelector('[data-simon-volume-value]');
        const audioFeedback = this.root.querySelector('[data-simon-audio-feedback]');

        if (
            !startButton
            || !soundButton
            || !audioVolumeInput
            || !audioVolumeValue
            || !audioFeedback
            || !statusText
            || !currentLevel
            || !bestScore
            || !board
            || padButtons.length !== 4
            || !keyboardFeedback
            || !keyboardResetButton
            || keyboardEditButtons.length !== SIMON_KEYBOARD_ZONES.length
            || keyboardPadBindings.length !== SIMON_KEYBOARD_ZONES.length
        ) {
            return null;
        }

        return {
            startButton,
            soundButton,
            audioVolumeInput,
            audioVolumeValue,
            audioFeedback,
            statusText,
            currentLevel,
            bestScore,
            board,
            padButtons,
            keyboardFeedback,
            keyboardResetButton,
            keyboardEditButtons,
            keyboardBindingValues,
            keyboardPadBindings,
        };
    }

    readConfig() {
        try {
            const rawConfig = this.root.dataset.simonConfig ?? '{}';
            const config = JSON.parse(rawConfig);

            return {
                labels: {
                    start: config.labels?.start ?? 'Nouvelle partie',
                    restart: config.labels?.restart ?? 'Recommencer',
                    idle: config.labels?.status?.idle ?? 'En attente',
                    preparation: config.labels?.status?.preparation ?? 'Prépare-toi…',
                    demo: config.labels?.status?.demo ?? 'Démonstration',
                    player: config.labels?.status?.player ?? 'Tour du joueur',
                    success: config.labels?.status?.success ?? 'Manche réussie',
                    failure: config.labels?.status?.failure ?? 'Défaite',
                },
                audio: {
                    eyebrow: config.audio?.eyebrow ?? 'Audio',
                    title: config.audio?.title ?? 'Son et volume',
                    toggleOn: config.audio?.toggleOn ?? 'Couper le son',
                    toggleOff: config.audio?.toggleOff ?? 'Activer le son',
                    volumeLabel: config.audio?.volumeLabel ?? 'Volume',
                    volumeValue: config.audio?.volumeValue ?? '{volume} %',
                    active: config.audio?.active ?? 'Son activé à {volume} %.',
                    muted: config.audio?.muted ?? 'Son coupé. Volume mémorisé : {volume} %.',
                    unavailable: config.audio?.unavailable ?? 'Le son n’est pas disponible dans ce navigateur.',
                },
                keyboard: {
                    title: config.keyboard?.title ?? 'Touches configurables',
                    intro: config.keyboard?.intro ?? 'Choisis les touches du clavier pour chacune des quatre zones.',
                    summary: config.keyboard?.summary ?? 'Configuration active : {bindings}',
                    idle: config.keyboard?.idle ?? 'Clique sur Modifier pour changer une touche.',
                    capture: config.keyboard?.capture ?? 'Appuie sur une touche pour {zone}.',
                    applied: config.keyboard?.applied ?? '{zone} : {key}',
                    duplicate: config.keyboard?.duplicate ?? '{key} est déjà utilisée par {zone}.',
                    invalid: config.keyboard?.invalid ?? 'Cette touche ne peut pas être utilisée.',
                    reset: config.keyboard?.reset ?? 'Touches réinitialisées : A / Z / Q / S',
                    actions: {
                        edit: config.keyboard?.actions?.edit ?? 'Modifier',
                        reset: config.keyboard?.actions?.reset ?? 'Réinitialiser les touches',
                    },
                    zones: {
                        'top-left': config.keyboard?.zones?.['top-left'] ?? 'Haut gauche',
                        'top-right': config.keyboard?.zones?.['top-right'] ?? 'Haut droite',
                        'bottom-left': config.keyboard?.zones?.['bottom-left'] ?? 'Bas gauche',
                        'bottom-right': config.keyboard?.zones?.['bottom-right'] ?? 'Bas droite',
                    },
                    padAria: config.keyboard?.padAria ?? 'Zone {zone}, touche {key}',
                },
            };
        } catch {
            return {
                labels: {
                    start: 'Nouvelle partie',
                    restart: 'Recommencer',
                    idle: 'En attente',
                    preparation: 'Prépare-toi…',
                    demo: 'Démonstration',
                    player: 'Tour du joueur',
                    success: 'Manche réussie',
                    failure: 'Défaite',
                },
                audio: {
                    eyebrow: 'Audio',
                    title: 'Son et volume',
                    toggleOn: 'Couper le son',
                    toggleOff: 'Activer le son',
                    volumeLabel: 'Volume',
                    volumeValue: '{volume} %',
                    active: 'Son activé à {volume} %.',
                    muted: 'Son coupé. Volume mémorisé : {volume} %.',
                    unavailable: 'Le son n’est pas disponible dans ce navigateur.',
                },
                keyboard: {
                    title: 'Touches configurables',
                    intro: 'Choisis les touches du clavier pour chacune des quatre zones.',
                    summary: 'Configuration active : {bindings}',
                    idle: 'Clique sur Modifier pour changer une touche.',
                    capture: 'Appuie sur une touche pour {zone}.',
                    applied: '{zone} : {key}',
                    duplicate: '{key} est déjà utilisée par {zone}.',
                    invalid: 'Cette touche ne peut pas être utilisée.',
                    reset: 'Touches réinitialisées : A / Z / Q / S',
                    actions: {
                        edit: 'Modifier',
                        reset: 'Réinitialiser les touches',
                    },
                    zones: {
                        'top-left': 'Haut gauche',
                        'top-right': 'Haut droite',
                        'bottom-left': 'Bas gauche',
                        'bottom-right': 'Bas droite',
                    },
                    padAria: 'Zone {zone}, touche {key}',
                },
            };
        }
    }

    async startNewGame() {
        this.cancelSession();
        const sessionId = this.sessionId;
        this.keyboard.cancelCapture();
        this.applyAudioPreferences();

        this.game.startNewGame();
        this.clearActivePads();
        this.renderScores();
        this.setStartButtonLabel(this.config.labels.start);
        this.setPreparationState();

        if (this.soundEnabled && this.audioAvailable) {
            await this.audio.unlock();
        }

        void this.audio.playStart();

        await this.playRoundSequence(sessionId, this.timing.initialPreparationDelay);
    }

    async playRoundSequence(sessionId, preparationDelay) {
        await wait(preparationDelay);

        if (this.isSessionStale(sessionId)) {
            return;
        }

        this.setGamePhase(SIMON_PHASE.DEMO);
        this.renderStatus(this.config.labels.demo);
        await wait(this.timing.demoLeadInDelay);

        if (this.isSessionStale(sessionId)) {
            return;
        }

        for (const step of this.game.sequence) {
            if (this.isSessionStale(sessionId)) {
                return;
            }

            this.setActivePad(step, true);
            void this.audio.playPad(step);

            await wait(this.timing.flashDuration);

            if (this.isSessionStale(sessionId)) {
                return;
            }

            this.setActivePad(step, false);

            await wait(this.timing.gapDuration);
        }

        if (this.isSessionStale(sessionId)) {
            return;
        }

        this.game.beginPlayerTurn();
        this.setGamePhase(SIMON_PHASE.PLAYER);
        this.renderStatus(this.config.labels.player);
        this.setPadsDisabled(false);
    }

    async handlePlayerInput(step) {
        if (this.game.phase !== SIMON_PHASE.PLAYER) {
            return;
        }

        const sessionId = this.sessionId;

        this.setActivePad(step, true);

        const result = this.game.submitPlayerStep(step);
        this.renderScores();

        if (result.status === 'correct') {
            void this.audio.playPad(step);
            await wait(this.timing.activePadDuration);

            if (this.isSessionStale(sessionId)) {
                return;
            }

            this.setActivePad(step, false);
            return;
        }

        if (result.status === 'round-complete') {
            void this.audio.playPad(step);
            await wait(this.timing.activePadDuration);

            if (this.isSessionStale(sessionId)) {
                return;
            }

            this.setActivePad(step, false);
            this.setGamePhase(SIMON_PHASE.SUCCESS);
            this.renderStatus(this.config.labels.success);
            this.persistBestScore();
            this.setPadsDisabled(true);
            void this.audio.playSuccess();

            await wait(this.timing.successDelay);

            if (this.isSessionStale(sessionId)) {
                return;
            }

            this.game.prepareNextRound();
            this.renderScores();
            this.setPreparationState();
            await this.playRoundSequence(sessionId, this.timing.nextPreparationDelay);
            return;
        }

        if (result.status === 'failure') {
            this.setActivePad(step, false);
            this.setGamePhase(SIMON_PHASE.FAILURE);
            this.renderStatus(this.config.labels.failure);
            this.setPadsDisabled(true);
            this.setStartButtonLabel(this.config.labels.restart);
            void this.audio.playError();

            await wait(this.timing.failureDelay);

            return;
        }

        this.setActivePad(step, false);
    }

    async toggleSound() {
        if (!this.audioAvailable) {
            this.renderAudioState();
            return;
        }

        this.audioPreferences.toggleMuted();
        this.applyAudioPreferences();

        if (this.soundEnabled) {
            await this.audio.unlock();
        }
    }

    handleAudioVolumeInput() {
        this.audioPreferences.setVolume(this.elements.audioVolumeInput.value);
        this.applyAudioPreferences();
    }

    handleKeydown(event) {
        if (event.defaultPrevented) {
            return;
        }

        if (this.keyboard.isCapturing()) {
            this.handleKeyboardCapture(event);
            return;
        }

        if (event.repeat || this.game.phase !== SIMON_PHASE.PLAYER) {
            return;
        }

        if (this.isInteractiveKeyboardTarget(event.target) || isSimonKeyboardModifierShortcut(event)) {
            return;
        }

        const step = this.keyboard.resolvePadIndexForKey(event.key);

        if (step === null) {
            return;
        }

        event.preventDefault();
        this.handlePlayerInput(step);
    }

    renderIdleState() {
        this.clearActivePads();
        this.renderScores();
        this.setGamePhase(SIMON_PHASE.IDLE);
        this.renderStatus(this.config.labels.idle);
        this.setStartButtonLabel(this.config.labels.start);
        this.setPadsDisabled(true);
        this.renderAudioState();
        this.renderKeyboardState();
    }

    renderScores() {
        this.elements.currentLevel.textContent = String(this.game.level);
        this.elements.bestScore.textContent = String(this.game.bestScore);
    }

    renderStatus(statusLabel) {
        this.elements.statusText.textContent = statusLabel;
    }

    setStartButtonLabel(label) {
        this.elements.startButton.textContent = label;
    }

    setPadsDisabled(disabled) {
        this.elements.padButtons.forEach(button => {
            button.disabled = disabled;
            button.setAttribute('aria-disabled', String(disabled));
        });
    }

    setGamePhase(phase) {
        this.root.dataset.simonPhase = phase;
    }

    setPreparationState() {
        this.clearActivePads();
        this.setGamePhase(SIMON_PHASE.PREPARATION);
        this.renderStatus(this.config.labels.preparation);
        this.setPadsDisabled(true);
        this.renderAudioState();
        this.renderKeyboardState();
    }

    setActivePad(step, active) {
        const button = this.elements.padButtons[step];

        if (!button) {
            return;
        }

        if (active) {
            if (this.activePadIndex !== null && this.activePadIndex !== step) {
                const previouslyActive = this.elements.padButtons[this.activePadIndex];
                previouslyActive?.classList.remove('is-active');
                previouslyActive?.removeAttribute('data-active');
            }

            this.activePadIndex = step;
            button.classList.add('is-active');
            button.setAttribute('data-active', 'true');
            return;
        }

        if (this.activePadIndex === step) {
            this.activePadIndex = null;
        }

        button.classList.remove('is-active');
        button.removeAttribute('data-active');
    }

    beginKeyboardCapture(zoneId) {
        if (!this.keyboard.startCapture(zoneId)) {
            return;
        }

        this.renderKeyboardState();
    }

    handleKeyboardCapture(event) {
        if (event.repeat) {
            event.preventDefault();
            return;
        }

        event.preventDefault();
        event.stopPropagation?.();

        if (isSimonKeyboardModifierShortcut(event)) {
            this.renderKeyboardFeedback(this.config.keyboard.invalid);
            return;
        }

        const result = this.keyboard.applyCapturedKey(event.key);

        if (result.status === 'invalid') {
            this.renderKeyboardFeedback(this.config.keyboard.invalid);
            return;
        }

        if (result.status === 'duplicate') {
            this.renderKeyboardFeedback(
                formatSimonKeyboardTemplate(this.config.keyboard.duplicate, {
                    key: result.key,
                    zone: this.config.keyboard.zones[result.zoneId] ?? result.zoneId,
                }),
            );
            return;
        }

        if (result.status === 'applied') {
            this.renderKeyboardState();
            this.renderKeyboardFeedback(
                formatSimonKeyboardTemplate(this.config.keyboard.applied, {
                    key: result.key,
                    zone: this.config.keyboard.zones[result.zoneId] ?? result.zoneId,
                }),
            );
        }
    }

    resetKeyboardBindings() {
        this.keyboard.resetToDefault();
        this.renderKeyboardState();
        this.renderKeyboardFeedback(this.config.keyboard.reset);
    }

    applyAudioPreferences() {
        this.soundEnabled = !this.audioPreferences.isMuted();
        this.audioVolume = this.audioPreferences.getVolume();

        if (typeof this.audio.setEnabled === 'function') {
            this.audio.setEnabled(this.soundEnabled && this.audioAvailable);
        }

        if (typeof this.audio.setVolume === 'function') {
            this.audio.setVolume(this.audioVolume);
        }

        this.renderAudioState();
    }

    renderAudioState() {
        const muted = !this.soundEnabled;
        const volume = this.audioVolume;

        if (muted) {
            this.root.dataset.simonAudioMuted = 'true';
        } else {
            delete this.root.dataset.simonAudioMuted;
        }

        this.elements.soundButton.disabled = !this.audioAvailable;
        this.elements.soundButton.setAttribute('aria-disabled', String(!this.audioAvailable));
        this.elements.soundButton.setAttribute('aria-pressed', String(!muted));
        this.elements.soundButton.setAttribute(
            'aria-label',
            muted ? this.config.audio.toggleOff : this.config.audio.toggleOn,
        );
        this.elements.soundButton.setAttribute(
            'title',
            muted ? this.config.audio.toggleOff : this.config.audio.toggleOn,
        );

        this.elements.audioVolumeInput.disabled = !this.audioAvailable;
        this.elements.audioVolumeInput.value = String(volume);
        this.elements.audioVolumeInput.setAttribute('aria-valuetext', formatSimonKeyboardTemplate(this.config.audio.volumeValue, {
            volume: String(volume),
        }));
        this.elements.audioVolumeValue.textContent = formatSimonKeyboardTemplate(this.config.audio.volumeValue, {
            volume: String(volume),
        });

        this.elements.audioFeedback.textContent = this.audioAvailable
            ? formatSimonKeyboardTemplate(muted ? this.config.audio.muted : this.config.audio.active, {
                volume: String(volume),
            })
            : this.config.audio.unavailable;
    }

    renderKeyboardState() {
        const bindings = this.keyboard.getBindings();
        const summary = describeSimonKeyboardBindings(bindings);
        const captureZone = this.keyboard.getCapturingZone();

        if (captureZone) {
            this.root.dataset.simonKeyboardCaptureZone = captureZone;
        } else {
            delete this.root.dataset.simonKeyboardCaptureZone;
        }

        this.elements.keyboardEditButtons.forEach(button => {
            const zoneId = button.dataset.simonKeyboardEdit;
            const isCapturingZone = captureZone === zoneId;

            button.setAttribute('aria-pressed', String(isCapturingZone));
            button.textContent = this.config.keyboard.actions.edit;
        });

        this.elements.keyboardBindingValues.forEach(bindingElement => {
            const zoneId = bindingElement.dataset.simonKeyboardKey;

            if (!zoneId) {
                return;
            }

            const key = bindings[zoneId] ?? '';
            bindingElement.textContent = key;
        });

        this.elements.keyboardPadBindings.forEach(button => {
            const zoneId = button.dataset.simonKeyboardZone;

            if (!zoneId) {
                return;
            }

            const key = bindings[zoneId] ?? '';
            const zoneLabel = this.config.keyboard.zones[zoneId] ?? zoneId;
            const label = formatSimonKeyboardTemplate(this.config.keyboard.padAria, {
                zone: zoneLabel,
                key,
            });

            button.setAttribute('aria-label', label);
            button.setAttribute('aria-keyshortcuts', key);
        });

        this.root.querySelectorAll('[data-simon-keyboard-summary]').forEach(summaryElement => {
            summaryElement.textContent = formatSimonKeyboardTemplate(this.config.keyboard.summary, {
                bindings: summary,
            });
        });

        this.elements.keyboardFeedback.textContent = captureZone
            ? formatSimonKeyboardTemplate(this.config.keyboard.capture, {
                zone: this.config.keyboard.zones[captureZone] ?? captureZone,
            })
            : this.config.keyboard.idle;
    }

    renderKeyboardFeedback(message) {
        this.elements.keyboardFeedback.textContent = message;
    }

    isInteractiveKeyboardTarget(target) {
        if (!target) {
            return false;
        }

        if (typeof target.closest === 'function') {
            return Boolean(target.closest('button, input, select, textarea, [contenteditable="true"], [role="button"], [role="textbox"]'));
        }

        const tagName = typeof target.tagName === 'string' ? target.tagName.toLowerCase() : '';

        return ['button', 'input', 'select', 'textarea'].includes(tagName);
    }

    clearActivePads() {
        this.activePadIndex = null;

        this.elements.padButtons.forEach(button => {
            button.classList.remove('is-active');
            button.removeAttribute('data-active');
        });
    }

    persistBestScore() {
        if (this.game.bestScore <= 0) {
            return;
        }

        saveSimonBestScore(this.game.bestScore, this.storage);
    }

    cancelSession() {
        this.sessionId += 1;
    }

    isSessionStale(sessionId) {
        return sessionId !== this.sessionId;
    }

    createTiming() {
        if (this.motionReduced) {
            return {
                initialPreparationDelay: 240,
                nextPreparationDelay: 180,
                demoLeadInDelay: 120,
                flashDuration: 160,
                gapDuration: 60,
                activePadDuration: 120,
                successDelay: 320,
                failureDelay: 380,
            };
        }

        return {
            initialPreparationDelay: 950,
            nextPreparationDelay: 700,
            demoLeadInDelay: 420,
            flashDuration: 280,
            gapDuration: 120,
            activePadDuration: 160,
            successDelay: 620,
            failureDelay: 640,
        };
    }
}

export function startSimonGamePage(root = globalThis.document?.querySelector?.('[data-simon-page]') ?? null) {
    if (!root) {
        return null;
    }

    const controller = new SimonGameController(root);
    controller.start();

    return controller;
}

function wait(duration) {
    return new Promise(resolve => {
        globalThis.setTimeout(resolve, duration);
    });
}

function formatSimonKeyboardTemplate(template, values) {
    return template.replace(/\{([a-zA-Z0-9_-]+)\}/g, (_, token) => String(values[token] ?? ''));
}
