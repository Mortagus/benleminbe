const TEXT_STATUS_DELAY_MS = 2500;
function isSupported() {
    return typeof window.PublicKeyCredential !== 'undefined'
        && typeof navigator !== 'undefined'
        && typeof navigator.credentials !== 'undefined'
        && typeof navigator.credentials.create === 'function'
        && typeof navigator.credentials.get === 'function';
}

function base64UrlToBuffer(value) {
    const normalized = value.replace(/-/g, '+').replace(/_/g, '/');
    const padding = '='.repeat((4 - (normalized.length % 4)) % 4);
    const binary = window.atob(normalized + padding);
    const bytes = new Uint8Array(binary.length);

    for (let index = 0; index < binary.length; index += 1) {
        bytes[index] = binary.charCodeAt(index);
    }

    return bytes.buffer;
}

function bufferToBase64Url(buffer) {
    const bytes = new Uint8Array(buffer);
    let binary = '';

    for (let index = 0; index < bytes.byteLength; index += 1) {
        binary += String.fromCharCode(bytes[index]);
    }

    return window.btoa(binary).replace(/\+/g, '-').replace(/\//g, '_').replace(/=+$/u, '');
}

function normalizeRequestOptions(options) {
    const normalized = typeof window.structuredClone === 'function'
        ? window.structuredClone(options)
        : JSON.parse(JSON.stringify(options));
    delete normalized.status;
    delete normalized.errorMessage;

    if (normalized.challenge) {
        normalized.challenge = base64UrlToBuffer(normalized.challenge);
    }

    if (normalized.user?.id) {
        normalized.user.id = base64UrlToBuffer(normalized.user.id);
    }

    for (const key of ['allowCredentials', 'excludeCredentials']) {
        if (!Array.isArray(normalized[key])) {
            continue;
        }

        normalized[key] = normalized[key].map((credential) => ({
            ...credential,
            id: base64UrlToBuffer(credential.id),
        }));
    }

    return normalized;
}

export function isRpIdCompatibleWithHostname(rpId, hostname) {
    if (!rpId) {
        return true;
    }

    const normalizedRpId = rpId.toLowerCase();
    const normalizedHostname = hostname.toLowerCase();

    return normalizedHostname === normalizedRpId
        || normalizedHostname.endsWith(`.${normalizedRpId}`);
}

function getOptionsRpId(options) {
    return options.rpId ?? options.rp?.id ?? '';
}

function getRpIdMismatchMessage(rpId) {
    const port = window.location.port ? `:${window.location.port}` : '';
    const expectedOrigin = `${window.location.protocol}//${rpId}${port}`;

    return `Cette URL ne correspond pas au domaine Passkey configuré (${rpId}). Utilise ${expectedOrigin} pour tester les passkeys.`;
}

export function assertCompatibleRpId(options, hostname = window.location.hostname) {
    const rpId = getOptionsRpId(options);
    if (isRpIdCompatibleWithHostname(rpId, hostname)) {
        return;
    }

    throw new Error(getRpIdMismatchMessage(rpId));
}

function serializeCredential(credential) {
    if (typeof credential.toJSON === 'function') {
        const json = credential.toJSON();
        if (json) {
            return json;
        }
    }

    const response = credential.response;
    const json = {
        id: credential.id,
        rawId: bufferToBase64Url(credential.rawId),
        type: credential.type,
        response: {
            clientDataJSON: bufferToBase64Url(response.clientDataJSON),
        },
        clientExtensionResults: credential.getClientExtensionResults(),
    };

    if ('attestationObject' in response) {
        json.response.attestationObject = bufferToBase64Url(response.attestationObject);
    }

    if ('authenticatorData' in response) {
        json.response.authenticatorData = bufferToBase64Url(response.authenticatorData);
        json.response.signature = bufferToBase64Url(response.signature);
        if (response.userHandle) {
            json.response.userHandle = bufferToBase64Url(response.userHandle);
        }
    }

    return json;
}

async function postJson(url, payload) {
    const response = await window.fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            Accept: 'application/json',
        },
        credentials: 'same-origin',
        body: JSON.stringify(payload),
    });

    const contentType = response.headers.get('content-type') ?? '';
    const data = contentType.includes('application/json')
        ? await response.json()
        : null;

    return { response, data };
}

function setStatus(container, message, type = 'info') {
    const target = container.querySelector('[data-private-webauthn-login-status], [data-private-webauthn-register-status]');
    if (!target) {
        return;
    }

    target.hidden = false;
    target.textContent = message;
    target.dataset.statusType = type;

    window.clearTimeout(target._privateWebauthnTimer);
    if (type === 'error') {
        return;
    }

    target._privateWebauthnTimer = window.setTimeout(() => {
        target.hidden = true;
    }, TEXT_STATUS_DELAY_MS);
}

function getSecureContextMessage() {
    return "WebAuthn nécessite un contexte sécurisé. Utilise `https://benlemin.be` en production ou `http://localhost:8000` en local.";
}

function isLoopbackOrigin() {
    return window.location.hostname === 'localhost'
        || window.location.hostname === '::1';
}

function setButtonLoading(button, isLoading) {
    button.disabled = isLoading;
    button.dataset.loading = isLoading ? 'true' : 'false';
}

function setupLoginWidget(container) {
    const button = container.querySelector('[data-private-webauthn-login-button]');
    if (!button) {
        return;
    }

    if (!isSupported()) {
        button.disabled = true;
        setStatus(container, "WebAuthn n'est pas disponible dans ce navigateur.");
        return;
    }

    if (!window.isSecureContext && !isLoopbackOrigin()) {
        button.disabled = true;
        setStatus(container, getSecureContextMessage(), 'error');
        return;
    }

    button.addEventListener('click', async () => {
        setButtonLoading(button, true);
        setStatus(container, 'Préparation de la passkey...');

        try {
            const optionsUrl = container.dataset.optionsUrl ?? '';
            const resultUrl = container.dataset.resultUrl ?? '';
            const { response: optionsResponse, data: optionsData } = await postJson(optionsUrl, {});
            if (!optionsResponse.ok || !optionsData) {
                throw new Error(optionsData?.errorMessage ?? 'Impossible de préparer la connexion.');
            }

            assertCompatibleRpId(optionsData);
            const credential = await navigator.credentials.get({
                publicKey: normalizeRequestOptions(optionsData),
            });
            if (!credential) {
                throw new Error('Aucune passkey n a été sélectionnée.');
            }

            const payload = serializeCredential(credential);
            const { response: resultResponse, data: resultData } = await postJson(resultUrl, payload);
            if (!resultResponse.ok) {
                throw new Error(resultData?.errorMessage ?? 'La connexion a échoué.');
            }

            if (resultResponse.redirected) {
                window.location.assign(resultResponse.url);
                return;
            }

            if (resultData?.status === 'ok') {
                window.location.assign('/private');
                return;
            }

            throw new Error('La connexion a échoué.');
        } catch (error) {
            const message = error instanceof Error && /insecure/i.test(error.message)
                ? getSecureContextMessage()
                : error instanceof Error
                    ? error.message
                    : 'La connexion a échoué.';

            setStatus(container, message, 'error');
        } finally {
            setButtonLoading(button, false);
        }
    });
}

function setupRegisterWidget(container) {
    const button = container.querySelector('[data-private-webauthn-register-button]');
    const form = container.querySelector('[data-private-webauthn-register-form]');
    if (!button || !form) {
        return;
    }

    if (!isSupported()) {
        button.disabled = true;
        setStatus(container, "WebAuthn n'est pas disponible dans ce navigateur.");
        return;
    }

    if (!window.isSecureContext && !isLoopbackOrigin()) {
        button.disabled = true;
        setStatus(container, getSecureContextMessage(), 'error');
        return;
    }

    button.addEventListener('click', async () => {
        const labelField = form.querySelector('input[name="label"]');
        const label = labelField?.value?.trim() || '';
        const csrfToken = container.dataset.csrfToken ?? '';
        const optionsUrl = container.dataset.optionsUrl ?? '';
        const resultUrl = container.dataset.resultUrl ?? '';

        setButtonLoading(button, true);
        setStatus(container, 'Préparation de la nouvelle passkey...');

        try {
            const { response: optionsResponse, data: optionsData } = await postJson(optionsUrl, {
                csrfToken,
                label,
            });
            if (!optionsResponse.ok || !optionsData) {
                throw new Error(optionsData?.errorMessage ?? 'Impossible de préparer l enregistrement.');
            }

            assertCompatibleRpId(optionsData);
            const credential = await navigator.credentials.create({
                publicKey: normalizeRequestOptions(optionsData),
            });
            if (!credential) {
                throw new Error('Aucune passkey n a été créée.');
            }

            const payload = {
                ...serializeCredential(credential),
                csrfToken,
            };
            const { response: resultResponse, data: resultData } = await postJson(resultUrl, payload);
            if (!resultResponse.ok || resultData?.status !== 'ok') {
                throw new Error(resultData?.errorMessage ?? 'L enregistrement a échoué.');
            }

            window.location.reload();
        } catch (error) {
            const message = error instanceof Error && /insecure/i.test(error.message)
                ? getSecureContextMessage()
                : error instanceof Error
                    ? error.message
                    : "L'enregistrement a échoué.";

            setStatus(container, message, 'error');
        } finally {
            setButtonLoading(button, false);
        }
    });
}

export function setupPrivateWebauthn(document) {
    for (const container of document.querySelectorAll('[data-private-webauthn-login]')) {
        setupLoginWidget(container);
    }

    for (const container of document.querySelectorAll('[data-private-webauthn-register]')) {
        setupRegisterWidget(container);
    }
}
