import { afterEach, describe, expect, test, vi } from 'vitest';
import { EncounterState } from '../../../../assets/scripts/lab/dnd/encounter-state.js';
import {
    getPlayerActors,
    PlayersPanel,
} from '../../../../assets/scripts/lab/dnd/players.js';
import {
    createInput,
    createPlayerItem,
    TestElement,
} from './dom-test-helpers.js';

describe('players panel data mapping', () => {
    afterEach(() => {
        delete globalThis.document;
    });

    test('maps started player rows to encounter actors', () => {
        const playerList = createPlayerList([
            createPlayerItem({
                name: createInput('Lia'),
                'armor-class': createInput('15'),
                'current-hit-points': createInput('18'),
                'base-hit-points': createInput('20'),
                initiative: createInput('12'),
            }),
            createPlayerItem({
                name: createInput(''),
                'armor-class': createInput(''),
                'current-hit-points': createInput(''),
                'base-hit-points': createInput(''),
                initiative: createInput(''),
            }),
            createPlayerItem({
                name: createInput('Borin'),
                'armor-class': createInput('17'),
                'current-hit-points': createInput('22'),
                'base-hit-points': createInput('24'),
                initiative: createInput('8'),
            }),
        ]);

        expect(getPlayerActors(playerList)).toEqual([
            {
                id: 'player-1',
                type: 'player',
                name: 'Lia',
                armorClass: 15,
                currentHitPoints: 18,
                baseHitPoints: 20,
                initiative: 12,
                roll: 12,
                done: false,
            },
            {
                id: 'player-2',
                type: 'player',
                name: 'Borin',
                armorClass: 17,
                currentHitPoints: 22,
                baseHitPoints: 24,
                initiative: 8,
                roll: 8,
                done: false,
            },
        ]);
    });

    test('starts the panel and synchronizes the initial player list', () => {
        const encounter = new EncounterState();
        const onPlayersChange = vi.fn();

        globalThis.document = createPlayersDocument();

        const panel = new PlayersPanel(encounter, {
            onPlayersChange,
        });
        panel.start();

        expect(panel).toBeInstanceOf(PlayersPanel);
        expect(encounter.players).toEqual([]);
        expect(onPlayersChange).toHaveBeenCalledOnce();
    });

    test('synchronizes existing player input changes to the encounter', () => {
        const encounter = new EncounterState();
        const onPlayersChange = vi.fn();
        const playerItem = createPanelPlayerItem({
            name: createInput('Lia'),
            'armor-class': createInput('15'),
            'current-hit-points': createInput('18'),
            'base-hit-points': createInput('20'),
            initiative: createInput('12'),
        });

        globalThis.document = createPlayersDocument([playerItem.item]);

        const panel = new PlayersPanel(encounter, {
            onPlayersChange,
        });
        panel.start();

        expect(encounter.players[0]).toMatchObject({
            name: 'Lia',
            initiative: 12,
            roll: 12,
        });

        playerItem.inputs.initiative.value = '16';
        playerItem.inputs.initiative.dispatchEvent({ type: 'input' });

        expect(encounter.players[0]).toMatchObject({
            name: 'Lia',
            initiative: 16,
            roll: 16,
        });
        expect(onPlayersChange).toHaveBeenCalledTimes(2);
    });
});

function createPlayerList(playerItems) {
    return {
        querySelectorAll: selector => selector === '.player-item' ? playerItems : [],
    };
}

function createPlayersDocument(playerItems = []) {
    const addPlayerButton = new TestElement('button');
    const playerPanel = new TestElement('section', ['dnd-panel--players']);
    const playerList = new TestElement('ul');
    const playerValidationSummary = new TestElement('div', ['dnd-validation-summary']);

    playerItems.forEach(playerItem => {
        playerList.appendChild(playerItem);
    });

    return {
        getElementById: id => ({
            addPlayer: addPlayerButton,
            playerList,
            playerValidationSummary,
        })[id] ?? null,
        querySelector: selector => selector === '.dnd-panel--players' ? playerPanel : null,
    };
}

function createPanelPlayerItem(inputs) {
    const item = createPlayerItem(inputs);
    item.appendChild(new TestElement('button', ['player-remove-button']));

    return {
        item,
        inputs,
    };
}
