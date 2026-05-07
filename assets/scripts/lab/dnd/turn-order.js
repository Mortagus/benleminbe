import { getTurnCount, shouldSkipTurn } from './initiative.js';

let roundOrder = [];

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
            }));
        })
        .sort((a, b) => b.initiative - a.initiative);

    return roundOrder;
}

export function renderRoundOrder(turnOrderList, turnOrderPlaceholder) {
    turnOrderList.innerHTML = '';

    if (roundOrder.length === 0) {
        turnOrderPlaceholder.hidden = false;
        turnOrderList.hidden = true;

        return;
    }

    turnOrderPlaceholder.hidden = true;
    turnOrderList.hidden = false;

    const firstActiveIndex = roundOrder.findIndex(actor => actor.done === false);

    roundOrder.forEach((actor, index) => {
        const li = document.createElement('li');
        li.classList.add('turn-order-item');

        if (index === firstActiveIndex) {
            li.classList.add('turn-order-item--active');
        }

        if (actor.done) {
            li.classList.add('turn-order-item--done');
        }

        li.innerHTML = `
            <div class="turn-order-item__image-placeholder">
                ${getActorInitial(actor)}
            </div>

            <div class="turn-order-item__name">
                ${actor.name}
            </div>

            <div class="turn-order-item__stats">
                <span>Init. ${actor.initiative}</span>
                <span>CA ${actor.armorClass}</span>
                <span>PV ${actor.currentHitPoints} / ${actor.baseHitPoints}</span>
            </div>

            ${index === firstActiveIndex ? '<div class="turn-order-item__badge">À jouer</div>' : ''}
        `;

        li.addEventListener('click', () => {
            roundOrder[index].done = true;
            renderRoundOrder(turnOrderList, turnOrderPlaceholder);
        });

        turnOrderList.appendChild(li);
    });
}

function getActorInitial(actor) {
    return actor.name.trim().charAt(0).toUpperCase() || '?';
}
