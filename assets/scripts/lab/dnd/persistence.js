import {
    createEncounterSnapshotDto,
    ENCOUNTER_SNAPSHOT_VERSION,
    LEGACY_ENCOUNTER_SNAPSHOT_VERSION,
    restoreEncounterFromSnapshot,
} from './dtos.js';

const SUPPORTED_ENCOUNTER_SNAPSHOT_VERSIONS = [
    LEGACY_ENCOUNTER_SNAPSHOT_VERSION,
    2,
    ENCOUNTER_SNAPSHOT_VERSION,
];

export const DND_INITIATIVE_TRACKER_STORAGE_NAMESPACE = 'dnd-initiative-tracker';
export const DND_INITIATIVE_TRACKER_STORAGE_KEY = `${DND_INITIATIVE_TRACKER_STORAGE_NAMESPACE}-save`;

const EMPTY_STATUS_MESSAGE = 'Aucune sauvegarde locale.';

export class EncounterPersistence {
    constructor(encounter, panels = {}) {
        this.encounter = encounter;
        this.monstersPanel = panels.monstersPanel ?? null;
        this.playersPanel = panels.playersPanel ?? null;
        this.rulesPanel = panels.rulesPanel ?? null;
        this.turnOrderPanel = panels.turnOrderPanel ?? null;

        this.statusElement = document.getElementById('encounterPersistenceStatus');
        this.restoreButton = document.getElementById('restoreEncounterSnapshot');
        this.resetButton = document.getElementById('resetEncounterSnapshot');
        this.restoreModal = document.getElementById('encounterRestoreModal');
        this.restoreModalContent = this.restoreModal?.querySelector('.dnd-persistence-modal__content');
        this.restoreSummary = document.getElementById('encounterRestoreSummary');
        this.restoreLoadButton = document.getElementById('encounterRestoreLoad');
        this.restoreCloseButtons = this.restoreModal?.querySelectorAll('[data-persistence-close]') ?? [];
        this.errorModal = document.getElementById('encounterPersistenceErrorModal');
        this.errorModalContent = this.errorModal?.querySelector('.dnd-persistence-modal__content');
        this.errorMessage = document.getElementById('encounterPersistenceErrorMessage');
        this.errorCloseButtons = this.errorModal?.querySelectorAll('[data-persistence-error-close]') ?? [];

        this.currentSnapshot = null;
        this.isModalOpen = false;
        this.isRestoring = false;
    }

    start() {
        this.bindEvents();

        const storedSnapshotResult = this.readStoredSnapshot();

        if (storedSnapshotResult.status === 'valid') {
            this.setCurrentSnapshot(storedSnapshotResult.snapshot);
            this.updateStatusFromSnapshot(storedSnapshotResult.snapshot);
            this.openRestorePrompt(storedSnapshotResult.snapshot);
            return;
        }

        if (storedSnapshotResult.status === 'invalid' || storedSnapshotResult.status === 'unavailable') {
            this.setCurrentSnapshot(null);
            this.updateStatusMessage(storedSnapshotResult.status === 'unavailable'
                ? EMPTY_STATUS_MESSAGE
                : 'Sauvegarde locale invalide.');
            this.disableRestoreButton();

            if (storedSnapshotResult.message) {
                this.openErrorModal(storedSnapshotResult.message);
            }
            return;
        }

        this.setCurrentSnapshot(null);
        this.updateStatusMessage(EMPTY_STATUS_MESSAGE);
        this.disableRestoreButton();
    }

    saveEncounter() {
        if (this.isModalOpen || this.isRestoring) {
            return;
        }

        try {
            this.playersPanel?.sync?.({ notify: false });

            const snapshot = createEncounterSnapshotDto(this.encounter);
            const storage = this.getStorage();

            if (!storage) {
                this.openErrorModal(this.createUnavailableStorageMessage('Impossible de sauvegarder la rencontre.'));
                return;
            }

            storage.setItem(DND_INITIATIVE_TRACKER_STORAGE_KEY, JSON.stringify(snapshot));
            this.setCurrentSnapshot(snapshot);
            this.updateStatusFromSnapshot(snapshot);
            this.enableRestoreButton();
        } catch (error) {
            this.openErrorModal(this.createSaveErrorMessage(error));
        }
    }

    restoreFromStoredSnapshot() {
        const result = this.readStoredSnapshot();

        if (result.status !== 'valid') {
            if (result.message) {
                this.openErrorModal(result.message);
            }
            return;
        }

        this.restoreFromSnapshot(result.snapshot);
    }

    restoreFromSnapshot(snapshot) {
        if (this.isModalOpen) {
            this.closeRestorePrompt();
        }

        this.isRestoring = true;

        try {
            restoreEncounterFromSnapshot(this.encounter, snapshot);
            this.playersPanel?.hydrateFromEncounter?.();
            this.rulesPanel?.sync?.();
            this.monstersPanel?.refresh?.();
            this.turnOrderPanel?.refresh?.();
            this.setCurrentSnapshot(snapshot);
            this.updateStatusFromSnapshot(snapshot);
            this.enableRestoreButton();
        } catch (error) {
            this.openErrorModal(this.createRestoreErrorMessage(error));
        } finally {
            this.isRestoring = false;
        }
    }

    bindEvents() {
        this.restoreButton?.addEventListener('click', () => {
            if (this.restoreButton.disabled) {
                return;
            }

            const result = this.readStoredSnapshot();

            if (result.status === 'valid') {
                this.openRestorePrompt(result.snapshot);
                return;
            }

            if (result.message) {
                this.openErrorModal(result.message);
            }
        });

        this.restoreLoadButton?.addEventListener('click', () => {
            const result = this.readStoredSnapshot();

            if (result.status !== 'valid') {
                if (result.message) {
                    this.openErrorModal(result.message);
                }
                return;
            }

            this.restoreFromSnapshot(result.snapshot);
        });

        this.resetButton?.addEventListener('click', () => {
            this.resetStoredSnapshot();
        });

        this.restoreCloseButtons.forEach(closeButton => {
            closeButton.addEventListener('click', () => {
                this.closeRestorePrompt();
            });
        });

        this.errorCloseButtons.forEach(closeButton => {
            closeButton.addEventListener('click', () => {
                this.closeErrorModal();
            });
        });

        if (typeof document.addEventListener === 'function') {
            document.addEventListener('keydown', event => {
                if (event.key === 'Escape' && this.restoreModal && !this.restoreModal.hidden) {
                    this.closeRestorePrompt();
                }

                if (event.key === 'Escape' && this.errorModal && !this.errorModal.hidden) {
                    this.closeErrorModal();
                }
            });
        }
    }

    openRestorePrompt(snapshot) {
        this.currentSnapshot = snapshot;
        this.isModalOpen = true;

        if (this.restoreSummary) {
            this.restoreSummary.textContent = this.describeSnapshot(snapshot);
        }

        if (this.restoreModal) {
            this.restoreModal.hidden = false;
        }

        this.restoreButton?.setAttribute('aria-expanded', 'true');
        this.restoreModalContent?.focus();
        this.enableRestoreButton();
    }

    closeRestorePrompt({ focusRestoreButton = true } = {}) {
        if (this.restoreModal) {
            this.restoreModal.hidden = true;
        }

        this.isModalOpen = false;
        this.restoreButton?.setAttribute('aria-expanded', 'false');

        if (focusRestoreButton) {
            this.restoreButton?.focus();
        }
    }

    resetStoredSnapshot() {
        const storage = this.getStorage();

        if (!storage) {
            this.openErrorModal(this.createUnavailableStorageMessage('Impossible de réinitialiser la sauvegarde locale.'));
            return;
        }

        try {
            storage.removeItem(DND_INITIATIVE_TRACKER_STORAGE_KEY);
        } catch (error) {
            this.openErrorModal(this.createResetErrorMessage(error));
            return;
        }

        this.setCurrentSnapshot(null);
        this.updateStatusMessage(EMPTY_STATUS_MESSAGE);
        this.disableRestoreButton();
        this.closeRestorePrompt({ focusRestoreButton: false });
    }

    openErrorModal(message) {
        if (this.restoreModal && !this.restoreModal.hidden) {
            this.closeRestorePrompt();
        }

        this.isModalOpen = true;

        if (this.errorMessage) {
            this.errorMessage.value = message;
        }

        if (this.errorModal) {
            this.errorModal.hidden = false;
        }

        this.errorModalContent?.focus();
    }

    closeErrorModal() {
        if (this.errorModal) {
            this.errorModal.hidden = true;
        }

        this.isModalOpen = false;
    }

    updateStatusFromSnapshot(snapshot) {
        this.updateStatusMessage(`Derniere sauvegarde locale : ${this.formatSavedAt(snapshot.savedAt)}`);
    }

    updateStatusMessage(message) {
        if (this.statusElement) {
            this.statusElement.textContent = message;
        }
    }

    enableRestoreButton() {
        if (this.restoreButton) {
            this.restoreButton.disabled = false;
        }
    }

    disableRestoreButton() {
        if (this.restoreButton) {
            this.restoreButton.disabled = true;
        }
    }

    setCurrentSnapshot(snapshot) {
        this.currentSnapshot = snapshot;
    }

    readStoredSnapshot() {
        const storage = this.getStorage();

        if (!storage) {
            return {
                status: 'unavailable',
                message: this.createUnavailableStorageMessage('Impossible de lire la sauvegarde locale.'),
            };
        }

        let rawSnapshot;

        try {
            rawSnapshot = storage.getItem(DND_INITIATIVE_TRACKER_STORAGE_KEY);
        } catch (error) {
            return {
                status: 'unavailable',
                message: this.createUnavailableStorageMessage(error instanceof Error ? error.message : String(error)),
            };
        }

        if (!rawSnapshot) {
            return {
                status: 'missing',
            };
        }

        let parsedSnapshot;

        try {
            parsedSnapshot = JSON.parse(rawSnapshot);
        } catch {
            return {
                status: 'invalid',
                message: this.createInvalidSnapshotMessage('La sauvegarde locale n’est pas un JSON valide.'),
            };
        }

        const validation = this.validateSnapshot(parsedSnapshot);

        if (!validation.valid) {
            return {
                status: 'invalid',
                message: validation.message,
            };
        }

        return {
            status: 'valid',
            snapshot: parsedSnapshot,
        };
    }

    validateSnapshot(snapshot) {
        if (!snapshot || typeof snapshot !== 'object' || Array.isArray(snapshot)) {
            return {
                valid: false,
                message: this.createInvalidSnapshotMessage('La sauvegarde locale est corrompue.'),
            };
        }

        if (!Number.isInteger(snapshot.version)) {
            return {
                valid: false,
                message: this.createInvalidSnapshotMessage('La sauvegarde locale ne contient pas de version valide.'),
            };
        }

        if (!SUPPORTED_ENCOUNTER_SNAPSHOT_VERSIONS.includes(snapshot.version)) {
            const versionMessage = snapshot.version > ENCOUNTER_SNAPSHOT_VERSION
                ? 'La sauvegarde locale a été créée avec une version plus récente du tracker.'
                : 'La sauvegarde locale a été créée avec une version non prise en charge du tracker.';

            return {
                valid: false,
                message: this.createInvalidSnapshotMessage(versionMessage),
            };
        }

        return {
            valid: true,
        };
    }

    getStorage() {
        try {
            return globalThis.localStorage ?? null;
        } catch {
            return null;
        }
    }

    formatSavedAt(savedAt) {
        const date = new Date(savedAt);

        if (Number.isNaN(date.getTime())) {
            return String(savedAt ?? '');
        }

        return new Intl.DateTimeFormat(globalThis.document?.documentElement?.lang || 'fr', {
            dateStyle: 'short',
            timeStyle: 'short',
        }).format(date);
    }

    describeSnapshot(snapshot) {
        const monsterCount = snapshot.monsters?.length ?? 0;
        const playerCount = snapshot.players?.length ?? 0;

        return `Sauvegarde du ${this.formatSavedAt(snapshot.savedAt)} - ${monsterCount} monstre${monsterCount > 1 ? 's' : ''}, ${playerCount} joueur${playerCount > 1 ? 's' : ''}.`;
    }

    createUnavailableStorageMessage(reason) {
        return [
            '[DnD Initiative Tracker] localStorage est indisponible.',
            `Cle: ${DND_INITIATIVE_TRACKER_STORAGE_KEY}`,
            `Raison: ${reason}`,
            'Action: le tracker peut fonctionner en memoire, mais la sauvegarde locale ne sera pas disponible.',
        ].join('\n');
    }

    createInvalidSnapshotMessage(reason) {
        return [
            '[DnD Initiative Tracker] Le snapshot local est invalide.',
            `Cle: ${DND_INITIATIVE_TRACKER_STORAGE_KEY}`,
            `Version attendue: ${ENCOUNTER_SNAPSHOT_VERSION}`,
            `Raison: ${reason}`,
        ].join('\n');
    }

    createSaveErrorMessage(error) {
        return [
            '[DnD Initiative Tracker] Impossible de sauvegarder la rencontre.',
            `Cle: ${DND_INITIATIVE_TRACKER_STORAGE_KEY}`,
            `Raison: ${error instanceof Error ? error.message : String(error)}`,
        ].join('\n');
    }

    createResetErrorMessage(error) {
        return [
            '[DnD Initiative Tracker] Impossible de réinitialiser la sauvegarde locale.',
            `Cle: ${DND_INITIATIVE_TRACKER_STORAGE_KEY}`,
            `Raison: ${error instanceof Error ? error.message : String(error)}`,
        ].join('\n');
    }

    createRestoreErrorMessage(error) {
        return [
            '[DnD Initiative Tracker] Impossible de restaurer la rencontre.',
            `Cle: ${DND_INITIATIVE_TRACKER_STORAGE_KEY}`,
            `Raison: ${error instanceof Error ? error.message : String(error)}`,
        ].join('\n');
    }
}
