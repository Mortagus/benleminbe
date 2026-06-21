import { afterEach, describe, expect, test, vi } from 'vitest';
import { SimonGameController } from '../../../../assets/scripts/lab/games/simon/controller.js';
import { SimonGame, SIMON_PHASE } from '../../../../assets/scripts/lab/games/simon/game.js';
import { TestElement } from '../dnd/dom-test-helpers.js';

describe('Simon game controller keyboard handling', () => {
    afterEach(() => {
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

    test('cancels pending round-complete updates after a restart', async () => {
        vi.useFakeTimers();
        globalThis.document = createDocumentDouble();
        globalThis.matchMedia = () => ({ matches: false });

        try {
            const controller = new SimonGameController(createRoot());
            controller.game = new SimonGame({ random: () => 0.1 });
            controller.game.sequence = [0];
            controller.game.level = 1;
            controller.game.phase = SIMON_PHASE.PLAYER;
            controller.renderScores = vi.fn();
            controller.renderStatus = vi.fn();
            controller.setActivePad = vi.fn();
            controller.setPadsDisabled = vi.fn();
            controller.persistBestScore = vi.fn();
            controller.setStartButtonLabel = vi.fn();
            controller.playCurrentRound = vi.fn();
            controller.audio = {
                playPad: vi.fn(),
                playSuccess: vi.fn(),
                playError: vi.fn(),
                setEnabled: vi.fn(),
                unlock: vi.fn(),
            };

            const pendingRound = controller.handlePlayerInput(0);

            controller.cancelSession();

            await vi.advanceTimersByTimeAsync(controller.timing.activePadDuration + 1);
            await pendingRound;

            expect(controller.setActivePad).toHaveBeenCalledWith(0, true);
            expect(controller.setActivePad).not.toHaveBeenCalledWith(0, false);
            expect(controller.renderStatus).not.toHaveBeenCalledWith(controller.config.labels.success);
            expect(controller.persistBestScore).not.toHaveBeenCalled();
            expect(controller.setPadsDisabled).not.toHaveBeenCalledWith(true);
            expect(controller.playCurrentRound).not.toHaveBeenCalled();
        } finally {
            vi.useRealTimers();
        }
    });
});

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
                        demo: 'Demo',
                        player: 'Player turn',
                        success: 'Success',
                        failure: 'Failure',
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
