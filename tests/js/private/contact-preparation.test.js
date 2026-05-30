import { describe, expect, test, vi } from 'vitest';
import { setupContactPreparationModal } from '../../../assets/scripts/private/contact-preparation.js';
import { TestElement } from '../lab/dnd/dom-test-helpers.js';

describe('private contact preparation modal', () => {
    test('fills the modal from the selected contact and closes on escape', () => {
        const html = new TestElement('html');
        const modal = createModal();
        const openButton = createOpenButton();
        const windowListeners = {};
        const windowObject = {
            addEventListener: (type, listener) => {
                windowListeners[type] = listener;
            },
        };
        const clipboard = {
            writeText: vi.fn(() => Promise.resolve()),
        };
        const document = {
            documentElement: html,
            querySelector: selector => (selector === '[data-contact-preparation-modal]' ? modal : null),
            querySelectorAll: selector => (selector === '[data-contact-preparation-open]' ? [openButton] : []),
        };

        setupContactPreparationModal(document, clipboard, windowObject);

        openButton.click();

        expect(modal.hidden).toBe(false);
        expect(html.classList.contains('private-modal-is-open')).toBe(true);
        expect(modal.querySelector('[data-contact-preparation-title]').textContent).toContain('Anne Example');
        expect(modal.querySelector('[data-contact-preparation-name]').textContent).toBe('Anne Example');
        expect(modal.querySelector('[data-contact-preparation-role]').textContent).toBe('Talent Acquisition Specialist');
        expect(modal.querySelector('[data-contact-preparation-organization]').textContent).toBe('Acme');
        expect(modal.querySelector('[data-contact-preparation-category]').textContent).toBe('Recrutement / RH');
        expect(modal.querySelector('[data-contact-preparation-recommended-channel]').textContent).toBe('LinkedIn');
        expect(modal.querySelector('[data-contact-preparation-subject]').value).toBe('Disponibilité freelance - développement web backend');
        expect(modal.querySelector('[data-contact-preparation-message]').value).toContain('Bonjour Anne,');
        expect(modal.querySelector('[data-contact-preparation-linkedin]').hidden).toBe(false);
        expect(modal.querySelector('[data-contact-preparation-linkedin]').getAttribute('href')).toBe('https://www.linkedin.com/in/anne-example');
        expect(modal.querySelector('[data-contact-preparation-email]').hidden).toBe(false);
        expect(modal.querySelector('[data-contact-preparation-email]').getAttribute('href')).toContain('mailto:anne@example.com');
        expect(modal.querySelector('[data-contact-preparation-phone]').getAttribute('href')).toBe('tel:+32475258941');
        expect(modal.querySelector('[data-contact-preparation-mark-form]').getAttribute('action')).toContain('/contacts/contact-1/mark-contacted');

        windowListeners.keydown({ key: 'Escape' });

        expect(modal.hidden).toBe(true);
        expect(html.classList.contains('private-modal-is-open')).toBe(false);
        expect(openButton.wasFocused).toBe(true);
    });

    test('copies the editable message and shows feedback', async () => {
        const html = new TestElement('html');
        const modal = createModal();
        const openButton = createOpenButton();
        const document = {
            documentElement: html,
            querySelector: selector => (selector === '[data-contact-preparation-modal]' ? modal : null),
            querySelectorAll: selector => (selector === '[data-contact-preparation-open]' ? [openButton] : []),
        };
        const clipboard = {
            writeText: vi.fn(() => Promise.resolve()),
        };
        const windowObject = {
            addEventListener: vi.fn(),
        };

        setupContactPreparationModal(document, clipboard, windowObject);
        openButton.click();

        const messageInput = modal.querySelector('[data-contact-preparation-message]');
        messageInput.value = 'Message ajusté';
        messageInput.dispatchEvent({ type: 'input' });
        modal.querySelector('[data-contact-preparation-copy]').click();

        await Promise.resolve();

        expect(clipboard.writeText).toHaveBeenCalledWith('Message ajusté');
        expect(modal.querySelector('[data-contact-preparation-feedback]').textContent).toBe('Message copié.');
    });
});

function createOpenButton() {
    const button = new TestElement('button');
    button.dataset.contactPreparationOpen = '';
    button.dataset.contactPreparation = JSON.stringify({
        id: 'contact-1',
        display_name: 'Anne Example',
        role: 'Talent Acquisition Specialist',
        organization: 'Acme',
        role_category_label: 'Recrutement / RH',
        profile_url: 'https://www.linkedin.com/in/anne-example',
        emails: ['anne@example.com'],
        phones: ['+32 475 25 89 41'],
        subject: 'Disponibilité freelance - développement web backend',
        message: 'Bonjour Anne,\n\nJe me permets de vous recontacter.',
        recommended_channel: 'linkedin',
        recommended_channel_label: 'LinkedIn',
        token: 'csrf-token',
    });

    return button;
}

function createModal() {
    const modal = new TestElement('div');
    modal.dataset.contactPreparationModal = '';
    modal.dataset.contactPreparationReturnTo = '/private/network/contacts';
    modal.hidden = true;

    const backdrop = new TestElement('div');
    backdrop.dataset.contactPreparationClose = '';
    modal.appendChild(backdrop);

    const title = new TestElement('h2');
    title.dataset.contactPreparationTitle = '';
    modal.appendChild(title);

    const name = new TestElement('dd');
    name.dataset.contactPreparationName = '';
    modal.appendChild(name);

    const role = new TestElement('dd');
    role.dataset.contactPreparationRole = '';
    modal.appendChild(role);

    const organization = new TestElement('dd');
    organization.dataset.contactPreparationOrganization = '';
    modal.appendChild(organization);

    const category = new TestElement('dd');
    category.dataset.contactPreparationCategory = '';
    modal.appendChild(category);

    const recommendedChannel = new TestElement('dd');
    recommendedChannel.dataset.contactPreparationRecommendedChannel = '';
    modal.appendChild(recommendedChannel);

    const linkedinLink = new TestElement('a');
    linkedinLink.dataset.contactPreparationLinkedin = '';
    linkedinLink.hidden = true;
    modal.appendChild(linkedinLink);

    const emailLink = new TestElement('a');
    emailLink.dataset.contactPreparationEmail = '';
    emailLink.hidden = true;
    modal.appendChild(emailLink);

    const phoneLink = new TestElement('a');
    phoneLink.dataset.contactPreparationPhone = '';
    phoneLink.hidden = true;
    modal.appendChild(phoneLink);

    const subject = new TestElement('input');
    subject.dataset.contactPreparationSubject = '';
    modal.appendChild(subject);

    const message = new TestElement('textarea');
    message.dataset.contactPreparationMessage = '';
    modal.appendChild(message);

    const feedback = new TestElement('p');
    feedback.dataset.contactPreparationFeedback = '';
    feedback.hidden = true;
    modal.appendChild(feedback);

    const copyButton = new TestElement('button');
    copyButton.dataset.contactPreparationCopy = '';
    modal.appendChild(copyButton);

    const markForm = new TestElement('form');
    markForm.dataset.contactPreparationMarkForm = '';
    markForm.dataset.contactPreparationMarkActionTemplate = '/private/network/contacts/__CONTACT_ID__/mark-contacted';
    const token = new TestElement('input');
    token.setAttribute('name', '_token');
    markForm.appendChild(token);
    const returnTo = new TestElement('input');
    returnTo.setAttribute('name', 'return_to');
    markForm.appendChild(returnTo);
    const submit = new TestElement('button');
    submit.textContent = 'Marquer comme contacté';
    markForm.appendChild(submit);
    modal.appendChild(markForm);

    return modal;
}
