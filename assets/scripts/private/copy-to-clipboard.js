const copyButtons = document.querySelectorAll('[data-copy-to-clipboard]');

async function copyText(value) {
    if (navigator.clipboard?.writeText) {
        await navigator.clipboard.writeText(value);
        return;
    }

    throw new Error('Clipboard API not supported');
}

copyButtons.forEach((button) => {
    const defaultLabel = button.dataset.copyLabel || button.getAttribute('aria-label') || 'Copier';
    const successLabel = button.dataset.copySuccessLabel || 'Copié';
    const copyIcon = button.querySelector('[data-copy-icon="copy"]');
    const successIcon = button.querySelector('[data-copy-icon="success"]');
    let resetTimer;

    const setState = (copied, label) => {
        copyIcon?.toggleAttribute('hidden', copied);
        successIcon?.toggleAttribute('hidden', !copied);
        button.setAttribute('aria-label', label);
        button.setAttribute('title', label);
    };

    setState(false, defaultLabel);

    button.addEventListener('click', async () => {
        const value = button.dataset.copyValue;

        if (!value) {
            return;
        }

        try {
            await copyText(value);
            button.classList.add('is-copied');
            setState(true, successLabel);
            button.setAttribute('aria-live', 'polite');

            window.clearTimeout(resetTimer);
            resetTimer = window.setTimeout(() => {
                button.classList.remove('is-copied');
                setState(false, defaultLabel);
            }, 1500);
        } catch {
            button.classList.add('is-copied');
            setState(false, 'Copie indisponible');

            window.clearTimeout(resetTimer);
            resetTimer = window.setTimeout(() => {
                button.classList.remove('is-copied');
                setState(false, defaultLabel);
            }, 1500);
        }
    });
});
