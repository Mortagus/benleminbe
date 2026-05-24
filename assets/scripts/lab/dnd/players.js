// DOM controller for the players panel.
// Player form rows are the temporary source of truth until sync() writes them
// into the encounter state before turn order generation.
import {
    clearValidationState,
    mergeValidationResults,
    showValidationErrors,
    validatePlayerItem,
} from './validation.js';

export function initializePlayersPanel(encounter, callbacks = {}) {
    const panel = new PlayersPanel(encounter, callbacks);

    panel.start();

    return panel;
}

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
            this.playerList.appendChild(createPlayerItem(() => this.sync()));
            this.sync();
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
                roll: Number(initiativeInput?.value || 0),
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

function hasStartedPlayer(playerItem) {
    return Array.from(playerItem.querySelectorAll('input'))
        .some(input => input.value.trim() !== '' || input.validity?.badInput);
}
