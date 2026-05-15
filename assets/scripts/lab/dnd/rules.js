export function initializeRulesPanel(callbacks) {
    const openButton = document.getElementById('openRulesPanel');
    const rulesModal = document.getElementById('rulesModal');
    const modalContent = rulesModal?.querySelector('.rules-modal__content');
    const closeButtons = rulesModal?.querySelectorAll('[data-rules-close]') ?? [];
    const ruleToggles = document.querySelectorAll('[data-rule-toggle]');

    ruleToggles.forEach(ruleToggle => {
        const ruleId = ruleToggle.dataset.ruleToggle;

        ruleToggle.checked = callbacks.isRuleActive(ruleId);
        ruleToggle.addEventListener('change', () => {
            callbacks.setRuleActive(ruleId, ruleToggle.checked);
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
