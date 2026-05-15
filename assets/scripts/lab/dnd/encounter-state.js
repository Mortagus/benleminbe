import { rollD20 } from './initiative.js';
import { monsterClasses } from './monster_classes.js';

export const RULES = {
    skipLowInitiative: {
        id: 'skip-low-initiative',
        defaultActive: true,
    },
    extraTurnOnTwenty: {
        id: 'extra-turn-on-twenty',
        defaultActive: true,
    },
};

export function createEncounterState() {
    return {
        monsters: [],
        players: [],
        rules: createDefaultRulesState(),
        turnOrder: [],
        currentRound: 1,
        activeTurnId: null,
    };
}

export function createMonsterSlots(encounter, count) {
    encounter.monsters = Array.from(
        { length: count },
        (_, index) => createEmptyMonster(index),
    );
}

export function selectMonster(encounter, index, monsterSlug) {
    if (!encounter.monsters[index]) {
        return;
    }

    const selectedMonsterClass = monsterClasses.find(monsterClass => monsterClass.slug === monsterSlug);

    encounter.monsters[index] = selectedMonsterClass
        ? createMonsterFromClass(selectedMonsterClass, index)
        : createEmptyMonster(index);
}

export function updateMonsterHitPoints(encounter, index, hitPoints) {
    if (!encounter.monsters[index]) {
        return;
    }

    encounter.monsters[index].currentHitPoints = Number(hitPoints || 0);
}

export function hasSelectedMonsters(encounter) {
    return encounter.monsters.some(monster => monster.slug !== null);
}

export function rollMonsterInitiatives(encounter, roll = rollD20) {
    encounter.monsters = encounter.monsters.map(monster => {
        if (monster.slug === null) {
            return monster;
        }

        const initiativeRoll = roll();

        return {
            ...monster,
            roll: initiativeRoll,
            initiative: initiativeRoll + monster.initiativeModifier,
        };
    });

    encounter.monsters.sort(compareByInitiative);
}

export function setPlayers(encounter, players) {
    encounter.players = players;
}

export function buildRoundOrder(encounter) {
    const actors = [
        ...getMonsterActors(encounter),
        ...encounter.players,
    ];

    encounter.turnOrder = actors
        .filter(actor => !shouldSkipTurn(encounter, actor))
        .flatMap(actor => {
            const turnCount = getTurnCount(encounter, actor);

            return Array.from({ length: turnCount }, (_, index) => ({
                ...actor,
                id: turnCount > 1 ? `${actor.id}-turn-${index + 1}` : actor.id,
                actorId: actor.id,
                done: false,
            }));
        })
        .sort((a, b) => b.initiative - a.initiative);

    encounter.currentRound = 1;
    refreshActiveTurn(encounter);

    return encounter.turnOrder;
}

export function toggleTurnDone(encounter, turnId) {
    const turn = encounter.turnOrder.find(actor => actor.id === turnId);

    if (!turn) {
        return;
    }

    turn.done = !turn.done;
    refreshActiveTurn(encounter);
}

export function moveTurnBefore(encounter, draggedTurnId, targetTurnId) {
    const draggedIndex = encounter.turnOrder.findIndex(actor => actor.id === draggedTurnId);

    if (draggedIndex === -1) {
        return;
    }

    const [draggedTurn] = encounter.turnOrder.splice(draggedIndex, 1);
    const targetIndex = encounter.turnOrder.findIndex(actor => actor.id === targetTurnId);

    if (targetIndex === -1) {
        encounter.turnOrder.push(draggedTurn);
        refreshActiveTurn(encounter);
        return;
    }

    encounter.turnOrder.splice(targetIndex, 0, draggedTurn);
    refreshActiveTurn(encounter);
}

export function isRuleActive(encounter, ruleId) {
    return encounter.rules[ruleId] === true;
}

export function setRuleActive(encounter, ruleId, isActive) {
    if (!isKnownRule(ruleId)) {
        return;
    }

    encounter.rules[ruleId] = isActive;
}

function createEmptyMonster(index) {
    return {
        id: `monster-${index + 1}`,
        slug: null,
        name: `Monstre ${index + 1}`,
        className: null,
        challengeRating: null,
        type: '-',
        size: null,
        armorClass: '-',
        baseHitPoints: 0,
        currentHitPoints: 0,
        alignment: null,
        isLegendary: false,
        abilities: {},
        initiativeModifier: 0,
        roll: null,
        initiative: null,
        originalData: null,
    };
}

function createMonsterFromClass(monsterClass, index) {
    const initiativeModifier = getMonsterInitiativeModifier(monsterClass);

    return {
        id: `${monsterClass.slug}-${index + 1}`,
        slug: monsterClass.slug,
        name: `${monsterClass.name} ${index + 1}`,
        className: monsterClass.name,
        challengeRating: monsterClass.challenge_rating,
        type: monsterClass.type,
        size: monsterClass.size,
        armorClass: monsterClass.armor_class,
        baseHitPoints: monsterClass.hit_points,
        currentHitPoints: monsterClass.hit_points,
        alignment: monsterClass.alignment,
        isLegendary: monsterClass.is_legendary,
        abilities: monsterClass.abilities ?? {},
        initiativeModifier,
        roll: null,
        initiative: null,
        originalData: monsterClass,
    };
}

function getMonsterActors(encounter) {
    return encounter.monsters
        .filter(monster => monster.slug !== null && monster.initiative !== null)
        .map(monster => ({
            id: monster.id,
            type: 'monster',
            name: monster.name,
            armorClass: monster.armorClass,
            currentHitPoints: monster.currentHitPoints,
            baseHitPoints: monster.baseHitPoints,
            initiative: monster.initiative,
            roll: monster.roll,
            initiativeModifier: monster.initiativeModifier,
            done: false,
        }));
}

function getMonsterInitiativeModifier(monsterClass) {
    if (typeof monsterClass.initiative_modifier === 'number') {
        return monsterClass.initiative_modifier;
    }

    if (typeof monsterClass.abilities?.dex?.modifier === 'number') {
        return monsterClass.abilities.dex.modifier;
    }

    return 0;
}

function createDefaultRulesState() {
    return Object.values(RULES).reduce((rules, rule) => ({
        ...rules,
        [rule.id]: rule.defaultActive,
    }), {});
}

function shouldSkipTurn(encounter, actor) {
    if (!isRuleActive(encounter, RULES.skipLowInitiative.id)) {
        return false;
    }

    return actor.initiative <= 1;
}

function getTurnCount(encounter, actor) {
    if (!isRuleActive(encounter, RULES.extraTurnOnTwenty.id)) {
        return 1;
    }

    return actor.roll === 20 ? 2 : 1;
}

function compareByInitiative(a, b) {
    if (a.initiative === null) {
        return 1;
    }

    if (b.initiative === null) {
        return -1;
    }

    return b.initiative - a.initiative;
}

function refreshActiveTurn(encounter) {
    const activeTurn = encounter.turnOrder.find(actor => !actor.done);
    encounter.activeTurnId = activeTurn?.id ?? null;
}

function isKnownRule(ruleId) {
    return Object.values(RULES).some(rule => rule.id === ruleId);
}
