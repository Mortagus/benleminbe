import { monsterClasses } from './monster_classes.js';

let monsters = [];
let roundOrder = [];

const monsterCountInput = document.getElementById('monsterCount');
const createMonstersButton = document.getElementById('createMonsters');
const rollInitiativeButton = document.getElementById('rollInitiative');
const monsterList = document.getElementById('monsterList');

const addPlayerButton = document.getElementById('addPlayer');
const playerList = document.getElementById('playerList');

const generateTurnOrderButton = document.getElementById('generateTurnOrder');
const turnOrderPlaceholder = document.getElementById('turnOrderPlaceholder');
const turnOrderList = document.getElementById('turnOrderList');

/* ==========================================================================
   Monsters
   ========================================================================== */

function createEmptyMonster(index) {
    return {
        id: `monster-${index + 1}`,
        slug: null,
        name: `Monstre ${index + 1}`,
        className: null,
        type: '-',
        armorClass: '-',
        baseHitPoints: 0,
        currentHitPoints: 0,
        roll: null,
        initiative: null,
        originalData: null,
    };
}

function createMonsterFromClass(monsterClass, index, previousMonster = null) {
    return {
        id: `${monsterClass.slug}-${index + 1}`,
        slug: monsterClass.slug,
        name: `${monsterClass.name} ${index + 1}`,
        className: monsterClass.name,
        challengeRating: monsterClass.challenge_rating,
        type: monsterClass.type,
        size: monsterClass.size,
        armorClass: monsterClass.armor_class,
        baseHitPoints: monsterClass.hit_points,
        currentHitPoints: monsterClass.hit_points,
        alignment: monsterClass.alignment,
        isLegendary: monsterClass.is_legendary,
        roll: previousMonster?.roll ?? null,
        initiative: previousMonster?.initiative ?? null,
        originalData: monsterClass,
    };
}

function rollD20() {
    return Math.floor(Math.random() * 20) + 1;
}

function renderMonsterOptions(selectedSlug) {
    const options = [
        '<option value="">Choisir un monstre</option>',
        ...monsterClasses.map(monsterClass => {
            const selected = monsterClass.slug === selectedSlug ? 'selected' : '';

            return `<option value="${monsterClass.slug}" ${selected}>${monsterClass.name}</option>`;
        }),
    ];

    return options.join('');
}

function getInitiativeClass(monster) {
    if (monster.roll === 20) {
        return 'monster-initiative--critical-success';
    }

    if (monster.roll === 1) {
        return 'monster-initiative--critical-failure';
    }

    return '';
}

function formatInitiative(monster) {
    if (monster.initiative === null) {
        return '-';
    }

    if (monster.roll === 20) {
        return `${monster.initiative} ★`;
    }

    if (monster.roll === 1) {
        return `${monster.initiative} ⚠`;
    }

    return monster.initiative;
}

function renderMonsters() {
    monsterList.innerHTML = '';

    monsters.forEach((monster, index) => {
        const li = document.createElement('li');
        li.classList.add('monster-item');

        li.innerHTML = `
            <div class="monster-main">
                <select class="monster-select" data-index="${index}">
                    ${renderMonsterOptions(monster.slug)}
                </select>

                <span class="monster-type">${monster.type}</span>
            </div>

            <div class="monster-stats">
                <span class="monster-stat">CA ${monster.armorClass}</span>

                <label class="monster-hp">
                    PV
                    <input
                        type="number"
                        min="0"
                        max="${monster.baseHitPoints}"
                        value="${monster.currentHitPoints}"
                        ${monster.slug === null ? 'disabled' : ''}
                    >
                    / ${monster.baseHitPoints}
                </label>

                <span class="monster-stat monster-initiative ${getInitiativeClass(monster)}">
                    Init. ${formatInitiative(monster)}
                </span>
            </div>
        `;

        bindMonsterItemEvents(li, index);
        monsterList.appendChild(li);
    });
}

function syncMonsterHitPointsFromDom() {
    const monsterItems = monsterList.querySelectorAll('.monster-item');

    monsterItems.forEach((item, index) => {
        const hitPointsInput = item.querySelector('.monster-hp input');

        if (!hitPointsInput || !monsters[index]) {
            return;
        }

        monsters[index].currentHitPoints = Number(hitPointsInput.value || 0);
    });
}

function bindMonsterItemEvents(monsterItem, index) {
    const monsterSelect = monsterItem.querySelector('.monster-select');
    const hitPointsInput = monsterItem.querySelector('.monster-hp input');

    monsterSelect.addEventListener('change', event => {
        const selectedSlug = event.target.value;
        const selectedMonsterClass = monsterClasses.find(monsterClass => monsterClass.slug === selectedSlug);

        monsters[index] = selectedMonsterClass
            ? createMonsterFromClass(selectedMonsterClass, index, monsters[index])
            : createEmptyMonster(index);

        updateRollInitiativeButtonState();
        renderMonsters();
    });

    hitPointsInput?.addEventListener('input', event => {
        monsters[index].currentHitPoints = Number(event.target.value);
    });
}

function updateRollInitiativeButtonState() {
    rollInitiativeButton.disabled = !monsters.some(monster => monster.slug !== null);
}

/* ==========================================================================
   Players
   ========================================================================== */

function createPlayerItem() {
    const li = document.createElement('li');
    li.classList.add('player-item');

    li.innerHTML = `
        <div class="player-field player-field--name">
            <label>Nom</label>
            <input type="text" placeholder="Nom du joueur">
        </div>

        <div class="player-field">
            <label>CA</label>
            <input type="number" min="0" placeholder="10">
        </div>

        <div class="player-field player-field--hp">
            <label>PV</label>
            <div class="player-hp-inputs">
                <input type="number" min="0" placeholder="Actuels">
                <span>/</span>
                <input type="number" min="0" placeholder="Max">
            </div>
        </div>

        <div class="player-field">
            <label>Init.</label>
            <input type="number" placeholder="0">
        </div>

        <button type="button" class="player-remove-button" aria-label="Supprimer ce joueur">
            Supprimer
        </button>
    `;

    bindPlayerItemEvents(li);

    return li;
}

function bindPlayerItemEvents(playerItem) {
    const removeButton = playerItem.querySelector('.player-remove-button');

    removeButton.addEventListener('click', () => {
        playerItem.remove();
    });
}

function bindExistingPlayerRemoveButtons() {
    const playerItems = playerList.querySelectorAll('.player-item');

    playerItems.forEach(bindPlayerItemEvents);
}

function getPlayerActors() {
    const playerItems = playerList.querySelectorAll('.player-item');

    return Array.from(playerItems)
        .map((item, index) => {
            const nameInput = item.querySelector('.player-field--name input');
            const fields = item.querySelectorAll('.player-field');

            const armorClassInput = fields[1]?.querySelector('input');
            const hitPointInputs = item.querySelectorAll('.player-field--hp input');
            const initiativeInput = fields[3]?.querySelector('input');

            return {
                id: `player-${index + 1}`,
                type: 'player',
                name: nameInput?.value || `Joueur ${index + 1}`,
                armorClass: Number(armorClassInput?.value || 0),
                currentHitPoints: Number(hitPointInputs[0]?.value || 0),
                baseHitPoints: Number(hitPointInputs[1]?.value || 0),
                initiative: Number(initiativeInput?.value || 0),
                done: false,
            };
        })
        .filter(actor => actor.name.trim() !== '');
}

/* ==========================================================================
   Round order
   ========================================================================== */

function getMonsterActors() {
    return monsters
        .filter(monster => monster.slug !== null && monster.initiative !== null)
        .map(monster => ({
            id: monster.id,
            type: 'monster',
            name: monster.name,
            armorClass: monster.armorClass,
            currentHitPoints: monster.currentHitPoints,
            baseHitPoints: monster.baseHitPoints,
            initiative: monster.initiative,
            done: false,
        }));
}

function buildRoundOrder() {
    const actors = [
        ...getMonsterActors(),
        ...getPlayerActors(),
    ];

    return actors
        .filter(actor => actor.initiative !== 1)
        .flatMap(actor => {
            if (actor.initiative === 20) {
                return [
                    { ...actor, id: `${actor.id}-critical-1` },
                    { ...actor, id: `${actor.id}-critical-2` },
                ];
            }

            return [actor];
        })
        .sort((a, b) => b.initiative - a.initiative);
}

function getActorInitial(actor) {
    return actor.name.trim().charAt(0).toUpperCase() || '?';
}

function renderRoundOrder() {
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
            renderRoundOrder();
        });

        turnOrderList.appendChild(li);
    });
}

/* ==========================================================================
   Events
   ========================================================================== */

createMonstersButton.addEventListener('click', () => {
    const count = Number(monsterCountInput.value);

    monsters = [];

    for (let i = 0; i < count; i++) {
        monsters.push(createEmptyMonster(i));
    }

    rollInitiativeButton.disabled = true;
    renderMonsters();
});

rollInitiativeButton.addEventListener('click', () => {
    monsters = monsters.map(monster => {
        if (monster.slug === null) {
            return monster;
        }

        const roll = rollD20();

        return {
            ...monster,
            roll,
            initiative: roll,
        };
    });

    monsters.sort((a, b) => {
        if (a.initiative === null) {
            return 1;
        }

        if (b.initiative === null) {
            return -1;
        }

        return b.initiative - a.initiative;
    });

    renderMonsters();
});

addPlayerButton.addEventListener('click', () => {
    playerList.appendChild(createPlayerItem());
});

generateTurnOrderButton.addEventListener('click', () => {
    syncMonsterHitPointsFromDom();

    roundOrder = buildRoundOrder();
    renderRoundOrder();
});

/* ==========================================================================
   Init
   ========================================================================== */

bindExistingPlayerRemoveButtons();
