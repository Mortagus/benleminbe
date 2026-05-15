export const MAX_MONSTER_COUNT = 30;

export function validateMonsterCountInput(monsterCountInput) {
    return validateIntegerInput(monsterCountInput, {
        fieldName: 'nombre de monstres',
        min: 1,
        max: MAX_MONSTER_COUNT,
        required: true,
    });
}

export function validateMonsterHitPointsInput(monsterItem, index) {
    const hitPointsInput = monsterItem.querySelector('.monster-hp input');

    if (!hitPointsInput || hitPointsInput.disabled) {
        return createValidationResult();
    }

    const maxHitPoints = Number(hitPointsInput.max);

    return validateIntegerInput(hitPointsInput, {
        fieldName: `PV actuels monstre ${index + 1}`,
        min: 0,
        max: Number.isFinite(maxHitPoints) ? maxHitPoints : null,
        required: true,
    });
}

export function validatePlayerItem(playerItem, index) {
    if (!hasStartedPlayer(playerItem)) {
        return createValidationResult();
    }

    const armorClassInput = getPlayerInput(playerItem, 'armor-class');
    const currentHitPointsInput = getPlayerInput(playerItem, 'current-hit-points');
    const baseHitPointsInput = getPlayerInput(playerItem, 'base-hit-points');
    const initiativeInput = getPlayerInput(playerItem, 'initiative');

    const result = mergeValidationResults(
        validateIntegerInput(armorClassInput, {
            fieldName: `CA joueur ${index + 1}`,
            min: 0,
            required: true,
        }),
        validateIntegerInput(currentHitPointsInput, {
            fieldName: `PV actuels joueur ${index + 1}`,
            min: 0,
            required: true,
        }),
        validateIntegerInput(baseHitPointsInput, {
            fieldName: `PV max joueur ${index + 1}`,
            min: 0,
            required: true,
        }),
        validateIntegerInput(initiativeInput, {
            fieldName: `initiative joueur ${index + 1}`,
            required: true,
        }),
    );

    const currentHitPoints = parseIntegerInputValue(currentHitPointsInput);
    const maxHitPoints = parseIntegerInputValue(baseHitPointsInput);

    if (
        currentHitPoints !== null
        && maxHitPoints !== null
        && currentHitPoints > maxHitPoints
    ) {
        result.errors.push({
            input: currentHitPointsInput,
            message: `Les PV actuels du joueur ${index + 1} ne peuvent pas dépasser ses PV max.`,
        });
    }

    return {
        isValid: result.errors.length === 0,
        errors: result.errors,
    };
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

function validateIntegerInput(input, options) {
    if (!input) {
        return createValidationResult([
            {
                input: null,
                message: `Le champ ${options.fieldName} est introuvable.`,
            },
        ]);
    }

    if (input.validity?.badInput) {
        return createValidationResult([
            {
                input,
                message: `Le champ ${options.fieldName} doit être un nombre entier.`,
            },
        ]);
    }

    const rawValue = input.value.trim();

    if (options.required && rawValue === '') {
        return createValidationResult([
            {
                input,
                message: `Le champ ${options.fieldName} est obligatoire.`,
            },
        ]);
    }

    if (rawValue === '') {
        return createValidationResult();
    }

    const value = Number(rawValue);

    if (!Number.isInteger(value)) {
        return createValidationResult([
            {
                input,
                message: `Le champ ${options.fieldName} doit être un nombre entier.`,
            },
        ]);
    }

    if (typeof options.min === 'number' && value < options.min) {
        return createValidationResult([
            {
                input,
                message: `Le champ ${options.fieldName} doit être supérieur ou égal à ${options.min}.`,
            },
        ]);
    }

    if (typeof options.max === 'number' && value > options.max) {
        return createValidationResult([
            {
                input,
                message: `Le champ ${options.fieldName} doit être inférieur ou égal à ${options.max}.`,
            },
        ]);
    }

    return createValidationResult();
}

function parseIntegerInputValue(input) {
    if (!input || input.value.trim() === '') {
        return null;
    }

    const value = Number(input.value.trim());

    return Number.isInteger(value) ? value : null;
}

function hasStartedPlayer(playerItem) {
    return Array.from(playerItem.querySelectorAll('input'))
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
