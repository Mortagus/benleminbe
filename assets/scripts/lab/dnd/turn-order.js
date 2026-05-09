import { getTurnCount, shouldSkipTurn } from './initiative.js';

let roundOrder = [];
let draggedActorId = null;
const turnOrderItemTemplate = document.getElementById('turnOrderItemTemplate');

export function buildRoundOrder(monsterActors, playerActors) {
    const actors = [
        ...monsterActors,
        ...playerActors,
    ];

    roundOrder = actors
        .filter(actor => !shouldSkipTurn(actor))
        .flatMap(actor => {
            const turnCount = getTurnCount(actor);

            return Array.from({ length: turnCount }, (_, index) => ({
                ...actor,
                id: turnCount > 1 ? `${actor.id}-turn-${index + 1}` : actor.id,
                done: false,
            }));
        })
        .sort((a, b) => b.initiative - a.initiative);

    return roundOrder;
}

export function renderRoundOrder(turnOrderList, turnOrderPlaceholder) {
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
            roundOrder[index].done = !roundOrder[index].done;
            renderRoundOrder(turnOrderList, turnOrderPlaceholder);
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

            moveActorBefore(draggedActorId, actor.id);
            renderRoundOrder(turnOrderList, turnOrderPlaceholder);
        });

        turnOrderList.appendChild(li);
    });
}

function getActorInitial(actor) {
    return actor.name.trim().charAt(0).toUpperCase() || '?';
}

function moveActorBefore(draggedActorId, targetActorId) {
    const draggedIndex = roundOrder.findIndex(actor => actor.id === draggedActorId);

    if (draggedIndex === -1) {
        return;
    }

    const [draggedActor] = roundOrder.splice(draggedIndex, 1);
    const targetIndex = roundOrder.findIndex(actor => actor.id === targetActorId);

    if (targetIndex === -1) {
        roundOrder.push(draggedActor);
        return;
    }

    roundOrder.splice(targetIndex, 0, draggedActor);
}
