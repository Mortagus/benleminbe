import { describe, expect, test } from 'vitest';
import {
    addConditionToActor,
    decrementRoundConditions,
    formatConditionLabel,
    normalizeActorCombatState,
    normalizeConditionPayload,
    removeConditionFromActor,
    setCombatStatusOnActor,
} from '../../../../assets/scripts/lab/dnd/conditions.js';

describe('combat conditions helpers', () => {
    test('normalizes missing combat state fields', () => {
        expect(normalizeActorCombatState({ id: 'actor-1' })).toMatchObject({
            conditions: [],
            combatStatus: 'normal',
        });
    });

    test('accepts blank durations for standard conditions and rejects invalid values', () => {
        expect(normalizeConditionPayload({
            slug: 'poisoned',
            remainingRounds: '',
        })).toMatchObject({
            slug: 'poisoned',
            label: 'Empoisonné',
            remainingRounds: null,
            level: null,
            note: '',
        });

        expect(normalizeConditionPayload({
            slug: 'poisoned',
            remainingRounds: 'abc',
        })).toBeNull();
    });

    test('requires a valid exhaustion level and formats it correctly', () => {
        expect(normalizeConditionPayload({
            slug: 'exhaustion',
            level: '',
        })).toBeNull();

        expect(normalizeConditionPayload({
            slug: 'exhaustion',
            level: 2,
        })).toMatchObject({
            slug: 'exhaustion',
            label: 'Épuisement',
            remainingRounds: null,
            level: 2,
        });

        expect(formatConditionLabel({
            slug: 'exhaustion',
            level: 2,
        })).toBe('Épuisement 2');
    });

    test('adds, decrements and removes conditions on an actor', () => {
        const actor = {
            id: 'actor-1',
            conditions: [],
            combatStatus: 'normal',
        };

        const added = addConditionToActor(actor, {
            slug: 'poisoned',
            remainingRounds: 2,
            note: 'Toxine',
        });

        expect(added).toMatchObject({
            slug: 'poisoned',
            label: 'Empoisonné',
            remainingRounds: 2,
            note: 'Toxine',
        });
        expect(actor.conditions).toHaveLength(1);
        expect(actor.conditions[0]).toMatchObject({
            slug: 'poisoned',
            remainingRounds: 2,
        });

        const expiredConditions = decrementRoundConditions(actor);

        expect(expiredConditions).toHaveLength(0);
        expect(actor.conditions).toHaveLength(1);
        expect(actor.conditions[0].remainingRounds).toBe(1);

        const removed = removeConditionFromActor(actor, added.id);

        expect(removed).toMatchObject({
            id: added.id,
            slug: 'poisoned',
        });
        expect(actor.conditions).toEqual([]);
    });

    test('keeps exhaustion as a special condition without round decrement', () => {
        const actor = {
            id: 'actor-1',
            conditions: [
                {
                    id: 'condition-1',
                    slug: 'exhaustion',
                    label: 'Épuisement',
                    remainingRounds: null,
                    level: 2,
                    note: '',
                },
            ],
        };

        const expiredConditions = decrementRoundConditions(actor);

        expect(expiredConditions).toEqual([]);
        expect(actor.conditions).toHaveLength(1);
        expect(actor.conditions[0]).toMatchObject({
            slug: 'exhaustion',
            level: 2,
        });
    });

    test('updates the combat status without touching conditions', () => {
        const actor = {
            id: 'actor-1',
            conditions: [],
            combatStatus: 'normal',
        };

        expect(setCombatStatusOnActor(actor, 'dead')).toBe('dead');
        expect(actor.combatStatus).toBe('dead');
        expect(setCombatStatusOnActor(actor, 'invalid-value')).toBe('normal');
        expect(actor.combatStatus).toBe('normal');
    });
});
