const RULES = {
    skipLowInitiative: {
        id: 'skip-low-initiative',
        defaultActive: true,
    },
    extraTurnOnTwenty: {
        id: 'extra-turn-on-twenty',
        defaultActive: true,
    },
};

const activeRules = new Set(
    Object.values(RULES)
        .filter(rule => rule.defaultActive)
        .map(rule => rule.id),
);

export function initializeRulesPanel() {
    const openButton = document.getElementById('openRulesPanel');
    const rulesModal = document.getElementById('rulesModal');
    const modalContent = rulesModal?.querySelector('.rules-modal__content');
    const closeButtons = rulesModal?.querySelectorAll('[data-rules-close]') ?? [];
    const ruleToggles = document.querySelectorAll('[data-rule-toggle]');

    ruleToggles.forEach(ruleToggle => {
        const ruleId = ruleToggle.dataset.ruleToggle;

        ruleToggle.checked = isRuleActive(ruleId);
        ruleToggle.addEventListener('change', () => {
            setRuleActive(ruleId, ruleToggle.checked);
        });
    });

    if (!openButton || !rulesModal) {
        return;
    }

    openButton.addEventListener('click', () => {
        openRulesModal(openButton, rulesModal, modalContent);
    });

    closeButtons.forEach(closeButton => {
        closeButton.addEventListener('click', () => {
            closeRulesModal(openButton, rulesModal);
        });
    });

    document.addEventListener('keydown', event => {
        if (event.key === 'Escape' && !rulesModal.hidden) {
            closeRulesModal(openButton, rulesModal);
        }
    });
}

export function shouldSkipTurn(actor) {
    if (!isRuleActive(RULES.skipLowInitiative.id)) {
        return false;
    }

    return actor.initiative <= 1;
}

export function getTurnCount(actor) {
    if (!isRuleActive(RULES.extraTurnOnTwenty.id)) {
        return 1;
    }

    return actor.roll === 20 ? 2 : 1;
}

function setRuleActive(ruleId, isActive) {
    if (!isKnownRule(ruleId)) {
        return;
    }

    if (isActive) {
        activeRules.add(ruleId);
        return;
    }

    activeRules.delete(ruleId);
}

function isRuleActive(ruleId) {
    return activeRules.has(ruleId);
}

function isKnownRule(ruleId) {
    return Object.values(RULES).some(rule => rule.id === ruleId);
}

function openRulesModal(openButton, rulesModal, modalContent) {
    openButton.setAttribute('aria-expanded', 'true');
    rulesModal.hidden = false;
    modalContent?.focus();
}

function closeRulesModal(openButton, rulesModal) {
    openButton.setAttribute('aria-expanded', 'false');
    rulesModal.hidden = true;
    openButton.focus();
}
