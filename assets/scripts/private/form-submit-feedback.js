export function setupPrivateFormSubmitFeedback(document, confirmFn = globalThis.confirm?.bind(globalThis) ?? (() => true)) {
    const forms = document.querySelectorAll('[data-private-confirm]');
    if (forms.length === 0) {
        return;
    }

    const documentElement = document.documentElement;

    forms.forEach((form) => {
        form.addEventListener('submit', (event) => {
            const confirmMessage = form.dataset.privateConfirm;
            if (confirmMessage && !confirmFn(confirmMessage)) {
                event.preventDefault();
                return;
            }

            const submitButton = form.querySelector('button');
            if (submitButton && typeof submitButton === 'object') {
                submitButton.disabled = true;
                submitButton.setAttribute?.('aria-busy', 'true');
                submitButton.dataset.originalLabel = submitButton.textContent ?? '';
                submitButton.textContent = form.dataset.privateProgressSubmit ?? submitButton.textContent ?? '';
            }

            if (form.dataset.privateProgressSubmit) {
                documentElement.classList.add('private-is-processing');
            }
        });
    });
}
