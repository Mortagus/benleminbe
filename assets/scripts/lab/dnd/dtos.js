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
 * @property {number} initiativeModifier
 * @property {PlayerIdentityDto} identity
 * @property {PlayerAbilityScoresDto} abilityScores
 * @property {PlayerCombatDto} combat
 * @property {PlayerPresentationDto} presentation
 * @property {PlayerProfileDto} profile
 * @property {PlayerProficienciesDto} proficiencies
 * @property {PlayerSpellbookDto} spellbook
 * @property {PlayerEquipmentDto} equipment
 * @property {PlayerStoryDto} story
 * @property {PlayerSourceDto} source
 * @property {PlayerImportDataDto|null} importData
 */
export function createEncounterPlayerDto(player) {
    const name = normalizeNullableText(
        player.name ?? player.identity?.name ?? player.profile?.name,
    ) ?? '';
    const identity = createPlayerIdentityDto(player);
    const abilityScores = createPlayerAbilityScoresDto(player);
    const combat = createPlayerCombatDto(player);
    const presentation = createPlayerPresentationDto(player);
    const profile = createPlayerProfileDto(player);
    const proficiencies = createPlayerProficienciesDto(player);
    const spellbook = createPlayerSpellbookDto(player);
    const equipment = createPlayerEquipmentDto(player);
    const story = createPlayerStoryDto(player);
    const source = createPlayerSourceDto(player);
    const importData = createPlayerImportDataDto(player.importData ?? player.import ?? null);

    return {
        id: String(player.id),
        type: 'player',
        name,
        armorClass: combat.armorClass,
        baseHitPoints: combat.baseHitPoints,
        currentHitPoints: combat.currentHitPoints,
        initiative: combat.initiative,
        roll: combat.roll,
        initiativeModifier: combat.initiativeModifier,
        identity,
        abilityScores,
        combat,
        presentation,
        profile,
        proficiencies,
        spellbook,
        equipment,
        story,
        source,
        importData,
    };
}

export function createRuntimePlayerFromDto(player) {
    const identity = createPlayerIdentityDto(player.identity ?? player);
    const abilityScores = createPlayerAbilityScoresDto(player.abilityScores ?? player);
    const combat = createPlayerCombatDto(player.combat ?? player);
    const presentation = createPlayerPresentationDto(player.presentation ?? player);
    const profile = createPlayerProfileDto(player.profile ?? player);
    const proficiencies = createPlayerProficienciesDto(player.proficiencies ?? player);
    const spellbook = createPlayerSpellbookDto(player.spellbook ?? player);
    const equipment = createPlayerEquipmentDto(player.equipment ?? player);
    const story = createPlayerStoryDto(player.story ?? player);
    const source = createPlayerSourceDto(player.source ?? player);
    const importData = createPlayerImportDataDto(player.importData ?? player.import ?? null);

    return {
        id: String(player.id),
        type: 'player',
        name: String(player.name ?? identity.name ?? profile.name ?? ''),
        armorClass: combat.armorClass,
        baseHitPoints: combat.baseHitPoints,
        currentHitPoints: combat.currentHitPoints,
        initiative: combat.initiative,
        roll: combat.roll,
        initiativeModifier: combat.initiativeModifier,
        identity,
        abilityScores,
        combat,
        presentation,
        profile,
        proficiencies,
        spellbook,
        equipment,
        story,
        source,
        importData,
        race: identity.race,
        className: identity.className,
        classPath: identity.classPath,
        background: identity.background,
        level: identity.level,
        alignment: identity.alignment,
        age: identity.age,
        sex: identity.sex,
        height: profile.height,
        weight: profile.weight,
        eyes: presentation.eyes,
        skin: presentation.skin,
        hair: presentation.hair,
    };
}

/**
 * Structured identity and game-sheet metadata for a player participant.
 *
 * @typedef {Object} PlayerIdentityDto
 * @property {string|null} race
 * @property {string|null} className
 * @property {string|null} classPath
 * @property {string|null} background
 * @property {number|null} level
 * @property {number|null} alignment
 * @property {number|null} age
 * @property {number|null} sex
 * @property {string} name
 */

/**
 * Ability scores are stored with long-form keys so the snapshot remains
 * readable even when the source XML uses abbreviations.
 *
 * @typedef {Object} PlayerAbilityScoresDto
 * @property {number|null} strength
 * @property {number|null} dexterity
 * @property {number|null} constitution
 * @property {number|null} intelligence
 * @property {number|null} wisdom
 * @property {number|null} charisma
 */

/**
 * Combat-facing data available on a player sheet.
 *
 * @typedef {Object} PlayerCombatDto
 * @property {number} armorClass
 * @property {number} baseHitPoints
 * @property {number} currentHitPoints
 * @property {number} initiative
 * @property {number} roll
 * @property {number} initiativeModifier
 */

/**
 * Physical presentation fields from the source sheet.
 *
 * @typedef {Object} PlayerPresentationDto
 * @property {string|null} height
 * @property {string|null} weight
 * @property {string|null} eyes
 * @property {string|null} skin
 * @property {string|null} hair
 * @property {string|null} appearance
 */

/**
 * Compact profile fields used for quick identification.
 *
 * @typedef {Object} PlayerProfileDto
 * @property {string|null} name
 * @property {string|null} race
 * @property {string|null} className
 * @property {string|null} classPath
 * @property {string|null} background
 * @property {number|null} level
 * @property {number|null} alignment
 * @property {number|null} age
 * @property {number|null} sex
 * @property {string|null} height
 * @property {string|null} weight
 */

/**
 * Proficiency metadata that can be filled from import sources.
 *
 * @typedef {Object} PlayerProficienciesDto
 * @property {string[]} skills
 * @property {string[]} tools
 * @property {string[]} languages
 */

/**
 * Spells known by the player.
 *
 * @typedef {Object} PlayerSpellDto
 * @property {string} name
 * @property {number|null} level
 */

/**
 * Spellbook fields preserved by the DTO.
 *
 * @typedef {Object} PlayerSpellbookDto
 * @property {PlayerSpellDto[]} known
 */

/**
 * Equipment and currency tracked from the character sheet.
 *
 * @typedef {Object} PlayerEquipmentDto
 * @property {string[]} weapons
 * @property {string[]} armor
 * @property {string[]} items
 * @property {{gp: number, pp: number, ep: number, sp: number, cp: number}} currency
 */

/**
 * Free-form roleplay material from the character sheet.
 *
 * @typedef {Object} PlayerStoryDto
 * @property {string|null} traits
 * @property {string|null} ideals
 * @property {string|null} bonds
 * @property {string|null} flaws
 * @property {string|null} backstory
 * @property {string|null} allies
 * @property {string|null} features
 * @property {string|null} treasure
 */

/**
 * Provenance of a player snapshot.
 *
 * @typedef {Object} PlayerSourceDto
 * @property {string} format
 * @property {string|null} origin
 * @property {string|null} fileName
 * @property {string|null} importedAt
 */

/**
 * Extra import payload preserved for imported player sheets.
 *
 * The core player fields already live on the player DTO itself. This payload
 * keeps the extra controller response data that is not otherwise stored.
 *
 * @typedef {Object} PlayerImportDataDto
 * @property {string[]} warnings
 * @property {Object<string, unknown>} raw
 */

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

function createPlayerIdentityDto(player) {
    const name = normalizeNullableText(
        player.name ?? player.identity?.name ?? player.profile?.name,
    ) ?? '';

    return {
        name,
        race: normalizeNullableText(player.identity?.race ?? player.race),
        className: normalizeNullableText(player.identity?.className ?? player.className),
        classPath: normalizeNullableText(player.identity?.classPath ?? player.classPath),
        background: normalizeNullableText(player.identity?.background ?? player.background),
        level: normalizeNullableNumber(player.identity?.level ?? player.level),
        alignment: normalizeNullableNumber(player.identity?.alignment ?? player.alignment),
        age: normalizeNullableNumber(player.identity?.age ?? player.age),
        sex: normalizeNullableNumber(player.identity?.sex ?? player.sex),
    };
}

function createPlayerAbilityScoresDto(player) {
    const source = player.abilityScores ?? player.ability_scores ?? player;

    return {
        strength: normalizeNullableNumber(source.strength ?? source.str ?? source.STR),
        dexterity: normalizeNullableNumber(source.dexterity ?? source.dex ?? source.DEX),
        constitution: normalizeNullableNumber(source.constitution ?? source.con ?? source.CON),
        intelligence: normalizeNullableNumber(source.intelligence ?? source.int ?? source.INT),
        wisdom: normalizeNullableNumber(source.wisdom ?? source.wis ?? source.WIS),
        charisma: normalizeNullableNumber(source.charisma ?? source.cha ?? source.CHA),
    };
}

function createPlayerCombatDto(player) {
    const initiativeModifier = normalizeNullableNumber(
        player.initiativeModifier
            ?? player.combat?.initiativeModifier
            ?? getAbilityModifier(player.abilityScores?.dexterity ?? player.dexterity ?? player.dex),
    );

    return {
        armorClass: normalizeNumber(player.armorClass ?? player.combat?.armorClass),
        baseHitPoints: normalizeNumber(player.baseHitPoints ?? player.combat?.baseHitPoints),
        currentHitPoints: normalizeNumber(player.currentHitPoints ?? player.combat?.currentHitPoints),
        initiative: normalizeNumber(player.initiative ?? player.combat?.initiative),
        roll: normalizeNumber(player.roll ?? player.combat?.roll),
        initiativeModifier: initiativeModifier ?? 0,
    };
}

function createPlayerPresentationDto(player) {
    const source = player.presentation ?? player;

    return {
        height: normalizeNullableText(source.height),
        weight: normalizeNullableText(source.weight),
        eyes: normalizeNullableText(source.eyes),
        skin: normalizeNullableText(source.skin),
        hair: normalizeNullableText(source.hair),
        appearance: normalizeNullableText(source.appearance),
    };
}

function createPlayerProfileDto(player) {
    const source = player.profile ?? player;

    return {
        name: normalizeNullableText(source.name),
        race: normalizeNullableText(source.race),
        className: normalizeNullableText(source.className),
        classPath: normalizeNullableText(source.classPath),
        background: normalizeNullableText(source.background),
        level: normalizeNullableNumber(source.level),
        alignment: normalizeNullableNumber(source.alignment),
        age: normalizeNullableNumber(source.age),
        sex: normalizeNullableNumber(source.sex),
        height: normalizeNullableText(source.height),
        weight: normalizeNullableText(source.weight),
    };
}

function createPlayerProficienciesDto(player) {
    const source = player.proficiencies ?? player;

    return {
        skills: normalizeTextList(source.skills ?? source.skillsList ?? source.skillsProficiencies ?? source.skillsProf),
        tools: normalizeTextList(source.tools ?? source.toolsList ?? source.toolsProficiencies),
        languages: normalizeTextList(source.languages ?? source.languageList),
    };
}

function createPlayerSpellbookDto(player) {
    const source = player.spellbook ?? player.spells ?? player;

    return {
        known: normalizeSpellList(source.known ?? source.knownSpells ?? source.spellsKnown),
    };
}

function createPlayerEquipmentDto(player) {
    const source = player.equipment ?? player;

    return {
        weapons: normalizeTextList(source.weapons),
        armor: normalizeTextList(source.armor),
        items: normalizeTextList(source.items),
        currency: {
            gp: normalizeNumber(source.currency?.gp ?? source.gp),
            pp: normalizeNumber(source.currency?.pp ?? source.pp),
            ep: normalizeNumber(source.currency?.ep ?? source.ep),
            sp: normalizeNumber(source.currency?.sp ?? source.sp),
            cp: normalizeNumber(source.currency?.cp ?? source.cp),
        },
    };
}

function createPlayerStoryDto(player) {
    const source = player.story ?? player;

    return {
        traits: normalizeNullableText(source.traits),
        ideals: normalizeNullableText(source.ideals),
        bonds: normalizeNullableText(source.bonds),
        flaws: normalizeNullableText(source.flaws),
        backstory: normalizeNullableText(source.backstory),
        allies: normalizeNullableText(source.allies),
        features: normalizeNullableText(source.features),
        treasure: normalizeNullableText(source.treasure),
    };
}

function createPlayerSourceDto(player) {
    const source = player.source ?? player;

    return {
        format: normalizeNullableText(source.format) ?? 'manual',
        origin: normalizeNullableText(source.origin),
        fileName: normalizeNullableText(source.fileName),
        importedAt: normalizeNullableText(source.importedAt),
    };
}

function createPlayerImportDataDto(source) {
    if (source === null || source === undefined || typeof source !== 'object') {
        return null;
    }

    return {
        warnings: normalizeTextList(source.warnings),
        raw: cloneSerializableValue(source.raw ?? {}),
    };
}

function normalizeTextList(value) {
    if (value === null || value === undefined || value === '') {
        return [];
    }

    if (Array.isArray(value)) {
        return value
            .map(item => normalizeNullableText(item))
            .filter(item => item !== null);
    }

    if (typeof value === 'string') {
        return value
            .split(',')
            .map(item => item.trim())
            .filter(item => item !== '');
    }

    return [String(value)];
}

function normalizeSpellList(value) {
    if (!Array.isArray(value)) {
        return [];
    }

    return value
        .map(spell => {
            if (typeof spell === 'string') {
                const name = spell.trim();

                return name === '' ? null : { name, level: null };
            }

            if (!spell || typeof spell !== 'object') {
                return null;
            }

            const name = normalizeNullableText(spell.name ?? spell.label);

            if (!name) {
                return null;
            }

            return {
                name,
                level: normalizeNullableNumber(spell.level),
            };
        })
        .filter(spell => spell !== null);
}

function getAbilityModifier(score) {
    const normalizedScore = normalizeNullableNumber(score);

    if (normalizedScore === null) {
        return null;
    }

    return Math.floor((normalizedScore - 10) / 2);
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

function cloneSerializableValue(value) {
    if (Array.isArray(value)) {
        return value.map(item => cloneSerializableValue(item));
    }

    if (value !== null && typeof value === 'object') {
        return Object.fromEntries(
            Object.entries(value).map(([key, item]) => [key, cloneSerializableValue(item)]),
        );
    }

    return value;
}
