import {
    moveTurn,
    toggleTurnDone,
} from './encounter-state.js';
import {
    clearValidationState,
    showValidationErrors,
} from './validation.js';

let draggedActorId = null;
const turnOrderItemTemplate = document.getElementById('turnOrderItemTemplate');

export function initializeTurnOrderPanel(encounter, callbacks = {}) {
    const generateTurnOrderButton = document.getElementById('generateTurnOrder');
    const turnOrderPanel = document.querySelector('.dnd-panel--turn-order');
    const turnOrderValidationSummary = document.getElementById('turnOrderValidationSummary');
    const turnOrderKeyboardHelpButton = document.getElementById('toggleTurnOrderKeyboardHelp');
    const turnOrderKeyboardHelp = document.getElementById('turnOrderKeyboardHelp');
    const turnOrderPlaceholder = document.getElementById('turnOrderPlaceholder');
    const turnOrderList = document.getElementById('turnOrderList');
    const turnOrderLiveRegion = document.getElementById('turnOrderLiveRegion');
    let pendingFocusTurnId = null;

    generateTurnOrderButton.addEventListener('click', () => {
        callbacks.onGenerateTurnOrder?.();
    });

    bindKeyboardHelp(turnOrderKeyboardHelpButton, turnOrderKeyboardHelp);

    function refresh(options = {}) {
        if (options.focusFirst) {
            pendingFocusTurnId = encounter.turnOrder[0]?.id ?? null;
        }

        renderRoundOrder(
            turnOrderList,
            turnOrderPlaceholder,
            encounter.turnOrder,
            {
                onToggleTurnDone: (turnId) => {
                    toggleTurnDone(encounter, turnId);
                    pendingFocusTurnId = turnId;
                    refresh();
                },
                onMoveTurn: (draggedTurnId, targetTurnId, placement) => {
                    moveTurn(encounter, draggedTurnId, targetTurnId, placement);
                    pendingFocusTurnId = draggedTurnId;
                    refresh();
                },
                onAnnounce: (message) => {
                    announceTurnOrderChange(turnOrderLiveRegion, message);
                },
            },
        );

        if (pendingFocusTurnId) {
            focusTurnItem(turnOrderList, pendingFocusTurnId);
            pendingFocusTurnId = null;
        }
    }

    function showEncounterValidationErrors(validationResult) {
        showValidationErrors(
            validationResult,
            turnOrderValidationSummary,
            'Impossible de générer le tour de table.',
        );
    }

    return {
        clearValidation: () => clearValidationState(turnOrderPanel),
        refresh,
        showEncounterValidationErrors,
    };
}

export function renderRoundOrder(turnOrderList, turnOrderPlaceholder, roundOrder, callbacks) {
    turnOrderList.replaceChildren();

    if (roundOrder.length === 0) {
        turnOrderPlaceholder.hidden = false;
        turnOrderList.hidden = true;

        return;
    }

    turnOrderPlaceholder.hidden = true;
    turnOrderList.hidden = false;

    const firstActiveIndex = roundOrder.findIndex(actor => !actor.done);

    roundOrder.forEach((actor, index) => {
        const li = turnOrderItemTemplate.content
            .firstElementChild
            .cloneNode(true);
        const actorPosition = index + 1;
        const actorDescription = getActorDescription(actor, actorPosition, roundOrder.length);

        if (index === firstActiveIndex) {
            li.classList.add('turn-order-item--active');
        }

        if (actor.done) {
            li.classList.add('turn-order-item--done');
        }

        li.tabIndex = 0;
        li.dataset.actorId = actor.id;
        li.setAttribute('aria-label', actorDescription);
        li.title = 'Entrée ou espace : basculer joué/non joué. Flèches gauche et droite : déplacer.';

        li.querySelector('.turn-order-item__image-placeholder').textContent = getActorInitial(actor);
        li.querySelector('.turn-order-item__name').textContent = actor.name;
        li.querySelector('.turn-order-item__initiative').textContent = `Init. ${actor.initiative}`;
        li.querySelector('.turn-order-item__armor-class').textContent = `CA ${actor.armorClass}`;
        li.querySelector('.turn-order-item__hit-points').textContent = `PV ${actor.currentHitPoints} / ${actor.baseHitPoints}`;

        const badge = li.querySelector('.turn-order-item__badge');
        badge.hidden = index !== firstActiveIndex;

        bindMoveButton(
            li.querySelector('[data-turn-move="previous"]'),
            roundOrder,
            actor,
            index,
            'previous',
            callbacks,
        );
        bindMoveButton(
            li.querySelector('[data-turn-move="next"]'),
            roundOrder,
            actor,
            index,
            'next',
            callbacks,
        );

        li.addEventListener('click', () => {
            callbacks.onToggleTurnDone(actor.id);
        });

        li.addEventListener('keydown', event => {
            if (event.target !== li) {
                return;
            }

            if (['Enter', ' '].includes(event.key)) {
                event.preventDefault();
                callbacks.onToggleTurnDone(actor.id);
                return;
            }

            if (event.key === 'ArrowLeft') {
                event.preventDefault();
                moveActorWithKeyboard(roundOrder, actor, index, 'previous', callbacks);
                return;
            }

            if (event.key === 'ArrowRight') {
                event.preventDefault();
                moveActorWithKeyboard(roundOrder, actor, index, 'next', callbacks);
            }
        });

        li.draggable = true;

        li.addEventListener('dragstart', () => {
            draggedActorId = actor.id;
            li.classList.add('turn-order-item--dragging');
        });

        li.addEventListener('dragend', () => {
            draggedActorId = null;
            li.classList.remove('turn-order-item--dragging');
        });

        li.addEventListener('dragover', (event) => {
            event.preventDefault();
            li.classList.add('turn-order-item--drag-over');
        });

        li.addEventListener('dragleave', () => {
            li.classList.remove('turn-order-item--drag-over');
        });

        li.addEventListener('drop', (event) => {
            event.preventDefault();

            li.classList.remove('turn-order-item--drag-over');

            if (!draggedActorId || draggedActorId === actor.id) {
                return;
            }

            callbacks.onMoveTurn(
                draggedActorId,
                actor.id,
                getDropPlacement(roundOrder, draggedActorId, actor.id),
            );
        });

        turnOrderList.appendChild(li);
    });
}

function getActorInitial(actor) {
    return actor.name.trim().charAt(0).toUpperCase() || '?';
}

function bindMoveButton(button, roundOrder, actor, index, direction, callbacks) {
    if (!button) {
        return;
    }

    button.tabIndex = -1;

    const isPrevious = direction === 'previous';
    const target = isPrevious
        ? roundOrder[index - 1]
        : roundOrder[index + 1];
    let label;

    if (!target) {
        label = `Aucun déplacement ${isPrevious ? 'vers la gauche' : 'vers la droite'} possible pour ${actor.name}`;
    } else {
        label = isPrevious
            ? `Déplacer ${actor.name} vers la gauche`
            : `Déplacer ${actor.name} vers la droite`;
    }

    button.setAttribute('aria-label', label);
    button.title = label;

    if (!target) {
        button.setAttribute('aria-disabled', 'true');
    }

    button.addEventListener('click', event => {
        event.stopPropagation();

        if (!target) {
            return;
        }

        callbacks.onMoveTurn(
            actor.id,
            target.id,
            isPrevious ? 'before' : 'after',
        );
        callbacks.onAnnounce?.(
            isPrevious
                ? `${actor.name} déplacé avant ${target.name}.`
                : `${actor.name} déplacé après ${target.name}.`,
        );
    });
}

function moveActorWithKeyboard(roundOrder, actor, index, direction, callbacks) {
    const isPrevious = direction === 'previous';
    const target = isPrevious
        ? roundOrder[index - 1]
        : roundOrder[index + 1];

    if (!target) {
        return;
    }

    callbacks.onMoveTurn(
        actor.id,
        target.id,
        isPrevious ? 'before' : 'after',
    );
    callbacks.onAnnounce?.(
        isPrevious
            ? `${actor.name} déplacé avant ${target.name}.`
            : `${actor.name} déplacé après ${target.name}.`,
    );
}

function getActorDescription(actor, position, total) {
    const turnStatus = actor.done ? 'tour joué' : 'tour à jouer';

    return `${actor.name}, position ${position} sur ${total}, initiative ${actor.initiative}, CA ${actor.armorClass}, PV ${actor.currentHitPoints} sur ${actor.baseHitPoints}, ${turnStatus}. Entrée ou espace pour basculer joué ou non joué. Flèches gauche et droite pour déplacer.`;
}

function focusTurnItem(turnOrderList, turnId) {
    const turnItem = Array.from(turnOrderList.querySelectorAll('.turn-order-item'))
        .find(item => item.dataset.actorId === turnId);

    turnItem?.focus();
}

function announceTurnOrderChange(liveRegion, message) {
    if (!liveRegion) {
        return;
    }

    liveRegion.textContent = '';
    window.requestAnimationFrame(() => {
        liveRegion.textContent = message;
    });
}

function bindKeyboardHelp(button, helpPanel) {
    if (!button || !helpPanel) {
        return;
    }

    button.addEventListener('click', () => {
        const shouldShowHelp = helpPanel.hidden;

        helpPanel.hidden = !shouldShowHelp;
        button.setAttribute('aria-expanded', String(shouldShowHelp));
        button.setAttribute(
            'aria-label',
            shouldShowHelp
                ? 'Masquer l’aide clavier de l’ordre du tour'
                : 'Afficher l’aide clavier de l’ordre du tour',
        );
    });
}

function getDropPlacement(roundOrder, draggedActorId, targetActorId) {
    const draggedIndex = roundOrder.findIndex(actor => actor.id === draggedActorId);
    const targetIndex = roundOrder.findIndex(actor => actor.id === targetActorId);

    if (draggedIndex === -1 || targetIndex === -1) {
        return 'before';
    }

    return draggedIndex < targetIndex ? 'after' : 'before';
}
