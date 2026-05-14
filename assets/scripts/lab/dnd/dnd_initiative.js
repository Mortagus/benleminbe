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

const monsterCountInput = document.getElementById('monsterCount');
const createMonstersButton = document.getElementById('createMonsters');
const rollInitiativeButton = document.getElementById('rollInitiative');
const monsterList = document.getElementById('monsterList');

const addPlayerButton = document.getElementById('addPlayer');
const playerList = document.getElementById('playerList');

const generateTurnOrderButton = document.getElementById('generateTurnOrder');
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
    const count = Number(monsterCountInput.value);

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
    syncMonsterHitPointsFromDom(monsterList);

    buildRoundOrder(
        getMonsterActors(),
        getPlayerActors(playerList),
    );

    renderRoundOrder(turnOrderList, turnOrderPlaceholder);
});

bindExistingPlayerRemoveButtons(playerList);
