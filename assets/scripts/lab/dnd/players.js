// DOM controller for the players panel.
// Player form rows are the temporary source of truth until sync() writes them
// into the encounter state before turn order generation.
import {
    clearValidationState,
    mergeValidationResults,
    showValidationErrors,
    validatePlayerItem,
} from './validation.js';
import { normalizePlayerSide } from './dtos.js';

export class PlayersPanel {
    constructor(encounter, callbacks = {}) {
        this.encounter = encounter;
        this.callbacks = callbacks;
        this.addPlayerButton = document.getElementById('addPlayer');
        this.importPlayerButton = document.getElementById('importPlayerXml');
        this.playerImportInput = document.getElementById('playerXmlImportInput');
        this.playerImportSubmitButton = document.getElementById('playerImportSubmit');
        this.playerPanel = document.querySelector('.dnd-panel--players');
        this.playerList = document.getElementById('playerList');
        this.playerValidationSummary = document.getElementById('playerValidationSummary');
        this.playerImportModal = document.getElementById('playerImportModal');
        this.playerImportModalContent = this.playerImportModal?.querySelector('.player-import-modal__content');
        this.playerImportStatus = document.getElementById('playerImportStatus');
        this.playerDetailsModal = document.getElementById('playerDetailsModal');
        this.playerDetailsModalContent = this.playerDetailsModal?.querySelector('.player-details-modal__content');
        this.playerDetailsTableBody = document.getElementById('playerDetailsTableBody');
        this.playerDetailsReturnFocus = null;
        this.playerImportSelectedFile = null;
    }

    start() {
        this.addPlayerButton.addEventListener('click', () => {
            this.handleAddPlayer();
        });

        this.importPlayerButton.addEventListener('click', () => {
            this.handleImportPlayerXml();
        });

        this.playerImportInput.addEventListener('change', event => {
            this.handlePlayerImportSelection(event);
        });

        this.playerImportSubmitButton.addEventListener('click', () => {
            this.handleConfirmPlayerImport();
        });

        this.bindPlayerImportModal();
        this.setPlayerImportSubmitEnabled(false);

        bindExistingPlayerItems(this.playerList, () => this.sync(), (playerItem, triggerButton) => {
            this.handleShowPlayerDetails(playerItem, triggerButton);
        });
        this.sync();
    }

    clearValidation() {
        clearValidationState(this.playerPanel);
    }

    getListElement() {
        return this.playerList;
    }

    sync(options = {}) {
        this.syncPlayerFormsToEncounter();

        if (options.notify !== false) {
            this.callbacks.onPlayersChange?.();
        }
    }

    hydrateFromEncounter() {
        this.playerList.replaceChildren();

        if (this.encounter.players.length === 0) {
            this.playerList.appendChild(createPlayerItem(
                () => this.sync(),
                (playerItem, triggerButton) => {
                    this.handleShowPlayerDetails(playerItem, triggerButton);
                },
            ));
            refreshPlayerAccessibility(this.playerList);
            return;
        }

        this.encounter.players.forEach(player => {
            const playerItem = createPlayerItem(
                () => this.sync(),
                (playerItemElement, triggerButton) => {
                    this.handleShowPlayerDetails(playerItemElement, triggerButton);
                },
            );

            fillPlayerItemFromEncounterPlayer(playerItem, player);
            playerItem.playerImportData = player.importData ?? null;
            playerItem.playerDetails = createPlayerDetailsFromEncounterPlayer(player);
            this.playerList.appendChild(playerItem);
        });

        refreshPlayerAccessibility(this.playerList);
    }

    handleAddPlayer() {
        this.playerList.appendChild(createPlayerItem(
            () => this.sync(),
            (playerItem, triggerButton) => {
                this.handleShowPlayerDetails(playerItem, triggerButton);
            },
        ));
        this.sync();
    }

    handleImportPlayerXml() {
        this.playerImportSelectedFile = null;
        this.playerImportInput.value = '';
        this.setPlayerImportSubmitEnabled(false);
        this.setPlayerImportStatus('');
        openPlayerImportModal(this.importPlayerButton, this.playerImportModal, this.playerImportModalContent);
    }

    handlePlayerImportSelection(event) {
        const file = event.target?.files?.[0] ?? this.playerImportInput.files?.[0] ?? null;

        if (!file) {
            this.playerImportSelectedFile = null;
            this.setPlayerImportSubmitEnabled(false);
            this.setPlayerImportStatus('');
            return;
        }

        this.playerImportSelectedFile = file;
        this.setPlayerImportSubmitEnabled(true);
        this.setPlayerImportStatus(`Fichier sélectionné : ${file.name}`);
    }

    handleConfirmPlayerImport() {
        if (!this.playerImportSelectedFile) {
            return;
        }

        const selectedFile = this.playerImportSelectedFile;

        this.setPlayerImportSubmitEnabled(false);
        this.setPlayerImportStatus('Import en cours...');

        try {
            const importResult = this.callbacks.onPlayerImportFile?.(selectedFile);

            if (isPromiseLike(importResult)) {
                importResult
                    .then(response => {
                        this.handleSuccessfulPlayerImport(response, selectedFile);
                    })
                    .catch(error => {
                        this.setPlayerImportStatus(error instanceof Error
                            ? error.message
                            : 'Impossible d’importer ce fichier XML.');
                    })
                    .finally(() => {
                        this.setPlayerImportSubmitEnabled(true);
                    });

                return;
            }

            this.handleSuccessfulPlayerImport(importResult, selectedFile);
            this.setPlayerImportSubmitEnabled(true);
        } catch (error) {
            this.setPlayerImportStatus(error instanceof Error
                ? error.message
                : 'Impossible d’importer ce fichier XML.');
            this.setPlayerImportSubmitEnabled(true);
        }
    }

    syncPlayerFormsToEncounter() {
        refreshPlayerAccessibility(this.playerList);
        this.encounter.setPlayers(getPlayerActors(this.playerList));
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

    setPlayerImportStatus(message) {
        if (!this.playerImportStatus) {
            return;
        }

        if (!message) {
            this.playerImportStatus.replaceChildren();
            this.playerImportStatus.hidden = true;
            return;
        }

        this.playerImportStatus.textContent = message;
        this.playerImportStatus.hidden = false;
    }

    setPlayerImportSubmitEnabled(isEnabled) {
        if (!this.playerImportSubmitButton) {
            return;
        }

        this.playerImportSubmitButton.disabled = !isEnabled;
    }

    handleSuccessfulPlayerImport(importResult, selectedFile) {
        const importedPlayer = importResult?.player;

        if (!importedPlayer) {
            throw new Error('La réponse d’import ne contient pas de joueur.');
        }

        const playerItem = createPlayerItem(
            () => this.sync(),
            (item, triggerButton) => {
                this.handleShowPlayerDetails(item, triggerButton);
            },
        );
        this.playerList.appendChild(playerItem);
        fillPlayerItemFromEncounterPlayer(playerItem, importedPlayer);
        playerItem.playerDetails = importResult;
        playerItem.playerImportData = importResult;
        this.sync();
        this.setPlayerImportStatus(`Joueur importé : ${importedPlayer.name ?? selectedFile.name}`);
        closePlayerImportModal(this.importPlayerButton, this.playerImportModal);
        this.setPlayerImportSubmitEnabled(true);
    }

    bindPlayerImportModal() {
        if (!this.playerImportModal) {
            return;
        }

        this.playerImportModal.querySelectorAll('[data-player-import-close]').forEach(closeButton => {
            closeButton.addEventListener('click', () => {
                closePlayerImportModal(this.importPlayerButton, this.playerImportModal);
            });
        });

        if (typeof document.addEventListener === 'function') {
            document.addEventListener('keydown', event => {
                if (event.key === 'Escape' && !this.playerImportModal.hidden) {
                    closePlayerImportModal(this.importPlayerButton, this.playerImportModal);
                }
            });
        }

        this.playerDetailsModal?.querySelectorAll('[data-player-details-close]').forEach(closeButton => {
            closeButton.addEventListener('click', () => {
                this.closePlayerDetailsModal();
            });
        });

        if (typeof document.addEventListener === 'function') {
            document.addEventListener('keydown', event => {
                if (event.key === 'Escape' && !this.playerDetailsModal?.hidden) {
                    this.closePlayerDetailsModal();
                }
            });
        }
    }

    handleShowPlayerDetails(playerItem, triggerButton = null) {
        if (!this.playerDetailsModal || !this.playerDetailsTableBody) {
            return;
        }

        const playerDetails = playerItem.playerDetails;

        if (!playerDetails) {
            return;
        }

        this.playerDetailsReturnFocus = triggerButton ?? null;
        renderPlayerDetailsTable(this.playerDetailsTableBody, playerDetails);
        openPlayerDetailsModal(this.playerDetailsModal, this.playerDetailsModalContent);
    }

    closePlayerDetailsModal() {
        if (!this.playerDetailsModal) {
            return;
        }

        closePlayerDetailsModal(this.playerDetailsModal);

        if (this.playerDetailsReturnFocus?.focus) {
            this.playerDetailsReturnFocus.focus();
        }

        this.playerDetailsReturnFocus = null;
    }
}

export function createPlayerItem(onPlayerListChange, onPlayerDetailsRequest = null) {
    const template = document.getElementById('playerItemTemplate');

    if (!template) {
        throw new Error('Template #playerItemTemplate introuvable.');
    }

    const fragment = template.content.cloneNode(true);
    const playerItem = fragment.querySelector('.player-item');

    bindPlayerItemEvents(playerItem, onPlayerListChange, onPlayerDetailsRequest);

    return playerItem;
}

export function bindExistingPlayerItems(playerList, onPlayerListChange, onPlayerDetailsRequest = null) {
    const playerItems = playerList.querySelectorAll('.player-item');

    playerItems.forEach(playerItem => {
        bindPlayerItemEvents(playerItem, onPlayerListChange, onPlayerDetailsRequest);
    });
    refreshPlayerAccessibility(playerList);
}

// Boundary from player form rows to the encounter state shape.
// The future DTO pass should start from this mapping instead of reading DOM fields elsewhere.
export function getPlayerActors(playerList) {
    return getStartedPlayerForms(playerList)
        .map((playerEntry, index) => createPlayerActor(playerEntry.playerItem, playerEntry.playerForm, index))
        .filter(actor => actor.name.trim() !== '');
}

function bindPlayerItemEvents(playerItem, onPlayerListChange, onPlayerDetailsRequest = null) {
    const removeButton = playerItem.querySelector('.player-remove-button');
    const detailsButton = playerItem.querySelector('[data-player-details-open]');

    removeButton.addEventListener('click', () => {
        playerItem.remove();
        onPlayerListChange();
    });

    detailsButton?.addEventListener('click', () => {
        onPlayerDetailsRequest?.(playerItem, detailsButton);
    });

    getPlayerFormControls(playerItem).forEach(control => {
        control.addEventListener('input', () => {
            onPlayerListChange();
        });

        control.addEventListener('change', () => {
            onPlayerListChange();
        });
    });
}

function getStartedPlayerForms(playerList) {
    const playerItems = playerList.querySelectorAll('.player-item');

    return Array.from(playerItems)
        .filter(playerItem => hasStartedPlayer(playerItem))
        .map(playerItem => ({
            playerItem,
            playerForm: readPlayerForm(playerItem),
        }));
}

function readPlayerForm(playerItem) {
    return {
        name: readPlayerField(playerItem, 'name'),
        side: readPlayerField(playerItem, 'side'),
        armorClass: readPlayerField(playerItem, 'armor-class'),
        currentHitPoints: readPlayerField(playerItem, 'current-hit-points'),
        baseHitPoints: readPlayerField(playerItem, 'base-hit-points'),
        initiative: readPlayerField(playerItem, 'initiative'),
        importData: playerItem.playerImportData ?? null,
    };
}

function createPlayerActor(playerItem, playerForm, index) {
    const playerNumber = index + 1;
    const initiative = normalizeNullablePlayerNumber(playerForm.initiative);

    const playerActor = {
        id: `player-${playerNumber}`,
        type: 'player',
        name: playerForm.name || `Joueur ${playerNumber}`,
        side: normalizePlayerSide(playerForm.side),
        armorClass: Number(playerForm.armorClass || 0),
        currentHitPoints: Number(playerForm.currentHitPoints || 0),
        baseHitPoints: Number(playerForm.baseHitPoints || 0),
        initiative,
        roll: initiative,
        initiativeModifier: getPlayerInitiativeModifier(playerItem),
        done: false,
    };

    if (playerForm.importData) {
        playerActor.importData = playerForm.importData;
    }

    return playerActor;
}

function getPlayerInitiativeModifier(playerItem) {
    const importedPlayer = playerItem.playerDetails?.player
        ?? playerItem.playerImportData?.player
        ?? null;

    if (typeof importedPlayer?.initiativeModifier === 'number') {
        return importedPlayer.initiativeModifier;
    }

    const dexterity = importedPlayer?.abilityScores?.dexterity
        ?? importedPlayer?.dexterity
        ?? importedPlayer?.dex;

    return getAbilityModifier(dexterity);
}

function fillPlayerItemFromEncounterPlayer(playerItem, player) {
    setPlayerInputValue(playerItem, 'name', player.name ?? player.identity?.name ?? '');
    setPlayerInputValue(playerItem, 'side', normalizePlayerSide(player.side));
    setPlayerInputValue(playerItem, 'armor-class', normalizePlayerFieldValue(player.armorClass));
    setPlayerInputValue(playerItem, 'current-hit-points', normalizePlayerFieldValue(player.currentHitPoints));
    setPlayerInputValue(playerItem, 'base-hit-points', normalizePlayerFieldValue(player.baseHitPoints));
    setPlayerInputValue(playerItem, 'initiative', normalizePlayerFieldValue(player.initiative));
}

function createPlayerDetailsFromEncounterPlayer(player) {
    const importData = player.importData ?? null;

    if (!importData) {
        return null;
    }

    return {
        player,
        warnings: importData.warnings ?? [],
        raw: importData.raw ?? {},
    };
}

function refreshPlayerAccessibility(playerList) {
    const playerItems = playerList.querySelectorAll('.player-item');

    playerItems.forEach((playerItem, index) => {
        const playerNumber = index + 1;

        assignFieldLabel(playerItem, 'name', `player-${playerNumber}-name`, `Nom du joueur ${playerNumber}`);
        assignFieldLabel(playerItem, 'side', `player-${playerNumber}-side`, `Camp du participant ${playerNumber}`);
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

        const detailsButton = playerItem.querySelector('[data-player-details-open]');

        if (detailsButton) {
            const detailsLabel = `Afficher la fiche du joueur ${playerNumber}`;
            detailsButton.setAttribute('aria-label', detailsLabel);
            detailsButton.title = detailsLabel;
            detailsButton.disabled = playerItem.playerDetails == null;
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

function setPlayerInputValue(playerItem, fieldName, value) {
    const input = getPlayerInput(playerItem, fieldName);

    if (!input) {
        return;
    }

    input.value = value;
}

function normalizePlayerFieldValue(value) {
    return value === null || value === undefined ? '' : String(value);
}

function getPlayerFormControls(playerItem) {
    return [
        ...playerItem.querySelectorAll('input'),
        ...playerItem.querySelectorAll('select'),
    ];
}

function normalizeNullablePlayerNumber(value) {
    if (value === null || value === undefined) {
        return null;
    }

    const normalized = String(value).trim();

    if (normalized === '') {
        return null;
    }

    const number = Number(normalized);

    return Number.isFinite(number) ? number : null;
}

function getAbilityModifier(score) {
    const normalizedScore = normalizeNullablePlayerNumber(score);

    if (normalizedScore === null) {
        return 0;
    }

    return Math.floor((normalizedScore - 10) / 2);
}

function openPlayerDetailsModal(playerDetailsModal, modalContent) {
    playerDetailsModal.hidden = false;
    modalContent?.focus();
}

function closePlayerDetailsModal(playerDetailsModal) {
    playerDetailsModal.hidden = true;
}

function renderPlayerDetailsTable(tableBody, playerDetails) {
    tableBody.replaceChildren();

    flattenDetailsEntries(playerDetails).forEach(([label, value]) => {
        const row = document.createElement('tr');
        const keyCell = document.createElement('th');
        const valueCell = document.createElement('td');

        keyCell.textContent = label;
        valueCell.textContent = value;

        row.appendChild(keyCell);
        row.appendChild(valueCell);
        tableBody.appendChild(row);
    });
}

function flattenDetailsEntries(value, prefix = '') {
    const rows = [];

    collectFlattenedEntries(value, prefix, rows);

    return rows;
}

function collectFlattenedEntries(value, prefix, rows) {
    if (value === null) {
        rows.push([prefix || 'value', 'null']);
        return;
    }

    if (value === undefined) {
        rows.push([prefix || 'value', '']);
        return;
    }

    if (Array.isArray(value)) {
        if (value.length === 0) {
            rows.push([prefix || 'value', '[]']);
            return;
        }

        value.forEach((item, index) => {
            collectFlattenedEntries(item, `${prefix}[${index}]`, rows);
        });

        return;
    }

    if (typeof value === 'object') {
        const entries = Object.entries(value);

        if (entries.length === 0) {
            rows.push([prefix || 'value', '{}']);
            return;
        }

        entries.forEach(([key, childValue]) => {
            const label = prefix ? `${prefix}.${key}` : key;
            collectFlattenedEntries(childValue, label, rows);
        });

        return;
    }

    rows.push([prefix || 'value', String(value)]);
}

function hasStartedPlayer(playerItem) {
    return Array.from(playerItem.querySelectorAll('input'))
        .filter(input => input.dataset.playerField !== 'side')
        .some(input => input.value.trim() !== '' || input.validity?.badInput);
}

function openPlayerImportModal(openButton, playerImportModal, modalContent) {
    if (!openButton || !playerImportModal) {
        return;
    }

    openButton.setAttribute('aria-expanded', 'true');
    playerImportModal.hidden = false;
    modalContent?.focus();
}

function closePlayerImportModal(openButton, playerImportModal) {
    if (!openButton || !playerImportModal) {
        return;
    }

    openButton.setAttribute('aria-expanded', 'false');
    playerImportModal.hidden = true;
    openButton.focus();
}

function isPromiseLike(value) {
    return value !== null && typeof value === 'object' && typeof value.then === 'function';
}
