export const SIMON_KEYBOARD_STORAGE_KEY = 'benleminbe-lab-simon-keyboard-bindings';

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

export const SIMON_DEFAULT_KEYBOARD_BINDINGS = Object.freeze({
    'top-left': 'A',
    'top-right': 'Z',
    'bottom-left': 'Q',
    'bottom-right': 'S',
});

const SIMON_KEYBOARD_VERSION = 1;
const SIMON_KEYBOARD_ZONE_ALIASES = Object.freeze({
    'top-left': ['top-left', 'topLeft', 'top_left'],
    'top-right': ['top-right', 'topRight', 'top_right'],
    'bottom-left': ['bottom-left', 'bottomLeft', 'bottom_left'],
    'bottom-right': ['bottom-right', 'bottomRight', 'bottom_right'],
});

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
    if (!storage) {
        return { ...SIMON_DEFAULT_KEYBOARD_BINDINGS };
    }

    try {
        const rawValue = storage.getItem(SIMON_KEYBOARD_STORAGE_KEY);

        if (rawValue === null) {
            return { ...SIMON_DEFAULT_KEYBOARD_BINDINGS };
        }

        const parsedValue = JSON.parse(rawValue);
        const bindings = coerceSimonKeyboardBindings(parsedValue);

        if (bindings) {
            return bindings;
        }

        storage.removeItem?.(SIMON_KEYBOARD_STORAGE_KEY);

        return { ...SIMON_DEFAULT_KEYBOARD_BINDINGS };
    } catch {
        storage.removeItem?.(SIMON_KEYBOARD_STORAGE_KEY);

        return { ...SIMON_DEFAULT_KEYBOARD_BINDINGS };
    }
}

export function saveSimonKeyboardBindings(bindings, storage = globalThis.localStorage ?? null) {
    if (!storage) {
        return false;
    }

    const normalizedBindings = validateSimonKeyboardBindings(bindings);

    if (!normalizedBindings) {
        return false;
    }

    try {
        storage.setItem(
            SIMON_KEYBOARD_STORAGE_KEY,
            JSON.stringify({
                version: SIMON_KEYBOARD_VERSION,
                bindings: normalizedBindings,
            }),
        );

        return true;
    } catch {
        return false;
    }
}

export function resetSimonKeyboardBindings(storage = globalThis.localStorage ?? null) {
    const bindings = { ...SIMON_DEFAULT_KEYBOARD_BINDINGS };

    saveSimonKeyboardBindings(bindings, storage);

    return bindings;
}

export function validateSimonKeyboardBindings(bindings) {
    if (!bindings || typeof bindings !== 'object' || Array.isArray(bindings)) {
        return null;
    }

    const normalizedBindings = {};

    for (const zone of SIMON_KEYBOARD_ZONES) {
        const rawKey = readBindingForZone(bindings, zone.id);
        const key = normalizeSimonKeyboardKey(rawKey);

        if (!key || hasDuplicateBinding(normalizedBindings, key)) {
            return null;
        }

        normalizedBindings[zone.id] = key;
    }

    return normalizedBindings;
}

export function normalizeSimonKeyboardKey(rawKey) {
    if (typeof rawKey !== 'string' || rawKey.length === 0) {
        return null;
    }

    if (rawKey.length !== 1) {
        return null;
    }

    if (rawKey === ' ' || rawKey === '\t' || rawKey === '\n' || rawKey === '\r' || rawKey === '\u00A0') {
        return null;
    }

    const normalizedKey = rawKey.toUpperCase();

    return normalizedKey.trim() === '' ? null : normalizedKey;
}

export function isSimonKeyboardModifierShortcut(event) {
    return Boolean(event.altKey || event.ctrlKey || event.metaKey);
}

export function isSimonKeyboardDisallowedKey(rawKey) {
    return normalizeSimonKeyboardKey(rawKey) === null;
}

export function describeSimonKeyboardBindings(bindings) {
    const normalizedBindings = validateSimonKeyboardBindings(bindings) ?? { ...SIMON_DEFAULT_KEYBOARD_BINDINGS };

    return SIMON_KEYBOARD_ZONES.map(zone => normalizedBindings[zone.id]).join(' / ');
}

function coerceSimonKeyboardBindings(value) {
    if (!value || typeof value !== 'object') {
        return null;
    }

    if (Array.isArray(value)) {
        return coerceSimonKeyboardArrayBindings(value);
    }

    if (value.version === SIMON_KEYBOARD_VERSION && value.bindings) {
        return validateSimonKeyboardBindings(value.bindings);
    }

    if (value.bindings && typeof value.bindings === 'object') {
        return validateSimonKeyboardBindings(value.bindings);
    }

    return validateSimonKeyboardBindings(value);
}

function coerceSimonKeyboardArrayBindings(values) {
    if (values.length < SIMON_KEYBOARD_ZONES.length) {
        return null;
    }

    const bindings = {};

    SIMON_KEYBOARD_ZONES.forEach((zone, index) => {
        bindings[zone.id] = values[index];
    });

    return validateSimonKeyboardBindings(bindings);
}

function readBindingForZone(bindings, zoneId) {
    const aliases = SIMON_KEYBOARD_ZONE_ALIASES[zoneId] ?? [zoneId];

    for (const alias of aliases) {
        if (alias in bindings) {
            return bindings[alias];
        }
    }

    return null;
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

function hasDuplicateBinding(bindings, key) {
    return Object.values(bindings).includes(key);
}
