import '../../../styles/lab/dnd/lab_dnd_initiative.css';

import {
    createMonsterSlots,
    getMonsterActors,
    hasSelectedMonsters,
    renderMonsters,
    rollMonsterInitiatives,
    syncMonsterHitPointsFromDom,
} from './monsters.js';

import {
    bindExistingPlayerRemoveButtons,
    createPlayerItem,
    getPlayerActors,
} from './players.js';

import {
    buildRoundOrder,
    renderRoundOrder,
} from './turn-order.js';

import { initializeRulesPanel } from './rules.js';

import {
    clearValidationState,
    focusFirstInvalidField,
    hasValidationErrors,
    mergeValidationResults,
    showValidationErrors,
    validateEncounterActors,
    validateMonsterCountInput,
    validateMonsterHitPointsInput,
    validatePlayerItem,
} from './validation.js';

const monsterCountInput = document.getElementById('monsterCount');
const createMonstersButton = document.getElementById('createMonsters');
const rollInitiativeButton = document.getElementById('rollInitiative');
const monsterPanel = document.querySelector('.dnd-panel--monsters');
const monsterList = document.getElementById('monsterList');
const monsterValidationSummary = document.getElementById('monsterValidationSummary');

const addPlayerButton = document.getElementById('addPlayer');
const playerPanel = document.querySelector('.dnd-panel--players');
const playerList = document.getElementById('playerList');
const playerValidationSummary = document.getElementById('playerValidationSummary');

const generateTurnOrderButton = document.getElementById('generateTurnOrder');
const turnOrderPanel = document.querySelector('.dnd-panel--turn-order');
const turnOrderValidationSummary = document.getElementById('turnOrderValidationSummary');
const turnOrderPlaceholder = document.getElementById('turnOrderPlaceholder');
const turnOrderList = document.getElementById('turnOrderList');

function updateRollInitiativeButtonState() {
    rollInitiativeButton.disabled = !hasSelectedMonsters();
}

function refreshMonsters() {
    renderMonsters(monsterList, () => {
        updateRollInitiativeButtonState();
        refreshMonsters();
    });
}

createMonstersButton.addEventListener('click', () => {
    clearValidationState(monsterPanel);

    const count = Number(monsterCountInput.value);
    const validationResult = validateMonsterCountInput(monsterCountInput);

    showValidationErrors(
        validationResult,
        monsterValidationSummary,
        'La liste de monstres contient une erreur.',
    );

    if (hasValidationErrors(validationResult)) {
        focusFirstInvalidField(validationResult);
        return;
    }

    createMonsterSlots(count);

    rollInitiativeButton.disabled = true;
    refreshMonsters();
});

rollInitiativeButton.addEventListener('click', () => {
    rollMonsterInitiatives();
    refreshMonsters();
});

addPlayerButton.addEventListener('click', () => {
    playerList.appendChild(createPlayerItem());
});

generateTurnOrderButton.addEventListener('click', () => {
    clearValidationState(monsterPanel);
    clearValidationState(playerPanel);
    clearValidationState(turnOrderPanel);

    const encounterValidationResult = validateEncounterActors(monsterList, playerList);

    const monsterValidationResult = mergeValidationResults(
        validateMonsterCountInput(monsterCountInput),
        ...getMonsterHitPointValidationResults(),
    );

    const playerValidationResult = mergeValidationResults(
        ...getPlayerValidationResults(),
    );

    showValidationErrors(
        monsterValidationResult,
        monsterValidationSummary,
        'La liste de monstres contient une erreur.',
    );
    showValidationErrors(
        encounterValidationResult,
        turnOrderValidationSummary,
        'Impossible de générer le tour de table.',
    );
    showValidationErrors(
        playerValidationResult,
        playerValidationSummary,
        'La liste de joueurs contient une erreur.',
    );

    if (
        hasValidationErrors(
            monsterValidationResult,
            encounterValidationResult,
            playerValidationResult,
        )
    ) {
        focusFirstInvalidField(
            monsterValidationResult,
            playerValidationResult,
        );
        return;
    }

    syncMonsterHitPointsFromDom(monsterList);

    buildRoundOrder(
        getMonsterActors(),
        getPlayerActors(playerList),
    );

    renderRoundOrder(turnOrderList, turnOrderPlaceholder);
});

bindExistingPlayerRemoveButtons(playerList);
initializeRulesPanel();

function getMonsterHitPointValidationResults() {
    return Array.from(monsterList.querySelectorAll('.monster-item'))
        .map((monsterItem, index) => validateMonsterHitPointsInput(monsterItem, index));
}

function getPlayerValidationResults() {
    return Array.from(playerList.querySelectorAll('.player-item'))
        .map((playerItem, index) => validatePlayerItem(playerItem, index));
}
