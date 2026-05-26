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
                side: 'ally',
                armorClass: 15,
                currentHitPoints: 18,
                baseHitPoints: 20,
                initiative: 16,
                roll: 16,
                initiativeModifier: 2,
                identity: {
                    race: 'Humaine',
                    className: 'Barde',
                    classPath: 'College des epes',
                    background: 'Historique sur mesure',
                    level: 3,
                    alignment: 6,
                    age: 20,
                    sex: 1,
                },
                abilityScores: {
                    strength: 9,
                    dexterity: 15,
                    constitution: 14,
                    intelligence: 13,
                    wisdom: 10,
                    charisma: 17,
                },
                presentation: {
                    height: '1,65 m',
                    weight: '55 kg',
                    eyes: 'Bleu-vert',
                    skin: 'Blanche',
                    hair: 'Bruns',
                    appearance: 'Physique : Ma peau est constellee de taches de rousseur.',
                },
                proficiencies: {
                    skills: ['Persuasion', 'Discretion'],
                    tools: ['flute', 'luth'],
                    languages: ['commun', 'elfique'],
                },
                spellbook: {
                    known: [
                        { name: 'illusion mineure', level: 0 },
                        { name: 'mot de guerison', level: 1 },
                    ],
                },
                equipment: {
                    weapons: ['epée courte'],
                    armor: ['armure de cuir'],
                    items: ['etui de flute'],
                    currency: {
                        gp: 171,
                        pp: 0,
                        ep: 0,
                        sp: 53,
                        cp: 34,
                    },
                },
                story: {
                    traits: 'Je semble legere et insouciante.',
                    ideals: 'Justice, pas vengeance.',
                    bonds: 'Je possede une preuve irrefutable.',
                    flaws: 'Je mens avec une aisance deconcertante.',
                    backstory: 'Mon vrai nom est Elianora Valcoren.',
                    allies: 'Aucun',
                    features: 'Inspiration bardique',
                    treasure: 'Une preuve cachee dans un etui de flute',
                },
                importData: {
                    warnings: ['Les compétences extraites depuis le XML restent conservées en ids bruts pour le moment.'],
                    raw: {
                        toolsProf: {
                            1: ['flute', 'luth'],
                        },
                    },
                },
                source: {
                    format: 'xml',
                    origin: 'builder',
                    fileName: 'Lyriel-Selthir.xml',
                    importedAt: '2026-05-25T09:00:00.000Z',
                },
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
                expect.objectContaining({
                    id: 'player-1',
                    type: 'player',
                    name: 'Lia',
                    side: 'ally',
                    armorClass: 15,
                    baseHitPoints: 20,
                    currentHitPoints: 18,
                    initiative: 16,
                    roll: 16,
                    initiativeModifier: 2,
                }),
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
        expect(snapshot.players[0]).toMatchObject({
            identity: {
                race: 'Humaine',
                className: 'Barde',
                classPath: 'College des epes',
                background: 'Historique sur mesure',
                level: 3,
                alignment: 6,
                age: 20,
                sex: 1,
            },
            abilityScores: {
                strength: 9,
                dexterity: 15,
                constitution: 14,
                intelligence: 13,
                wisdom: 10,
                charisma: 17,
            },
            proficiencies: {
                skills: ['Persuasion', 'Discretion'],
                tools: ['flute', 'luth'],
                languages: ['commun', 'elfique'],
            },
            importData: {
                warnings: ['Les compétences extraites depuis le XML restent conservées en ids bruts pour le moment.'],
                raw: {
                    toolsProf: {
                        1: ['flute', 'luth'],
                    },
                },
            },
            source: {
                format: 'xml',
                origin: 'builder',
                fileName: 'Lyriel-Selthir.xml',
                importedAt: '2026-05-25T09:00:00.000Z',
            },
        });
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
                    side: 'ally',
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
            side: 'ally',
            identity: {
                race: null,
                className: null,
                classPath: null,
                background: null,
                level: null,
                alignment: null,
                age: null,
                sex: null,
            },
            abilityScores: {
                strength: null,
                dexterity: null,
                constitution: null,
                intelligence: null,
                wisdom: null,
                charisma: null,
            },
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

    test('restores an enriched player dto without losing identifiable fields', () => {
        const encounter = new EncounterState({ bestiary: bestiarySample });

        restoreEncounterFromSnapshot(encounter, {
            version: ENCOUNTER_SNAPSHOT_VERSION,
            savedAt: '2026-05-25T09:00:00.000Z',
            monsters: [],
            players: [
                {
                    id: 'player-lyriel',
                    type: 'player',
                    name: 'Lyriel Selthir',
                    armorClass: 14,
                    baseHitPoints: 21,
                    currentHitPoints: 18,
                    initiative: 12,
                    roll: 10,
                    identity: {
                        race: 'Demi-elfe',
                        className: 'Barde',
                        classPath: 'College des epees',
                        background: 'Historique sur mesure',
                        level: 3,
                        alignment: 6,
                        age: 20,
                        sex: 1,
                    },
                    abilityScores: {
                        strength: 9,
                        dexterity: 15,
                        constitution: 14,
                        intelligence: 13,
                        wisdom: 10,
                        charisma: 17,
                    },
                    proficiencies: {
                        skills: ['Persuasion', 'Sagesse'],
                        tools: ['flute', 'luth'],
                        languages: ['commun', 'elfique', 'nain'],
                    },
                    spellbook: {
                        known: [
                            { name: 'illusion mineure', level: 0 },
                            { name: 'fou rire de tasha', level: 1 },
                        ],
                    },
                    equipment: {
                        weapons: ['epée courte'],
                        armor: ['armure de cuir'],
                        items: ['etui de flute'],
                        currency: {
                            gp: 171,
                            pp: 0,
                            ep: 0,
                            sp: 53,
                            cp: 34,
                        },
                    },
                    story: {
                        traits: 'Charmante en apparence.',
                        ideals: 'Justice, pas vengeance.',
                        bonds: 'Preuve d’un crime familial.',
                        flaws: 'Je mens avec aisance.',
                        backstory: 'Mon vrai nom est Elianora Valcoren.',
                        allies: 'Aucun',
                        features: 'Inspiration bardique',
                        treasure: 'Preuve cachee dans une flute',
                    },
                    importData: {
                        warnings: ['Les compétences extraites depuis le XML restent conservées en ids bruts pour le moment.'],
                        raw: {
                            toolsProf: {
                                1: ['flute', 'luth'],
                            },
                        },
                    },
                    source: {
                        format: 'xml',
                        origin: 'builder',
                        fileName: 'Lyriel-Selthir.xml',
                        importedAt: '2026-05-25T09:00:00.000Z',
                    },
                },
            ],
            rules: {},
            turnOrder: [],
            currentRound: 1,
            activeTurnId: null,
        });

        expect(encounter.players[0]).toMatchObject({
            id: 'player-lyriel',
            name: 'Lyriel Selthir',
            initiativeModifier: 2,
            race: 'Demi-elfe',
            className: 'Barde',
            classPath: 'College des epees',
            background: 'Historique sur mesure',
            level: 3,
            alignment: 6,
            age: 20,
            sex: 1,
            height: null,
            weight: null,
            abilityScores: {
                dexterity: 15,
                charisma: 17,
            },
            spellbook: {
                known: [
                    { name: 'illusion mineure', level: 0 },
                    { name: 'fou rire de tasha', level: 1 },
                ],
            },
            importData: {
                warnings: ['Les compétences extraites depuis le XML restent conservées en ids bruts pour le moment.'],
                raw: {
                    toolsProf: {
                        1: ['flute', 'luth'],
                    },
                },
            },
            source: {
                format: 'xml',
                origin: 'builder',
                fileName: 'Lyriel-Selthir.xml',
                importedAt: '2026-05-25T09:00:00.000Z',
            },
        });
    });
});
