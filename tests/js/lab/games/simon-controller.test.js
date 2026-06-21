import { afterEach, describe, expect, test, vi } from 'vitest';
import { SimonGameController } from '../../../../assets/scripts/lab/games/simon/controller.js';
import { SimonGame, SIMON_PHASE } from '../../../../assets/scripts/lab/games/simon/game.js';
import { SIMON_DEFAULT_KEYBOARD_BINDINGS, SIMON_KEYBOARD_STORAGE_KEY } from '../../../../assets/scripts/lab/games/simon/keyboard.js';
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

        const controller = createController();
        controller.game = new SimonGame();
        controller.game.phase = SIMON_PHASE.PLAYER;
        controller.handlePlayerInput = vi.fn();

        controller.handleKeydown(createKeyboardEvent({ key: 'S', repeat: true }));

        expect(controller.handlePlayerInput).not.toHaveBeenCalled();
    });

    test('maps the default keyboard shortcuts to Simon pads when the game is active', () => {
        globalThis.document = createDocumentDouble();
        globalThis.matchMedia = () => ({ matches: false });

        const controller = createController();
        controller.game = new SimonGame();
        controller.game.phase = SIMON_PHASE.PLAYER;
        controller.handlePlayerInput = vi.fn();
        const event = createKeyboardEvent({ key: 'S' });

        controller.handleKeydown(event);

        expect(event.preventDefault).toHaveBeenCalledOnce();
        expect(controller.handlePlayerInput).toHaveBeenCalledWith(3);
    });

    test('renders the default audio state and keeps volume changes separate from mute', async () => {
        globalThis.document = createDocumentDouble();
        globalThis.matchMedia = () => ({ matches: false });

        const controller = createController();
        controller.start();

        expect(controller.elements.soundButton.getAttribute('aria-label')).toBe('Couper le son');
        expect(controller.elements.soundButton.getAttribute('aria-pressed')).toBe('true');
        expect(controller.elements.audioVolumeInput.value).toBe('75');
        expect(controller.elements.audioVolumeValue.textContent).toBe('75 %');
        expect(controller.elements.audioFeedback.textContent).toBe('Son activé à 75 %.');
        expect(controller.audio.setEnabled).toHaveBeenCalledWith(true);
        expect(controller.audio.setVolume).toHaveBeenCalledWith(75);

        controller.elements.soundButton.click();
        await Promise.resolve();

        expect(controller.elements.soundButton.getAttribute('aria-label')).toBe('Activer le son');
        expect(controller.elements.soundButton.getAttribute('aria-pressed')).toBe('false');
        expect(controller.root.dataset.simonAudioMuted).toBe('true');
        expect(controller.audio.setEnabled).toHaveBeenLastCalledWith(false);
        expect(controller.audio.setVolume).toHaveBeenLastCalledWith(75);
        expect(controller.elements.audioFeedback.textContent).toBe('Son coupé. Volume mémorisé : 75 %.');

        controller.elements.audioVolumeInput.value = '55';
        controller.elements.audioVolumeInput.dispatchEvent({ type: 'input' });

        expect(controller.elements.audioVolumeValue.textContent).toBe('55 %');
        expect(controller.audio.setVolume).toHaveBeenLastCalledWith(55);
        expect(controller.elements.audioFeedback.textContent).toBe('Son coupé. Volume mémorisé : 55 %.');
        expect(controller.root.dataset.simonAudioMuted).toBe('true');

        controller.elements.soundButton.click();
        await Promise.resolve();

        expect(controller.elements.soundButton.getAttribute('aria-label')).toBe('Couper le son');
        expect(controller.elements.soundButton.getAttribute('aria-pressed')).toBe('true');
        expect(controller.root.dataset.simonAudioMuted).toBeUndefined();
        expect(controller.audio.setEnabled).toHaveBeenLastCalledWith(true);
        expect(controller.audio.setVolume).toHaveBeenLastCalledWith(55);
        expect(controller.elements.audioFeedback.textContent).toBe('Son activé à 55 %.');
        expect(JSON.parse(controller.storage.getItem('benleminbe-lab-simon-audio-preferences'))).toEqual({
            version: 1,
            muted: false,
            volume: 55,
        });
    });

    test('disables the audio controls when Web Audio is unavailable', () => {
        globalThis.document = createDocumentDouble();
        globalThis.matchMedia = () => ({ matches: false });

        const controller = createController([0.1], createLocalStorageMock(), {
            isSupported: () => false,
            unlock: vi.fn(),
        });
        controller.start();

        expect(controller.elements.soundButton.disabled).toBe(true);
        expect(controller.elements.audioVolumeInput.disabled).toBe(true);
        expect(controller.elements.audioFeedback.textContent).toBe('Le son n’est pas disponible dans ce navigateur.');
    });

    test('ignores shortcuts while the keyboard mapping is being edited', () => {
        globalThis.document = createDocumentDouble();
        globalThis.matchMedia = () => ({ matches: false });

        const controller = createController();
        controller.start();
        controller.game = new SimonGame();
        controller.game.phase = SIMON_PHASE.PLAYER;
        controller.handlePlayerInput = vi.fn();

        controller.elements.keyboardEditButtons[0].click();

        const event = createKeyboardEvent({ key: 'M' });
        controller.handleKeydown(event);

        expect(event.preventDefault).toHaveBeenCalledOnce();
        expect(event.stopPropagation).toHaveBeenCalledOnce();
        expect(controller.handlePlayerInput).not.toHaveBeenCalled();
        expect(controller.keyboard.getBinding('top-left')).toBe('M');
        expect(controller.root.dataset.simonKeyboardCaptureZone).toBeUndefined();
        expect(controller.elements.keyboardBindingValues[0].textContent).toBe('M');
        expect(controller.elements.keyboardFeedback.textContent).toBe('Haut gauche : M');
    });

    test('rejects modifier shortcuts while the keyboard mapping is being edited', () => {
        globalThis.document = createDocumentDouble();
        globalThis.matchMedia = () => ({ matches: false });

        const controller = createController();
        controller.start();
        controller.elements.keyboardEditButtons[0].click();

        const event = createKeyboardEvent({ key: 'M', ctrlKey: true });
        controller.handleKeydown(event);

        expect(event.preventDefault).toHaveBeenCalledOnce();
        expect(controller.keyboard.getBinding('top-left')).toBe(SIMON_DEFAULT_KEYBOARD_BINDINGS['top-left']);
        expect(controller.keyboard.isCapturing()).toBe(true);
        expect(controller.elements.keyboardFeedback.textContent).toBe('Cette touche ne peut pas être utilisée.');
    });

    test.each(['button', 'input', 'select', 'textarea'])(
        'ignores shortcuts when the focus is inside an interactive %s',
        (tagName) => {
            globalThis.document = createDocumentDouble();
            globalThis.matchMedia = () => ({ matches: false });

            const controller = createController();
            controller.game = new SimonGame();
            controller.game.phase = SIMON_PHASE.PLAYER;
            controller.handlePlayerInput = vi.fn();

            const event = createKeyboardEvent({
                key: 'S',
                target: new TestElement(tagName),
            });

            controller.handleKeydown(event);

            expect(event.preventDefault).not.toHaveBeenCalled();
            expect(controller.handlePlayerInput).not.toHaveBeenCalled();
        },
    );

    test('runs a preparation phase before showing the sequence and unlocking the player turn', async () => {
        vi.useFakeTimers();
        globalThis.document = createDocumentDouble();
        globalThis.matchMedia = () => ({ matches: false });

        const controller = createController([0.1]);
        const startPromise = controller.startNewGame();

        expect(controller.root.dataset.simonPhase).toBe(SIMON_PHASE.PREPARATION);
        expect(controller.elements.statusText.textContent).toBe('Prépare-toi…');
        expect(controller.elements.padButtons.every(button => button.disabled)).toBe(true);

        const preparationEvent = createKeyboardEvent({ key: 'S' });
        controller.handleKeydown(preparationEvent);

        expect(preparationEvent.preventDefault).not.toHaveBeenCalled();
        expect(controller.game.playerIndex).toBe(0);

        await Promise.resolve();

        expect(controller.audio.playStart).toHaveBeenCalledOnce();

        await vi.advanceTimersByTimeAsync(controller.timing.initialPreparationDelay);

        expect(controller.root.dataset.simonPhase).toBe(SIMON_PHASE.DEMO);
        expect(controller.elements.statusText.textContent).toBe('Observe la séquence');
        expect(controller.elements.padButtons.every(button => button.disabled)).toBe(true);

        const demoEvent = createKeyboardEvent({ key: 'S' });
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

    test('resets the keyboard mapping from the reset button', () => {
        globalThis.document = createDocumentDouble();
        globalThis.matchMedia = () => ({ matches: false });

        const storage = createLocalStorageMock();
        const controller = createController([0.1], storage);
        controller.start();
        controller.elements.keyboardEditButtons[0].click();
        controller.handleKeydown(createKeyboardEvent({ key: 'M' }));

        expect(controller.keyboard.getBinding('top-left')).toBe('M');

        controller.elements.keyboardResetButton.click();

        expect(controller.keyboard.getBindings()).toEqual(SIMON_DEFAULT_KEYBOARD_BINDINGS);
        expect(controller.elements.keyboardFeedback.textContent).toBe('Touches réinitialisées : A / Z / Q / S');
        expect(JSON.parse(storage.getItem(SIMON_KEYBOARD_STORAGE_KEY))).toEqual({
            version: 1,
            bindings: SIMON_DEFAULT_KEYBOARD_BINDINGS,
        });
    });
});

function createController(randomValues = [0.1], storage = createLocalStorageMock(), audioOverrides = {}) {
    return new SimonGameController(createRoot(), {
        random: createRandomSequence(randomValues),
        audio: createAudioMock(audioOverrides),
        storage,
    });
}

function createRoot() {
    const startButton = createElement('button', 'simonStart');
    const soundButton = createElement('button', 'simonSound');
    const statusText = createElement('strong', 'simonStatus');
    const currentLevel = createElement('strong', 'simonLevel');
    const bestScore = createElement('strong', 'simonBest');
    const board = createElement('div', 'simonBoard');
    const audioVolumeInput = createElement('input', 'simonVolume');
    audioVolumeInput.value = '75';
    const audioVolumeValue = createElement('output', 'simonVolumeValue', '75 %');
    const audioFeedback = createElement('p', 'simonAudioFeedback', 'Son activé à 75 %.');
    const keyboardFeedback = createElement('p', 'simonKeyboardFeedback', 'Clique sur Modifier pour changer une touche.');
    const keyboardResetButton = createElement('button', 'simonKeyboardReset', 'Réinitialiser les touches');
    const keyboardSummary = createElement('p', 'simonKeyboardSummary', 'Configuration active : A / Z / Q / S');
    const keyboardPads = createKeyboardPads();
    const keyboardEditButtons = createKeyboardEditButtons();

    return {
        dataset: {
            simonConfig: JSON.stringify({
                labels: {
                    start: 'Nouvelle partie',
                    restart: 'Recommencer',
                    status: {
                        idle: 'En attente',
                        preparation: 'Prépare-toi…',
                        demo: 'Observe la séquence',
                        player: 'À toi de jouer',
                        success: 'Bien joué !',
                        failure: 'Partie terminée',
                    },
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
                    zones: {
                        'top-left': 'Haut gauche',
                        'top-right': 'Haut droite',
                        'bottom-left': 'Bas gauche',
                        'bottom-right': 'Bas droite',
                    },
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
                    padAria: 'Zone {zone}, touche {key}',
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
                    return statusText;
                case '[data-simon-level]':
                    return currentLevel;
                case '[data-simon-best]':
                    return bestScore;
                case '[data-simon-board]':
                    return board;
                case '[data-simon-keyboard-feedback]':
                    return keyboardFeedback;
                case '[data-simon-keyboard-reset]':
                    return keyboardResetButton;
                case '[data-simon-volume]':
                    return audioVolumeInput;
                case '[data-simon-volume-value]':
                    return audioVolumeValue;
                case '[data-simon-audio-feedback]':
                    return audioFeedback;
                default:
                    return null;
            }
        },
        querySelectorAll: selector => {
            switch (selector) {
                case '[data-simon-pad]':
                    return keyboardPads.map(item => item.pad);
                case '[data-simon-keyboard-edit]':
                    return keyboardEditButtons.map(item => item.button);
                case '[data-simon-keyboard-key]':
                    return [
                        ...keyboardPads.map(item => item.key),
                        ...keyboardEditButtons.map(item => item.binding),
                    ];
                case '[data-simon-keyboard-zone]':
                    return keyboardPads.map(item => item.pad);
                case '[data-simon-keyboard-summary]':
                    return [keyboardSummary];
                default:
                    return [];
            }
        },
    };
}

function createElement(tagName, dataKey = null, textContent = '') {
    const element = new TestElement(tagName);

    if (dataKey) {
        element.dataset[dataKey] = '';
    }

    element.textContent = textContent;

    return element;
}

function createKeyboardPads() {
    return [
        ['top-left', 0, 'A'],
        ['top-right', 1, 'Z'],
        ['bottom-left', 2, 'Q'],
        ['bottom-right', 3, 'S'],
    ].map(([zoneId, index, key]) => {
        const pad = createElement('button', null);
        pad.dataset.simonPad = String(index);
        pad.dataset.simonKeyboardZone = zoneId;

        const keyElement = createElement('span', 'simonKeyboardKey', key);
        keyElement.dataset.simonKeyboardKey = zoneId;

        return {
            pad,
            key: keyElement,
        };
    });
}

function createKeyboardEditButtons() {
    return [
        ['top-left', 'A'],
        ['top-right', 'Z'],
        ['bottom-left', 'Q'],
        ['bottom-right', 'S'],
    ].map(([zoneId, key]) => {
        const button = createElement('button', 'simonKeyboardEdit', 'Modifier');
        button.dataset.simonKeyboardEdit = zoneId;

        const binding = createElement('strong', 'simonKeyboardKey', key);
        binding.dataset.simonKeyboardKey = zoneId;

        return {
            button,
            binding,
        };
    });
}

function createDocumentDouble() {
    return {
        addEventListener: vi.fn(),
    };
}

function createKeyboardEvent({ key, repeat = false, target = null, altKey = false, ctrlKey = false, metaKey = false }) {
    return {
        key,
        repeat,
        target,
        altKey,
        ctrlKey,
        metaKey,
        defaultPrevented: false,
        preventDefault: vi.fn(),
        stopPropagation: vi.fn(),
    };
}

function createAudioMock(overrides = {}) {
    return {
        playPad: vi.fn(),
        playStart: vi.fn(),
        playSuccess: vi.fn(),
        playError: vi.fn(),
        setVolume: vi.fn(),
        setEnabled: vi.fn(),
        unlock: vi.fn(() => Promise.resolve(true)),
        isSupported: () => true,
        ...overrides,
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
