// DOM controller for the players panel.
// Player form rows are the temporary source of truth until sync() writes them
// into the encounter state before turn order generation.
import {
    clearValidationState,
    mergeValidationResults,
    showValidationErrors,
    validatePlayerItem,
} from './validation.js';

export class PlayersPanel {
    constructor(encounter, callbacks = {}) {
        this.encounter = encounter;
        this.callbacks = callbacks;
        this.addPlayerButton = document.getElementById('addPlayer');
        this.playerPanel = document.querySelector('.dnd-panel--players');
        this.playerList = document.getElementById('playerList');
        this.playerValidationSummary = document.getElementById('playerValidationSummary');
    }

    start() {
        this.addPlayerButton.addEventListener('click', () => {
            this.handleAddPlayer();
        });

        bindExistingPlayerItems(this.playerList, () => this.sync());
        this.sync();
    }

    clearValidation() {
        clearValidationState(this.playerPanel);
    }

    getListElement() {
        return this.playerList;
    }

    sync() {
        this.syncPlayerFormsToEncounter();
    }

    handleAddPlayer() {
        this.playerList.appendChild(createPlayerItem(() => this.sync()));
        this.sync();
    }

    syncPlayerFormsToEncounter() {
        refreshPlayerAccessibility(this.playerList);
        this.encounter.setPlayers(getPlayerActors(this.playerList));
        this.callbacks.onPlayersChange?.();
    }

    validateForTurnOrder() {
        const validationResult = mergeValidationResults(
            ...this.getPlayerValidationResults(),
        );

        showValidationErrors(
            validationResult,
            this.playerValidationSummary,
            'Un joueur contient une erreur.',
        );

        return validationResult;
    }

    getPlayerValidationResults() {
        return Array.from(this.playerList.querySelectorAll('.player-item'))
            .map((playerItem, index) => validatePlayerItem(playerItem, index));
    }
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

    playerItems.forEach(playerItem => {
        bindPlayerItemEvents(playerItem, onPlayerListChange);
    });
    refreshPlayerAccessibility(playerList);
}

// Boundary from player form rows to the encounter state shape.
// The future DTO pass should start from this mapping instead of reading DOM fields elsewhere.
export function getPlayerActors(playerList) {
    return getStartedPlayerForms(playerList)
        .map((playerForm, index) => createPlayerActor(playerForm, index))
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

function getStartedPlayerForms(playerList) {
    const playerItems = playerList.querySelectorAll('.player-item');

    return Array.from(playerItems)
        .filter(playerItem => hasStartedPlayer(playerItem))
        .map(readPlayerForm);
}

function readPlayerForm(playerItem) {
    return {
        name: readPlayerField(playerItem, 'name'),
        armorClass: readPlayerField(playerItem, 'armor-class'),
        currentHitPoints: readPlayerField(playerItem, 'current-hit-points'),
        baseHitPoints: readPlayerField(playerItem, 'base-hit-points'),
        initiative: readPlayerField(playerItem, 'initiative'),
    };
}

function createPlayerActor(playerForm, index) {
    const playerNumber = index + 1;

    return {
        id: `player-${playerNumber}`,
        type: 'player',
        name: playerForm.name || `Joueur ${playerNumber}`,
        armorClass: Number(playerForm.armorClass || 0),
        currentHitPoints: Number(playerForm.currentHitPoints || 0),
        baseHitPoints: Number(playerForm.baseHitPoints || 0),
        initiative: Number(playerForm.initiative || 0),
        roll: Number(playerForm.initiative || 0),
        done: false,
    };
}

function refreshPlayerAccessibility(playerList) {
    const playerItems = playerList.querySelectorAll('.player-item');

    playerItems.forEach((playerItem, index) => {
        const playerNumber = index + 1;

        assignFieldLabel(playerItem, 'name', `player-${playerNumber}-name`, `Nom du joueur ${playerNumber}`);
        assignFieldLabel(playerItem, 'armor-class', `player-${playerNumber}-armor-class`, `CA du joueur ${playerNumber}`);
        assignFieldLabel(playerItem, 'initiative', `player-${playerNumber}-initiative`, `Initiative du joueur ${playerNumber}`);
        assignInputAriaLabel(playerItem, 'current-hit-points', `PV actuels du joueur ${playerNumber}`);
        assignInputAriaLabel(playerItem, 'base-hit-points', `PV max du joueur ${playerNumber}`);

        const hitPointsLabel = playerItem.querySelector('[data-player-label="hit-points"]');

        if (hitPointsLabel) {
            hitPointsLabel.id = `player-${playerNumber}-hit-points-label`;
        }

        const removeButton = playerItem.querySelector('.player-remove-button');

        if (removeButton) {
            const removeLabel = `Supprimer le joueur ${playerNumber}`;
            removeButton.setAttribute('aria-label', removeLabel);
            removeButton.title = removeLabel;
        }
    });
}

function assignFieldLabel(playerItem, fieldName, id, labelText) {
    const input = getPlayerInput(playerItem, fieldName);
    const label = playerItem.querySelector(`[data-player-label="${fieldName}"]`);

    if (!input || !label) {
        return;
    }

    input.id = id;
    label.setAttribute('for', id);
    input.setAttribute('aria-label', labelText);
}

function assignInputAriaLabel(playerItem, fieldName, labelText) {
    const input = getPlayerInput(playerItem, fieldName);

    if (!input) {
        return;
    }

    input.setAttribute('aria-label', labelText);
}

function getPlayerInput(playerItem, fieldName) {
    return playerItem.querySelector(`[data-player-field="${fieldName}"]`);
}

function readPlayerField(playerItem, fieldName) {
    return getPlayerInput(playerItem, fieldName)?.value ?? '';
}

function hasStartedPlayer(playerItem) {
    return Array.from(playerItem.querySelectorAll('input'))
        .some(input => input.value.trim() !== '' || input.validity?.badInput);
}
