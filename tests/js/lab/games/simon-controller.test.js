import { afterEach, describe, expect, test, vi } from 'vitest';
import { SimonGameController } from '../../../../assets/scripts/lab/games/simon/controller.js';
import { SimonGame, SIMON_PHASE } from '../../../../assets/scripts/lab/games/simon/game.js';
import { TestElement } from '../dnd/dom-test-helpers.js';

describe('Simon game controller keyboard handling', () => {
    afterEach(() => {
        vi.useRealTimers();
        delete globalThis.document;
        delete globalThis.localStorage;
        delete globalThis.matchMedia;
    });

    test('ignores repeated keydown events during the player turn', () => {
        globalThis.document = createDocumentDouble();
        globalThis.matchMedia = () => ({ matches: false });

        const controller = new SimonGameController(createRoot());
        controller.game = new SimonGame();
        controller.game.phase = SIMON_PHASE.PLAYER;
        controller.handlePlayerInput = vi.fn();

        controller.handleKeydown(createKeyboardEvent({ key: '1', repeat: true }));

        expect(controller.handlePlayerInput).not.toHaveBeenCalled();
    });

    test('maps shortcut keys to Simon pads when the game is active', () => {
        globalThis.document = createDocumentDouble();
        globalThis.matchMedia = () => ({ matches: false });

        const controller = new SimonGameController(createRoot());
        controller.game = new SimonGame();
        controller.game.phase = SIMON_PHASE.PLAYER;
        controller.handlePlayerInput = vi.fn();
        const event = createKeyboardEvent({ key: '4' });

        controller.handleKeydown(event);

        expect(event.preventDefault).toHaveBeenCalledOnce();
        expect(controller.handlePlayerInput).toHaveBeenCalledWith(3);
    });

    test('runs a preparation phase before showing the sequence and unlocking the player turn', async () => {
        vi.useFakeTimers();
        globalThis.document = createDocumentDouble();
        globalThis.matchMedia = () => ({ matches: false });

        const controller = createController([0.1]);
        const startPromise = controller.startNewGame();

        expect(controller.root.dataset.simonPhase).toBe(SIMON_PHASE.PREPARATION);
        expect(controller.elements.statusText.textContent).toBe('Prépare-toi…');
        expect(controller.elements.padButtons.every(button => button.disabled)).toBe(true);

        const preparationEvent = createKeyboardEvent({ key: '1' });
        controller.handleKeydown(preparationEvent);

        expect(preparationEvent.preventDefault).not.toHaveBeenCalled();
        expect(controller.game.playerIndex).toBe(0);

        await Promise.resolve();

        expect(controller.audio.playStart).toHaveBeenCalledOnce();

        await vi.advanceTimersByTimeAsync(controller.timing.initialPreparationDelay);

        expect(controller.root.dataset.simonPhase).toBe(SIMON_PHASE.DEMO);
        expect(controller.elements.statusText.textContent).toBe('Observe la séquence');
        expect(controller.elements.padButtons.every(button => button.disabled)).toBe(true);

        const demoEvent = createKeyboardEvent({ key: '1' });
        controller.handleKeydown(demoEvent);

        expect(demoEvent.preventDefault).not.toHaveBeenCalled();
        expect(controller.game.playerIndex).toBe(0);

        await vi.advanceTimersByTimeAsync(
            controller.timing.demoLeadInDelay + controller.timing.flashDuration + controller.timing.gapDuration,
        );

        await startPromise;

        expect(controller.root.dataset.simonPhase).toBe(SIMON_PHASE.PLAYER);
        expect(controller.elements.statusText.textContent).toBe('À toi de jouer');
        expect(controller.elements.padButtons.every(button => button.disabled)).toBe(false);
    });

    test('cancels a pending preparation when a new game restarts', async () => {
        vi.useFakeTimers();
        globalThis.document = createDocumentDouble();
        globalThis.matchMedia = () => ({ matches: false });

        const controller = createController([0.1, 0.9]);
        const activePadSpy = vi.spyOn(controller, 'setActivePad');
        const firstStart = controller.startNewGame();

        await vi.advanceTimersByTimeAsync(controller.timing.initialPreparationDelay / 2);

        const secondStart = controller.startNewGame();

        await vi.advanceTimersByTimeAsync(
            controller.timing.initialPreparationDelay
                + controller.timing.demoLeadInDelay
                + controller.timing.flashDuration
                + controller.timing.gapDuration,
        );

        await Promise.all([firstStart, secondStart]);

        expect(controller.root.dataset.simonPhase).toBe(SIMON_PHASE.PLAYER);
        expect(controller.elements.statusText.textContent).toBe('À toi de jouer');
        expect(activePadSpy).toHaveBeenCalledWith(3, true);
        expect(activePadSpy).not.toHaveBeenCalledWith(0, true);
    });

    test('reuses the same preparation flow for the next round after a success', async () => {
        vi.useFakeTimers();
        globalThis.document = createDocumentDouble();
        globalThis.matchMedia = () => ({ matches: false });

        const controller = createController([0.1, 0.9]);
        const startPromise = controller.startNewGame();

        await vi.advanceTimersByTimeAsync(
            controller.timing.initialPreparationDelay
                + controller.timing.demoLeadInDelay
                + controller.timing.flashDuration
                + controller.timing.gapDuration,
        );

        await startPromise;

        const roundCompletePromise = controller.handlePlayerInput(0);

        expect(controller.game.phase).toBe(SIMON_PHASE.SUCCESS);

        await vi.advanceTimersByTimeAsync(controller.timing.activePadDuration);

        expect(controller.root.dataset.simonPhase).toBe(SIMON_PHASE.SUCCESS);
        expect(controller.elements.statusText.textContent).toBe('Bien joué !');
        expect(controller.elements.padButtons.every(button => button.disabled)).toBe(true);

        await vi.advanceTimersByTimeAsync(controller.timing.successDelay);

        await Promise.resolve();

        expect(controller.root.dataset.simonPhase).toBe(SIMON_PHASE.PREPARATION);
        expect(controller.elements.statusText.textContent).toBe('Prépare-toi…');
        expect(controller.game.level).toBe(2);

        await vi.advanceTimersByTimeAsync(controller.timing.nextPreparationDelay);

        await Promise.resolve();

        expect(controller.root.dataset.simonPhase).toBe(SIMON_PHASE.DEMO);
        expect(controller.elements.statusText.textContent).toBe('Observe la séquence');

        await vi.runAllTimersAsync();

        await roundCompletePromise;

        expect(controller.root.dataset.simonPhase).toBe(SIMON_PHASE.PLAYER);
        expect(controller.elements.statusText.textContent).toBe('À toi de jouer');
        expect(controller.elements.padButtons.every(button => button.disabled)).toBe(false);
    });
});

function createController(randomValues = [0.1]) {
    return new SimonGameController(createRoot(), {
        random: createRandomSequence(randomValues),
        audio: createAudioMock(),
        storage: createLocalStorageMock(),
    });
}

function createRoot() {
    const startButton = new TestElement('button');
    const soundButton = new TestElement('button');
    const status = new TestElement('strong');
    const level = new TestElement('strong');
    const best = new TestElement('strong');
    const board = new TestElement('div');
    const pads = [0, 1, 2, 3].map(index => {
        const pad = new TestElement('button');
        pad.dataset.simonPad = String(index);
        return pad;
    });

    return {
        dataset: {
            simonConfig: JSON.stringify({
                labels: {
                    start: 'Start',
                    restart: 'Restart',
                    soundOn: 'Sound on',
                    soundOff: 'Sound off',
                    status: {
                        idle: 'Idle',
                        preparation: 'Prépare-toi…',
                        demo: 'Observe la séquence',
                        player: 'À toi de jouer',
                        success: 'Bien joué !',
                        failure: 'Partie terminée',
                    },
                },
            }),
        },
        querySelector: selector => {
            switch (selector) {
                case '[data-simon-start]':
                    return startButton;
                case '[data-simon-sound]':
                    return soundButton;
                case '[data-simon-status]':
                    return status;
                case '[data-simon-level]':
                    return level;
                case '[data-simon-best]':
                    return best;
                case '[data-simon-board]':
                    return board;
                default:
                    return null;
            }
        },
        querySelectorAll: selector => selector === '[data-simon-pad]' ? pads : [],
    };
}

function createDocumentDouble() {
    return {
        addEventListener: vi.fn(),
    };
}

function createKeyboardEvent({ key, repeat = false }) {
    return {
        key,
        repeat,
        altKey: false,
        ctrlKey: false,
        metaKey: false,
        defaultPrevented: false,
        preventDefault: vi.fn(),
    };
}

function createAudioMock() {
    return {
        playPad: vi.fn(),
        playStart: vi.fn(),
        playSuccess: vi.fn(),
        playError: vi.fn(),
        setEnabled: vi.fn(),
        unlock: vi.fn(),
    };
}

function createRandomSequence(values) {
    let index = 0;

    return () => values[index++] ?? values[values.length - 1] ?? 0;
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
        clear: () => {
            entries.clear();
        },
    };
}
