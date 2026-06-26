import { Buffer } from 'node:buffer';
import { afterEach, describe, expect, test, vi } from 'vitest';
import {
    assertCompatibleRpId,
    isRpIdCompatibleWithHostname,
    setupPrivateWebauthn,
} from '../../../assets/scripts/private/webauthn.js';
import { TestElement } from '../lab/dnd/dom-test-helpers.js';

describe('private WebAuthn helpers', () => {
    afterEach(() => {
        vi.unstubAllGlobals();
    });

    test('matches relying party id against the current hostname', () => {
        expect(isRpIdCompatibleWithHostname('localhost', 'localhost')).toBe(true);
        expect(isRpIdCompatibleWithHostname('example.com', 'admin.example.com')).toBe(true);
        expect(isRpIdCompatibleWithHostname('localhost', '127.0.0.1')).toBe(false);
    });

    test('reports a clear local URL when the relying party id does not match', () => {
        stubBrowser({
            hostname: '127.0.0.1',
            protocol: 'http:',
            port: '8000',
        });

        expect(() => assertCompatibleRpId({ rpId: 'localhost' }, '127.0.0.1'))
            .toThrow('Utilise http://localhost:8000 pour tester les passkeys.');
    });

    test('does not call passkey login when options rpId does not match the hostname', async () => {
        const credentials = {
            create: vi.fn(),
            get: vi.fn(),
        };
        const fetch = vi.fn(() => Promise.resolve(createJsonResponse({
            challenge: 'AA',
            rpId: 'localhost',
            allowCredentials: [],
            userVerification: 'required',
            status: 'ok',
            errorMessage: '',
        })));
        stubBrowser({
            credentials,
            fetch,
            hostname: '127.0.0.1',
            protocol: 'http:',
            port: '8000',
        });
        const { button, document, status } = createLoginWidget();

        setupPrivateWebauthn(document);
        button.click();
        await flushPromises();

        expect(fetch).toHaveBeenCalledOnce();
        expect(credentials.get).not.toHaveBeenCalled();
        expect(status.hidden).toBe(false);
        expect(status.dataset.statusType).toBe('error');
        expect(status.textContent).toContain('localhost');
        expect(status.textContent).toContain('http://localhost:8000');
    });

    test('continues passkey login when options rpId matches the hostname', async () => {
        const credentials = {
            create: vi.fn(),
            get: vi.fn(() => Promise.reject(new Error('Authenticator unavailable in test.'))),
        };
        const fetch = vi.fn(() => Promise.resolve(createJsonResponse({
            challenge: 'AA',
            rpId: 'localhost',
            allowCredentials: [],
            userVerification: 'required',
            status: 'ok',
            errorMessage: '',
        })));
        stubBrowser({
            credentials,
            fetch,
            hostname: 'localhost',
            protocol: 'http:',
            port: '8000',
        });
        const { button, document } = createLoginWidget();

        setupPrivateWebauthn(document);
        button.click();
        await flushPromises();

        expect(fetch).toHaveBeenCalledOnce();
        expect(credentials.get).toHaveBeenCalledOnce();
    });

    test('includes the csrf token in the passkey registration result payload', async () => {
        const credentials = {
            create: vi.fn(() => Promise.resolve({
                toJSON: () => ({
                    id: 'credential-id',
                    rawId: 'raw-credential-id',
                    type: 'public-key',
                    response: {
                        attestationObject: 'attestation-object',
                        clientDataJSON: 'client-data-json',
                    },
                    clientExtensionResults: {},
                }),
            })),
            get: vi.fn(),
        };
        const fetch = vi.fn()
            .mockResolvedValueOnce(createJsonResponse({
                challenge: 'AA',
                rp: { id: 'localhost', name: 'localhost' },
                user: { id: 'AA', name: 'private_admin', displayName: 'Administrateur prive' },
                pubKeyCredParams: [{ type: 'public-key', alg: -7 }],
                authenticatorSelection: {
                    userVerification: 'required',
                    residentKey: 'required',
                },
                attestation: 'none',
                excludeCredentials: [],
                status: 'ok',
                errorMessage: '',
            }))
            .mockResolvedValueOnce(createJsonResponse({
                status: 'ok',
                errorMessage: '',
            }));
        stubBrowser({
            credentials,
            fetch,
            hostname: 'localhost',
            protocol: 'http:',
            port: '8000',
        });
        const { button, document } = createRegisterWidget();

        setupPrivateWebauthn(document);
        button.click();
        await flushPromises();

        expect(credentials.create).toHaveBeenCalledOnce();
        expect(fetch).toHaveBeenCalledTimes(2);

        const resultBody = JSON.parse(fetch.mock.calls[1][1].body);
        expect(resultBody.csrfToken).toBe('csrf-token');
        expect(resultBody.rawId).toBe('raw-credential-id');
        expect(resultBody.response.attestationObject).toBe('attestation-object');
    });
});

function createLoginWidget() {
    const container = new TestElement('section');
    container.dataset.optionsUrl = '/private/security/passkeys/login/options';
    container.dataset.resultUrl = '/private/security/passkeys/login';

    const button = new TestElement('button');
    button.dataset.privateWebauthnLoginButton = '';
    container.appendChild(button);

    const status = new TestElement('p');
    status.dataset.privateWebauthnLoginStatus = '';
    status.hidden = true;
    container.appendChild(status);

    container.querySelector = selector => {
        if (selector === '[data-private-webauthn-login-button]') {
            return button;
        }

        if (selector.includes('[data-private-webauthn-login-status]')) {
            return status;
        }

        return null;
    };

    return {
        button,
        status,
        document: {
            querySelectorAll: selector => (selector === '[data-private-webauthn-login]' ? [container] : []),
        },
    };
}

function createRegisterWidget() {
    const container = new TestElement('section');
    container.dataset.optionsUrl = '/private/security/passkeys/register/options';
    container.dataset.resultUrl = '/private/security/passkeys/register';
    container.dataset.csrfToken = 'csrf-token';

    const button = new TestElement('button');
    button.dataset.privateWebauthnRegisterButton = '';
    container.appendChild(button);

    const form = new TestElement('form');
    form.dataset.privateWebauthnRegisterForm = '';
    const labelInput = new TestElement('input');
    labelInput.value = 'Passkey 1';
    form.querySelector = selector => {
        if (selector === 'input[name="label"]') {
            return labelInput;
        }

        return null;
    };
    form.appendChild(labelInput);
    container.appendChild(form);

    const status = new TestElement('p');
    status.dataset.privateWebauthnRegisterStatus = '';
    status.hidden = true;
    container.appendChild(status);

    container.querySelector = selector => {
        if (selector === '[data-private-webauthn-register-button]') {
            return button;
        }

        if (selector === '[data-private-webauthn-register-form]') {
            return form;
        }

        if (selector.includes('[data-private-webauthn-register-status]')) {
            return status;
        }

        return null;
    };

    return {
        button,
        status,
        document: {
            querySelectorAll: selector => (selector === '[data-private-webauthn-register]' ? [container] : []),
        },
    };
}

function stubBrowser(options = {}) {
    const credentials = options.credentials ?? {
        create: vi.fn(),
        get: vi.fn(),
    };

    vi.stubGlobal('navigator', { credentials });
    vi.stubGlobal('window', {
        PublicKeyCredential: function PublicKeyCredential() {},
        atob: value => Buffer.from(value, 'base64').toString('binary'),
        btoa: value => Buffer.from(value, 'binary').toString('base64'),
        clearTimeout: vi.fn(),
        fetch: options.fetch ?? vi.fn(),
        isSecureContext: true,
        location: {
            assign: vi.fn(),
            hostname: options.hostname ?? 'localhost',
            port: options.port ?? '8000',
            protocol: options.protocol ?? 'http:',
            reload: vi.fn(),
        },
        setTimeout: vi.fn(() => 1),
        structuredClone: value => JSON.parse(JSON.stringify(value)),
    });
}

function createJsonResponse(data, ok = true) {
    return {
        headers: {
            get: header => (header.toLowerCase() === 'content-type' ? 'application/json' : ''),
        },
        json: () => Promise.resolve(data),
        ok,
        redirected: false,
    };
}

async function flushPromises() {
    await Promise.resolve();
    await Promise.resolve();
    await Promise.resolve();
}
