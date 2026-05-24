// DOM controller for the rules modal and rule toggles.
// Rule changes update encounter state through callbacks; the turn order is
// rebuilt only when the main generation flow runs.

export class RulesPanel {
    constructor(callbacks) {
        this.callbacks = callbacks;
        this.openButton = document.getElementById('openRulesPanel');
        this.rulesModal = document.getElementById('rulesModal');
        this.modalContent = this.rulesModal?.querySelector('.rules-modal__content');
        this.closeButtons = this.rulesModal?.querySelectorAll('[data-rules-close]') ?? [];
        this.ruleToggles = document.querySelectorAll('[data-rule-toggle]');
    }

    start() {
        this.bindRuleToggles();
        this.bindRulesModal();
    }

    bindRuleToggles() {
        this.ruleToggles.forEach(ruleToggle => {
            const ruleId = ruleToggle.dataset.ruleToggle;

            ruleToggle.checked = this.callbacks.isRuleActive(ruleId);
            ruleToggle.addEventListener('change', () => {
                this.callbacks.setRuleActive(ruleId, ruleToggle.checked);
            });
        });
    }

    bindRulesModal() {
        if (!this.openButton || !this.rulesModal) {
            return;
        }

        this.openButton.addEventListener('click', () => {
            openRulesModal(this.openButton, this.rulesModal, this.modalContent);
        });

        this.closeButtons.forEach(closeButton => {
            closeButton.addEventListener('click', () => {
                closeRulesModal(this.openButton, this.rulesModal);
            });
        });

        document.addEventListener('keydown', event => {
            if (event.key === 'Escape' && !this.rulesModal.hidden) {
                closeRulesModal(this.openButton, this.rulesModal);
            }
        });
    }
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
