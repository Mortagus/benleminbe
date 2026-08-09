import { describe, expect, it } from 'vitest';
import {
    adjustInputValue,
    createInitialPrototypeState,
    createWorkoutPrototypeLifecycle,
    getCompletedSetCount,
    readPrototypeSnapshot,
    restorePrototypeState,
    writePrototypeSnapshot,
} from '../../../../assets/scripts/private/workout/prototype.js';

function createExercises() {
    return [
        {
            id: 'squat',
            sets: [
                { id: '1', fields: [{ id: 'load', input: { value: '70' } }, { id: 'repetitions', input: { value: '8' } }] },
                { id: '2', fields: [{ id: 'load', input: { value: '70' } }, { id: 'repetitions', input: { value: '8' } }] },
            ],
        },
        {
            id: 'row',
            sets: [{ id: '1', fields: [{ id: 'duration', input: { value: '25' } }, { id: 'recovery', input: { value: '5' } }] }],
        },
    ];
}

describe('workout prototype persistence', () => {
    it('reads an available versioned snapshot', () => {
        const storage = { getItem: () => JSON.stringify({ version: 1 }) };

        expect(readPrototypeSnapshot(storage)).toEqual({ status: 'available', state: { version: 1 } });
    });

    it('distinguishes an absent, invalid or unavailable snapshot', () => {
        expect(readPrototypeSnapshot({ getItem: () => null })).toEqual({ status: 'absent', state: null });
        expect(readPrototypeSnapshot({ getItem: () => '{' })).toEqual({ status: 'invalid', state: null });
        expect(readPrototypeSnapshot({ getItem: () => { throw new Error('blocked'); } })).toEqual({ status: 'unavailable', state: null });
    });

    it('writes a snapshot and handles unavailable storage', () => {
        const store = new Map();
        const storage = {
            getItem: (key) => store.get(key) ?? null,
            setItem: (key, value) => store.set(key, value),
        };

        expect(writePrototypeSnapshot(storage, { version: 1 })).toBe(true);
        expect(readPrototypeSnapshot(storage)).toEqual({ status: 'available', state: { version: 1 } });
        expect(writePrototypeSnapshot({ setItem: () => { throw new Error('blocked'); } }, { version: 1 })).toBe(false);
    });
});

describe('workout prototype state', () => {
    it('uses stable exercise and set identifiers in its initial state', () => {
        const state = createInitialPrototypeState(createExercises());

        expect(state).toMatchObject({
            version: 2,
            activeExerciseId: 'squat',
            exercises: { squat: { activeSetId: '1' }, row: { activeSetId: '1' } },
        });
    });

    it('falls back safely when a snapshot version, identifiers or values are invalid', () => {
        const state = restorePrototypeState({
            version: 999,
            activeExerciseId: 'missing',
            exercises: {},
        }, createExercises());

        expect(state.activeExerciseId).toBe('squat');
        expect(state.exercises.squat.activeSetId).toBe('1');
        expect(state.exercises.squat.sets['1']).toEqual({ fields: { load: '70', repetitions: '8' }, completed: false });
    });

    it('restores only compatible stable identifiers and counts completed sets', () => {
        const state = restorePrototypeState({
            version: 2,
            activeExerciseId: 'row',
            exercises: {
                squat: {
                    activeSetId: '2',
                    sets: {
                        1: { fields: { load: '71.25', repetitions: '9' }, completed: true },
                        2: { fields: { load: '-1', repetitions: 'nope' }, completed: true },
                    },
                },
            },
        }, createExercises());

        expect(state.activeExerciseId).toBe('row');
        expect(state.exercises.squat.activeSetId).toBe('2');
        expect(state.exercises.squat.sets['1']).toEqual({ fields: { load: '71.25', repetitions: '9' }, completed: true });
        expect(state.exercises.squat.sets['2']).toEqual({ fields: { load: '70', repetitions: '8' }, completed: true });
        expect(getCompletedSetCount(state.exercises.squat)).toBe(2);
    });
});

describe('workout prototype stepper lifecycle', () => {
    it('applies exactly one native step and emits one input event', () => {
        let inputEvents = 0;
        const input = {
            value: '8',
            stepUp: () => {
                input.value = String(Math.min(10, Number(input.value) + 1));
            },
            stepDown: () => {
                input.value = String(Math.max(0, Number(input.value) - 1));
            },
            dispatchEvent: () => {
                ++inputEvents;
            },
        };
        const Event = class {
            constructor(type) {
                this.type = type;
            }
        };

        adjustInputValue(input, 1, Event);
        adjustInputValue(input, 1, Event);
        expect(input.value).toBe('10');
        expect(inputEvents).toBe(2);

        input.value = '0';
        adjustInputValue(input, -1, Event);
        expect(input.value).toBe('0');
    });

    it('rejects invalid stepper directions and incompatible inputs', () => {
        const Event = class {};

        expect(() => adjustInputValue({}, 1, Event)).toThrow('compatible number input');
        expect(() => adjustInputValue({ stepUp() {}, stepDown() {}, dispatchEvent() {} }, 0, Event)).toThrow('must be 1 or -1');
    });

    it('aborts listeners from a previous initialization cycle', () => {
        const root = {};
        const firstLifecycle = createWorkoutPrototypeLifecycle(root, globalThis.AbortController);
        const secondLifecycle = createWorkoutPrototypeLifecycle(root, globalThis.AbortController);

        expect(firstLifecycle.signal.aborted).toBe(true);
        expect(secondLifecycle.signal.aborted).toBe(false);
    });
});
