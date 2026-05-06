import { monsterClasses } from './monster_classes.js';

let monsters = [];

const monsterCountInput = document.getElementById('monsterCount');
const createMonstersButton = document.getElementById('createMonsters');
const rollInitiativeButton = document.getElementById('rollInitiative');
const monsterList = document.getElementById('monsterList');

function renderMonsters() {
    monsterList.innerHTML = '';

    monsters.forEach((monster, index) => {
        const li = document.createElement('li');
        li.classList.add('monster-item');

        li.innerHTML = `
            <div class="monster-main">
                <strong class="monster-name">${monster.name}</strong>
                <span class="monster-type">${monster.type}</span>
            </div>

            <div class="monster-stats">
                <span class="monster-stat">CA ${monster.armorClass}</span>

                <label class="monster-hp">
                    PV
                    <input
                        type="number"
                        min="0"
                        value="${monster.currentHitPoints}"
                        data-index="${index}"
                    >
                    / ${monster.baseHitPoints}
                </label>

                <span class="monster-stat">Init. ${monster.initiative ?? '-'}</span>
            </div>
        `;

        const hpInput = li.querySelector('input');

        hpInput.addEventListener('change', (event) => {
            monsters[index].currentHitPoints = Number(event.target.value);
        });

        monsterList.appendChild(li);
    });
}

function rollD20() {
    return Math.floor(Math.random() * 20) + 1;
}

createMonstersButton.addEventListener('click', () => {
    const count = Number(monsterCountInput.value);

    monsters = [];

    for (let i = 0; i < count; i++) {
        const monsterClass = monsterClasses[i % monsterClasses.length];

        monsters.push({
            id: `${monsterClass.slug}-${i + 1}`,
            slug: monsterClass.slug,
            name: `${monsterClass.name} ${i + 1}`,
            className: monsterClass.name,

            challengeRating: monsterClass.challenge_rating,
            type: monsterClass.type,
            size: monsterClass.size,
            armorClass: monsterClass.armor_class,
            baseHitPoints: monsterClass.hit_points,
            currentHitPoints: monsterClass.hit_points,
            alignment: monsterClass.alignment,
            isLegendary: monsterClass.is_legendary,

            roll: null,
            initiative: null,
            originalData: monsterClass,
        });
    }

    rollInitiativeButton.disabled = monsters.length === 0;
    renderMonsters();
});

rollInitiativeButton.addEventListener('click', () => {
    monsters = monsters.map(monster => {
        const roll = rollD20();

        return {
            ...monster,
            roll,
            initiative: roll,
        };
    });

    monsters.sort((a, b) => b.initiative - a.initiative);

    renderMonsters();
});
