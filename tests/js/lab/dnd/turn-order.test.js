import { afterEach, beforeEach, describe, expect, test, vi } from 'vitest';
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
