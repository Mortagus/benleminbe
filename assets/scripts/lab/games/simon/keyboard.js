import {
    loadSimonPreferences,
    normalizeSimonKeyboardKey,
    SIMON_DEFAULT_KEYBOARD_BINDINGS,
    SIMON_PREFERENCES_STORAGE_KEY,
    validateSimonKeyboardBindings,
    updateSimonPreferences,
} from './preferences.js';

export const SIMON_KEYBOARD_STORAGE_KEY = SIMON_PREFERENCES_STORAGE_KEY;

export const SIMON_KEYBOARD_ZONES = Object.freeze([
    Object.freeze({
        id: 'top-left',
        padIndex: 0,
        labelKey: 'top_left',
    }),
    Object.freeze({
        id: 'top-right',
        padIndex: 1,
        labelKey: 'top_right',
    }),
    Object.freeze({
        id: 'bottom-left',
        padIndex: 2,
        labelKey: 'bottom_left',
    }),
    Object.freeze({
        id: 'bottom-right',
        padIndex: 3,
        labelKey: 'bottom_right',
    }),
]);

export class SimonKeyboardSettings {
    constructor({ storage = globalThis.localStorage ?? null, bindings = null } = {}) {
        this.storage = storage;
        this.capturingZone = null;
        this.bindings = this.resolveInitialBindings(bindings);
        this.keyToZone = buildKeyToZoneIndex(this.bindings);
    }

    getBindings() {
        return { ...this.bindings };
    }

    getDisplayBindings() {
        return SIMON_KEYBOARD_ZONES.map(zone => ({
            ...zone,
            key: this.getBinding(zone.id),
        }));
    }

    getBinding(zoneId) {
        return this.bindings[zoneId] ?? SIMON_DEFAULT_KEYBOARD_BINDINGS[zoneId] ?? '';
    }

    getBindingLabel(zoneId) {
        return this.getBinding(zoneId);
    }

    startCapture(zoneId) {
        if (!isSimonKeyboardZone(zoneId)) {
            return false;
        }

        this.capturingZone = zoneId;

        return true;
    }

    cancelCapture() {
        this.capturingZone = null;
    }

    isCapturing() {
        return this.capturingZone !== null;
    }

    getCapturingZone() {
        return this.capturingZone;
    }

    applyCapturedKey(rawKey) {
        if (!this.capturingZone) {
            return {
                status: 'idle',
            };
        }

        const zoneId = this.capturingZone;
        const key = normalizeSimonKeyboardKey(rawKey);

        if (!key) {
            return {
                status: 'invalid',
            };
        }

        const duplicateZone = this.keyToZone.get(key);

        if (duplicateZone && duplicateZone !== zoneId) {
            return {
                status: 'duplicate',
                key,
                zoneId: duplicateZone,
                binding: this.getBinding(duplicateZone),
            };
        }

        this.bindings = {
            ...this.bindings,
            [zoneId]: key,
        };
        this.keyToZone = buildKeyToZoneIndex(this.bindings);
        this.capturingZone = null;
        this.persist();

        return {
            status: 'applied',
            zoneId,
            key,
            bindings: this.getBindings(),
        };
    }

    resetToDefault() {
        this.bindings = { ...SIMON_DEFAULT_KEYBOARD_BINDINGS };
        this.keyToZone = buildKeyToZoneIndex(this.bindings);
        this.capturingZone = null;
        this.persist();

        return {
            status: 'reset',
            bindings: this.getBindings(),
        };
    }

    resolvePadIndexForKey(rawKey) {
        const key = normalizeSimonKeyboardKey(rawKey);

        if (!key) {
            return null;
        }

        const zoneId = this.keyToZone.get(key);

        if (!zoneId) {
            return null;
        }

        return getZoneById(zoneId)?.padIndex ?? null;
    }

    getZoneIdForKey(rawKey) {
        const key = normalizeSimonKeyboardKey(rawKey);

        if (!key) {
            return null;
        }

        return this.keyToZone.get(key) ?? null;
    }

    resolveInitialBindings(bindings) {
        if (bindings) {
            const normalizedBindings = validateSimonKeyboardBindings(bindings);

            if (normalizedBindings) {
                return normalizedBindings;
            }
        }

        return loadSimonKeyboardBindings(this.storage);
    }

    persist() {
        saveSimonKeyboardBindings(this.bindings, this.storage);
    }
}

export function loadSimonKeyboardBindings(storage = globalThis.localStorage ?? null) {
    return { ...loadSimonPreferences(storage).keyboard.bindings };
}

export function saveSimonKeyboardBindings(bindings, storage = globalThis.localStorage ?? null) {
    const normalizedBindings = validateSimonKeyboardBindings(bindings);

    if (!normalizedBindings) {
        return false;
    }

    const result = updateSimonPreferences({
        keyboard: {
            bindings: normalizedBindings,
        },
    }, storage);

    return result.saved;
}

export function resetSimonKeyboardBindings(storage = globalThis.localStorage ?? null) {
    const bindings = { ...SIMON_DEFAULT_KEYBOARD_BINDINGS };

    updateSimonPreferences({
        keyboard: {
            bindings,
        },
    }, storage);

    return bindings;
}

export function describeSimonKeyboardBindings(bindings) {
    const normalizedBindings = validateSimonKeyboardBindings(bindings) ?? { ...SIMON_DEFAULT_KEYBOARD_BINDINGS };

    return SIMON_KEYBOARD_ZONES.map(zone => normalizedBindings[zone.id]).join(' / ');
}

export function isSimonKeyboardModifierShortcut(event) {
    return Boolean(event.altKey || event.ctrlKey || event.metaKey);
}

export function isSimonKeyboardDisallowedKey(rawKey) {
    return normalizeSimonKeyboardKey(rawKey) === null;
}

function buildKeyToZoneIndex(bindings) {
    const keyToZone = new Map();

    for (const [zoneId, key] of Object.entries(bindings)) {
        keyToZone.set(key, zoneId);
    }

    return keyToZone;
}

function getZoneById(zoneId) {
    return SIMON_KEYBOARD_ZONES.find(zone => zone.id === zoneId) ?? null;
}

function isSimonKeyboardZone(zoneId) {
    return SIMON_KEYBOARD_ZONES.some(zone => zone.id === zoneId);
}

export {
    SIMON_DEFAULT_KEYBOARD_BINDINGS,
    normalizeSimonKeyboardKey,
    validateSimonKeyboardBindings,
};
