import { createWorkoutPrototypeDebugger } from './prototype-debug.js';

const STORAGE_KEY = 'benleminbe.workout.prototype.v1';
const SNAPSHOT_VERSION = 1;
const INITIALIZATION_CONTROLLERS = new WeakMap();

export function readPrototypeSnapshot(storage) {
    let value;

    try {
        value = storage.getItem(STORAGE_KEY);
    } catch {
        return { status: 'unavailable', state: null };
    }

    if (value === null) {
        return { status: 'absent', state: null };
    }

    try {
        return { status: 'available', state: JSON.parse(value) };
    } catch {
        return { status: 'invalid', state: null };
    }
}

export function writePrototypeSnapshot(storage, state) {
    try {
        storage.setItem(STORAGE_KEY, JSON.stringify(state));

        return true;
    } catch {
        return false;
    }
}

export function createWorkoutPrototypeLifecycle(root, AbortController) {
    INITIALIZATION_CONTROLLERS.get(root)?.abort();

    const controller = new AbortController();
    INITIALIZATION_CONTROLLERS.set(root, controller);

    return controller;
}

export function adjustInputValue(input, direction, Event) {
    if (direction !== 1 && direction !== -1) {
        throw new Error('Workout stepper direction must be 1 or -1.');
    }

    if (typeof input?.stepUp !== 'function' || typeof input.stepDown !== 'function') {
        throw new Error('Workout stepper requires a compatible number input.');
    }

    if (direction === 1) {
        input.stepUp();
    } else {
        input.stepDown();
    }

    input.dispatchEvent(new Event('input', { bubbles: true }));
}

export function createInitialPrototypeState(exercises) {
    return {
        version: SNAPSHOT_VERSION,
        activeExerciseId: exercises[0].id,
        exercises: Object.fromEntries(exercises.map((exercise) => [exercise.id, {
            activeSetId: exercise.sets[0].id,
            sets: Object.fromEntries(exercise.sets.map((set) => [set.id, {
                load: set.loadInput.value,
                repetitions: set.repetitionsInput.value,
                completed: false,
            }])),
        }])),
    };
}

export function restorePrototypeState(snapshot, exercises) {
    const initialState = createInitialPrototypeState(exercises);

    if (snapshot?.version !== SNAPSHOT_VERSION || snapshot.exercises === null || typeof snapshot.exercises !== 'object') {
        return initialState;
    }

    exercises.forEach((exercise) => {
        const savedExercise = snapshot.exercises[exercise.id];

        if (savedExercise === null || typeof savedExercise !== 'object') {
            return;
        }

        const availableSetIds = new Set(exercise.sets.map((set) => set.id));
        initialState.exercises[exercise.id].activeSetId = availableSetIds.has(savedExercise.activeSetId)
            ? savedExercise.activeSetId
            : exercise.sets[0].id;

        exercise.sets.forEach((set) => {
            const savedSet = savedExercise.sets?.[set.id];

            if (savedSet === null || typeof savedSet !== 'object') {
                return;
            }

            initialState.exercises[exercise.id].sets[set.id] = {
                load: isValidNumberValue(savedSet.load) ? savedSet.load : set.loadInput.value,
                repetitions: isValidNumberValue(savedSet.repetitions) ? savedSet.repetitions : set.repetitionsInput.value,
                completed: savedSet.completed === true,
            };
        });
    });

    if (exercises.some((exercise) => exercise.id === snapshot.activeExerciseId)) {
        initialState.activeExerciseId = snapshot.activeExerciseId;
    }

    return initialState;
}

export function getCompletedSetCount(exerciseState) {
    return Object.values(exerciseState.sets).filter((set) => set.completed).length;
}

function isValidNumberValue(value) {
    return typeof value === 'string' && value !== '' && Number.isFinite(Number(value)) && Number(value) >= 0;
}

function requireElement(root, selector) {
    const element = root.querySelector(selector);

    if (element === null) {
        throw new Error(`Missing required workout element: ${selector}`);
    }

    return element;
}

function requireDataId(element, attribute) {
    const id = element.dataset[attribute];

    if (typeof id !== 'string' || id === '') {
        throw new Error(`Missing required workout data attribute: data-${attribute.replace(/[A-Z]/g, (letter) => `-${letter.toLowerCase()}`)}`);
    }

    return id;
}

function collectPrototypeElements(root) {
    const exerciseElements = [...root.querySelectorAll('[data-workout-exercise-id]')];

    if (exerciseElements.length === 0) {
        throw new Error('Workout prototype requires at least one exercise.');
    }

    const exercises = exerciseElements.map((element) => {
        const id = requireDataId(element, 'workoutExerciseId');
        const setElements = [...element.querySelectorAll('[data-workout-set-id]')];

        if (setElements.length === 0) {
            throw new Error(`Workout exercise ${id} requires at least one set.`);
        }

        const sets = setElements.map((setElement) => ({
            id: requireDataId(setElement, 'workoutSetId'),
            element: setElement,
            loadInput: requireElement(setElement, '[data-workout-load]'),
            repetitionsInput: requireElement(setElement, '[data-workout-repetitions]'),
            completionButton: requireElement(setElement, '[data-workout-complete]'),
        }));

        if (new Set(sets.map((set) => set.id)).size !== sets.length) {
            throw new Error(`Workout exercise ${id} contains duplicate set identifiers.`);
        }

        return {
            id,
            element,
            sets,
            completion: requireElement(element, '[data-workout-completion]'),
            setPosition: requireElement(element, '[data-workout-set-position]'),
        };
    });

    if (new Set(exercises.map((exercise) => exercise.id)).size !== exercises.length) {
        throw new Error('Workout prototype contains duplicate exercise identifiers.');
    }

    const exerciseButtons = [...root.querySelectorAll('[data-workout-select]')];

    exerciseButtons.forEach((button) => {
        const exerciseId = button.dataset.workoutSelect;

        if (!exercises.some((exercise) => exercise.id === exerciseId)) {
            throw new Error(`Workout navigation references an unknown exercise: ${exerciseId ?? '(missing)'}`);
        }
    });

    return {
        exercises,
        status: requireElement(root, '[data-workout-save-status]'),
        navigation: requireElement(root, '[data-workout-navigation]'),
        previousButton: requireElement(root, '[data-workout-previous]'),
        nextButton: requireElement(root, '[data-workout-next]'),
        resetButton: requireElement(root, '[data-workout-reset]'),
        exerciseButtons,
    };
}

function createPersistence(storage, status, schedule, debug) {
    let scheduled = false;
    let stateToSave = null;

    return {
        request(state) {
            stateToSave = state;

            if (scheduled) {
                return;
            }

            scheduled = true;
            schedule(() => {
                scheduled = false;
                const saved = writePrototypeSnapshot(storage, stateToSave);
                status.textContent = saved ? 'Enregistré localement.' : 'Enregistrement local indisponible.';

                if (!saved) {
                    debug?.storageFailure('write');
                }
            });
        },
    };
}

function renderPrototype(elements, state, options = {}) {
    const activeExercise = elements.exercises.find((exercise) => exercise.id === state.activeExerciseId);

    elements.exercises.forEach((exercise) => {
        const exerciseState = state.exercises[exercise.id];
        const isActiveExercise = exercise.id === state.activeExerciseId;
        const activeSet = exercise.sets.find((set) => set.id === exerciseState.activeSetId) ?? exercise.sets[0];

        exercise.element.hidden = !isActiveExercise;
        exercise.completion.textContent = `${getCompletedSetCount(exerciseState)}/${exercise.sets.length}`;
        exercise.setPosition.textContent = `Série ${exercise.sets.indexOf(activeSet) + 1} sur ${exercise.sets.length}`;
        exercise.sets.forEach((set) => {
            const setState = exerciseState.sets[set.id];
            const isActiveSet = set.id === activeSet.id;

            set.element.hidden = !isActiveSet;
            set.element.classList.toggle('is-completed', setState.completed);
            set.loadInput.value = setState.load;
            set.repetitionsInput.value = setState.repetitions;
            set.completionButton.textContent = setState.completed ? 'Annuler' : 'Valider';
            set.completionButton.setAttribute('aria-pressed', String(setState.completed));
        });
    });
    elements.exerciseButtons.forEach((button) => {
        const isCurrent = button.dataset.workoutSelect === state.activeExerciseId;
        button.classList.toggle('is-current', isCurrent);
        button.toggleAttribute('aria-current', isCurrent);
    });
    const activeSetIndex = activeExercise.sets.findIndex((set) => set.id === state.exercises[activeExercise.id].activeSetId);
    elements.previousButton.disabled = elements.exercises.indexOf(activeExercise) === 0;
    elements.nextButton.disabled = elements.exercises.indexOf(activeExercise) === elements.exercises.length - 1;
    elements.navigation.hidden = activeSetIndex !== activeExercise.sets.length - 1;

    if (options.focusSet === true) {
        const activeSet = activeExercise.sets[activeSetIndex];
        activeSet.element.scrollIntoView({ behavior: options.reducedMotion ? 'auto' : 'smooth', block: 'center' });
        activeSet.loadInput.focus({ preventScroll: true });
    }
}

function findExercise(elements, id) {
    const exercise = elements.exercises.find((item) => item.id === id);

    if (exercise === undefined) {
        throw new Error(`Unknown workout exercise: ${id}`);
    }

    return exercise;
}

function getSetContext(target) {
    const exerciseElement = target.closest('[data-workout-exercise-id]');
    const setElement = target.closest('[data-workout-set-id]');

    if (exerciseElement === null || setElement === null) {
        throw new Error('Workout mutation requires an exercise and a set.');
    }

    return {
        exerciseId: requireDataId(exerciseElement, 'workoutExerciseId'),
        setId: requireDataId(setElement, 'workoutSetId'),
    };
}

export function setupWorkoutPrototype(document, dependencies = {}) {
    const root = document.querySelector('[data-workout-prototype]');

    if (root === null) {
        return null;
    }

    const browser = {
        storage: dependencies.storage ?? globalThis.localStorage,
        location: dependencies.location ?? globalThis.location,
        AbortController: dependencies.AbortController ?? globalThis.AbortController,
        Event: dependencies.Event ?? globalThis.Event,
        schedule: dependencies.schedule ?? globalThis.queueMicrotask,
        reducedMotion: dependencies.reducedMotion ?? globalThis.matchMedia('(prefers-reduced-motion: reduce)').matches,
        console: dependencies.console ?? globalThis.console,
    };
    const lifecycle = createWorkoutPrototypeLifecycle(root, browser.AbortController);
    const elements = collectPrototypeElements(root);
    const debug = createWorkoutPrototypeDebugger(document, browser.location, lifecycle.signal, browser.console);
    const snapshot = readPrototypeSnapshot(browser.storage);
    let state = restorePrototypeState(snapshot.state, elements.exercises);
    const persistence = createPersistence(browser.storage, elements.status, browser.schedule, debug);

    if (snapshot.status === 'invalid') {
        elements.status.textContent = 'Ancien essai local ignoré.';
        debug?.storageFailure('read');
    } else if (snapshot.status === 'unavailable') {
        elements.status.textContent = 'Stockage local indisponible.';
        debug?.storageFailure('read');
    }

    const renderAndPersist = (options) => {
        renderPrototype(elements, state, { ...options, reducedMotion: browser.reducedMotion });
        persistence.request(state);
    };

    root.addEventListener('input', (event) => {
        if (!event.target.matches('[data-workout-load], [data-workout-repetitions]')) {
            return;
        }

        const { exerciseId, setId } = getSetContext(event.target);
        const field = event.target.matches('[data-workout-load]') ? 'load' : 'repetitions';
        state.exercises[exerciseId].sets[setId][field] = event.target.value;
        persistence.request(state);
    }, { signal: lifecycle.signal });
    root.addEventListener('click', (event) => {
        const adjustmentButton = event.target.closest('[data-workout-adjust]');

        if (adjustmentButton !== null) {
            const direction = adjustmentButton.dataset.workoutAdjust === 'increase' ? 1 : adjustmentButton.dataset.workoutAdjust === 'decrease' ? -1 : null;

            if (direction === null) {
                throw new Error('Workout adjustment direction is invalid.');
            }

            event.preventDefault();
            debug?.stepperAdjustment();
            const valueControl = adjustmentButton.closest('[data-workout-value-control]');

            if (valueControl === null) {
                throw new Error('Workout adjustment button requires a value control.');
            }

            adjustInputValue(requireElement(valueControl, 'input'), direction, browser.Event);

            return;
        }

        const completionButton = event.target.closest('[data-workout-complete]');

        if (completionButton !== null) {
            const { exerciseId, setId } = getSetContext(completionButton);
            const exercise = findExercise(elements, exerciseId);
            const setState = state.exercises[exerciseId].sets[setId];
            const completed = !setState.completed;
            const currentSetIndex = exercise.sets.findIndex((set) => set.id === setId);

            setState.completed = completed;
            if (completed && currentSetIndex < exercise.sets.length - 1) {
                state.exercises[exerciseId].activeSetId = exercise.sets[currentSetIndex + 1].id;
            }
            renderAndPersist({ focusSet: completed && currentSetIndex < exercise.sets.length - 1 });

            return;
        }

        const exerciseButton = event.target.closest('[data-workout-select]');

        if (exerciseButton !== null) {
            state.activeExerciseId = findExercise(elements, exerciseButton.dataset.workoutSelect).id;
            renderAndPersist();
        }
    }, { signal: lifecycle.signal });
    elements.previousButton.addEventListener('click', () => {
        const index = elements.exercises.findIndex((exercise) => exercise.id === state.activeExerciseId);
        state.activeExerciseId = elements.exercises[Math.max(0, index - 1)].id;
        renderAndPersist();
    }, { signal: lifecycle.signal });
    elements.nextButton.addEventListener('click', () => {
        const index = elements.exercises.findIndex((exercise) => exercise.id === state.activeExerciseId);
        state.activeExerciseId = elements.exercises[Math.min(elements.exercises.length - 1, index + 1)].id;
        renderAndPersist();
    }, { signal: lifecycle.signal });
    elements.resetButton.addEventListener('click', () => {
        try {
            browser.storage.removeItem(STORAGE_KEY);
        } catch {
            elements.status.textContent = 'Réinitialisation locale indisponible.';
            debug?.storageFailure('remove');

            return;
        }

        browser.location.reload();
    }, { signal: lifecycle.signal });

    renderPrototype(elements, state, { reducedMotion: browser.reducedMotion });

    return lifecycle;
}
