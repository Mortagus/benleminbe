import { afterEach, describe, expect, test, vi } from 'vitest';
import {
    initializeRulesPanel,
    RulesPanel,
} from '../../../../assets/scripts/lab/dnd/rules.js';
import { TestElement } from './dom-test-helpers.js';

describe('rules panel', () => {
    afterEach(() => {
        delete globalThis.document;
    });

    test('syncs rule toggles and reports rule changes through callbacks', () => {
        const skipLowInitiativeToggle = createRuleToggle('skip-low-initiative');
        const extraTurnToggle = createRuleToggle('extra-turn-on-twenty');
        const setRuleActive = vi.fn();

        globalThis.document = createRulesDocument({
            ruleToggles: [
                skipLowInitiativeToggle,
                extraTurnToggle,
            ],
        });

        initializeRulesPanel({
            isRuleActive: ruleId => ruleId === 'skip-low-initiative',
            setRuleActive,
        });

        expect(skipLowInitiativeToggle.checked).toBe(true);
        expect(extraTurnToggle.checked).toBe(false);

        extraTurnToggle.checked = true;
        extraTurnToggle.dispatchEvent({ type: 'change' });

        expect(setRuleActive).toHaveBeenCalledWith('extra-turn-on-twenty', true);
    });

    test('keeps initializeRulesPanel as a compatibility wrapper', () => {
        globalThis.document = createRulesDocument({
            ruleToggles: [],
        });

        const panel = initializeRulesPanel({
            isRuleActive: () => false,
            setRuleActive: () => {},
        });

        expect(panel).toBeInstanceOf(RulesPanel);
    });
});

function createRuleToggle(ruleId) {
    const input = new TestElement('input');
    input.dataset.ruleToggle = ruleId;
    input.checked = false;

    return input;
}

function createRulesDocument({ ruleToggles }) {
    return {
        getElementById: () => null,
        querySelectorAll: selector => selector === '[data-rule-toggle]' ? ruleToggles : [],
        addEventListener: () => {},
    };
}
