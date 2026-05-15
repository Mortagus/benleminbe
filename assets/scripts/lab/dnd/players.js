import { setPlayers } from './encounter-state.js';
import {
    clearValidationState,
    mergeValidationResults,
    showValidationErrors,
    validatePlayerItem,
} from './validation.js';

export function initializePlayersPanel(encounter, callbacks = {}) {
    const addPlayerButton = document.getElementById('addPlayer');
    const playerPanel = document.querySelector('.dnd-panel--players');
    const playerList = document.getElementById('playerList');
    const playerValidationSummary = document.getElementById('playerValidationSummary');

    function sync() {
        setPlayers(encounter, getPlayerActors(playerList));
        callbacks.onPlayersChange?.();
    }

    addPlayerButton.addEventListener('click', () => {
        playerList.appendChild(createPlayerItem(sync));
        sync();
    });

    bindExistingPlayerItems(playerList, sync);
    sync();

    function validateForTurnOrder() {
        const validationResult = mergeValidationResults(
            ...getPlayerValidationResults(),
        );

        showValidationErrors(
            validationResult,
            playerValidationSummary,
            'La liste de joueurs contient une erreur.',
        );

        return validationResult;
    }

    function getPlayerValidationResults() {
        return Array.from(playerList.querySelectorAll('.player-item'))
            .map((playerItem, index) => validatePlayerItem(playerItem, index));
    }

    return {
        clearValidation: () => clearValidationState(playerPanel),
        getListElement: () => playerList,
        sync,
        validateForTurnOrder,
    };
}

export function createPlayerItem(onPlayerListChange) {
    const template = document.getElementById('playerItemTemplate');

    if (!template) {
        throw new Error('Template #playerItemTemplate introuvable.');
    }

    const fragment = template.content.cloneNode(true);
    const playerItem = fragment.querySelector('.player-item');

    bindPlayerItemEvents(playerItem, onPlayerListChange);

    return playerItem;
}

export function bindExistingPlayerItems(playerList, onPlayerListChange) {
    const playerItems = playerList.querySelectorAll('.player-item');

    playerItems.forEach(playerItem => bindPlayerItemEvents(playerItem, onPlayerListChange));
}

export function getPlayerActors(playerList) {
    const playerItems = playerList.querySelectorAll('.player-item');

    return Array.from(playerItems)
        .filter(playerItem => hasStartedPlayer(playerItem))
        .map((item, index) => {
            const nameInput = getPlayerInput(item, 'name');
            const armorClassInput = getPlayerInput(item, 'armor-class');
            const currentHitPointsInput = getPlayerInput(item, 'current-hit-points');
            const baseHitPointsInput = getPlayerInput(item, 'base-hit-points');
            const initiativeInput = getPlayerInput(item, 'initiative');

            return {
                id: `player-${index + 1}`,
                type: 'player',
                name: nameInput?.value || `Joueur ${index + 1}`,
                armorClass: Number(armorClassInput?.value || 0),
                currentHitPoints: Number(currentHitPointsInput?.value || 0),
                baseHitPoints: Number(baseHitPointsInput?.value || 0),
                initiative: Number(initiativeInput?.value || 0),
                done: false,
            };
        })
        .filter(actor => actor.name.trim() !== '');
}

function bindPlayerItemEvents(playerItem, onPlayerListChange) {
    const removeButton = playerItem.querySelector('.player-remove-button');

    removeButton.addEventListener('click', () => {
        playerItem.remove();
        onPlayerListChange();
    });

    playerItem.querySelectorAll('input').forEach(input => {
        input.addEventListener('input', () => {
            onPlayerListChange();
        });
    });
}

function getPlayerInput(playerItem, fieldName) {
    return playerItem.querySelector(`[data-player-field="${fieldName}"]`);
}

function hasStartedPlayer(playerItem) {
    return Array.from(playerItem.querySelectorAll('input'))
        .some(input => input.value.trim() !== '' || input.validity?.badInput);
}
