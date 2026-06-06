import { describe, expect, test } from 'vitest';
import { bestiarySample } from '../../../fixtures/dnd/bestiary-sample.js';
import {
    addCondition,
    adjustActorHitPoints,
    buildRoundOrder,
    createEncounterState,
    createMonsterSlots,
    EncounterState,
    hasSelectedMonsters,
    moveTurn,
    rollMonsterInitiatives,
    selectMonster,
    setPlayers,
    setCombatStatus,
    setRuleActive,
    updateActorHitPoints,
    toggleTurnDone,
} from '../../../../assets/scripts/lab/dnd/encounter-state.js';

function createTestEncounter() {
    return createEncounterState({ bestiary: bestiarySample });
}

function getMonster(slug) {
    return bestiarySample.find(monster => monster.slug === slug);
}

describe('encounter state', () => {
    test('creates an EncounterState instance through the compatibility factory', () => {
        const encounter = createTestEncounter();

        expect(encounter).toBeInstanceOf(EncounterState);
        expect(encounter.bestiary).toBe(bestiarySample);
    });

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

    test('breaks tied player initiatives by initiative modifier when the rule is active', () => {
        const encounter = createTestEncounter();

        setRuleActive(encounter, 'break-initiative-ties-with-dexterity', true);
        setPlayers(encounter, [
            createPlayer({ id: 'player-1', name: 'Lia', initiative: 12, roll: 12, initiativeModifier: 4 }),
            createPlayer({ id: 'player-2', name: 'Borin', initiative: 12, roll: 12, initiativeModifier: 1 }),
        ]);

        buildRoundOrder(encounter);

        expect(encounter.turnOrder.map(actor => actor.id)).toEqual([
            'player-1',
            'player-2',
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

    test('advances to the next actor and marks the current turn as done', () => {
        const encounter = createTestEncounter();

        setPlayers(encounter, [
            createPlayer({ id: 'player-1', name: 'Lia', initiative: 18, roll: 18 }),
            createPlayer({ id: 'player-2', name: 'Borin', initiative: 12, roll: 12 }),
        ]);
        buildRoundOrder(encounter);

        const result = encounter.advanceToNextTurn();

        expect(result).toMatchObject({
            status: 'advanced',
            activeTurnId: 'player-2',
        });
        expect(encounter.turnOrder.map(actor => actor.done)).toEqual([true, false]);
        expect(encounter.activeTurnId).toBe('player-2');
    });

    test('marks the round complete when the last actor is advanced', () => {
        const encounter = createTestEncounter();

        setPlayers(encounter, [
            createPlayer({ id: 'player-1', name: 'Lia', initiative: 18, roll: 18 }),
        ]);
        buildRoundOrder(encounter);

        const result = encounter.advanceToNextTurn();

        expect(result).toMatchObject({
            status: 'round-complete',
            activeTurnId: null,
        });
        expect(encounter.turnOrder[0].done).toBe(true);
        expect(encounter.isRoundComplete()).toBe(true);
        expect(encounter.activeTurnId).toBe(null);
    });

    test('starts a new round without changing the manual order', () => {
        const encounter = createTestEncounter();

        setPlayers(encounter, [
            createPlayer({ id: 'player-1', name: 'Lia', initiative: 18, roll: 18 }),
            createPlayer({ id: 'player-2', name: 'Borin', initiative: 12, roll: 12 }),
        ]);
        buildRoundOrder(encounter);
        encounter.moveTurn('player-2', 'player-1', 'before');
        encounter.turnOrder[0].done = true;
        encounter.turnOrder[1].done = true;
        encounter.currentRound = 3;
        encounter.activeTurnId = null;

        encounter.startNewRound();

        expect(encounter.currentRound).toBe(4);
        expect(encounter.turnOrder.map(actor => actor.id)).toEqual(['player-2', 'player-1']);
        expect(encounter.turnOrder.every(actor => actor.done)).toBe(false);
        expect(encounter.activeTurnId).toBe('player-2');
    });

    test('resets only the turn progress without changing the current round', () => {
        const encounter = createTestEncounter();

        setPlayers(encounter, [
            createPlayer({ id: 'player-1', name: 'Lia', initiative: 18, roll: 18 }),
            createPlayer({ id: 'player-2', name: 'Borin', initiative: 12, roll: 12 }),
        ]);
        buildRoundOrder(encounter);
        encounter.advanceToNextTurn();
        encounter.currentRound = 5;

        encounter.resetTurnProgress();

        expect(encounter.currentRound).toBe(5);
        expect(encounter.turnOrder.every(actor => actor.done)).toBe(false);
        expect(encounter.activeTurnId).toBe('player-1');
    });

    test('resets the encounter state while preserving rules', () => {
        const encounter = createTestEncounter();

        setRuleActive(encounter, 'skip-low-initiative', false);
        createMonsterSlots(encounter, 1);
        selectMonster(encounter, 0, 'acolyte');
        setPlayers(encounter, [
            createPlayer({ id: 'player-1', name: 'Lia', initiative: 18, roll: 18 }),
        ]);
        buildRoundOrder(encounter);
        encounter.advanceToNextTurn();
        encounter.currentRound = 4;

        encounter.resetEncounter();

        expect(encounter.monsters).toEqual([]);
        expect(encounter.players).toEqual([]);
        expect(encounter.turnOrder).toEqual([]);
        expect(encounter.currentRound).toBe(1);
        expect(encounter.activeTurnId).toBe(null);
        expect(encounter.isRuleActive('skip-low-initiative')).toBe(false);
    });

    test('adds a condition to the actor and keeps turn-order copies synchronized', () => {
        const encounter = createTestEncounter();

        setPlayers(encounter, [
            createPlayer({
                id: 'player-1',
                name: 'Lia',
                initiative: 18,
                roll: 18,
                currentHitPoints: 13,
                baseHitPoints: 20,
            }),
        ]);
        buildRoundOrder(encounter);

        const result = addCondition(encounter, 'player-1', {
            slug: 'poisoned',
            remainingRounds: 2,
            note: 'Toxine',
        });

        expect(result).toMatchObject({
            actorId: 'player-1',
            actorName: 'Lia',
            condition: {
                slug: 'poisoned',
                remainingRounds: 2,
                note: 'Toxine',
            },
        });
        expect(encounter.players[0].conditions).toHaveLength(1);
        expect(encounter.players[0].conditions[0]).toMatchObject({
            slug: 'poisoned',
            remainingRounds: 2,
        });
        expect(encounter.turnOrder[0].conditions).toHaveLength(1);
        expect(encounter.turnOrder[0].conditions[0]).toMatchObject({
            slug: 'poisoned',
            remainingRounds: 2,
        });
        expect(encounter.activeTurnId).toBe('player-1');
    });

    test('decrements round-based conditions only when a new round starts', () => {
        const encounter = createTestEncounter();

        setPlayers(encounter, [
            createPlayer({
                id: 'player-1',
                name: 'Lia',
                initiative: 18,
                roll: 18,
            }),
        ]);
        buildRoundOrder(encounter);
        addCondition(encounter, 'player-1', {
            slug: 'poisoned',
            remainingRounds: 2,
        });

        const firstRound = encounter.startNewRound();

        expect(firstRound.expiredConditions).toEqual([]);
        expect(encounter.currentRound).toBe(2);
        expect(encounter.players[0].conditions[0]).toMatchObject({
            slug: 'poisoned',
            remainingRounds: 1,
        });
        expect(encounter.turnOrder[0].conditions[0]).toMatchObject({
            slug: 'poisoned',
            remainingRounds: 1,
        });

        const secondRound = encounter.startNewRound();

        expect(secondRound.expiredConditions).toHaveLength(1);
        expect(secondRound.expiredConditions[0]).toMatchObject({
            actorId: 'player-1',
            actorName: 'Lia',
        });
        expect(encounter.currentRound).toBe(3);
        expect(encounter.players[0].conditions).toEqual([]);
        expect(encounter.turnOrder[0].conditions).toEqual([]);
    });

    test('does not decrement condition durations when advancing turns or resetting turn progress', () => {
        const encounter = createTestEncounter();

        setPlayers(encounter, [
            createPlayer({
                id: 'player-1',
                name: 'Lia',
                initiative: 18,
                roll: 18,
            }),
            createPlayer({
                id: 'player-2',
                name: 'Borin',
                initiative: 12,
                roll: 12,
            }),
        ]);
        buildRoundOrder(encounter);
        addCondition(encounter, 'player-1', {
            slug: 'poisoned',
            remainingRounds: 2,
        });

        encounter.advanceToNextTurn();
        expect(encounter.players[0].conditions[0].remainingRounds).toBe(2);
        expect(encounter.turnOrder[0].conditions[0].remainingRounds).toBe(2);

        encounter.resetTurnProgress();
        expect(encounter.players[0].conditions[0].remainingRounds).toBe(2);
        expect(encounter.turnOrder[0].conditions[0].remainingRounds).toBe(2);
    });

    test('updates combat status independently from hit points', () => {
        const encounter = createTestEncounter();

        setPlayers(encounter, [
            createPlayer({
                id: 'player-1',
                name: 'Lia',
                initiative: 18,
                roll: 18,
                currentHitPoints: 0,
                baseHitPoints: 20,
            }),
        ]);
        buildRoundOrder(encounter);

        expect(encounter.players[0].combatStatus).toBe('normal');
        expect(encounter.turnOrder[0].combatStatus).toBe('normal');

        const setDeadResult = setCombatStatus(encounter, 'player-1', 'dead');

        expect(setDeadResult).toMatchObject({
            actorId: 'player-1',
            actorName: 'Lia',
            combatStatus: 'dead',
        });
        expect(encounter.players[0].combatStatus).toBe('dead');
        expect(encounter.turnOrder[0].combatStatus).toBe('dead');

        updateActorHitPoints(encounter, 'player-1', 12);

        expect(encounter.players[0].combatStatus).toBe('dead');
        expect(encounter.turnOrder[0].combatStatus).toBe('dead');
        expect(encounter.players[0].currentHitPoints).toBe(12);
        expect(encounter.turnOrder[0].currentHitPoints).toBe(12);
    });

    test('updates an actor hit points directly without changing combat flow state', () => {
        const encounter = createTestEncounter();

        setPlayers(encounter, [
            createPlayer({
                id: 'player-critical',
                name: 'Lia',
                initiative: 20,
                roll: 20,
                currentHitPoints: 13,
                baseHitPoints: 20,
            }),
        ]);
        buildRoundOrder(encounter);

        const result = updateActorHitPoints(encounter, 'player-critical', 12);

        expect(result).toMatchObject({
            actorId: 'player-critical',
            actorName: 'Lia',
            currentHitPoints: 12,
            baseHitPoints: 20,
        });
        expect(encounter.players[0].currentHitPoints).toBe(12);
        expect(encounter.turnOrder.map(actor => actor.currentHitPoints)).toEqual([12, 12]);
        expect(encounter.currentRound).toBe(1);
        expect(encounter.activeTurnId).toBe('player-critical-turn-1');
        expect(encounter.turnOrder.every(actor => actor.done)).toBe(false);
    });

    test('adjusts actor hit points through damage and healing with bounds', () => {
        const encounter = createTestEncounter();

        setPlayers(encounter, [
            createPlayer({
                id: 'player-1',
                name: 'Lia',
                initiative: 18,
                roll: 18,
                currentHitPoints: 4,
                baseHitPoints: 20,
            }),
        ]);
        buildRoundOrder(encounter);

        const damageResult = adjustActorHitPoints(encounter, 'player-1', -7);
        const healResult = adjustActorHitPoints(encounter, 'player-1', 99);

        expect(damageResult).toMatchObject({
            currentHitPoints: 0,
            baseHitPoints: 20,
        });
        expect(healResult).toMatchObject({
            currentHitPoints: 20,
            baseHitPoints: 20,
        });
        expect(encounter.players[0].currentHitPoints).toBe(20);
        expect(encounter.turnOrder[0].currentHitPoints).toBe(20);
        expect(encounter.currentRound).toBe(1);
        expect(encounter.activeTurnId).toBe('player-1');
    });

    test('keeps monster turn-order copies in sync when monster hit points change', () => {
        const encounter = createTestEncounter();

        createMonsterSlots(encounter, 1);
        selectMonster(encounter, 0, 'acolyte');
        rollMonsterInitiatives(encounter, () => 12);
        buildRoundOrder(encounter);

        encounter.updateMonsterHitPoints(0, 5);

        expect(encounter.monsters[0].currentHitPoints).toBe(5);
        expect(encounter.turnOrder[0].currentHitPoints).toBe(5);
        expect(encounter.activeTurnId).toBe('acolyte-1');
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
        initiativeModifier: 0,
        conditions: [],
        combatStatus: 'normal',
        ...overrides,
    };
}
