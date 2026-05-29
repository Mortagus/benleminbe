import { describe, expect, test, vi } from 'vitest';
import { setupPrivateFormSubmitFeedback } from '../../../assets/scripts/private/form-submit-feedback.js';
import { TestElement } from '../lab/dnd/dom-test-helpers.js';

describe('private form submit feedback', () => {
    test('keeps the page normal when confirmation is cancelled', () => {
        const root = new TestElement('html');
        const form = createConfirmableForm();
        const document = createDocumentDouble(root, form);
        const confirm = vi.fn(() => false);

        setupPrivateFormSubmitFeedback(document, confirm);

        const preventDefault = vi.fn();
        form.dispatchEvent({ type: 'submit', preventDefault });

        expect(confirm).toHaveBeenCalledWith('Tout réinitialiser ?');
        expect(preventDefault).toHaveBeenCalledOnce();
        expect(root.classList.contains('private-is-processing')).toBe(false);
        expect(form.querySelector('button').disabled).toBe(false);
        expect(form.querySelector('button').textContent).toBe('Tout réinitialiser');
        expect(form.querySelector('button').getAttribute('aria-busy')).toBe(null);
    });

    test('switches to progress state when confirmation is accepted', () => {
        const root = new TestElement('html');
        const form = createConfirmableForm();
        const document = createDocumentDouble(root, form);
        const confirm = vi.fn(() => true);

        setupPrivateFormSubmitFeedback(document, confirm);

        const preventDefault = vi.fn();
        form.dispatchEvent({ type: 'submit', preventDefault });

        expect(confirm).toHaveBeenCalledWith('Tout réinitialiser ?');
        expect(preventDefault).not.toHaveBeenCalled();
        expect(root.classList.contains('private-is-processing')).toBe(true);
        expect(form.querySelector('button').disabled).toBe(true);
        expect(form.querySelector('button').textContent).toBe('Réinitialisation en cours…');
        expect(form.querySelector('button').getAttribute('aria-busy')).toBe('true');
        expect(form.querySelector('button').dataset.originalLabel).toBe('Tout réinitialiser');
    });
});

function createConfirmableForm() {
    const form = new TestElement('form');
    form.dataset.privateConfirm = 'Tout réinitialiser ?';
    form.dataset.privateProgressSubmit = 'Réinitialisation en cours…';

    const button = new TestElement('button');
    button.textContent = 'Tout réinitialiser';
    button.setAttribute('type', 'submit');
    form.appendChild(button);

    return form;
}

function createDocumentDouble(root, form) {
    return {
        documentElement: root,
        querySelectorAll: selector => (selector === '[data-private-confirm]' ? [form] : []),
    };
}
