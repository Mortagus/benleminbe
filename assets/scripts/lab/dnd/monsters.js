// DOM controller for the monsters panel.
// It renders monster slots from encounter state and sends user changes back
// through encounter-state helpers.
import { bestiary } from './bestiary.js';
import { formatInitiative, getInitiativeClass } from './initiative.js';
import {
    createMonsterSlots,
    hasSelectedMonsters,
    rollMonsterInitiatives,
    selectMonster,
    updateMonsterHitPoints,
} from './encounter-state.js';
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

export function initializeMonstersPanel(encounter, callbacks = {}) {
    const monsterCountInput = document.getElementById('monsterCount');
    const createMonstersButton = document.getElementById('createMonsters');
    const rollInitiativeButton = document.getElementById('rollInitiative');
    const monsterPanel = document.querySelector('.dnd-panel--monsters');
    const monsterList = document.getElementById('monsterList');
    const monsterValidationSummary = document.getElementById('monsterValidationSummary');

    function refreshRollInitiativeButtonState() {
        rollInitiativeButton.disabled = !hasSelectedMonsters(encounter);
    }

    function refresh() {
        renderMonsters(monsterList, encounter.monsters, {
            onMonsterSelectionChange: (index, selectedSlug) => {
                selectMonster(encounter, index, selectedSlug);
                refreshRollInitiativeButtonState();
                refresh();
                callbacks.onEncounterChange?.();
            },
            onMonsterHitPointsChange: (index, hitPoints) => {
                updateMonsterHitPoints(encounter, index, hitPoints);
                callbacks.onEncounterChange?.();
            },
        });
    }

    createMonstersButton.addEventListener('click', () => {
        clearValidationState(monsterPanel);

        const count = Number(monsterCountInput.value);
        const validationResult = validateMonsterCountInput(monsterCountInput);

        showMonsterValidationErrors(validationResult);

        if (hasValidationErrors(validationResult)) {
            focusFirstInvalidField(validationResult);
            return;
        }

        createMonsterSlots(encounter, count);

        rollInitiativeButton.disabled = true;
        refresh();
        callbacks.onEncounterChange?.();
    });

    rollInitiativeButton.addEventListener('click', () => {
        rollMonsterInitiatives(encounter);
        refresh();
        callbacks.onEncounterChange?.();
    });

    function validateForTurnOrder() {
        const validationResult = mergeValidationResults(
            validateMonsterCountInput(monsterCountInput),
            ...getMonsterHitPointValidationResults(),
        );

        showMonsterValidationErrors(validationResult);

        return validationResult;
    }

    function showMonsterValidationErrors(validationResult) {
        showValidationErrors(
            validationResult,
            monsterValidationSummary,
            'Un monstre contient une erreur.',
        );
    }

    function getMonsterHitPointValidationResults() {
        return Array.from(monsterList.querySelectorAll('.monster-item'))
            .map((monsterItem, index) => validateMonsterHitPointsInput(monsterItem, index));
    }

    return {
        clearValidation: () => clearValidationState(monsterPanel),
        getListElement: () => monsterList,
        refresh,
        validateForTurnOrder,
    };
}

export function renderMonsters(monsterList, monsters, callbacks) {
    const monsterItems = document.createDocumentFragment();

    monsters.forEach((monster, index) => {
        monsterItems.appendChild(renderMonsterItem(monster, index, callbacks));
    });

    monsterList.replaceChildren(monsterItems);
}

function renderMonsterItem(monster, index, callbacks) {
    const fragment = monsterItemTemplate.content.cloneNode(true);
    const monsterItem = fragment.querySelector('.monster-item');

    populateMonsterItem(monsterItem, monster, index);
    bindMonsterItemEvents(monsterItem, index, callbacks);

    return monsterItem;
}

function populateMonsterItem(monsterItem, monster, index) {
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
    renderMonsterOptions(select, monster.slug);

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

function renderMonsterOptions(select, selectedSlug) {
    const placeholderOption = document.createElement('option');
    placeholderOption.value = '';
    placeholderOption.textContent = 'Choisir';

    const options = [placeholderOption];

    const monstersByType = Map.groupBy(
        [...bestiary].sort((firstMonster, secondMonster) =>
            firstMonster.name.localeCompare(secondMonster.name, 'fr', { sensitivity: 'base' })
        ),
        monster => monster.type,
    );

    Array.from(monstersByType.entries())
        .sort(([firstType], [secondType]) =>
            firstType.localeCompare(secondType, 'fr', { sensitivity: 'base' })
        )
        .forEach(([type, monsters]) => {
            const optgroup = document.createElement('optgroup');
            optgroup.label = type;

            monsters.forEach(monster => {
                const option = monsterOptionTemplate.content
                    .cloneNode(true)
                    .querySelector('option');

                option.value = monster.slug;
                option.textContent = monster.name + ' (FP: ' + monster.challenge_rating + ')';
                option.selected = monster.slug === selectedSlug;

                optgroup.appendChild(option);
            });

            options.push(optgroup);
        });

    select.replaceChildren(...options);
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
