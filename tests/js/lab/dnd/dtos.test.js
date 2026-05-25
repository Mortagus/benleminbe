import { describe, expect, test } from 'vitest';
import { bestiarySample } from '../../../fixtures/dnd/bestiary-sample.js';
import { EncounterState } from '../../../../assets/scripts/lab/dnd/encounter-state.js';
import {
    createEncounterMonsterDto,
    createEncounterSnapshotDto,
    createTurnEntryDto,
    ENCOUNTER_SNAPSHOT_VERSION,
    restoreEncounterFromSnapshot,
} from '../../../../assets/scripts/lab/dnd/dtos.js';

describe('DND DTO helpers', () => {
    test('creates a versioned encounter snapshot without bestiary data', () => {
        const encounter = new EncounterState({ bestiary: bestiarySample });

        encounter.createMonsterSlots(1);
        encounter.selectMonster(0, 'acolyte');
        encounter.rollMonsterInitiatives(() => 12);
        encounter.setPlayers([
            {
                id: 'player-1',
                type: 'player',
                name: 'Lia',
                armorClass: 15,
                currentHitPoints: 18,
                baseHitPoints: 20,
                initiative: 16,
                roll: 16,
            },
        ]);
        encounter.buildRoundOrder();

        const snapshot = createEncounterSnapshotDto(encounter, {
            savedAt: '2026-05-25T09:00:00.000Z',
        });

        expect(snapshot).toEqual({
            version: ENCOUNTER_SNAPSHOT_VERSION,
            savedAt: '2026-05-25T09:00:00.000Z',
            monsters: [
                expect.objectContaining({
                    id: 'acolyte-1',
                    slug: 'acolyte',
                    name: 'Acolyte 1',
                    armorClass: 10,
                    currentHitPoints: 9,
                    initiative: 12,
                    roll: 12,
                }),
            ],
            players: [
                {
                    id: 'player-1',
                    type: 'player',
                    name: 'Lia',
                    armorClass: 15,
                    baseHitPoints: 20,
                    currentHitPoints: 18,
                    initiative: 16,
                    roll: 16,
                },
            ],
            rules: {
                'skip-low-initiative': true,
                'extra-turn-on-twenty': true,
                'break-initiative-ties-with-dexterity': false,
            },
            turnOrder: [
                {
                    id: 'player-1',
                    actorId: 'player-1',
                    actorType: 'player',
                    done: false,
                },
                {
                    id: 'acolyte-1',
                    actorId: 'acolyte-1',
                    actorType: 'monster',
                    done: false,
                },
            ],
            currentRound: 1,
            activeTurnId: 'player-1',
        });
        expect(snapshot).not.toHaveProperty('bestiary');
    });

    test('normalizes display placeholders from empty monster slots', () => {
        const encounter = new EncounterState({ bestiary: bestiarySample });

        encounter.createMonsterSlots(1);

        expect(createEncounterMonsterDto(encounter.monsters[0])).toMatchObject({
            id: 'monster-1',
            slug: null,
            className: null,
            challengeRating: null,
            type: null,
            size: null,
            armorClass: null,
            baseHitPoints: 0,
            currentHitPoints: 0,
            alignment: null,
            isLegendary: false,
            abilities: {},
            roll: null,
            initiative: null,
        });
    });

    test('creates minimal turn entries from current copied actor turns', () => {
        expect(createTurnEntryDto({
            id: 'player-critical-turn-1',
            actorId: 'player-critical',
            type: 'player',
            name: 'Critical',
            armorClass: 14,
            currentHitPoints: 20,
            baseHitPoints: 20,
            initiative: 20,
            roll: 20,
            done: true,
        })).toEqual({
            id: 'player-critical-turn-1',
            actorId: 'player-critical',
            actorType: 'player',
            done: true,
        });
    });

    test('restores an encounter snapshot and hydrates turn entries from participants', () => {
        const encounter = new EncounterState({ bestiary: bestiarySample });

        restoreEncounterFromSnapshot(encounter, {
            version: ENCOUNTER_SNAPSHOT_VERSION,
            savedAt: '2026-05-25T09:00:00.000Z',
            monsters: [
                {
                    id: 'saved-monster-1',
                    slug: 'acolyte',
                    name: 'Acolyte sauvegardé',
                    className: 'Acolyte',
                    challengeRating: '1/4',
                    type: 'Humanoïde sauvegardé',
                    size: 'M',
                    armorClass: 99,
                    baseHitPoints: 9,
                    currentHitPoints: 4,
                    alignment: 'neutre',
                    isLegendary: false,
                    abilities: {},
                    initiativeModifier: 0,
                    roll: 12,
                    initiative: 12,
                },
            ],
            players: [
                {
                    id: 'player-1',
                    type: 'player',
                    name: 'Lia',
                    armorClass: 15,
                    baseHitPoints: 20,
                    currentHitPoints: 18,
                    initiative: 16,
                    roll: 16,
                },
            ],
            rules: {
                'skip-low-initiative': false,
                'extra-turn-on-twenty': true,
                'break-initiative-ties-with-dexterity': true,
            },
            turnOrder: [
                {
                    id: 'player-1',
                    actorId: 'player-1',
                    actorType: 'player',
                    done: true,
                },
                {
                    id: 'saved-monster-1',
                    actorId: 'saved-monster-1',
                    actorType: 'monster',
                    done: false,
                },
            ],
            currentRound: 3,
            activeTurnId: 'saved-monster-1',
        });

        expect(encounter.monsters[0]).toMatchObject({
            id: 'saved-monster-1',
            name: 'Acolyte sauvegardé',
            type: 'Humanoïde sauvegardé',
            armorClass: 99,
            currentHitPoints: 4,
        });
        expect(encounter.players[0]).toMatchObject({
            id: 'player-1',
            name: 'Lia',
        });
        expect(encounter.turnOrder).toEqual([
            expect.objectContaining({
                id: 'player-1',
                actorId: 'player-1',
                type: 'player',
                name: 'Lia',
                done: true,
            }),
            expect.objectContaining({
                id: 'saved-monster-1',
                actorId: 'saved-monster-1',
                type: 'monster',
                name: 'Acolyte sauvegardé',
                armorClass: 99,
                done: false,
            }),
        ]);
        expect(encounter.rules).toEqual({
            'skip-low-initiative': false,
            'extra-turn-on-twenty': true,
            'break-initiative-ties-with-dexterity': true,
        });
        expect(encounter.currentRound).toBe(3);
        expect(encounter.activeTurnId).toBe('saved-monster-1');
    });

    test('ignores orphan turn entries during snapshot restoration', () => {
        const encounter = new EncounterState({ bestiary: bestiarySample });

        restoreEncounterFromSnapshot(encounter, {
            version: ENCOUNTER_SNAPSHOT_VERSION,
            savedAt: '2026-05-25T09:00:00.000Z',
            monsters: [],
            players: [
                {
                    id: 'player-1',
                    type: 'player',
                    name: 'Lia',
                    armorClass: 15,
                    baseHitPoints: 20,
                    currentHitPoints: 18,
                    initiative: 16,
                    roll: 16,
                },
            ],
            rules: {},
            turnOrder: [
                {
                    id: 'missing-monster',
                    actorId: 'missing-monster',
                    actorType: 'monster',
                    done: false,
                },
                {
                    id: 'player-1',
                    actorId: 'player-1',
                    actorType: 'player',
                    done: false,
                },
            ],
            currentRound: 1,
            activeTurnId: 'missing-monster',
        });

        expect(encounter.turnOrder.map(turn => turn.id)).toEqual(['player-1']);
        expect(encounter.activeTurnId).toBe('player-1');
    });

    test('keeps an empty restored turn order empty', () => {
        const encounter = new EncounterState({ bestiary: bestiarySample });

        restoreEncounterFromSnapshot(encounter, {
            version: ENCOUNTER_SNAPSHOT_VERSION,
            savedAt: '2026-05-25T09:00:00.000Z',
            monsters: [],
            players: [
                {
                    id: 'player-1',
                    type: 'player',
                    name: 'Lia',
                    armorClass: 15,
                    baseHitPoints: 20,
                    currentHitPoints: 18,
                    initiative: 16,
                    roll: 16,
                },
            ],
            rules: {},
            turnOrder: [],
            currentRound: 2,
            activeTurnId: null,
        });

        expect(encounter.players).toHaveLength(1);
        expect(encounter.turnOrder).toEqual([]);
        expect(encounter.activeTurnId).toBe(null);
        expect(encounter.currentRound).toBe(2);
    });
});
