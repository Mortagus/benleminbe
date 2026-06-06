const CONDITION_CATALOG = [
    { slug: 'blinded', label: 'Aveuglé' },
    { slug: 'charmed', label: 'Charmé' },
    { slug: 'deafened', label: 'Assourdi' },
    { slug: 'exhaustion', label: 'Épuisement' },
    { slug: 'frightened', label: 'Effrayé' },
    { slug: 'grappled', label: 'Agrippé' },
    { slug: 'incapacitated', label: 'Incapacité' },
    { slug: 'invisible', label: 'Invisible' },
    { slug: 'paralyzed', label: 'Paralysé' },
    { slug: 'petrified', label: 'Pétrifié' },
    { slug: 'poisoned', label: 'Empoisonné' },
    { slug: 'prone', label: 'À terre' },
    { slug: 'restrained', label: 'Entravé' },
    { slug: 'stunned', label: 'Étourdi' },
];

const CONDITION_CATALOG_MAP = new Map(
    CONDITION_CATALOG.map(condition => [condition.slug, condition]),
);

const COMBAT_STATUS_CATALOG = [
    { value: 'normal', label: 'Normal' },
    { value: 'unconscious', label: 'Inconscient' },
    { value: 'dead', label: 'Mort' },
    { value: 'out_of_combat', label: 'Hors combat' },
];

const COMBAT_STATUS_MAP = new Map(
    COMBAT_STATUS_CATALOG.map(status => [status.value, status]),
);

let conditionSequence = 0;

export { CONDITION_CATALOG, COMBAT_STATUS_CATALOG };

export function createConditionRecord(payload) {
    const normalizedPayload = normalizeConditionPayload(payload);

    if (!normalizedPayload) {
        return null;
    }

    return {
        id: createConditionId(),
        ...normalizedPayload,
    };
}

export function normalizeActorCombatState(actor = {}) {
    return {
        ...actor,
        conditions: normalizeConditionList(actor.conditions),
        combatStatus: normalizeCombatStatus(actor.combatStatus),
    };
}

export function normalizeConditionList(conditions) {
    if (!Array.isArray(conditions)) {
        return [];
    }

    return conditions
        .map(condition => normalizeConditionRecord(condition))
        .filter(condition => condition !== null);
}

export function normalizeConditionRecord(condition) {
    if (!condition || typeof condition !== 'object') {
        return null;
    }

    const slug = String(condition.slug ?? '').trim();
    const catalogEntry = CONDITION_CATALOG_MAP.get(slug);

    if (!catalogEntry) {
        return null;
    }

    const normalizedRemainingRounds = normalizeOptionalPositiveInteger(
        condition.remainingRounds,
    );
    const normalizedLevel = slug === 'exhaustion'
        ? normalizeExhaustionLevel(condition.level)
        : null;

    if (slug === 'exhaustion' && normalizedLevel === null) {
        return null;
    }

    if (slug !== 'exhaustion' && condition.remainingRounds !== undefined && normalizedRemainingRounds === null && condition.remainingRounds !== null) {
        return null;
    }

    return {
        id: String(condition.id ?? createConditionId()),
        slug,
        label: catalogEntry.label,
        remainingRounds: slug === 'exhaustion' ? null : normalizedRemainingRounds,
        level: normalizedLevel,
        note: normalizeConditionNote(condition.note),
    };
}

export function normalizeConditionPayload(payload = {}) {
    const slug = String(payload.slug ?? payload.condition ?? '').trim();
    const catalogEntry = CONDITION_CATALOG_MAP.get(slug);

    if (!catalogEntry) {
        return null;
    }

    const note = normalizeConditionNote(payload.note);
    const remainingRoundsInput = payload.remainingRounds ?? payload.durationRounds ?? payload.rounds;
    const hasRemainingRoundsInput = hasMeaningfulConditionValue(remainingRoundsInput);
    const remainingRounds = slug === 'exhaustion'
        ? null
        : normalizeOptionalPositiveInteger(remainingRoundsInput);
    const levelInput = payload.level ?? payload.exhaustionLevel;
    const level = slug === 'exhaustion'
        ? normalizeExhaustionLevel(levelInput)
        : null;

    if (slug === 'exhaustion' && level === null) {
        return null;
    }

    if (slug !== 'exhaustion'
        && hasRemainingRoundsInput
        && remainingRounds === null) {
        return null;
    }

    return {
        slug,
        label: catalogEntry.label,
        remainingRounds: slug === 'exhaustion' ? null : remainingRounds,
        level,
        note,
    };
}

export function addConditionToActor(actor, payload) {
    const condition = createConditionRecord(payload);

    if (!condition) {
        return null;
    }

    actor.conditions = [...normalizeConditionList(actor.conditions), condition];

    return condition;
}

export function removeConditionFromActor(actor, conditionId) {
    const conditions = normalizeConditionList(actor.conditions);
    const conditionIndex = conditions.findIndex(condition => condition.id === String(conditionId));

    if (conditionIndex === -1) {
        return null;
    }

    const [removedCondition] = conditions.splice(conditionIndex, 1);
    actor.conditions = conditions;

    return removedCondition;
}

export function setCombatStatusOnActor(actor, status) {
    const normalizedStatus = normalizeCombatStatus(status);

    if (normalizedStatus === null) {
        return null;
    }

    actor.combatStatus = normalizedStatus;

    return normalizedStatus;
}

export function decrementRoundConditions(actor) {
    const currentConditions = normalizeConditionList(actor.conditions);
    const nextConditions = [];
    const expiredConditions = [];

    currentConditions.forEach(condition => {
        if (!Number.isInteger(condition.remainingRounds)) {
            nextConditions.push(condition);
            return;
        }

        const nextRemainingRounds = condition.remainingRounds - 1;

        if (nextRemainingRounds <= 0) {
            expiredConditions.push(condition);
            return;
        }

        nextConditions.push({
            ...condition,
            remainingRounds: nextRemainingRounds,
        });
    });

    actor.conditions = nextConditions;

    return expiredConditions;
}

export function isConditionExpired(condition) {
    return Number.isInteger(condition?.remainingRounds) && condition.remainingRounds <= 0;
}

export function normalizeCombatStatus(status) {
    const normalizedStatus = String(status ?? 'normal').trim();

    return COMBAT_STATUS_MAP.has(normalizedStatus)
        ? normalizedStatus
        : 'normal';
}

export function formatCombatStatusLabel(status) {
    const normalizedStatus = normalizeCombatStatus(status);

    return COMBAT_STATUS_MAP.get(normalizedStatus)?.label ?? 'Normal';
}

export function formatConditionLabel(condition) {
    const slug = String(condition?.slug ?? '').trim();
    const catalogEntry = CONDITION_CATALOG_MAP.get(slug);

    if (!catalogEntry) {
        return '';
    }

    if (slug === 'exhaustion') {
        const level = normalizeExhaustionLevel(condition?.level);

        return level === null
            ? catalogEntry.label
            : `${catalogEntry.label} ${level}`;
    }

    const remainingRounds = normalizeOptionalPositiveInteger(condition?.remainingRounds);

    if (remainingRounds !== null) {
        return `${catalogEntry.label} · ${remainingRounds}r`;
    }

    return catalogEntry.label;
}

export function getConditionCatalog() {
    return [...CONDITION_CATALOG];
}

export function getCombatStatusCatalog() {
    return [...COMBAT_STATUS_CATALOG];
}

function createConditionId() {
    conditionSequence += 1;

    return `condition-${conditionSequence}`;
}

function normalizeOptionalPositiveInteger(value) {
    if (value === null || value === undefined || String(value).trim() === '') {
        return null;
    }

    const normalizedValue = Number(value);

    if (!Number.isInteger(normalizedValue) || normalizedValue <= 0) {
        return null;
    }

    return normalizedValue;
}

function normalizeExhaustionLevel(value) {
    const normalizedValue = Number(value);

    if (!Number.isInteger(normalizedValue) || normalizedValue < 1 || normalizedValue > 6) {
        return null;
    }

    return normalizedValue;
}

function normalizeConditionNote(value) {
    return value === null || value === undefined
        ? ''
        : String(value).trim();
}

function hasMeaningfulConditionValue(value) {
    return value !== null
        && value !== undefined
        && String(value).trim() !== '';
}
