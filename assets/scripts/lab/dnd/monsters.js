import { monsterClasses } from './monster_classes.js';
import { formatInitiative, getInitiativeClass, rollD20 } from './initiative.js';

let monsters = [];

const monsterItemTemplate = document.getElementById('monsterItemTemplate');
const monsterOptionTemplate = document.getElementById('monsterOptionTemplate');

export function createEmptyMonster(index) {
    return {
        id: `monster-${index + 1}`,
        slug: null,
        name: `Monstre ${index + 1}`,
        className: null,
        challengeRating: null,
        type: '-',
        size: null,
        armorClass: '-',
        baseHitPoints: 0,
        currentHitPoints: 0,
        alignment: null,
        isLegendary: false,
        abilities: {},
        initiativeModifier: 0,
        roll: null,
        initiative: null,
        originalData: null,
    };
}

export function createMonsterFromClass(monsterClass, index) {
    const initiativeModifier = getMonsterInitiativeModifier(monsterClass);

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
        abilities: monsterClass.abilities ?? {},
        initiativeModifier: initiativeModifier,
        roll: null,
        initiative: null,
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
        const initiative = roll + monster.initiativeModifier;

        return {
            ...monster,
            roll,
            initiative,
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
            roll: monster.roll,
            initiativeModifier: monster.initiativeModifier,
            done: false,
        }));
}

export function renderMonsters(monsterList, onMonsterSelectionChange) {
    monsterList.innerHTML = '';

    monsters.forEach((monster, index) => {
        const fragment = monsterItemTemplate.content.cloneNode(true);
        const li = fragment.querySelector('.monster-item');

        const select = li.querySelector('.monster-select');
        const type = li.querySelector('.monster-type');
        const armorClass = li.querySelector('.monster-armor-class');
        const hpInput = li.querySelector('.monster-hp input');
        const hpMax = li.querySelector('.monster-hit-points-max');
        const initiative = li.querySelector('.monster-initiative');
        const initiativeModifier = li.querySelector('.monster-initiative-modifier');

        select.dataset.index = String(index);
        renderMonsterOptions(select, monster.slug);

        type.textContent = monster.type;
        armorClass.textContent = `CA ${monster.armorClass}`;

        hpInput.max = String(monster.baseHitPoints);
        hpInput.value = String(monster.currentHitPoints);
        hpInput.disabled = monster.slug === null;

        hpMax.textContent = String(monster.baseHitPoints);

        initiativeModifier.textContent = formatModifier(monster.initiativeModifier);
        initiativeModifier.title = `Modificateur d’initiative : ${formatModifier(monster.initiativeModifier)}. Ajouté au résultat du d20 lors du jet d’initiative.`;
        initiativeModifier.setAttribute(
            'aria-label',
            `Modificateur d’initiative : ${formatModifier(monster.initiativeModifier)}`
        );

        initiative.textContent = `Init. ${formatInitiative(monster)}`;
        initiative.title = getInitiativeTooltip(monster);

        const initiativeClass = getInitiativeClass(monster);

        if (initiativeClass !== '') {
            initiative.classList.add(initiativeClass);
        }

        bindMonsterItemEvents(li, index, onMonsterSelectionChange);
        monsterList.appendChild(li);
    });
}

function getInitiativeTooltip(monster) {
    if (monster.roll === null) {
        return 'Aucun jet d’initiative effectué.';
    }

    const modifier = formatModifier(monster.initiativeModifier);
    const finalScore = formatInitiative(monster);

    let tooltip = [
        `Jet de d20 : ${monster.roll}`,
        `Modificateur : ${modifier}`,
        `Initiative finale : ${finalScore}`,
    ];

    if (monster.roll === 20) {
        tooltip.push('', 'Succès critique');
    } else if (monster.roll === 1) {
        tooltip.push('', 'Échec critique');
    }

    return tooltip.join('\n');
}

function formatModifier(value) {
    const modifier = Number(value);

    if (Number.isNaN(modifier)) {
        return '-';
    }

    return modifier > 0 ? `+${modifier}` : String(modifier);
}

function renderMonsterOptions(select, selectedSlug) {
    select.innerHTML = '';

    const placeholderOption = document.createElement('option');
    placeholderOption.value = '';
    placeholderOption.textContent = 'Choisir un monstre';

    select.appendChild(placeholderOption);

    monsterClasses.forEach(monsterClass => {
        const option = monsterOptionTemplate.content
            .cloneNode(true)
            .querySelector('option');

        option.value = monsterClass.slug;
        option.textContent = monsterClass.name;
        option.selected = monsterClass.slug === selectedSlug;

        select.appendChild(option);
    });
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
        monsters[index].currentHitPoints = Number(event.target.value || 0);
    });
}

function getMonsterInitiativeModifier(monsterClass) {
    if (typeof monsterClass.initiative_modifier === 'number') {
        return monsterClass.initiative_modifier;
    }

    if (typeof monsterClass.abilities?.dex?.modifier === 'number') {
        return monsterClass.abilities.dex.modifier;
    }

    return 0;
}
