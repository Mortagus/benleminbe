import { afterEach, describe, expect, test, vi } from 'vitest';
import { bestiarySample } from '../../../fixtures/dnd/bestiary-sample.js';
import { EncounterState } from '../../../../assets/scripts/lab/dnd/encounter-state.js';
import {
    createEncounterSnapshotDto,
    ENCOUNTER_SNAPSHOT_VERSION,
} from '../../../../assets/scripts/lab/dnd/dtos.js';
import {
    DND_INITIATIVE_TRACKER_STORAGE_KEY,
    EncounterPersistence,
} from '../../../../assets/scripts/lab/dnd/persistence.js';
import { TestElement } from './dom-test-helpers.js';

describe('DnD encounter persistence', () => {
    afterEach(() => {
        delete globalThis.document;
        delete globalThis.localStorage;
    });

    test('saves encounter snapshots to localStorage', () => {
        const encounter = new EncounterState();
        encounter.setPlayers([
            {
                id: 'player-1',
                type: 'player',
                name: 'Lia',
                side: 'party',
                armorClass: 15,
                baseHitPoints: 20,
                currentHitPoints: 18,
                initiative: 12,
                roll: 12,
                initiativeModifier: 2,
                importData: {
                    warnings: ['Champ brut conservé.'],
                    raw: {
                        toolsProf: [],
                    },
                },
            },
        ]);

        const playersSync = vi.fn();
        const storage = createLocalStorageMock();

        globalThis.document = createPersistenceDocument();
        globalThis.localStorage = storage;

        const persistence = new EncounterPersistence(encounter, {
            playersPanel: {
                sync: playersSync,
            },
        });
        persistence.start();
        persistence.saveEncounter();

        expect(playersSync).toHaveBeenCalledWith({ notify: false });
        expect(storage.getItem(DND_INITIATIVE_TRACKER_STORAGE_KEY)).not.toBeNull();

        const snapshot = JSON.parse(storage.getItem(DND_INITIATIVE_TRACKER_STORAGE_KEY));

        expect(snapshot.version).toBe(ENCOUNTER_SNAPSHOT_VERSION);
        expect(snapshot.players).toHaveLength(1);
        expect(snapshot.players[0]).toMatchObject({
            id: 'player-1',
            name: 'Lia',
            importData: {
                warnings: ['Champ brut conservé.'],
                raw: {
                    toolsProf: [],
                },
            },
        });
        expect(globalThis.document.getElementById('encounterPersistenceStatus').textContent)
            .toContain('Derniere sauvegarde locale');
        expect(globalThis.document.getElementById('restoreEncounterSnapshot').disabled).toBe(false);
    });

    test('prompts for restore and hydrates the encounter from the stored snapshot', () => {
        const encounter = new EncounterState({ bestiary: bestiarySample });
        const sourceEncounter = new EncounterState({ bestiary: bestiarySample });
        sourceEncounter.createMonsterSlots(1);
        sourceEncounter.selectMonster(0, 'acolyte');
        sourceEncounter.rollMonsterInitiatives(() => 12);
        sourceEncounter.setPlayers([
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
                initiativeModifier: 2,
                importData: {
                    warnings: ['Champ brut conservé.'],
                    raw: {
                        toolsProf: [],
                    },
                },
            },
        ]);
        sourceEncounter.buildRoundOrder();

        const snapshot = createEncounterSnapshotDto(sourceEncounter, {
            savedAt: '2026-05-25T09:00:00.000Z',
        });
        const storage = createLocalStorageMock({
            [DND_INITIATIVE_TRACKER_STORAGE_KEY]: JSON.stringify(snapshot),
        });
        const playersHydrate = vi.fn();
        const rulesSync = vi.fn();
        const monstersRefresh = vi.fn();
        const turnOrderRefresh = vi.fn();

        globalThis.document = createPersistenceDocument();
        globalThis.localStorage = storage;

        const persistence = new EncounterPersistence(encounter, {
            playersPanel: {
                hydrateFromEncounter: playersHydrate,
            },
            rulesPanel: {
                sync: rulesSync,
            },
            monstersPanel: {
                refresh: monstersRefresh,
            },
            turnOrderPanel: {
                refresh: turnOrderRefresh,
            },
        });
        persistence.start();

        expect(globalThis.document.getElementById('encounterRestoreModal').hidden).toBe(false);
        expect(globalThis.document.getElementById('encounterPersistenceStatus').textContent)
            .toContain('Derniere sauvegarde locale');

        globalThis.document.getElementById('encounterRestoreLoad').dispatchEvent({
            type: 'click',
        });

        expect(encounter.players).toHaveLength(1);
        expect(encounter.players[0]).toMatchObject({
            id: 'player-1',
            name: 'Lia',
            side: 'ally',
        });
        expect(playersHydrate).toHaveBeenCalledOnce();
        expect(rulesSync).toHaveBeenCalledOnce();
        expect(monstersRefresh).toHaveBeenCalledOnce();
        expect(turnOrderRefresh).toHaveBeenCalledOnce();
        expect(globalThis.document.getElementById('encounterRestoreModal').hidden).toBe(true);
    });

    test('rejects a snapshot with an unsupported version', () => {
        const storage = createLocalStorageMock({
            [DND_INITIATIVE_TRACKER_STORAGE_KEY]: JSON.stringify({
                version: 999,
                savedAt: '2026-05-25T09:00:00.000Z',
                monsters: [],
                players: [],
                rules: {},
                turnOrder: [],
                currentRound: 1,
                activeTurnId: null,
            }),
        });

        globalThis.document = createPersistenceDocument();
        globalThis.localStorage = storage;

        const persistence = new EncounterPersistence(new EncounterState());
        persistence.start();

        expect(globalThis.document.getElementById('encounterPersistenceErrorModal').hidden).toBe(false);
        expect(globalThis.document.getElementById('encounterPersistenceErrorMessage').value)
            .toContain('Version attendue');
        expect(globalThis.document.getElementById('restoreEncounterSnapshot').disabled).toBe(true);
    });

    test('surfaces localStorage write failures in the error modal', () => {
        const encounter = new EncounterState();
        encounter.setPlayers([
            {
                id: 'player-1',
                type: 'player',
                name: 'Lia',
                side: 'party',
                armorClass: 15,
                baseHitPoints: 20,
                currentHitPoints: 18,
                initiative: 12,
                roll: 12,
                initiativeModifier: 2,
            },
        ]);

        const playersSync = vi.fn();
        const storage = createLocalStorageMock({}, {
            setItem: () => {
                throw new Error('QuotaExceededError');
            },
        });

        globalThis.document = createPersistenceDocument();
        globalThis.localStorage = storage;

        const persistence = new EncounterPersistence(encounter, {
            playersPanel: {
                sync: playersSync,
            },
        });
        persistence.start();
        persistence.saveEncounter();

        expect(playersSync).toHaveBeenCalledWith({ notify: false });
        expect(globalThis.document.getElementById('encounterPersistenceErrorModal').hidden).toBe(false);
        expect(globalThis.document.getElementById('encounterPersistenceErrorMessage').value)
            .toContain('Impossible de sauvegarder la rencontre');
    });
});

function createPersistenceDocument() {
    const status = new TestElement('p');
    const restoreButton = new TestElement('button');
    restoreButton.disabled = true;
    const restoreModal = new TestElement('div');
    const restoreContent = new TestElement('section');
    const restoreSummary = new TestElement('p');
    const restoreLoadButton = new TestElement('button');
    const restoreCloseButton = new TestElement('button');
    const restoreBackdrop = new TestElement('div');
    const errorModal = new TestElement('div');
    const errorContent = new TestElement('section');
    const errorMessage = new TestElement('textarea');
    const errorCloseButton = new TestElement('button');
    const errorBackdrop = new TestElement('div');

    restoreModal.hidden = true;
    restoreModal.querySelector = selector => selector === '.dnd-persistence-modal__content'
        ? restoreContent
        : null;
    restoreModal.querySelectorAll = selector => selector === '[data-persistence-close]'
        ? [restoreBackdrop, restoreCloseButton]
        : [];

    errorModal.hidden = true;
    errorModal.querySelector = selector => selector === '.dnd-persistence-modal__content'
        ? errorContent
        : null;
    errorModal.querySelectorAll = selector => selector === '[data-persistence-error-close]'
        ? [errorBackdrop, errorCloseButton]
        : [];

    return {
        documentElement: {
            lang: 'fr',
        },
        addEventListener: () => {},
        getElementById: id => ({
            encounterPersistenceStatus: status,
            restoreEncounterSnapshot: restoreButton,
            encounterRestoreModal: restoreModal,
            encounterRestoreSummary: restoreSummary,
            encounterRestoreLoad: restoreLoadButton,
            encounterPersistenceErrorModal: errorModal,
            encounterPersistenceErrorMessage: errorMessage,
        })[id] ?? null,
    };
}

function createLocalStorageMock(initialEntries = {}, overrides = {}) {
    const entries = new Map(Object.entries(initialEntries));

    return {
        getItem: key => entries.has(key) ? entries.get(key) : null,
        setItem: overrides.setItem ?? ((key, value) => {
            entries.set(key, String(value));
        }),
        removeItem: overrides.removeItem ?? (key => {
            entries.delete(key);
        }),
    };
}
