import { describe, expect, test } from 'vitest';
import { getPlayerActors } from '../../../../assets/scripts/lab/dnd/players.js';
import {
    createInput,
    createPlayerItem,
} from './dom-test-helpers.js';

describe('players panel data mapping', () => {
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
});

function createPlayerList(playerItems) {
    return {
        querySelectorAll: selector => selector === '.player-item' ? playerItems : [],
    };
}
