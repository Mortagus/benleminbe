// Entry point for the DnD initiative tracker.
// It wires the shared encounter state to the DOM panels and keeps the main
// "generate turn order" flow in one place.
import '../../../styles/lab/dnd/lab_dnd_initiative.css';

import {
    buildRoundOrder,
    createEncounterState,
    isRuleActive,
    setRuleActive,
} from './encounter-state.js';

import { initializeMonstersPanel } from './monsters.js';

import { initializePlayersPanel } from './players.js';

import { initializeTurnOrderPanel } from './turn-order.js';

import { initializeRulesPanel } from './rules.js';

import {
    focusFirstInvalidField,
    hasValidationErrors,
    validateEncounterActors,
} from './validation.js';

const encounter = createEncounterState();

const turnOrderPanel = initializeTurnOrderPanel(encounter, {
    onGenerateTurnOrder: generateTurnOrder,
});

function refreshDisplayedTurnOrder() {
    // Refreshes the current rendering only; buildRoundOrder() runs from generateTurnOrder().
    turnOrderPanel.refresh();
}

const monstersPanel = initializeMonstersPanel(encounter, {
    onEncounterChange: refreshDisplayedTurnOrder,
});

const playersPanel = initializePlayersPanel(encounter, {
    onPlayersChange: refreshDisplayedTurnOrder,
});

initializeRulesPanel({
    isRuleActive: (ruleId) => isRuleActive(encounter, ruleId),
    setRuleActive: (ruleId, active) => {
        setRuleActive(encounter, ruleId, active);
        turnOrderPanel.refresh();
    },
});

function generateTurnOrder() {
    monstersPanel.clearValidation();
    playersPanel.clearValidation();
    turnOrderPanel.clearValidation();

    const encounterValidationResult = validateEncounterActors(
        monstersPanel.getListElement(),
        playersPanel.getListElement(),
    );

    const monsterValidationResult = monstersPanel.validateForTurnOrder();
    const playerValidationResult = playersPanel.validateForTurnOrder();

    turnOrderPanel.showEncounterValidationErrors(encounterValidationResult);

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

    playersPanel.sync();
    buildRoundOrder(encounter);

    turnOrderPanel.refresh({ focusFirst: true });
}
