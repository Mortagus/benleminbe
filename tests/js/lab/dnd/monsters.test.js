import { afterEach, beforeEach, describe, expect, test, vi } from 'vitest';
import { bestiarySample } from '../../../fixtures/dnd/bestiary-sample.js';
import { EncounterState } from '../../../../assets/scripts/lab/dnd/encounter-state.js';
import {
    createDocumentDouble,
    createInput,
    createMonsterItemTemplate,
    createMonsterOptionTemplate,
    TestElement,
} from './dom-test-helpers.js';

const originalMapGroupBy = Map.groupBy;
const originalHTMLElement = globalThis.HTMLElement;

describe('monsters panel rendering', () => {
    beforeEach(() => {
        vi.resetModules();
        Map.groupBy ??= groupBy;
        globalThis.document = createDocumentDouble({
            monsterItemTemplate: createMonsterItemTemplate(),
            monsterOptionTemplate: createMonsterOptionTemplate(),
        });
    });

    afterEach(() => {
        Map.groupBy = originalMapGroupBy;
        if (originalHTMLElement) {
            globalThis.HTMLElement = originalHTMLElement;
        } else {
            delete globalThis.HTMLElement;
        }
        delete globalThis.document;
    });

    test('renders selected monster data and reports row changes', async () => {
        const { renderMonsters } = await import('../../../../assets/scripts/lab/dnd/monsters.js');
        const monsterList = new TestElement('ul');
        const onMonsterSelectionChange = vi.fn();
        const onMonsterHitPointsChange = vi.fn();

        renderMonsters(
            monsterList,
            [
                {
                    id: 'acolyte-1',
                    slug: 'acolyte',
                    name: 'Acolyte 1',
                    challengeRating: '1/4',
                    type: 'Humanoïde',
                    size: 'M',
                    alignment: 'neutre bon',
                    isLegendary: true,
                    armorClass: 10,
                    baseHitPoints: 9,
                    currentHitPoints: 7,
                    initiativeModifier: 0,
                    roll: 12,
                    initiative: 12,
                },
            ],
            {
                onMonsterSelectionChange,
                onMonsterHitPointsChange,
            },
            {
                catalog: [
                    createCatalogMonster({
                        slug: 'custom-brute',
                        name: 'Brute custom',
                        type: 'Brute',
                        challenge_rating: '2',
                    }),
                    createCatalogMonster({
                        slug: 'custom-beast',
                        name: 'Bete custom',
                        type: 'Bete',
                        challenge_rating: '1/2',
                    }),
                ],
            },
        );

        const monsterItem = monsterList.children[0];
        const select = monsterItem.querySelector('.monster-select');
        const hitPointsInput = monsterItem.querySelector('.monster-hp input');

        expect(monsterList.children).toHaveLength(1);
        expect(monsterItem.querySelector('.monster-type').textContent).toBe('Humanoïde');
        expect(monsterItem.querySelector('.monster-size').textContent).toBe('Taille: M');
        expect(monsterItem.querySelector('.monster-cr').textContent).toBe('FP: 1/4');
        expect(monsterItem.querySelector('.monster-alignment').textContent).toBe('Alignement: neutre bon');
        expect(monsterItem.querySelector('.monster-legendary').hidden).toBe(false);
        expect(monsterItem.querySelector('.monster-legendary').textContent).toBe('Légendaire');
        expect(monsterItem.querySelector('.monster-armor-class').textContent).toBe('CA 10');
        expect(hitPointsInput.value).toBe('7');
        expect(hitPointsInput.max).toBe('9');
        expect(monsterItem.querySelector('.monster-hit-points-max').textContent).toBe('9');
        expect(monsterItem.querySelector('.monster-initiative').textContent).toBe('Init. 12');
        expect(select.children.map(child => child.label ?? child.textContent)).toEqual([
            'Choisir',
            'Bete',
            'Brute',
        ]);

        select.value = 'aarakocra';
        select.dispatchEvent({ type: 'change' });
        hitPointsInput.value = '6';
        hitPointsInput.dispatchEvent({ type: 'input' });

        expect(onMonsterSelectionChange).toHaveBeenCalledWith(0, 'aarakocra');
        expect(onMonsterHitPointsChange).toHaveBeenCalledWith(0, '6');
    });

    test('starts the panel without changing the encounter immediately', async () => {
        const { MonstersPanel } = await import('../../../../assets/scripts/lab/dnd/monsters.js');
        const encounter = new EncounterState();
        const onEncounterChange = vi.fn();

        globalThis.document = createMonstersDocument();

        const panel = new MonstersPanel(encounter, {
            onEncounterChange,
        });
        panel.start();

        expect(panel).toBeInstanceOf(MonstersPanel);
        expect(onEncounterChange).not.toHaveBeenCalled();
    });

    test('creates monster slots from a valid count', async () => {
        const { MonstersPanel } = await import('../../../../assets/scripts/lab/dnd/monsters.js');
        const encounter = new EncounterState({ bestiary: bestiarySample });
        const onEncounterChange = vi.fn();
        const documentDouble = createMonstersDocument();

        globalThis.document = documentDouble;

        const panel = new MonstersPanel(encounter, {
            onEncounterChange,
        });
        panel.start();

        documentDouble.elements.monsterCountInput.value = '2';
        documentDouble.elements.createMonstersButton.dispatchEvent({ type: 'click' });

        expect(encounter.monsters).toHaveLength(2);
        expect(documentDouble.elements.monsterList.children).toHaveLength(2);
        expect(documentDouble.elements.rollInitiativeButton.disabled).toBe(true);
        expect(onEncounterChange).toHaveBeenCalledOnce();
    });

    test('filters monster options by search and type while keeping selected monsters visible', async () => {
        const { MonstersPanel } = await import('../../../../assets/scripts/lab/dnd/monsters.js');
        const encounter = new EncounterState({
            bestiary: [
                createCatalogMonster({
                    slug: 'acolyte',
                    name: 'Acolyte',
                    type: 'Humanoïde',
                    challenge_rating: '1/4',
                }),
                createCatalogMonster({
                    slug: 'aboleth',
                    name: 'Aboleth',
                    type: 'Aberration',
                    challenge_rating: '10',
                }),
                createCatalogMonster({
                    slug: 'bandit',
                    name: 'Bandit',
                    type: 'Humanoïde',
                    challenge_rating: '1/8',
                }),
            ],
        });
        const documentDouble = createMonstersDocument();

        encounter.createMonsterSlots(1);
        encounter.selectMonster(0, 'aboleth');
        globalThis.document = documentDouble;

        const panel = new MonstersPanel(encounter);
        panel.start();

        documentDouble.elements.monsterSearchInput.value = 'aco';
        documentDouble.elements.monsterTypeFilter.value = 'Humanoïde';
        panel.refresh();

        const select = documentDouble.elements.monsterList.children[0]
            .querySelector('.monster-select');
        const groupLabels = select.children.map(child => child.label ?? child.textContent);

        expect(documentDouble.elements.monsterTypeFilter.children.map(option => option.textContent)).toEqual([
            'Tous',
            'Aberration',
            'Humanoïde',
        ]);
        expect(groupLabels).toEqual([
            'Choisir',
            'Aberration',
            'Humanoïde',
        ]);
        expect(select.children[1].children.map(option => option.value)).toEqual(['aboleth']);
        expect(select.children[2].children.map(option => option.value)).toEqual(['acolyte']);
    });

    test('keeps the encounter unchanged when the monster count is invalid', async () => {
        globalThis.HTMLElement = TestElement;

        const { MonstersPanel } = await import('../../../../assets/scripts/lab/dnd/monsters.js');
        const encounter = new EncounterState({ bestiary: bestiarySample });
        const onEncounterChange = vi.fn();
        const documentDouble = createMonstersDocument();

        globalThis.document = documentDouble;

        const panel = new MonstersPanel(encounter, {
            onEncounterChange,
        });
        panel.start();

        documentDouble.elements.monsterCountInput.value = '31';
        documentDouble.elements.createMonstersButton.dispatchEvent({ type: 'click' });

        expect(encounter.monsters).toHaveLength(0);
        expect(documentDouble.elements.monsterValidationSummary.hidden).toBe(false);
        expect(documentDouble.elements.monsterCountInput.classList.contains('dnd-field--invalid')).toBe(true);
        expect(documentDouble.elements.monsterCountInput.wasFocused).toBe(true);
        expect(onEncounterChange).not.toHaveBeenCalled();
    });

    test('plays monster initiative feedback without blocking the roll', async () => {
        const { MonstersPanel } = await import('../../../../assets/scripts/lab/dnd/monsters.js');
        const encounter = new EncounterState({ bestiary: bestiarySample });
        const onEncounterChange = vi.fn();
        const onMonsterInitiativeRoll = vi.fn(() => new Promise(resolve => {
            onMonsterInitiativeRoll.resolve = resolve;
        }));
        const documentDouble = createMonstersDocument();

        encounter.createMonsterSlots(1);
        encounter.selectMonster(0, 'acolyte');
        globalThis.document = documentDouble;

        const panel = new MonstersPanel(encounter, {
            onEncounterChange,
            onMonsterInitiativeRoll,
        });
        panel.start();

        await panel.handleRollInitiative({ delayMs: 0 });

        expect(onMonsterInitiativeRoll).toHaveBeenCalledOnce();
        expect(encounter.monsters[0].initiative).not.toBeNull();
        expect(onEncounterChange).toHaveBeenCalledOnce();
        expect(documentDouble.elements.rollInitiativeButton.classList.contains('dnd-button--audio-loading')).toBe(true);
        expect(documentDouble.elements.rollInitiativeButton.getAttribute('aria-busy')).toBe('true');

        onMonsterInitiativeRoll.resolve();
        await Promise.resolve();

        expect(documentDouble.elements.rollInitiativeButton.classList.contains('dnd-button--audio-loading')).toBe(false);
        expect(documentDouble.elements.rollInitiativeButton.getAttribute('aria-busy')).toBe(null);
    });

    test('rolls selected monster initiatives one by one before sorting', async () => {
        const { MonstersPanel } = await import('../../../../assets/scripts/lab/dnd/monsters.js');
        const encounter = new EncounterState({ bestiary: bestiarySample });
        const documentDouble = createMonstersDocument();
        const onEncounterChange = vi.fn();
        const rolls = [5, 20];

        encounter.createMonsterSlots(2);
        encounter.selectMonster(0, 'acolyte');
        encounter.selectMonster(1, 'aarakocra');
        globalThis.document = documentDouble;

        const panel = new MonstersPanel(encounter, {
            onEncounterChange,
        });
        panel.start();

        await panel.handleRollInitiative({
            delayMs: 0,
            roll: () => rolls.shift(),
        });

        expect(encounter.monsters.map(monster => monster.slug)).toEqual([
            'aarakocra',
            'acolyte',
        ]);
        expect(encounter.monsters.map(monster => monster.roll)).toEqual([20, 5]);
        expect(onEncounterChange).toHaveBeenCalledOnce();
    });
});

function createMonstersDocument() {
    const monsterCountInput = createInput('');
    const monsterSearchInput = createInput('');
    const monsterTypeFilter = new TestElement('select');
    const createMonstersButton = new TestElement('button');
    const rollInitiativeButton = new TestElement('button');
    const monsterPanel = new TestElement('section', ['dnd-panel--monsters']);
    const monsterList = new TestElement('ul');
    const monsterValidationSummary = new TestElement('div', ['dnd-validation-summary']);

    const documentDouble = {
        ...createDocumentDouble({
            monsterCount: monsterCountInput,
            monsterSearch: monsterSearchInput,
            monsterTypeFilter,
            createMonsters: createMonstersButton,
            rollInitiative: rollInitiativeButton,
            monsterList,
            monsterValidationSummary,
            monsterItemTemplate: createMonsterItemTemplate(),
            monsterOptionTemplate: createMonsterOptionTemplate(),
        }),
        querySelector: selector => selector === '.dnd-panel--monsters' ? monsterPanel : null,
    };

    documentDouble.elements = {
        monsterCountInput,
        monsterSearchInput,
        monsterTypeFilter,
        createMonstersButton,
        rollInitiativeButton,
        monsterPanel,
        monsterList,
        monsterValidationSummary,
    };

    return documentDouble;
}

function groupBy(items, callback) {
    const groups = new Map();

    items.forEach(item => {
        const key = callback(item);
        const group = groups.get(key) ?? [];
        group.push(item);
        groups.set(key, group);
    });

    return groups;
}

function createCatalogMonster(overrides) {
    return {
        slug: 'monster',
        name: 'Monster',
        type: 'Type',
        challenge_rating: '0',
        ...overrides,
    };
}
