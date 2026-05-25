import { afterEach, describe, expect, test, vi } from 'vitest';
import { EncounterState } from '../../../../assets/scripts/lab/dnd/encounter-state.js';
import {
    getPlayerActors,
    PlayersPanel,
} from '../../../../assets/scripts/lab/dnd/players.js';
import {
    createInput,
    createPlayerItem,
    createPlayerItemTemplate,
    TestElement,
} from './dom-test-helpers.js';

describe('players panel data mapping', () => {
    afterEach(() => {
        delete globalThis.document;
    });

    test('maps started player rows to encounter actors', () => {
        const playerList = createPlayerList([
            createPlayerItem({
                name: createInput('Lia'),
                'armor-class': createInput('15'),
                'current-hit-points': createInput('18'),
                'base-hit-points': createInput('20'),
                initiative: createInput('12'),
            }),
            createPlayerItem({
                name: createInput(''),
                'armor-class': createInput(''),
                'current-hit-points': createInput(''),
                'base-hit-points': createInput(''),
                initiative: createInput(''),
            }),
            createPlayerItem({
                name: createInput('Borin'),
                'armor-class': createInput('17'),
                'current-hit-points': createInput('22'),
                'base-hit-points': createInput('24'),
                initiative: createInput('8'),
            }),
        ]);

        expect(getPlayerActors(playerList)).toEqual([
            {
                id: 'player-1',
                type: 'player',
                name: 'Lia',
                armorClass: 15,
                currentHitPoints: 18,
                baseHitPoints: 20,
                initiative: 12,
                roll: 12,
                done: false,
            },
            {
                id: 'player-2',
                type: 'player',
                name: 'Borin',
                armorClass: 17,
                currentHitPoints: 22,
                baseHitPoints: 24,
                initiative: 8,
                roll: 8,
                done: false,
            },
        ]);
    });

    test('starts the panel and synchronizes the initial player list', () => {
        const encounter = new EncounterState();
        const onPlayersChange = vi.fn();

        globalThis.document = createPlayersDocument();

        const panel = new PlayersPanel(encounter, {
            onPlayersChange,
        });
        panel.start();

        expect(panel).toBeInstanceOf(PlayersPanel);
        expect(encounter.players).toEqual([]);
        expect(onPlayersChange).toHaveBeenCalledOnce();
    });

    test('opens the XML import picker and forwards the selected file', () => {
        const encounter = new EncounterState();
        const onPlayerImportFile = vi.fn(() => ({
            player: {
                id: 'player-1',
                name: 'Lyriel Selthir',
                armorClass: 15,
                currentHitPoints: 18,
                baseHitPoints: 18,
                initiative: null,
            },
            warnings: ['Champ brut conservé.'],
            raw: {
                toolsProf: [],
            },
        }));
        const fileInput = new TestElement('input');
        const importButton = new TestElement('button');
        const submitButton = new TestElement('button');
        const modal = new TestElement('div');
        const modalContent = new TestElement('section');
        const status = new TestElement('div');
        const closeButton = new TestElement('button');
        const detailsModal = new TestElement('div');
        const detailsModalContent = new TestElement('section');
        const detailsTableBody = new TestElement('tbody');

        globalThis.document = createPlayersDocument([], {
            addPlayerButton: new TestElement('button'),
            importButton,
            fileInput,
            submitButton,
            modal,
            modalContent,
            closeButton,
            status,
            detailsModal,
            detailsModalContent,
            detailsTableBody,
        });

        const panel = new PlayersPanel(encounter, {
            onPlayerImportFile,
        });
        panel.start();

        importButton.dispatchEvent({ type: 'click' });
        expect(modal.hidden).toBe(false);

        const file = { name: 'Lyriel-Selthir.xml' };
        fileInput.files = [file];
        fileInput.dispatchEvent({
            type: 'change',
            target: fileInput,
        });

        expect(submitButton.disabled).toBe(false);
        expect(onPlayerImportFile).toHaveBeenCalledTimes(0);

        submitButton.dispatchEvent({ type: 'click' });

        expect(onPlayerImportFile).toHaveBeenCalledOnce();
        expect(onPlayerImportFile).toHaveBeenCalledWith(file);
        expect(modal.hidden).toBe(true);
        expect(submitButton.disabled).toBe(false);
        expect(panel.getListElement().querySelectorAll('.player-item')).toHaveLength(1);
        expect(panel.getListElement().querySelector('[data-player-field="name"]').value).toBe('Lyriel Selthir');
        expect(panel.getListElement().querySelector('[data-player-field="armor-class"]').value).toBe('15');
        expect(panel.getListElement().querySelector('[data-player-field="current-hit-points"]').value).toBe('18');
        expect(panel.getListElement().querySelector('[data-player-field="base-hit-points"]').value).toBe('18');
        expect(panel.getListElement().querySelector('[data-player-field="initiative"]').value).toBe('');
        expect(status.hidden).toBe(false);
        expect(status.textContent).toContain('Joueur importé : Lyriel Selthir');

        const detailsButton = panel.getListElement().querySelector('[data-player-details-open]');
        detailsButton.dispatchEvent({ type: 'click' });

        expect(detailsModal.hidden).toBe(false);
        expect(detailsTableBody.children.some(row => row.children[0].textContent === 'player.id')).toBe(true);
        expect(detailsTableBody.children.some(row => row.children[0].textContent === 'player.name')).toBe(true);
        expect(detailsTableBody.children.some(row => row.children[0].textContent === 'player.id'
            && row.children[1].textContent === 'player-1')).toBe(true);
        expect(detailsTableBody.children.some(row => row.children[0].textContent === 'warnings[0]')).toBe(true);
    });

    test('synchronizes existing player input changes to the encounter', () => {
        const encounter = new EncounterState();
        const onPlayersChange = vi.fn();
        const playerItem = createPanelPlayerItem({
            name: createInput('Lia'),
            'armor-class': createInput('15'),
            'current-hit-points': createInput('18'),
            'base-hit-points': createInput('20'),
            initiative: createInput('12'),
        });

        globalThis.document = createPlayersDocument([playerItem.item]);

        const panel = new PlayersPanel(encounter, {
            onPlayersChange,
        });
        panel.start();

        expect(encounter.players[0]).toMatchObject({
            name: 'Lia',
            initiative: 12,
            roll: 12,
        });

        playerItem.inputs.initiative.value = '16';
        playerItem.inputs.initiative.dispatchEvent({ type: 'input' });

        expect(encounter.players[0]).toMatchObject({
            name: 'Lia',
            initiative: 16,
            roll: 16,
        });
        expect(onPlayersChange).toHaveBeenCalledTimes(2);
    });
});

function createPlayerList(playerItems) {
    return {
        querySelectorAll: selector => selector === '.player-item' ? playerItems : [],
    };
}

function createPlayersDocument(playerItems = [], overrides = {}) {
    const addPlayerButton = overrides.addPlayerButton ?? new TestElement('button');
    const importButton = overrides.importButton ?? new TestElement('button');
    const fileInput = overrides.fileInput ?? new TestElement('input');
    const submitButton = overrides.submitButton ?? new TestElement('button');
    const playerPanel = new TestElement('section', ['dnd-panel--players']);
    const playerList = new TestElement('ul');
    const playerValidationSummary = new TestElement('div', ['dnd-validation-summary']);
    const playerImportStatus = overrides.status ?? new TestElement('div');
    const playerImportModal = overrides.modal ?? new TestElement('div');
    const playerImportModalContent = overrides.modalContent ?? new TestElement('section');
    const closeButton = overrides.closeButton ?? new TestElement('button');
    const playerItemTemplate = overrides.playerItemTemplate ?? createPlayerItemTemplate();
    const playerDetailsModal = overrides.detailsModal ?? new TestElement('div');
    const playerDetailsModalContent = overrides.detailsModalContent ?? new TestElement('section');
    const playerDetailsTableBody = overrides.detailsTableBody ?? new TestElement('tbody');
    playerImportStatus.hidden = true;
    playerImportModal.hidden = true;
    playerImportModal.querySelector = selector => selector === '.player-import-modal__content'
        ? playerImportModalContent
        : null;
    playerImportModal.querySelectorAll = selector => selector === '[data-player-import-close]'
        ? [closeButton]
        : [];
    playerDetailsModal.hidden = true;
    playerDetailsModal.querySelector = selector => selector === '.player-details-modal__content'
        ? playerDetailsModalContent
        : null;
    playerDetailsModal.querySelectorAll = selector => selector === '[data-player-details-close]'
        ? [new TestElement('button')]
        : [];

    playerItems.forEach(playerItem => {
        playerList.appendChild(playerItem);
    });

    return {
        createElement: tagName => new TestElement(tagName),
        createDocumentFragment: () => new TestElement('fragment'),
        getElementById: id => ({
            addPlayer: addPlayerButton,
            importPlayerXml: importButton,
            playerXmlImportInput: fileInput,
            playerImportSubmit: submitButton,
            playerImportModal,
            playerItemTemplate,
            playerDetailsModal,
            playerDetailsTableBody,
            playerList,
            playerValidationSummary,
            playerImportStatus,
        })[id] ?? null,
        querySelector: selector => selector === '.dnd-panel--players' ? playerPanel : null,
    };
}

function createPanelPlayerItem(inputs) {
    const item = createPlayerItem(inputs);
    item.appendChild(new TestElement('button', ['player-remove-button']));

    return {
        item,
        inputs,
    };
}
