// DOM controller for the monsters panel.
// It renders monster slots from encounter state and sends user changes back
// through EncounterState methods.
import { bestiary } from './bestiary.js';
import { formatInitiative, getInitiativeClass } from './initiative.js';
import {
    clearValidationState,
    focusFirstInvalidField,
    hasValidationErrors,
    mergeValidationResults,
    showValidationErrors,
    validateMonsterCountInput,
    validateMonsterHitPointsInput,
} from './validation.js';

const monsterItemTemplate = document.getElementById('monsterItemTemplate');
const monsterOptionTemplate = document.getElementById('monsterOptionTemplate');

export class MonstersPanel {
    constructor(encounter, callbacks = {}) {
        this.encounter = encounter;
        this.callbacks = callbacks;
        this.monsterCountInput = document.getElementById('monsterCount');
        this.createMonstersButton = document.getElementById('createMonsters');
        this.rollInitiativeButton = document.getElementById('rollInitiative');
        this.monsterPanel = document.querySelector('.dnd-panel--monsters');
        this.monsterList = document.getElementById('monsterList');
        this.monsterValidationSummary = document.getElementById('monsterValidationSummary');
    }

    start() {
        this.createMonstersButton.addEventListener('click', () => {
            this.handleCreateMonsterSlots();
        });

        this.rollInitiativeButton.addEventListener('click', () => {
            this.handleRollInitiative();
        });
    }

    clearValidation() {
        clearValidationState(this.monsterPanel);
    }

    getListElement() {
        return this.monsterList;
    }

    refresh() {
        renderMonsters(this.monsterList, this.encounter.monsters, {
            onMonsterSelectionChange: (index, selectedSlug) => {
                this.handleMonsterSelectionChange(index, selectedSlug);
            },
            onMonsterHitPointsChange: (index, hitPoints) => {
                this.handleMonsterHitPointsChange(index, hitPoints);
            },
        }, {
            catalog: this.encounter.bestiary,
        });
    }

    validateForTurnOrder() {
        const validationResult = mergeValidationResults(
            validateMonsterCountInput(this.monsterCountInput),
            ...this.getMonsterHitPointValidationResults(),
        );

        this.showMonsterValidationErrors(validationResult);

        return validationResult;
    }

    handleCreateMonsterSlots() {
        this.clearValidation();

        const count = Number(this.monsterCountInput.value);
        const validationResult = validateMonsterCountInput(this.monsterCountInput);

        this.showMonsterValidationErrors(validationResult);

        if (hasValidationErrors(validationResult)) {
            focusFirstInvalidField(validationResult);
            return;
        }

        this.encounter.createMonsterSlots(count);

        this.rollInitiativeButton.disabled = true;
        this.refresh();
        this.callbacks.onEncounterChange?.();
    }

    handleRollInitiative() {
        this.playMonsterInitiativeSound();
        this.encounter.rollMonsterInitiatives();
        this.refresh();
        this.callbacks.onEncounterChange?.();
    }

    playMonsterInitiativeSound() {
        const soundFeedback = this.callbacks.onMonsterInitiativeRoll?.();

        if (!soundFeedback) {
            return;
        }

        this.setRollInitiativeAudioLoading(true);
        Promise.resolve(soundFeedback)
            .finally(() => {
                this.setRollInitiativeAudioLoading(false);
            });
    }

    setRollInitiativeAudioLoading(isLoading) {
        if (isLoading) {
            this.rollInitiativeButton.classList.add('dnd-button--audio-loading');
            this.rollInitiativeButton.setAttribute('aria-busy', 'true');
            return;
        }

        this.rollInitiativeButton.classList.remove('dnd-button--audio-loading');
        this.rollInitiativeButton.removeAttribute('aria-busy');
    }

    handleMonsterSelectionChange(index, selectedSlug) {
        this.encounter.selectMonster(index, selectedSlug);
        this.refreshRollInitiativeButtonState();
        this.refresh();
        this.callbacks.onEncounterChange?.();
    }

    handleMonsterHitPointsChange(index, hitPoints) {
        this.encounter.updateMonsterHitPoints(index, hitPoints);
        this.callbacks.onEncounterChange?.();
    }

    refreshRollInitiativeButtonState() {
        this.rollInitiativeButton.disabled = !this.encounter.hasSelectedMonsters();
    }

    showMonsterValidationErrors(validationResult) {
        showValidationErrors(
            validationResult,
            this.monsterValidationSummary,
            'Un monstre contient une erreur.',
        );
    }

    getMonsterHitPointValidationResults() {
        return Array.from(this.monsterList.querySelectorAll('.monster-item'))
            .map((monsterItem, index) => validateMonsterHitPointsInput(monsterItem, index));
    }
}

export function renderMonsters(monsterList, monsters, callbacks, options = {}) {
    const monsterItems = document.createDocumentFragment();
    const catalog = options.catalog ?? bestiary;

    monsters.forEach((monster, index) => {
        monsterItems.appendChild(renderMonsterItem(monster, index, callbacks, catalog));
    });

    monsterList.replaceChildren(monsterItems);
}

function renderMonsterItem(monster, index, callbacks, catalog) {
    const fragment = monsterItemTemplate.content.cloneNode(true);
    const monsterItem = fragment.querySelector('.monster-item');

    populateMonsterItem(monsterItem, monster, index, catalog);
    bindMonsterItemEvents(monsterItem, index, callbacks);

    return monsterItem;
}

function populateMonsterItem(monsterItem, monster, index, catalog) {
    const select = monsterItem.querySelector('.monster-select');
    const type = monsterItem.querySelector('.monster-type');
    const size = monsterItem.querySelector('.monster-size');
    const challengeRating = monsterItem.querySelector('.monster-cr');
    const armorClass = monsterItem.querySelector('.monster-armor-class');
    const hpInput = monsterItem.querySelector('.monster-hp input');
    const hpMax = monsterItem.querySelector('.monster-hit-points-max');
    const initiative = monsterItem.querySelector('.monster-initiative');
    const initiativeModifier = monsterItem.querySelector('.monster-initiative-modifier');

    select.dataset.index = String(index);
    select.setAttribute('aria-label', `Choisir le monstre ${index + 1}`);
    renderMonsterOptions(select, monster.slug, catalog);

    type.textContent = monster.type;
    size.textContent = 'Taille: ' + monster.size;
    challengeRating.textContent = 'FP: ' + monster.challengeRating;
    armorClass.textContent = `CA ${monster.armorClass}`;

    hpInput.max = String(monster.baseHitPoints);
    hpInput.value = String(monster.currentHitPoints);
    hpInput.disabled = monster.slug === null;
    hpInput.setAttribute('aria-label', `PV actuels du monstre ${index + 1}`);

    hpMax.textContent = String(monster.baseHitPoints);

    initiativeModifier.textContent = formatModifier(monster.initiativeModifier);
    initiativeModifier.title = `Mod. initiative : ${formatModifier(monster.initiativeModifier)}`;
    initiativeModifier.setAttribute(
        'aria-label',
        `Modificateur d’initiative : ${formatModifier(monster.initiativeModifier)}`
    );

    initiative.textContent = `Init. ${formatInitiative(monster)}`;
    initiative.title = getInitiativeTooltip(monster);

    const initiativeClass = getInitiativeClass(monster);

    if (initiativeClass !== '') {
        initiative.classList.add(initiativeClass);
    }
}

function getInitiativeTooltip(monster) {
    if (monster.roll === null) {
        return 'Initiative non lancée.';
    }

    const modifier = formatModifier(monster.initiativeModifier);
    const finalScore = formatInitiative(monster);

    let tooltip = [
        `D20 : ${monster.roll}`,
        `Modificateur : ${modifier}`,
        `Initiative : ${finalScore}`,
    ];

    if (monster.roll === 20) {
        tooltip.push('', 'Succès critique');
    } else if (monster.roll === 1) {
        tooltip.push('', 'Échec critique');
    }

    return tooltip.join('\n');
}

function formatModifier(value) {
    const modifier = Number(value);

    if (Number.isNaN(modifier)) {
        return '-';
    }

    return modifier > 0 ? `+${modifier}` : String(modifier);
}

function renderMonsterOptions(select, selectedSlug, catalog) {
    const options = [createMonsterPlaceholderOption()];

    getSortedMonsterGroups(catalog)
        .forEach(([type, monsters]) => {
            const optgroup = document.createElement('optgroup');
            optgroup.label = type;

            monsters.forEach(monster => {
                optgroup.appendChild(createMonsterOption(monster, selectedSlug));
            });

            options.push(optgroup);
        });

    select.replaceChildren(...options);
}

function getSortedMonsterGroups(catalog) {
    const monstersByType = Map.groupBy(
        [...catalog].sort((firstMonster, secondMonster) =>
            firstMonster.name.localeCompare(secondMonster.name, 'fr', { sensitivity: 'base' })
        ),
        monster => monster.type,
    );

    return Array.from(monstersByType.entries())
        .sort(([firstType], [secondType]) =>
            firstType.localeCompare(secondType, 'fr', { sensitivity: 'base' })
        );
}

function createMonsterPlaceholderOption() {
    const placeholderOption = document.createElement('option');
    placeholderOption.value = '';
    placeholderOption.textContent = 'Choisir';

    return placeholderOption;
}

function createMonsterOption(monster, selectedSlug) {
    const option = monsterOptionTemplate.content
        .cloneNode(true)
        .querySelector('option');

    option.value = monster.slug;
    option.textContent = monster.name + ' (FP: ' + monster.challenge_rating + ')';
    option.selected = monster.slug === selectedSlug;

    return option;
}

function bindMonsterItemEvents(monsterItem, index, callbacks) {
    const monsterSelect = monsterItem.querySelector('.monster-select');
    const hitPointsInput = monsterItem.querySelector('.monster-hp input');

    monsterSelect.addEventListener('change', event => {
        callbacks.onMonsterSelectionChange(index, event.target.value);
    });

    hitPointsInput?.addEventListener('input', event => {
        callbacks.onMonsterHitPointsChange(index, event.target.value);
    });
}
