// Entry point for the DnD initiative tracker.
// It wires the shared encounter state to the DOM panels and keeps the main
// "generate turn order" flow in one place.
import '../../../styles/lab/dnd/lab_dnd_initiative.css';

import { EncounterState } from './encounter-state.js';

import { MonstersPanel } from './monsters.js';

import { PlayersPanel } from './players.js';

import { TurnOrderPanel } from './turn-order.js';

import { RulesPanel } from './rules.js';

import { EncounterPersistence } from './persistence.js';

import { playSoundEffect } from './sound-effects.js';

import {
    describeHitPointsChange,
    describeInvalidHitPointsChange,
    parseHitPointsChange,
} from './hit-points.js';

import {
    focusFirstInvalidField,
    hasValidationErrors,
    validateEncounterActors,
} from './validation.js';

class DndInitiativeTrackerApp {
    constructor() {
        this.encounter = new EncounterState();
        this.monstersPanel = null;
        this.playersPanel = null;
        this.rulesPanel = null;
        this.turnOrderPanel = null;
        this.persistence = null;
        this.playerImportUrl = document.getElementById('playerImportModal')?.dataset.playerImportUrl
            ?? '/lab/dnd-initiative/import-player';
    }

    start() {
        this.turnOrderPanel = new TurnOrderPanel(this.encounter, {
            onGenerateTurnOrder: () => this.generateTurnOrder(),
            onAdvanceTurn: () => this.advanceCombatTurn(),
            onStartNewRound: () => this.startNewRound(),
            onResetTurnProgress: () => this.resetTurnProgress(),
            onResetEncounter: () => this.resetEncounter(),
            onApplyHitPointsChange: (turnId, rawValue) => this.applyTurnHitPointsChange(turnId, rawValue),
        });
        this.turnOrderPanel.start();

        this.monstersPanel = new MonstersPanel(this.encounter, {
            onEncounterChange: () => this.refreshDisplayedTurnOrder(),
            onMonsterInitiativeRoll: () => playSoundEffect('monsterInitiativeRoll'),
        });
        this.monstersPanel.start();

        this.playersPanel = new PlayersPanel(this.encounter, {
            onPlayersChange: () => this.refreshDisplayedTurnOrder(),
            onPlayerImportFile: (file) => this.importPlayerXml(file),
        });
        this.playersPanel.start();

        this.rulesPanel = new RulesPanel({
            isRuleActive: (ruleId) => this.encounter.isRuleActive(ruleId),
            setRuleActive: (ruleId, active) => this.setRuleActive(ruleId, active),
        });
        this.rulesPanel.start();

        this.persistence = new EncounterPersistence(this.encounter, {
            monstersPanel: this.monstersPanel,
            playersPanel: this.playersPanel,
            rulesPanel: this.rulesPanel,
            turnOrderPanel: this.turnOrderPanel,
        });
        this.persistence.start();
    }

    refreshDisplayedTurnOrder() {
        // Refreshes the current rendering only; buildRoundOrder() runs from generateTurnOrder().
        this.turnOrderPanel.refresh();
        this.persistence?.saveEncounter();
    }

    setRuleActive(ruleId, active) {
        this.encounter.setRuleActive(ruleId, active);
        this.turnOrderPanel.refresh();
        this.persistence?.saveEncounter();
    }

    async importPlayerXml(file) {
        const formData = new globalThis.FormData();
        formData.append('file', file, file.name);

        const response = await globalThis.fetch(this.playerImportUrl, {
            method: 'POST',
            body: formData,
            headers: {
                Accept: 'application/json',
            },
        });

        const payload = await response.json().catch(() => ({}));

        if (!response.ok) {
            throw new Error(payload.message ?? 'L’import XML a échoué.');
        }

        return payload;
    }

    generateTurnOrder() {
        this.monstersPanel.clearValidation();
        this.playersPanel.clearValidation();
        this.turnOrderPanel.clearValidation();

        const encounterValidationResult = validateEncounterActors(
            this.monstersPanel.getListElement(),
            this.playersPanel.getListElement(),
        );

        const monsterValidationResult = this.monstersPanel.validateForTurnOrder();
        const playerValidationResult = this.playersPanel.validateForTurnOrder();

        this.turnOrderPanel.showEncounterValidationErrors(encounterValidationResult);

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

        this.playersPanel.sync({ notify: false });
        this.encounter.buildRoundOrder();

        this.turnOrderPanel.refresh({ focusFirst: true });
        this.persistence?.saveEncounter();
    }

    advanceCombatTurn() {
        const result = this.encounter.advanceToNextTurn();

        if (result.status === 'empty') {
            this.turnOrderPanel?.refresh?.();
            return;
        }

        this.turnOrderPanel?.refresh?.(
            result.activeTurnId
                ? { focusTurnId: result.activeTurnId }
                : {},
        );
        this.persistence?.saveEncounter();
    }

    startNewRound() {
        this.encounter.startNewRound();
        this.turnOrderPanel?.refresh?.({ focusFirst: true });
        this.persistence?.saveEncounter();
    }

    resetTurnProgress() {
        this.encounter.resetTurnProgress();
        this.turnOrderPanel?.refresh?.({ focusFirst: true });
        this.persistence?.saveEncounter();
    }

    resetEncounter() {
        const shouldReset = typeof globalThis.confirm === 'function'
            ? globalThis.confirm('Réinitialiser la rencontre ? Cette action effacera les acteurs et l’ordre du tour.')
            : true;

        if (!shouldReset) {
            return;
        }

        this.encounter.resetEncounter();
        this.monstersPanel?.refresh?.();
        this.playersPanel?.hydrateFromEncounter?.();
        this.rulesPanel?.sync?.();
        this.turnOrderPanel?.refresh?.();
        this.persistence?.saveEncounter();
    }

    applyTurnHitPointsChange(turnId, rawValue) {
        const turn = this.encounter.turnOrder.find(actor => actor.id === turnId);

        if (!turn) {
            return {
                ok: false,
                message: 'Acteur introuvable pour la modification des PV.',
            };
        }

        const change = parseHitPointsChange(rawValue);

        if (!change) {
            return {
                ok: false,
                message: describeInvalidHitPointsChange(turn.name),
            };
        }

        const actorId = turn.actorId ?? turn.id;
        const result = change.kind === 'set'
            ? this.encounter.updateActorHitPoints(actorId, change.amount)
            : this.encounter.adjustActorHitPoints(
                actorId,
                change.kind === 'damage' ? -change.amount : change.amount,
            );

        if (!result) {
            return {
                ok: false,
                message: 'Acteur introuvable pour la modification des PV.',
            };
        }

        if (turn.type === 'monster') {
            this.monstersPanel?.refresh?.();
        }

        if (turn.type === 'player') {
            this.playersPanel?.hydrateFromEncounter?.();
        }

        this.turnOrderPanel?.refresh?.({
            focusTurnId: turn.id,
            focusHitPointsInput: true,
        });
        this.persistence?.saveEncounter();

        return {
            ok: true,
            message: describeHitPointsChange(
                result.actorName,
                change,
                result.currentHitPoints,
                result.baseHitPoints,
            ),
        };
    }
}

new DndInitiativeTrackerApp().start();
