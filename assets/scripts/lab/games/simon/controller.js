import { SimonAudio } from './audio.js';
import { SimonGame, SIMON_PHASE } from './game.js';
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
        this.sessionId = 0;
        this.activePadIndex = null;
        this.soundEnabled = true;
        this.elements = this.findElements();
        this.boundHandleKeydown = event => this.handleKeydown(event);
    }

    start() {
        if (!this.elements) {
            return;
        }

        this.bindEvents();
        this.audio.setEnabled(this.soundEnabled);
        this.renderIdleState();
    }

    bindEvents() {
        this.elements.startButton?.addEventListener('click', () => {
            void this.startNewGame();
        });

        this.elements.soundButton?.addEventListener('click', () => {
            void this.toggleSound();
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

        if (!startButton || !soundButton || !statusText || !currentLevel || !bestScore || !board || padButtons.length !== 4) {
            return null;
        }

        return {
            startButton,
            soundButton,
            statusText,
            currentLevel,
            bestScore,
            board,
            padButtons,
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
                    soundOn: config.labels?.soundOn ?? 'Son activé',
                    soundOff: config.labels?.soundOff ?? 'Son coupé',
                    idle: config.labels?.status?.idle ?? 'En attente',
                    preparation: config.labels?.status?.preparation ?? 'Prépare-toi…',
                    demo: config.labels?.status?.demo ?? 'Démonstration',
                    player: config.labels?.status?.player ?? 'Tour du joueur',
                    success: config.labels?.status?.success ?? 'Manche réussie',
                    failure: config.labels?.status?.failure ?? 'Défaite',
                },
            };
        } catch {
            return {
                labels: {
                    start: 'Nouvelle partie',
                    restart: 'Recommencer',
                    soundOn: 'Son activé',
                    soundOff: 'Son coupé',
                    idle: 'En attente',
                    preparation: 'Prépare-toi…',
                    demo: 'Démonstration',
                    player: 'Tour du joueur',
                    success: 'Manche réussie',
                    failure: 'Défaite',
                },
            };
        }
    }

    async startNewGame() {
        this.cancelSession();
        const sessionId = this.sessionId;
        this.audio.setEnabled(this.soundEnabled);

        this.game.startNewGame();
        this.clearActivePads();
        this.renderScores();
        this.setStartButtonLabel(this.config.labels.start);
        this.setPreparationState();

        if (this.soundEnabled) {
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
        this.soundEnabled = !this.soundEnabled;
        this.audio.setEnabled(this.soundEnabled);
        this.setSoundButtonLabel(this.soundEnabled);

        if (this.soundEnabled) {
            await this.audio.unlock();
        }
    }

    handleKeydown(event) {
        if (event.defaultPrevented || event.repeat || this.game.phase !== SIMON_PHASE.PLAYER) {
            return;
        }

        if (event.altKey || event.ctrlKey || event.metaKey) {
            return;
        }

        const keyToStep = {
            1: 0,
            2: 1,
            3: 2,
            4: 3,
        };

        const step = keyToStep[event.key];

        if (typeof step !== 'number') {
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
        this.setSoundButtonLabel(this.soundEnabled);
        this.setPadsDisabled(true);
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

    setSoundButtonLabel(enabled) {
        this.elements.soundButton.textContent = enabled
            ? this.config.labels.soundOn
            : this.config.labels.soundOff;
        this.elements.soundButton.setAttribute('aria-pressed', String(enabled));
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
