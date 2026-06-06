// Helpers for quick hit point edits in the turn order.
// Parsing stays separate from the encounter mutation logic so both layers
// remain easy to test independently.

export function parseHitPointsChange(rawValue) {
    const trimmedValue = String(rawValue ?? '').trim();

    if (trimmedValue === '') {
        return null;
    }

    const match = /^([+-]?)(\d+)$/.exec(trimmedValue);

    if (!match) {
        return null;
    }

    const amount = Number(match[2]);

    if (!Number.isInteger(amount)) {
        return null;
    }

    if (match[1] === '+') {
        return {
            kind: 'heal',
            amount,
        };
    }

    if (match[1] === '-') {
        return {
            kind: 'damage',
            amount,
        };
    }

    return {
        kind: 'set',
        amount,
    };
}

export function describeHitPointsChange(actorName, change, currentHitPoints, baseHitPoints) {
    const actorLabel = String(actorName ?? 'Acteur');

    if (change.kind === 'damage') {
        return `${actorLabel} subit ${change.amount} dégâts. PV ${currentHitPoints} / ${baseHitPoints}.`;
    }

    if (change.kind === 'heal') {
        return `${actorLabel} récupère ${change.amount} PV. PV ${currentHitPoints} / ${baseHitPoints}.`;
    }

    return `${actorLabel} : PV définis à ${currentHitPoints} / ${baseHitPoints}.`;
}

export function describeInvalidHitPointsChange(actorName) {
    const actorLabel = String(actorName ?? 'cet acteur');

    return `Saisie invalide pour ${actorLabel}. Utilise -N, +N ou N.`;
}
