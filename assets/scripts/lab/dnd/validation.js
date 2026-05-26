// Validation helpers for the DnD tracker forms.
// Public validators still accept DOM nodes because panels own their forms, but
// validation rules below operate on normalized field data.
export const MAX_MONSTER_COUNT = 30;

// DOM-facing validators.
export function validateMonsterCountInput(monsterCountInput) {
    return validateIntegerInput(monsterCountInput, {
        fieldName: 'nombre de monstres',
        min: 1,
        max: MAX_MONSTER_COUNT,
        required: true,
    });
}

export function validateMonsterHitPointsInput(monsterItem, index) {
    const hitPointsInput = getMonsterHitPointsInput(monsterItem);

    if (!hitPointsInput || hitPointsInput.disabled) {
        return createValidationResult();
    }

    const maxHitPoints = parseFiniteNumber(hitPointsInput.max);

    return validateIntegerInput(hitPointsInput, {
        fieldName: `PV actuels monstre ${index + 1}`,
        min: 0,
        max: maxHitPoints,
        required: true,
    });
}

export function validatePlayerItem(playerItem, index) {
    if (!hasStartedPlayer(playerItem)) {
        return createValidationResult();
    }

    const fields = getPlayerFields(playerItem);

    return mergeValidationResults(
        validateIntegerInput(fields.armorClass, {
            fieldName: `CA joueur ${index + 1}`,
            min: 0,
            required: true,
        }),
        validateIntegerInput(fields.currentHitPoints, {
            fieldName: `PV actuels joueur ${index + 1}`,
            min: 0,
            required: true,
        }),
        validateIntegerInput(fields.baseHitPoints, {
            fieldName: `PV max joueur ${index + 1}`,
            min: 0,
            required: true,
        }),
        validateIntegerInput(fields.initiative, {
            fieldName: `initiative joueur ${index + 1}`,
            required: true,
        }),
        validateCurrentHitPointsLimit({
            currentHitPoints: parseIntegerInputValue(fields.currentHitPoints),
            maxHitPoints: parseIntegerInputValue(fields.baseHitPoints),
            currentHitPointsInput: fields.currentHitPoints,
            actorLabel: `joueur ${index + 1}`,
        }),
    );
}

export function validateEncounterActors(monsterList, playerList) {
    const selectedMonsters = Array.from(monsterList.querySelectorAll('.monster-select'))
        .filter(monsterSelect => monsterSelect.value !== '');
    const startedPlayers = Array.from(playerList.querySelectorAll('.player-item'))
        .filter(playerItem => hasStartedPlayer(playerItem));

    if (selectedMonsters.length > 0 || startedPlayers.length > 0) {
        return createValidationResult();
    }

    return createValidationResult([
        {
            input: null,
            message: 'Ajoute au moins un monstre ou un joueur avant de générer l’ordre du tour.',
        },
    ]);
}

// Pure validation rules.
export function validateIntegerValue(field, options) {
    if (field.badInput) {
        return createValidationResult([
            {
                input: field.input,
                message: `Le champ ${options.fieldName} doit être un nombre entier.`,
            },
        ]);
    }

    const rawValue = field.rawValue.trim();

    if (options.required && rawValue === '') {
        return createValidationResult([
            {
                input: field.input,
                message: `Le champ ${options.fieldName} est obligatoire.`,
            },
        ]);
    }

    if (rawValue === '') {
        return createValidationResult();
    }

    const value = parseIntegerValue(rawValue);

    if (value === null) {
        return createValidationResult([
            {
                input: field.input,
                message: `Le champ ${options.fieldName} doit être un nombre entier.`,
            },
        ]);
    }

    if (typeof options.min === 'number' && value < options.min) {
        return createValidationResult([
            {
                input: field.input,
                message: `Le champ ${options.fieldName} doit être supérieur ou égal à ${options.min}.`,
            },
        ]);
    }

    if (typeof options.max === 'number' && value > options.max) {
        return createValidationResult([
            {
                input: field.input,
                message: `Le champ ${options.fieldName} doit être inférieur ou égal à ${options.max}.`,
            },
        ]);
    }

    return createValidationResult();
}

export function validateCurrentHitPointsLimit({
    currentHitPoints,
    maxHitPoints,
    currentHitPointsInput,
    actorLabel,
}) {
    if (
        currentHitPoints === null
        || maxHitPoints === null
        || currentHitPoints <= maxHitPoints
    ) {
        return createValidationResult();
    }

    return createValidationResult([
        {
            input: currentHitPointsInput,
            message: `Les PV actuels du ${actorLabel} ne peuvent pas dépasser ses PV max.`,
        },
    ]);
}

// Validation result helpers.
export function mergeValidationResults(...results) {
    const errors = results.flatMap(result => result.errors);

    return {
        isValid: errors.length === 0,
        errors,
    };
}

export function hasValidationErrors(...results) {
    return results.some(result => !result.isValid);
}

// DOM feedback helpers.
export function focusFirstInvalidField(...results) {
    const firstInvalidInput = results
        .flatMap(result => result.errors)
        .map(error => error.input)
        .find(input => input instanceof HTMLElement);

    firstInvalidInput?.focus();
}

export function clearValidationState(scope) {
    if (!scope) {
        return;
    }

    scope.querySelectorAll('.dnd-field--invalid').forEach(input => {
        input.classList.remove('dnd-field--invalid');
        input.removeAttribute('aria-invalid');
    });

    scope.querySelectorAll('.dnd-validation-summary').forEach(summary => {
        summary.hidden = true;
        summary.replaceChildren();
    });
}

export function showValidationErrors(validationResult, summaryElement, summaryMessage) {
    if (!summaryElement) {
        return;
    }

    summaryElement.replaceChildren();

    if (validationResult.isValid) {
        summaryElement.hidden = true;
        return;
    }

    const paragraph = document.createElement('p');
    paragraph.textContent = summaryMessage;

    const list = document.createElement('ul');

    validationResult.errors.forEach(error => {
        const item = document.createElement('li');
        item.textContent = error.message;
        list.appendChild(item);

        if (error.input) {
            error.input.classList.add('dnd-field--invalid');
            error.input.setAttribute('aria-invalid', 'true');
        }
    });

    summaryElement.append(paragraph, list);
    summaryElement.hidden = false;
}

// DOM field readers.
function validateIntegerInput(input, options) {
    if (!input) {
        return createValidationResult([
            {
                input: null,
                message: `Le champ ${options.fieldName} est introuvable.`,
            },
        ]);
    }

    return validateIntegerValue(readInputField(input), options);
}

function parseIntegerInputValue(input) {
    if (!input) {
        return null;
    }

    return parseIntegerValue(input.value);
}

function parseIntegerValue(rawValue) {
    if (rawValue.trim() === '') {
        return null;
    }

    const value = Number(rawValue.trim());

    return Number.isInteger(value) ? value : null;
}

function parseFiniteNumber(rawValue) {
    const value = Number(rawValue);

    return Number.isFinite(value) ? value : null;
}

function readInputField(input) {
    return {
        input,
        rawValue: input.value,
        badInput: Boolean(input.validity?.badInput),
    };
}

function getMonsterHitPointsInput(monsterItem) {
    return monsterItem.querySelector('.monster-hp input');
}

function getPlayerFields(playerItem) {
    return {
        armorClass: getPlayerInput(playerItem, 'armor-class'),
        currentHitPoints: getPlayerInput(playerItem, 'current-hit-points'),
        baseHitPoints: getPlayerInput(playerItem, 'base-hit-points'),
        initiative: getPlayerInput(playerItem, 'initiative'),
    };
}

function hasStartedPlayer(playerItem) {
    return Array.from(playerItem.querySelectorAll('input'))
        .filter(input => input.dataset.playerField !== 'side')
        .some(input => input.value.trim() !== '' || input.validity?.badInput);
}

function getPlayerInput(playerItem, fieldName) {
    return playerItem.querySelector(`[data-player-field="${fieldName}"]`);
}

function createValidationResult(errors = []) {
    return {
        isValid: errors.length === 0,
        errors,
    };
}
