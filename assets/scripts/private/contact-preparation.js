function copyText(value, clipboard = navigator.clipboard) {
    if (clipboard?.writeText) {
        return clipboard.writeText(value);
    }

    return Promise.reject(new Error('Clipboard API not supported'));
}

export function setupContactPreparationModal(document, clipboard = navigator.clipboard, windowObject = globalThis.window) {
    const modal = document.querySelector('[data-contact-preparation-modal]');
    if (!modal) {
        return;
    }

    const openButtons = document.querySelectorAll('[data-contact-preparation-open]');
    const closeButtons = modal.querySelectorAll('[data-contact-preparation-close]');
    const title = modal.querySelector('[data-contact-preparation-title]');
    const name = modal.querySelector('[data-contact-preparation-name]');
    const role = modal.querySelector('[data-contact-preparation-role]');
    const organization = modal.querySelector('[data-contact-preparation-organization]');
    const category = modal.querySelector('[data-contact-preparation-category]');
    const recommendedChannel = modal.querySelector('[data-contact-preparation-recommended-channel]');
    const subjectInput = modal.querySelector('[data-contact-preparation-subject]');
    const messageInput = modal.querySelector('[data-contact-preparation-message]');
    const copyButton = modal.querySelector('[data-contact-preparation-copy]');
    const feedback = modal.querySelector('[data-contact-preparation-feedback]');
    const linkedinLink = modal.querySelector('[data-contact-preparation-linkedin]');
    const emailLink = modal.querySelector('[data-contact-preparation-email]');
    const phoneLink = modal.querySelector('[data-contact-preparation-phone]');
    const markForm = modal.querySelector('[data-contact-preparation-mark-form]');
    const markToken = markForm?.querySelector('input[name="_token"]');
    const markReturnTo = markForm?.querySelector('input[name="return_to"]');
    const markActionTemplate = markForm?.dataset.contactPreparationMarkActionTemplate || '';
    const html = document.documentElement;
    let activeTrigger = null;
    let feedbackResetTimer = null;

    const setFeedback = (message = '') => {
        if (!feedback) {
            return;
        }

        windowObject?.clearTimeout?.(feedbackResetTimer);

        feedback.textContent = message;
        feedback.hidden = message === '';

        if (message !== '') {
            feedbackResetTimer = windowObject?.setTimeout?.(() => {
                feedback.textContent = '';
                feedback.hidden = true;
            }, 1500) ?? null;
        }
    };

    const setChannelLink = (link, href, hidden) => {
        if (!link) {
            return;
        }

        link.hidden = hidden;
        if (hidden) {
            link.removeAttribute('href');
            return;
        }

        link.setAttribute('href', href);
    };

    const sanitizeTel = value => (value || '').replace(/[^0-9+]/g, '');

    const updateEmailLink = () => {
        if (!emailLink || !subjectInput || !messageInput) {
            return;
        }

        const email = emailLink.dataset.contactPreparationAddress || '';
        if (!email) {
            setChannelLink(emailLink, '', true);
            return;
        }

        const subject = subjectInput.value.trim();
        const body = messageInput.value.trim();
        const href = `mailto:${email}?subject=${encodeURIComponent(subject)}&body=${encodeURIComponent(body)}`;
        setChannelLink(emailLink, href, false);
    };

    const syncModal = payload => {
        if (title) {
            title.textContent = `Préparer un contact${payload.display_name ? ` - ${payload.display_name}` : ''}`;
        }

        if (name) {
            name.textContent = payload.display_name || 'Contact';
        }

        if (role) {
            role.textContent = payload.role || 'Non renseigné';
        }

        if (organization) {
            organization.textContent = payload.organization || 'Sans entreprise renseignée';
        }

        if (category) {
            category.textContent = payload.role_category_label || 'Autre';
        }

        if (recommendedChannel) {
            recommendedChannel.textContent = payload.recommended_channel_label || 'Aucun canal exploitable';
        }

        if (subjectInput) {
            subjectInput.value = payload.subject || '';
        }

        if (messageInput) {
            messageInput.value = payload.message || '';
        }

        if (linkedinLink) {
            const linkedinUrl = payload.profile_url || '';
            setChannelLink(linkedinLink, linkedinUrl, linkedinUrl === '');
        }

        if (emailLink) {
            emailLink.dataset.contactPreparationAddress = (Array.isArray(payload.emails) ? payload.emails[0] : '') || '';
            setChannelLink(emailLink, '', !(Array.isArray(payload.emails) && payload.emails[0]));
        }

        if (phoneLink) {
            const phone = sanitizeTel((Array.isArray(payload.phones) ? payload.phones[0] : '') || '');
            setChannelLink(phoneLink, phone ? `tel:${phone}` : '', phone === '');
        }

        if (markForm && markActionTemplate) {
            markForm.setAttribute('action', markActionTemplate.replace('__CONTACT_ID__', payload.id || ''));
        }

        if (markToken) {
            markToken.value = payload.token || '';
        }

        if (markReturnTo) {
            markReturnTo.value = payload.return_to || '';
        }

        setFeedback('');
        updateEmailLink();
    };

    const openModal = trigger => {
        activeTrigger = trigger;

        let payload = {};

        try {
            payload = JSON.parse(trigger.dataset.contactPreparation || '{}');
        } catch {
            payload = {};
        }

        payload.return_to = modal.dataset.contactPreparationReturnTo || '';

        syncModal(payload);

        modal.hidden = false;
        html.classList.add('private-modal-is-open');

        (subjectInput || messageInput || modal).focus?.();
    };

    const closeModal = () => {
        modal.hidden = true;
        html.classList.remove('private-modal-is-open');

        if (activeTrigger?.focus) {
            activeTrigger.focus();
        }

        activeTrigger = null;
    };

    openButtons.forEach(button => {
        button.addEventListener('click', () => {
            openModal(button);
        });
    });

    closeButtons.forEach(button => {
        button.addEventListener('click', () => {
            closeModal();
        });
    });

    modal.addEventListener('click', event => {
        if (event.target?.hasAttribute?.('data-contact-preparation-close')) {
            closeModal();
        }
    });

    windowObject?.addEventListener?.('keydown', event => {
        if (event.key === 'Escape' && !modal.hidden) {
            closeModal();
        }
    });

    copyButton?.addEventListener('click', async () => {
        if (!messageInput?.value) {
            return;
        }

        try {
            await copyText(messageInput.value, clipboard);
            setFeedback('Message copié.');
        } catch {
            setFeedback('Copie indisponible.');
        }
    });

    subjectInput?.addEventListener('input', updateEmailLink);
    messageInput?.addEventListener('input', updateEmailLink);

    updateEmailLink();
}

export default setupContactPreparationModal;
