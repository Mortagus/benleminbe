export function createWorkoutPrototypeDebugger(document, location, signal, console) {
    if (!new globalThis.URLSearchParams(location.search).has('workoutDebug')) {
        return null;
    }

    ['click', 'dblclick', 'pointerdown', 'pointerup', 'touchstart', 'touchend', 'mousedown', 'mouseup', 'input', 'change'].forEach((type) => {
        document.addEventListener(type, (event) => {
            if (event.target.closest?.('[data-workout-value-control]') === null) {
                return;
            }

            console.debug('[Workout stepper event]', {
                type: event.type,
                detail: event.detail,
                target: event.target,
                currentTarget: event.currentTarget,
                pointerType: event.pointerType,
                isPrimary: event.isPrimary,
                isTrusted: event.isTrusted,
                timeStamp: event.timeStamp,
            });
        }, { capture: true, signal });
    });

    return {
        stepperAdjustment() {
            console.count('Workout stepper adjustment');
            console.trace('Workout stepper adjustment');
        },
        storageFailure(operation) {
            console.warn(`Workout prototype storage ${operation} failed.`);
        },
    };
}
