import { monsterClasses } from './monster_classes.js';
import { formatInitiative, getInitiativeClass, rollD20 } from './initiative.js';

let monsters = [];

export function createEmptyMonster(index) {
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

export function createMonsterFromClass(monsterClass, index, previousMonster = null) {
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

export function createMonsterSlots(count) {
    monsters = [];

    for (let i = 0; i < count; i++) {
        monsters.push(createEmptyMonster(i));
    }
}

export function hasSelectedMonsters() {
    return monsters.some(monster => monster.slug !== null);
}

export function rollMonsterInitiatives() {
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
}

export function syncMonsterHitPointsFromDom(monsterList) {
    const monsterItems = monsterList.querySelectorAll('.monster-item');

    monsterItems.forEach((item, index) => {
        const hitPointsInput = item.querySelector('.monster-hp input');

        if (!hitPointsInput || !monsters[index]) {
            return;
        }

        monsters[index].currentHitPoints = Number(hitPointsInput.value || 0);
    });
}

export function getMonsterActors() {
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

export function renderMonsters(monsterList, onMonsterSelectionChange) {
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

        bindMonsterItemEvents(li, index, onMonsterSelectionChange);
        monsterList.appendChild(li);
    });
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

function bindMonsterItemEvents(monsterItem, index, onMonsterSelectionChange) {
    const monsterSelect = monsterItem.querySelector('.monster-select');
    const hitPointsInput = monsterItem.querySelector('.monster-hp input');

    monsterSelect.addEventListener('change', event => {
        const selectedSlug = event.target.value;
        const selectedMonsterClass = monsterClasses.find(monsterClass => monsterClass.slug === selectedSlug);

        monsters[index] = selectedMonsterClass
            ? createMonsterFromClass(selectedMonsterClass, index, monsters[index])
            : createEmptyMonster(index);

        onMonsterSelectionChange();
    });

    hitPointsInput?.addEventListener('input', event => {
        monsters[index].currentHitPoints = Number(event.target.value);
    });
}
