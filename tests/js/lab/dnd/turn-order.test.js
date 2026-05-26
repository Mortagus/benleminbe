import { afterEach, beforeEach, describe, expect, test, vi } from 'vitest';
import { EncounterState } from '../../../../assets/scripts/lab/dnd/encounter-state.js';
import {
    createDocumentDouble,
    createTurnOrderTemplate,
    TestElement,
} from './dom-test-helpers.js';

describe('turn order rendering', () => {
    beforeEach(() => {
        vi.resetModules();
        globalThis.document = createDocumentDouble({
            turnOrderItemTemplate: createTurnOrderTemplate(),
        });
    });

    afterEach(() => {
        delete globalThis.document;
        delete globalThis.window;
    });

    test('shows the placeholder when the round order is empty', async () => {
        const { renderRoundOrder } = await import('../../../../assets/scripts/lab/dnd/turn-order.js');
        const turnOrderList = new TestElement('ol');
        const turnOrderPlaceholder = new TestElement('div');

        renderRoundOrder(turnOrderList, turnOrderPlaceholder, [], {});

        expect(turnOrderPlaceholder.hidden).toBe(false);
        expect(turnOrderList.hidden).toBe(true);
        expect(turnOrderList.children).toHaveLength(0);
    });

    test('renders turns and toggles a turn from click and keyboard', async () => {
        const { renderRoundOrder } = await import('../../../../assets/scripts/lab/dnd/turn-order.js');
        const turnOrderList = new TestElement('ol');
        const turnOrderPlaceholder = new TestElement('div');
        const onToggleTurnDone = vi.fn();

        renderRoundOrder(
            turnOrderList,
            turnOrderPlaceholder,
            [
                createTurn({
                    id: 'player-1',
                    name: 'Lia',
                    initiative: 14,
                }),
            ],
            {
                onToggleTurnDone,
            },
        );

        const item = turnOrderList.children[0];

        expect(turnOrderPlaceholder.hidden).toBe(true);
        expect(turnOrderList.hidden).toBe(false);
        expect(item.classList.contains('turn-order-item--active')).toBe(true);
        expect(item.querySelector('.turn-order-item__name').textContent).toBe('Lia');
        expect(item.querySelector('.turn-order-item__initiative').textContent).toBe('Init. 14');

        item.dispatchEvent({ type: 'click' });
        item.dispatchEvent({
            type: 'keydown',
            key: 'Enter',
            preventDefault: vi.fn(),
        });

        expect(onToggleTurnDone).toHaveBeenCalledTimes(2);
        expect(onToggleTurnDone).toHaveBeenNthCalledWith(1, 'player-1');
        expect(onToggleTurnDone).toHaveBeenNthCalledWith(2, 'player-1');
    });

    test('renders side classes and legendary markers', async () => {
        const { renderRoundOrder } = await import('../../../../assets/scripts/lab/dnd/turn-order.js');
        const turnOrderList = new TestElement('ol');
        const turnOrderPlaceholder = new TestElement('div');

        renderRoundOrder(
            turnOrderList,
            turnOrderPlaceholder,
            [
                createTurn({
                    id: 'ally-1',
                    name: 'Lia',
                    side: 'ally',
                    initiative: 14,
                }),
                createTurn({
                    id: 'boss-1',
                    type: 'monster',
                    name: 'Dragon',
                    side: 'hostile',
                    isLegendary: true,
                    initiative: 20,
                }),
            ],
            {},
        );

        const allyItem = turnOrderList.children[0];
        const bossItem = turnOrderList.children[1];

        expect(allyItem.classList.contains('turn-order-item--side-ally')).toBe(true);
        expect(allyItem.querySelector('.turn-order-item__legendary-badge').hidden).toBe(true);
        expect(bossItem.classList.contains('turn-order-item--legendary')).toBe(true);
        expect(bossItem.classList.contains('turn-order-item--side-hostile')).toBe(true);
        expect(bossItem.querySelector('.turn-order-item__legendary-badge').hidden).toBe(false);
        expect(bossItem.querySelector('.turn-order-item__legendary-badge').textContent).toBe('Boss');
    });

    test('moves turns through the rendered next button', async () => {
        const { renderRoundOrder } = await import('../../../../assets/scripts/lab/dnd/turn-order.js');
        const turnOrderList = new TestElement('ol');
        const turnOrderPlaceholder = new TestElement('div');
        const onMoveTurn = vi.fn();
        const onAnnounce = vi.fn();

        renderRoundOrder(
            turnOrderList,
            turnOrderPlaceholder,
            [
                createTurn({ id: 'player-1', name: 'Lia', initiative: 14 }),
                createTurn({ id: 'player-2', name: 'Borin', initiative: 9 }),
            ],
            {
                onMoveTurn,
                onAnnounce,
            },
        );

        turnOrderList.children[0]
            .querySelector('[data-turn-move="next"]')
            .dispatchEvent({ type: 'click' });

        expect(onMoveTurn).toHaveBeenCalledWith('player-1', 'player-2', 'after');
        expect(onAnnounce).toHaveBeenCalledWith('Lia déplacé après Borin.');
    });

    test('moves turns through keyboard arrows', async () => {
        const { renderRoundOrder } = await import('../../../../assets/scripts/lab/dnd/turn-order.js');
        const turnOrderList = new TestElement('ol');
        const turnOrderPlaceholder = new TestElement('div');
        const onMoveTurn = vi.fn();
        const onAnnounce = vi.fn();

        renderRoundOrder(
            turnOrderList,
            turnOrderPlaceholder,
            [
                createTurn({ id: 'player-1', name: 'Lia', initiative: 14 }),
                createTurn({ id: 'player-2', name: 'Borin', initiative: 9 }),
            ],
            {
                onMoveTurn,
                onAnnounce,
            },
        );

        turnOrderList.children[0].dispatchEvent({
            type: 'keydown',
            key: 'ArrowRight',
            preventDefault: vi.fn(),
        });

        expect(onMoveTurn).toHaveBeenCalledWith('player-1', 'player-2', 'after');
        expect(onAnnounce).toHaveBeenCalledWith('Lia déplacé après Borin.');
    });

    test('moves turns through drag and drop', async () => {
        const { renderRoundOrder } = await import('../../../../assets/scripts/lab/dnd/turn-order.js');
        const turnOrderList = new TestElement('ol');
        const turnOrderPlaceholder = new TestElement('div');
        const onMoveTurn = vi.fn();

        renderRoundOrder(
            turnOrderList,
            turnOrderPlaceholder,
            [
                createTurn({ id: 'player-1', name: 'Lia', initiative: 14 }),
                createTurn({ id: 'player-2', name: 'Borin', initiative: 9 }),
            ],
            {
                onMoveTurn,
            },
        );

        turnOrderList.children[0].dispatchEvent({ type: 'dragstart' });
        turnOrderList.children[1].dispatchEvent({ type: 'drop' });

        expect(onMoveTurn).toHaveBeenCalledWith('player-1', 'player-2', 'after');
    });

    test('starts the panel and reports generate requests', async () => {
        const encounter = new EncounterState();
        const onGenerateTurnOrder = vi.fn();

        globalThis.document = createTurnOrderDocument();

        const { TurnOrderPanel } = await import('../../../../assets/scripts/lab/dnd/turn-order.js');
        const panel = new TurnOrderPanel(encounter, {
            onGenerateTurnOrder,
        });
        panel.start();

        expect(panel).toBeInstanceOf(TurnOrderPanel);

        globalThis.document
            .getElementById('generateTurnOrder')
            .dispatchEvent({ type: 'click' });

        expect(onGenerateTurnOrder).toHaveBeenCalledOnce();
    });
});

function createTurn(overrides = {}) {
    return {
        id: 'player',
        actorId: 'player',
        type: 'player',
        name: 'Player',
        armorClass: 14,
        currentHitPoints: 20,
        baseHitPoints: 20,
        initiative: 10,
        roll: 10,
        done: false,
        ...overrides,
    };
}

function createTurnOrderDocument() {
    const generateTurnOrderButton = new TestElement('button');
    const turnOrderPanel = new TestElement('section', ['dnd-panel--turn-order']);
    const turnOrderValidationSummary = new TestElement('div', ['dnd-validation-summary']);
    const keyboardHelpButton = new TestElement('button');
    const keyboardHelp = new TestElement('div');
    const turnOrderPlaceholder = new TestElement('div');
    const turnOrderList = new TestElement('ol');
    const turnOrderLiveRegion = new TestElement('div');

    return {
        ...createDocumentDouble({
            generateTurnOrder: generateTurnOrderButton,
            turnOrderValidationSummary,
            toggleTurnOrderKeyboardHelp: keyboardHelpButton,
            turnOrderKeyboardHelp: keyboardHelp,
            turnOrderPlaceholder,
            turnOrderList,
            turnOrderLiveRegion,
            turnOrderItemTemplate: createTurnOrderTemplate(),
        }),
        querySelector: selector => selector === '.dnd-panel--turn-order' ? turnOrderPanel : null,
    };
}
