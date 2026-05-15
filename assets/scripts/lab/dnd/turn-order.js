import {
    moveTurnBefore,
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
    const turnOrderPlaceholder = document.getElementById('turnOrderPlaceholder');
    const turnOrderList = document.getElementById('turnOrderList');

    generateTurnOrderButton.addEventListener('click', () => {
        callbacks.onGenerateTurnOrder?.();
    });

    function refresh() {
        renderRoundOrder(
            turnOrderList,
            turnOrderPlaceholder,
            encounter.turnOrder,
            {
                onToggleTurnDone: (turnId) => {
                    toggleTurnDone(encounter, turnId);
                    refresh();
                },
                onMoveTurnBefore: (draggedTurnId, targetTurnId) => {
                    moveTurnBefore(encounter, draggedTurnId, targetTurnId);
                    refresh();
                },
            },
        );
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

        if (index === firstActiveIndex) {
            li.classList.add('turn-order-item--active');
        }

        if (actor.done) {
            li.classList.add('turn-order-item--done');
        }

        li.querySelector('.turn-order-item__image-placeholder').textContent = getActorInitial(actor);
        li.querySelector('.turn-order-item__name').textContent = actor.name;
        li.querySelector('.turn-order-item__initiative').textContent = `Init. ${actor.initiative}`;
        li.querySelector('.turn-order-item__armor-class').textContent = `CA ${actor.armorClass}`;
        li.querySelector('.turn-order-item__hit-points').textContent = `PV ${actor.currentHitPoints} / ${actor.baseHitPoints}`;

        const badge = li.querySelector('.turn-order-item__badge');
        badge.hidden = index !== firstActiveIndex;

        li.addEventListener('click', () => {
            callbacks.onToggleTurnDone(actor.id);
        });

        li.draggable = true;
        li.dataset.actorId = actor.id;

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

            callbacks.onMoveTurnBefore(draggedActorId, actor.id);
        });

        turnOrderList.appendChild(li);
    });
}

function getActorInitial(actor) {
    return actor.name.trim().charAt(0).toUpperCase() || '?';
}
