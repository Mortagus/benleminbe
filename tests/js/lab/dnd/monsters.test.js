import { afterEach, beforeEach, describe, expect, test, vi } from 'vitest';
import { EncounterState } from '../../../../assets/scripts/lab/dnd/encounter-state.js';
import {
    createDocumentDouble,
    createInput,
    createMonsterItemTemplate,
    createMonsterOptionTemplate,
    TestElement,
} from './dom-test-helpers.js';

const originalMapGroupBy = Map.groupBy;

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
        );

        const monsterItem = monsterList.children[0];
        const select = monsterItem.querySelector('.monster-select');
        const hitPointsInput = monsterItem.querySelector('.monster-hp input');

        expect(monsterList.children).toHaveLength(1);
        expect(monsterItem.querySelector('.monster-type').textContent).toBe('Humanoïde');
        expect(monsterItem.querySelector('.monster-size').textContent).toBe('Taille: M');
        expect(monsterItem.querySelector('.monster-cr').textContent).toBe('FP: 1/4');
        expect(monsterItem.querySelector('.monster-armor-class').textContent).toBe('CA 10');
        expect(hitPointsInput.value).toBe('7');
        expect(hitPointsInput.max).toBe('9');
        expect(monsterItem.querySelector('.monster-hit-points-max').textContent).toBe('9');
        expect(monsterItem.querySelector('.monster-initiative').textContent).toBe('Init. 12');

        select.value = 'aarakocra';
        select.dispatchEvent({ type: 'change' });
        hitPointsInput.value = '6';
        hitPointsInput.dispatchEvent({ type: 'input' });

        expect(onMonsterSelectionChange).toHaveBeenCalledWith(0, 'aarakocra');
        expect(onMonsterHitPointsChange).toHaveBeenCalledWith(0, '6');
    });

    test('keeps initializeMonstersPanel as a compatibility wrapper', async () => {
        const { initializeMonstersPanel, MonstersPanel } = await import('../../../../assets/scripts/lab/dnd/monsters.js');
        const encounter = new EncounterState();
        const onEncounterChange = vi.fn();

        globalThis.document = createMonstersDocument();

        const panel = initializeMonstersPanel(encounter, {
            onEncounterChange,
        });

        expect(panel).toBeInstanceOf(MonstersPanel);
        expect(onEncounterChange).not.toHaveBeenCalled();
    });
});

function createMonstersDocument() {
    const monsterCountInput = createInput('');
    const createMonstersButton = new TestElement('button');
    const rollInitiativeButton = new TestElement('button');
    const monsterPanel = new TestElement('section', ['dnd-panel--monsters']);
    const monsterList = new TestElement('ul');
    const monsterValidationSummary = new TestElement('div', ['dnd-validation-summary']);

    return {
        ...createDocumentDouble({
            monsterCount: monsterCountInput,
            createMonsters: createMonstersButton,
            rollInitiative: rollInitiativeButton,
            monsterList,
            monsterValidationSummary,
            monsterItemTemplate: createMonsterItemTemplate(),
            monsterOptionTemplate: createMonsterOptionTemplate(),
        }),
        querySelector: selector => selector === '.dnd-panel--monsters' ? monsterPanel : null,
    };
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
