import { describe, expect, test } from 'vitest';
import { bestiarySample } from '../../../fixtures/dnd/bestiary-sample.js';
import {
    buildRoundOrder,
    createEncounterState,
    createMonsterSlots,
    hasSelectedMonsters,
    moveTurn,
    rollMonsterInitiatives,
    selectMonster,
    setPlayers,
    setRuleActive,
    toggleTurnDone,
} from '../../../../assets/scripts/lab/dnd/encounter-state.js';

function createTestEncounter() {
    return createEncounterState({ bestiary: bestiarySample });
}

function getMonster(slug) {
    return bestiarySample.find(monster => monster.slug === slug);
}

describe('encounter state', () => {
    test('creates empty monster slots', () => {
        const encounter = createTestEncounter();

        createMonsterSlots(encounter, 2);

        expect(encounter.monsters).toHaveLength(2);
        expect(encounter.monsters[0]).toMatchObject({
            id: 'monster-1',
            slug: null,
            name: 'Monstre 1',
            type: '-',
            armorClass: '-',
            currentHitPoints: 0,
            initiative: null,
        });
        expect(hasSelectedMonsters(encounter)).toBe(false);
    });

    test('selects a monster from the injected bestiary', () => {
        const encounter = createTestEncounter();
        const acolyte = getMonster('acolyte');

        createMonsterSlots(encounter, 1);
        selectMonster(encounter, 0, 'acolyte');

        expect(encounter.monsters[0]).toMatchObject({
            id: 'acolyte-1',
            slug: 'acolyte',
            name: 'Acolyte 1',
            className: 'Acolyte',
            challengeRating: acolyte.challenge_rating,
            type: acolyte.type,
            armorClass: acolyte.armor_class,
            baseHitPoints: acolyte.hit_points,
            currentHitPoints: acolyte.hit_points,
            initiativeModifier: acolyte.initiative_modifier,
        });
        expect(hasSelectedMonsters(encounter)).toBe(true);
    });

    test('keeps the injected bestiary out of serialized encounter data', () => {
        const encounter = createTestEncounter();

        expect(encounter.bestiary).toBe(bestiarySample);
        expect(Object.keys(encounter)).not.toContain('bestiary');
        expect(JSON.parse(JSON.stringify(encounter))).not.toHaveProperty('bestiary');
    });

    test('rolls monster initiatives and sorts monsters descending', () => {
        const encounter = createTestEncounter();
        const rolls = [10, 5, 20];

        createMonsterSlots(encounter, 3);
        selectMonster(encounter, 0, 'acolyte');
        selectMonster(encounter, 1, 'aboleth');
        selectMonster(encounter, 2, 'aarakocra');
        rollMonsterInitiatives(encounter, () => rolls.shift());

        expect(encounter.monsters.map(monster => monster.slug)).toEqual([
            'aarakocra',
            'acolyte',
            'aboleth',
        ]);
        expect(encounter.monsters.map(monster => monster.initiative)).toEqual([22, 10, 4]);
    });

    test('builds a round order from monsters and players sorted by initiative', () => {
        const encounter = createTestEncounter();

        createMonsterSlots(encounter, 1);
        selectMonster(encounter, 0, 'acolyte');
        rollMonsterInitiatives(encounter, () => 12);
        setPlayers(encounter, [
            createPlayer({ id: 'player-1', name: 'Lia', initiative: 18, roll: 18 }),
            createPlayer({ id: 'player-2', name: 'Borin', initiative: 8, roll: 8 }),
        ]);

        const roundOrder = buildRoundOrder(encounter);

        expect(roundOrder.map(actor => actor.id)).toEqual(['player-1', 'acolyte-1', 'player-2']);
        expect(encounter.currentRound).toBe(1);
        expect(encounter.activeTurnId).toBe('player-1');
    });

    test('keeps tied initiative actors in stable order by default', () => {
        const encounter = createTestEncounter();
        const rolls = [10, 13];

        createMonsterSlots(encounter, 2);
        selectMonster(encounter, 0, 'aarakocra');
        selectMonster(encounter, 1, 'aboleth');
        rollMonsterInitiatives(encounter, () => rolls.shift());
        setPlayers(encounter, [
            createPlayer({ id: 'player-1', name: 'Lia', initiative: 12, roll: 12 }),
        ]);

        buildRoundOrder(encounter);

        expect(encounter.turnOrder.map(actor => actor.id)).toEqual([
            'aarakocra-1',
            'aboleth-2',
            'player-1',
        ]);
    });

    test('breaks tied initiatives by dexterity modifier when the rule is active', () => {
        const encounter = createTestEncounter();
        const rolls = [10, 13];

        setRuleActive(encounter, 'break-initiative-ties-with-dexterity', true);
        createMonsterSlots(encounter, 2);
        selectMonster(encounter, 0, 'aarakocra');
        selectMonster(encounter, 1, 'aboleth');
        rollMonsterInitiatives(encounter, () => rolls.shift());
        setPlayers(encounter, [
            createPlayer({ id: 'player-1', name: 'Lia', initiative: 12, roll: 12 }),
        ]);

        buildRoundOrder(encounter);

        expect(encounter.turnOrder.map(actor => actor.id)).toEqual([
            'aarakocra-1',
            'player-1',
            'aboleth-2',
        ]);
    });

    test('uses zero as the current player dexterity tie breaker', () => {
        const encounter = createTestEncounter();
        const rolls = [13];

        setRuleActive(encounter, 'break-initiative-ties-with-dexterity', true);
        createMonsterSlots(encounter, 1);
        selectMonster(encounter, 0, 'aboleth');
        rollMonsterInitiatives(encounter, () => rolls.shift());
        setPlayers(encounter, [
            createPlayer({ id: 'player-1', name: 'Lia', initiative: 12, roll: 12 }),
        ]);

        buildRoundOrder(encounter);

        expect(encounter.turnOrder.map(actor => actor.id)).toEqual([
            'player-1',
            'aboleth-1',
        ]);
    });

    test('skips actors with initiative lower than or equal to one when the rule is active', () => {
        const encounter = createTestEncounter();

        setPlayers(encounter, [
            createPlayer({ id: 'player-low', name: 'Low', initiative: 1, roll: 1 }),
            createPlayer({ id: 'player-ready', name: 'Ready', initiative: 2, roll: 2 }),
        ]);

        buildRoundOrder(encounter);

        expect(encounter.turnOrder.map(actor => actor.id)).toEqual(['player-ready']);
    });

    test('keeps low initiative actors when the skip rule is disabled', () => {
        const encounter = createTestEncounter();

        setRuleActive(encounter, 'skip-low-initiative', false);
        setPlayers(encounter, [
            createPlayer({ id: 'player-low', name: 'Low', initiative: 1, roll: 1 }),
            createPlayer({ id: 'player-ready', name: 'Ready', initiative: 2, roll: 2 }),
        ]);

        buildRoundOrder(encounter);

        expect(encounter.turnOrder.map(actor => actor.id)).toEqual(['player-ready', 'player-low']);
    });

    test('creates an extra turn on natural twenty when the rule is active', () => {
        const encounter = createTestEncounter();

        setPlayers(encounter, [
            createPlayer({ id: 'player-critical', name: 'Critical', initiative: 20, roll: 20 }),
        ]);

        buildRoundOrder(encounter);

        expect(encounter.turnOrder.map(actor => actor.id)).toEqual([
            'player-critical-turn-1',
            'player-critical-turn-2',
        ]);
        expect(encounter.turnOrder.every(actor => actor.actorId === 'player-critical')).toBe(true);
    });

    test('does not create an extra turn when the natural twenty rule is disabled', () => {
        const encounter = createTestEncounter();

        setRuleActive(encounter, 'extra-turn-on-twenty', false);
        setPlayers(encounter, [
            createPlayer({ id: 'player-critical', name: 'Critical', initiative: 20, roll: 20 }),
        ]);

        buildRoundOrder(encounter);

        expect(encounter.turnOrder.map(actor => actor.id)).toEqual(['player-critical']);
    });

    test('updates active turn when turns are toggled done', () => {
        const encounter = createTestEncounter();

        setPlayers(encounter, [
            createPlayer({ id: 'player-1', name: 'Lia', initiative: 18, roll: 18 }),
            createPlayer({ id: 'player-2', name: 'Borin', initiative: 8, roll: 8 }),
        ]);
        buildRoundOrder(encounter);

        toggleTurnDone(encounter, 'player-1');

        expect(encounter.turnOrder[0].done).toBe(true);
        expect(encounter.activeTurnId).toBe('player-2');

        toggleTurnDone(encounter, 'player-2');

        expect(encounter.activeTurnId).toBe(null);
    });

    test('moves turns before or after a target turn', () => {
        const encounter = createTestEncounter();

        setPlayers(encounter, [
            createPlayer({ id: 'player-1', name: 'Lia', initiative: 18, roll: 18 }),
            createPlayer({ id: 'player-2', name: 'Borin', initiative: 12, roll: 12 }),
            createPlayer({ id: 'player-3', name: 'Nyx', initiative: 8, roll: 8 }),
        ]);
        buildRoundOrder(encounter);

        moveTurn(encounter, 'player-3', 'player-1', 'before');
        expect(encounter.turnOrder.map(actor => actor.id)).toEqual(['player-3', 'player-1', 'player-2']);
        expect(encounter.activeTurnId).toBe('player-3');

        moveTurn(encounter, 'player-3', 'player-2', 'after');
        expect(encounter.turnOrder.map(actor => actor.id)).toEqual(['player-1', 'player-2', 'player-3']);
        expect(encounter.activeTurnId).toBe('player-1');
    });
});

function createPlayer(overrides = {}) {
    return {
        id: 'player',
        type: 'player',
        name: 'Player',
        armorClass: 14,
        currentHitPoints: 20,
        baseHitPoints: 20,
        initiative: 10,
        roll: 10,
        ...overrides,
    };
}
