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

const monstersPanel = initializeMonstersPanel(encounter, {
    onEncounterChange: turnOrderPanel.refresh,
});

const playersPanel = initializePlayersPanel(encounter, {
    onPlayersChange: turnOrderPanel.refresh,
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

    turnOrderPanel.refresh();
}
