// DOM controller for the turn order panel.
// It renders the already-built turn order and handles played state, focus,
// keyboard movement, button movement, and drag and drop.
import {
    clearValidationState,
    showValidationErrors,
} from './validation.js';
import { formatInitiative } from './initiative.js';

let draggedTurnId = null;
const turnOrderItemTemplate = document.getElementById('turnOrderItemTemplate');

export class TurnOrderPanel {
    constructor(encounter, callbacks = {}) {
        this.encounter = encounter;
        this.callbacks = callbacks;
        this.generateTurnOrderButton = document.getElementById('generateTurnOrder');
        this.turnOrderPanel = document.querySelector('.dnd-panel--turn-order');
        this.turnOrderValidationSummary = document.getElementById('turnOrderValidationSummary');
        this.turnOrderKeyboardHelpButton = document.getElementById('toggleTurnOrderKeyboardHelp');
        this.turnOrderKeyboardHelp = document.getElementById('turnOrderKeyboardHelp');
        this.turnOrderPlaceholder = document.getElementById('turnOrderPlaceholder');
        this.turnOrderList = document.getElementById('turnOrderList');
        this.turnOrderLiveRegion = document.getElementById('turnOrderLiveRegion');
        this.pendingFocusTurnId = null;
    }

    start() {
        this.bindGenerateTurnOrderButton();
        bindKeyboardHelp(this.turnOrderKeyboardHelpButton, this.turnOrderKeyboardHelp);
    }

    bindGenerateTurnOrderButton() {
        this.generateTurnOrderButton.addEventListener('click', () => {
            this.callbacks.onGenerateTurnOrder?.();
        });
    }

    clearValidation() {
        clearValidationState(this.turnOrderPanel);
    }

    refresh(options = {}) {
        this.rememberFocusTarget(options);
        this.renderTurnOrder();
        this.restorePendingFocus();
    }

    rememberFocusTarget(options) {
        if (options.focusFirst) {
            this.pendingFocusTurnId = this.encounter.turnOrder[0]?.id ?? null;
        }
    }

    renderTurnOrder() {
        renderRoundOrder(
            this.turnOrderList,
            this.turnOrderPlaceholder,
            this.encounter.turnOrder,
            {
                onToggleTurnDone: (turnId) => {
                    this.encounter.toggleTurnDone(turnId);
                    this.pendingFocusTurnId = turnId;
                    this.refresh();
                },
                onMoveTurn: (draggedTurnId, targetTurnId, placement) => {
                    this.encounter.moveTurn(draggedTurnId, targetTurnId, placement);
                    this.pendingFocusTurnId = draggedTurnId;
                    this.refresh();
                },
                onAnnounce: (message) => {
                    announceTurnOrderChange(this.turnOrderLiveRegion, message);
                },
            },
        );
    }

    restorePendingFocus() {
        if (this.pendingFocusTurnId) {
            focusTurnItem(this.turnOrderList, this.pendingFocusTurnId);
            this.pendingFocusTurnId = null;
        }
    }

    showEncounterValidationErrors(validationResult) {
        showValidationErrors(
            validationResult,
            this.turnOrderValidationSummary,
            'Impossible de générer l’ordre du tour.',
        );
    }
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
        turnOrderList.appendChild(renderTurnOrderItem(
            roundOrder,
            actor,
            index,
            firstActiveIndex,
            callbacks,
        ));
    });
}

function renderTurnOrderItem(roundOrder, actor, index, firstActiveIndex, callbacks) {
    const li = turnOrderItemTemplate.content
        .firstElementChild
        .cloneNode(true);
    const actorPosition = index + 1;

    populateTurnOrderItem(li, actor, {
        isActive: index === firstActiveIndex,
        position: actorPosition,
        total: roundOrder.length,
    });
    bindTurnOrderItemControls(li, roundOrder, actor, index, callbacks);

    return li;
}

function populateTurnOrderItem(li, actor, options) {
    const actorSide = getActorSide(actor);
    const actorDescription = getActorDescription(actor, options.position, options.total);

    li.dataset.actorType = actor.type ?? '';
    li.dataset.actorSide = actorSide;

    if (options.isActive) {
        li.classList.add('turn-order-item--active');
    }

    li.classList.add(`turn-order-item--side-${actorSide}`);

    if (actor.done) {
        li.classList.add('turn-order-item--done');
    }

    if (actor.isLegendary) {
        li.classList.add('turn-order-item--legendary');
    }

    li.tabIndex = 0;
    li.dataset.actorId = actor.id;
    li.setAttribute('aria-label', actorDescription);
    li.title = 'Entrée/Espace : joué. Flèches : déplacer.';

    const legendaryBadge = li.querySelector('.turn-order-item__legendary-badge');
    if (legendaryBadge) {
        legendaryBadge.hidden = !actor.isLegendary;
        legendaryBadge.textContent = actor.isLegendary ? 'Boss' : '';
        legendaryBadge.title = actor.isLegendary ? 'Boss légendaire' : '';
    }

    li.querySelector('.turn-order-item__image-placeholder').textContent = getActorInitial(actor);
    li.querySelector('.turn-order-item__name').textContent = actor.name;
    li.querySelector('.turn-order-item__initiative').textContent = `Init. ${formatInitiative(actor)}`;
    li.querySelector('.turn-order-item__armor-class').textContent = `CA ${actor.armorClass}`;
    li.querySelector('.turn-order-item__hit-points').textContent = `PV ${actor.currentHitPoints} / ${actor.baseHitPoints}`;

    const badge = li.querySelector('.turn-order-item__badge');
    badge.hidden = !options.isActive;
}

function bindTurnOrderItemControls(li, roundOrder, actor, index, callbacks) {
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
    bindTurnOrderItemEvents(li, roundOrder, actor, index, callbacks);
}

function bindTurnOrderItemEvents(li, roundOrder, actor, index, callbacks) {
    bindTurnToggleEvents(li, actor, callbacks);
    bindTurnKeyboardEvents(li, roundOrder, actor, index, callbacks);
    bindTurnDragAndDropEvents(li, roundOrder, actor, callbacks);
}

function bindTurnToggleEvents(li, actor, callbacks) {
    li.addEventListener('click', () => {
        callbacks.onToggleTurnDone(actor.id);
    });
}

function bindTurnKeyboardEvents(li, roundOrder, actor, index, callbacks) {
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
}

function bindTurnDragAndDropEvents(li, roundOrder, actor, callbacks) {
    li.draggable = true;

    li.addEventListener('dragstart', () => {
        draggedTurnId = actor.id;
        li.classList.add('turn-order-item--dragging');
    });

    li.addEventListener('dragend', () => {
        draggedTurnId = null;
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

        if (!draggedTurnId || draggedTurnId === actor.id) {
            return;
        }

        callbacks.onMoveTurn(
            draggedTurnId,
            actor.id,
            getDropPlacement(roundOrder, draggedTurnId, actor.id),
        );
    });
}

function getActorInitial(actor) {
    return actor.name.trim().charAt(0).toUpperCase() || '?';
}

function getActorSide(actor) {
    if (['party', 'ally', 'hostile', 'neutral'].includes(actor.side)) {
        return actor.side;
    }

    return actor.type === 'monster' ? 'hostile' : 'party';
}

function getActorSideLabel(side) {
    const labels = {
        party: 'PJ',
        ally: 'Allié',
        hostile: 'Hostile',
        neutral: 'Neutre',
    };

    return labels[side] ?? 'PJ';
}

function bindMoveButton(button, roundOrder, actor, index, direction, callbacks) {
    if (!button) {
        return;
    }

    button.tabIndex = -1;

    const target = getAdjacentTurn(roundOrder, index, direction);
    const label = getMoveButtonLabel(actor, target, direction);

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
            getMovePlacement(direction),
        );
        callbacks.onAnnounce?.(getMoveAnnouncement(actor, target, direction));
    });
}

function moveActorWithKeyboard(roundOrder, actor, index, direction, callbacks) {
    const target = getAdjacentTurn(roundOrder, index, direction);

    if (!target) {
        return;
    }

    callbacks.onMoveTurn(
        actor.id,
        target.id,
        getMovePlacement(direction),
    );
    callbacks.onAnnounce?.(getMoveAnnouncement(actor, target, direction));
}

function getAdjacentTurn(roundOrder, index, direction) {
    return direction === 'previous'
        ? roundOrder[index - 1]
        : roundOrder[index + 1];
}

function getMovePlacement(direction) {
    return direction === 'previous' ? 'before' : 'after';
}

function getMoveButtonLabel(actor, target, direction) {
    if (!target) {
        return `${actor.name} ne peut pas être déplacé ${direction === 'previous' ? 'avant' : 'après'}`;
    }

    return direction === 'previous'
        ? `Déplacer ${actor.name} avant`
        : `Déplacer ${actor.name} après`;
}

function getMoveAnnouncement(actor, target, direction) {
    return direction === 'previous'
        ? `${actor.name} déplacé avant ${target.name}.`
        : `${actor.name} déplacé après ${target.name}.`;
}

function getActorDescription(actor, position, total) {
    const turnStatus = actor.done ? 'joué' : 'à jouer';
    const actorSide = getActorSide(actor);
    const actorRoles = [getActorSideLabel(actorSide)];

    if (actor.isLegendary) {
        actorRoles.push('boss légendaire');
    }

    return `${actorRoles.join(', ')}. ${actor.name}, position ${position} sur ${total}, initiative ${actor.initiative}, CA ${actor.armorClass}, PV ${actor.currentHitPoints} sur ${actor.baseHitPoints}, ${turnStatus}. Entrée ou espace pour basculer joué. Flèches gauche et droite pour déplacer.`;
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
                ? 'Masquer l’aide clavier'
                : 'Afficher l’aide clavier',
        );
    });
}

function getDropPlacement(roundOrder, draggedTurnId, targetActorId) {
    const draggedIndex = roundOrder.findIndex(actor => actor.id === draggedTurnId);
    const targetIndex = roundOrder.findIndex(actor => actor.id === targetActorId);

    if (draggedIndex === -1 || targetIndex === -1) {
        return 'before';
    }

    return draggedIndex < targetIndex ? 'after' : 'before';
}
