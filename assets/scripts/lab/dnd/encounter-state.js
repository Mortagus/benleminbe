// Mutable encounter state and rules for the initiative tracker.
// This module intentionally stays independent from the DOM so it can be tested
// as the business contract of the tool.
import { rollD20 } from './initiative.js';
import { bestiary } from './bestiary.js';

export const RULES = {
    skipLowInitiative: {
        id: 'skip-low-initiative',
        defaultActive: true,
    },
    extraTurnOnTwenty: {
        id: 'extra-turn-on-twenty',
        defaultActive: true,
    },
    breakInitiativeTiesWithDexterity: {
        id: 'break-initiative-ties-with-dexterity',
        defaultActive: false,
    },
};

export class EncounterState {
    constructor(options = {}) {
        this.monsters = [];
        this.players = [];
        this.rules = createDefaultRulesState();
        this.turnOrder = [];
        this.currentRound = 1;
        this.activeTurnId = null;

        Object.defineProperty(this, 'bestiary', {
            value: options.bestiary ?? bestiary,
            enumerable: false,
        });
    }

    createMonsterSlots(count) {
        this.monsters = Array.from(
            { length: count },
            (_, index) => createEmptyMonster(index),
        );
    }

    selectMonster(index, monsterSlug) {
        if (!this.monsters[index]) {
            return;
        }

        const selectedMonster = this.bestiary.find(monster => monster.slug === monsterSlug);

        this.monsters[index] = selectedMonster
            ? createMonsterFromBestiaryEntry(selectedMonster, index)
            : createEmptyMonster(index);
    }

    updateMonsterHitPoints(index, hitPoints) {
        if (!this.monsters[index]) {
            return;
        }

        this.monsters[index].currentHitPoints = Number(hitPoints || 0);
    }

    hasSelectedMonsters() {
        return this.monsters.some(monster => monster.slug !== null);
    }

    rollMonsterInitiatives(roll = rollD20) {
        this.monsters = this.monsters.map(monster => {
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

        this.monsters.sort((a, b) => this.compareByInitiative(a, b));
    }

    setPlayers(players) {
        this.players = players;
    }

    buildRoundOrder() {
        const actors = [
            ...getMonsterActors(this),
            ...this.players,
        ];

        this.turnOrder = actors
            .filter(actor => !this.shouldSkipTurn(actor))
            .flatMap(actor => {
                const turnCount = this.getTurnCount(actor);

                return Array.from({ length: turnCount }, (_, index) => ({
                    ...actor,
                    id: turnCount > 1 ? `${actor.id}-turn-${index + 1}` : actor.id,
                    actorId: actor.id,
                    done: false,
                }));
            })
            .sort((a, b) => this.compareByInitiative(a, b));

        this.currentRound = 1;
        this.refreshActiveTurn();

        return this.turnOrder;
    }

    toggleTurnDone(turnId) {
        const turn = this.turnOrder.find(actor => actor.id === turnId);

        if (!turn) {
            return;
        }

        turn.done = !turn.done;
        this.refreshActiveTurn();
    }

    moveTurn(draggedTurnId, targetTurnId, placement = 'before') {
        const draggedIndex = this.turnOrder.findIndex(actor => actor.id === draggedTurnId);

        if (draggedIndex === -1) {
            return;
        }

        const [draggedTurn] = this.turnOrder.splice(draggedIndex, 1);
        const targetIndex = this.turnOrder.findIndex(actor => actor.id === targetTurnId);

        if (targetIndex === -1) {
            this.turnOrder.push(draggedTurn);
            this.refreshActiveTurn();
            return;
        }

        const insertionIndex = placement === 'after'
            ? targetIndex + 1
            : targetIndex;

        this.turnOrder.splice(insertionIndex, 0, draggedTurn);
        this.refreshActiveTurn();
    }

    isRuleActive(ruleId) {
        return this.rules[ruleId] === true;
    }

    setRuleActive(ruleId, isActive) {
        if (!isKnownRule(ruleId)) {
            return;
        }

        this.rules[ruleId] = isActive;
    }

    shouldSkipTurn(actor) {
        if (!this.isRuleActive(RULES.skipLowInitiative.id)) {
            return false;
        }

        return actor.initiative <= 1;
    }

    getTurnCount(actor) {
        if (!this.isRuleActive(RULES.extraTurnOnTwenty.id)) {
            return 1;
        }

        return actor.roll === 20 ? 2 : 1;
    }

    compareByInitiative(a, b) {
        if (a.initiative === null) {
            return 1;
        }

        if (b.initiative === null) {
            return -1;
        }

        const initiativeOrder = b.initiative - a.initiative;

        if (initiativeOrder !== 0) {
            return initiativeOrder;
        }

        if (!this.isRuleActive(RULES.breakInitiativeTiesWithDexterity.id)) {
            return 0;
        }

        return getInitiativeTieBreaker(b) - getInitiativeTieBreaker(a);
    }

    refreshActiveTurn() {
        const activeTurn = this.turnOrder.find(actor => !actor.done);
        this.activeTurnId = activeTurn?.id ?? null;
    }
}

// Compatibility wrappers kept while DOM panels migrate to the EncounterState API.
export function createEncounterState(options = {}) {
    return new EncounterState(options);
}

export function createMonsterSlots(encounter, count) {
    encounter.createMonsterSlots(count);
}

export function selectMonster(encounter, index, monsterSlug) {
    encounter.selectMonster(index, monsterSlug);
}

export function updateMonsterHitPoints(encounter, index, hitPoints) {
    encounter.updateMonsterHitPoints(index, hitPoints);
}

export function hasSelectedMonsters(encounter) {
    return encounter.hasSelectedMonsters();
}

export function rollMonsterInitiatives(encounter, roll = rollD20) {
    encounter.rollMonsterInitiatives(roll);
}

export function setPlayers(encounter, players) {
    encounter.setPlayers(players);
}

export function buildRoundOrder(encounter) {
    return encounter.buildRoundOrder();
}

export function toggleTurnDone(encounter, turnId) {
    encounter.toggleTurnDone(turnId);
}

export function moveTurn(encounter, draggedTurnId, targetTurnId, placement = 'before') {
    encounter.moveTurn(draggedTurnId, targetTurnId, placement);
}

export function isRuleActive(encounter, ruleId) {
    return encounter.isRuleActive(ruleId);
}

export function setRuleActive(encounter, ruleId, isActive) {
    encounter.setRuleActive(ruleId, isActive);
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
    };
}

function createMonsterFromBestiaryEntry(monster, index) {
    const initiativeModifier = getMonsterInitiativeModifier(monster);

    return {
        id: `${monster.slug}-${index + 1}`,
        slug: monster.slug,
        name: `${monster.name} ${index + 1}`,
        className: monster.name,
        challengeRating: monster.challenge_rating,
        type: monster.type,
        size: monster.size,
        armorClass: monster.armor_class,
        baseHitPoints: monster.hit_points,
        currentHitPoints: monster.hit_points,
        alignment: monster.alignment,
        isLegendary: monster.is_legendary,
        abilities: monster.abilities ?? {},
        initiativeModifier,
        roll: null,
        initiative: null,
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

function getMonsterInitiativeModifier(monster) {
    if (typeof monster.initiative_modifier === 'number') {
        return monster.initiative_modifier;
    }

    if (typeof monster.abilities?.dex?.modifier === 'number') {
        return monster.abilities.dex.modifier;
    }

    return 0;
}

function createDefaultRulesState() {
    return Object.values(RULES).reduce((rules, rule) => ({
        ...rules,
        [rule.id]: rule.defaultActive,
    }), {});
}

function getInitiativeTieBreaker(actor) {
    return typeof actor.initiativeModifier === 'number'
        ? actor.initiativeModifier
        : 0;
}

function isKnownRule(ruleId) {
    return Object.values(RULES).some(rule => rule.id === ruleId);
}
