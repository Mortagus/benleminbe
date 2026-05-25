// Data transfer object contracts for the DnD initiative tracker.
// This file contains the persistable structures and pure conversion helpers.
// Browser storage wiring should live in persistence.js once localStorage is implemented.

export const ENCOUNTER_SNAPSHOT_VERSION = 1;

/**
 * Persistable encounter snapshot for localStorage and future JSON export.
 *
 * The generated bestiary is intentionally not part of the snapshot. Monster
 * instances refer back to catalog entries through their `slug`.
 *
 * @typedef {Object} EncounterSnapshotDto
 * @property {number} version
 * @property {string} savedAt
 * @property {EncounterMonsterDto[]} monsters
 * @property {EncounterPlayerDto[]} players
 * @property {RulesStateDto} rules
 * @property {TurnEntryDto[]} turnOrder
 * @property {number} currentRound
 * @property {string|null} activeTurnId
 */
export function createEncounterSnapshotDto(encounter, options = {}) {
    return {
        version: ENCOUNTER_SNAPSHOT_VERSION,
        savedAt: options.savedAt ?? new Date().toISOString(),
        monsters: encounter.monsters.map(monster => createEncounterMonsterDto(monster)),
        players: encounter.players.map(player => createEncounterPlayerDto(player)),
        rules: createRulesStateDto(encounter.rules),
        turnOrder: encounter.turnOrder.map(turn => createTurnEntryDto(turn)),
        currentRound: Number(encounter.currentRound || 1),
        activeTurnId: encounter.activeTurnId ?? null,
    };
}

export function restoreEncounterFromSnapshot(encounter, snapshot) {
    const monsters = (snapshot.monsters ?? []).map(monster => createRuntimeMonsterFromDto(monster));
    const players = (snapshot.players ?? []).map(player => createRuntimePlayerFromDto(player));
    const turnOrder = createRuntimeTurnOrderFromDto(snapshot.turnOrder ?? [], {
        monsters,
        players,
    });

    encounter.monsters = monsters;
    encounter.players = players;
    encounter.rules = createRulesStateDto(snapshot.rules ?? {});
    encounter.turnOrder = turnOrder;
    encounter.currentRound = normalizePositiveInteger(snapshot.currentRound, 1);
    encounter.activeTurnId = turnOrder.some(turn => turn.id === snapshot.activeTurnId)
        ? snapshot.activeTurnId
        : getFirstPendingTurnId(turnOrder);
}

/**
 * Monster instance stored in a prepared or active encounter.
 *
 * `slug` links the instance back to the generated bestiary when a catalog
 * monster is selected. Empty preparation slots keep `slug` and catalog-derived
 * fields nullable.
 *
 * @typedef {Object} EncounterMonsterDto
 * @property {string} id
 * @property {string|null} slug
 * @property {string} name
 * @property {string|null} className
 * @property {string|null} challengeRating
 * @property {string|null} type
 * @property {string|null} size
 * @property {number|null} armorClass
 * @property {number} baseHitPoints
 * @property {number} currentHitPoints
 * @property {string|null} alignment
 * @property {boolean} isLegendary
 * @property {Object<string, {score: number, modifier: number}>} abilities
 * @property {number} initiativeModifier
 * @property {number|null} roll
 * @property {number|null} initiative
 */
export function createEncounterMonsterDto(monster) {
    return {
        id: String(monster.id),
        slug: monster.slug ?? null,
        name: String(monster.name),
        className: monster.className ?? null,
        challengeRating: normalizeNullableText(monster.challengeRating),
        type: normalizeNullableText(monster.type),
        size: monster.size ?? null,
        armorClass: normalizeNullableNumber(monster.armorClass),
        baseHitPoints: normalizeNumber(monster.baseHitPoints),
        currentHitPoints: normalizeNumber(monster.currentHitPoints),
        alignment: monster.alignment ?? null,
        isLegendary: monster.isLegendary === true,
        abilities: cloneAbilities(monster.abilities),
        initiativeModifier: normalizeNumber(monster.initiativeModifier),
        roll: normalizeNullableNumber(monster.roll),
        initiative: normalizeNullableNumber(monster.initiative),
    };
}

export function createRuntimeMonsterFromDto(monster) {
    return {
        id: String(monster.id),
        slug: monster.slug ?? null,
        name: String(monster.name),
        className: monster.className ?? null,
        challengeRating: monster.challengeRating ?? null,
        type: monster.type ?? '-',
        size: monster.size ?? null,
        armorClass: monster.armorClass ?? '-',
        baseHitPoints: normalizeNumber(monster.baseHitPoints),
        currentHitPoints: normalizeNumber(monster.currentHitPoints),
        alignment: monster.alignment ?? null,
        isLegendary: monster.isLegendary === true,
        abilities: cloneAbilities(monster.abilities),
        initiativeModifier: normalizeNumber(monster.initiativeModifier),
        roll: normalizeNullableNumber(monster.roll),
        initiative: normalizeNullableNumber(monster.initiative),
    };
}

/**
 * Player participant stored in a prepared or active encounter.
 *
 * @typedef {Object} EncounterPlayerDto
 * @property {string} id
 * @property {'player'} type
 * @property {string} name
 * @property {number} armorClass
 * @property {number} baseHitPoints
 * @property {number} currentHitPoints
 * @property {number} initiative
 * @property {number} roll
 */
export function createEncounterPlayerDto(player) {
    return {
        id: String(player.id),
        type: 'player',
        name: String(player.name),
        armorClass: normalizeNumber(player.armorClass),
        baseHitPoints: normalizeNumber(player.baseHitPoints),
        currentHitPoints: normalizeNumber(player.currentHitPoints),
        initiative: normalizeNumber(player.initiative),
        roll: normalizeNumber(player.roll),
    };
}

export function createRuntimePlayerFromDto(player) {
    return {
        id: String(player.id),
        type: 'player',
        name: String(player.name),
        armorClass: normalizeNumber(player.armorClass),
        baseHitPoints: normalizeNumber(player.baseHitPoints),
        currentHitPoints: normalizeNumber(player.currentHitPoints),
        initiative: normalizeNumber(player.initiative),
        roll: normalizeNumber(player.roll),
    };
}

/**
 * Entry in the generated turn order.
 *
 * The entry identifies a participant and stores turn-specific state. Participant
 * details should be resolved from `players` or `monsters` to avoid stale copies.
 *
 * @typedef {Object} TurnEntryDto
 * @property {string} id
 * @property {string} actorId
 * @property {'player'|'monster'} actorType
 * @property {boolean} done
 */
export function createTurnEntryDto(turn) {
    return {
        id: String(turn.id),
        actorId: String(turn.actorId ?? turn.id),
        actorType: turn.type,
        done: turn.done === true,
    };
}

export function createRuntimeTurnOrderFromDto(turnOrder, participants) {
    return turnOrder
        .map(turnEntry => hydrateTurnEntry(turnEntry, participants))
        .filter(turn => turn !== null);
}

/**
 * Active state of optional encounter rules.
 *
 * @typedef {Object} RulesStateDto
 * @property {boolean} ['skip-low-initiative']
 * @property {boolean} ['extra-turn-on-twenty']
 * @property {boolean} ['break-initiative-ties-with-dexterity']
 */
export function createRulesStateDto(rules) {
    return {
        'skip-low-initiative': rules['skip-low-initiative'] === true,
        'extra-turn-on-twenty': rules['extra-turn-on-twenty'] === true,
        'break-initiative-ties-with-dexterity': rules['break-initiative-ties-with-dexterity'] === true,
    };
}

function hydrateTurnEntry(turnEntry, { monsters, players }) {
    const actor = findTurnActor(turnEntry, { monsters, players });

    if (!actor) {
        return null;
    }

    return {
        ...actor,
        id: String(turnEntry.id),
        actorId: String(turnEntry.actorId),
        done: turnEntry.done === true,
    };
}

function findTurnActor(turnEntry, { monsters, players }) {
    if (turnEntry.actorType === 'monster') {
        return monsters
            .filter(monster => monster.initiative !== null)
            .map(monster => createMonsterTurnActor(monster))
            .find(monster => monster.id === turnEntry.actorId) ?? null;
    }

    return players
        .find(player => player.id === turnEntry.actorId) ?? null;
}

function createMonsterTurnActor(monster) {
    return {
        id: monster.id,
        type: 'monster',
        name: monster.name,
        armorClass: monster.armorClass,
        currentHitPoints: monster.currentHitPoints,
        baseHitPoints: monster.baseHitPoints,
        initiative: monster.initiative,
        roll: monster.roll,
        initiativeModifier: monster.initiativeModifier,
    };
}

function getFirstPendingTurnId(turnOrder) {
    return turnOrder.find(turn => !turn.done)?.id ?? null;
}

function normalizeNullableText(value) {
    if (value === null || value === undefined || value === '-') {
        return null;
    }

    return String(value);
}

function normalizeNumber(value) {
    const number = Number(value);

    return Number.isFinite(number) ? number : 0;
}

function normalizeNullableNumber(value) {
    if (value === null || value === undefined || value === '-') {
        return null;
    }

    const number = Number(value);

    return Number.isFinite(number) ? number : null;
}

function normalizePositiveInteger(value, fallback) {
    const number = Number(value);

    return Number.isInteger(number) && number > 0 ? number : fallback;
}

function cloneAbilities(abilities) {
    if (!abilities || typeof abilities !== 'object') {
        return {};
    }

    return Object.fromEntries(
        Object.entries(abilities).map(([abilityName, ability]) => [
            abilityName,
            {
                score: normalizeNumber(ability.score),
                modifier: normalizeNumber(ability.modifier),
            },
        ]),
    );
}
