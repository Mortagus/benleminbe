export function rollD20() {
    return Math.floor(Math.random() * 20) + 1;
}

export function getInitiativeClass(actor) {
    if (actor.roll === 20) {
        return 'monster-initiative--critical-success';
    }

    if (actor.roll === 1) {
        return 'monster-initiative--critical-failure';
    }

    return '';
}

export function formatInitiative(actor) {
    if (actor.initiative === null) {
        return '-';
    }

    if (actor.roll === 20) {
        return `${actor.initiative} ★`;
    }

    if (actor.roll === 1) {
        return `${actor.initiative} ⚠`;
    }

    return actor.initiative;
}
