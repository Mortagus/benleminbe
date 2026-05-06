import {monsterClasses} from "./monster_classes.js";

let monsters = [];

const monsterCountInput = document.getElementById('monsterCount');
const createMonstersButton = document.getElementById('createMonsters');
const rollInitiativeButton = document.getElementById('rollInitiative');
const monsterList = document.getElementById('monsterList');

function renderMonsters() {
    monsterList.innerHTML = '';

    monsters.forEach((monster, index) => {
        const li = document.createElement('li');

        li.innerHTML = `
					<strong>${monster.name}</strong>
					— CA : ${monster.armorClass}
					— PV :
					<input
						type="number"
						min="0"
						value="${monster.currentHitPoints}"
						style="width: 60px;"
						data-index="${index}"
					>
					/ ${monster.baseHitPoints}
					— Initiative : ${monster.initiative ?? '-'}
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
            name: `${monsterClass.name} ${i + 1}`,
            className: monsterClass.name,
            armorClass: monsterClass.armor_class?.value ?? '?',

            baseHitPoints: monsterClass.hit_points?.average ?? 0,
            currentHitPoints: monsterClass.hit_points?.average ?? 0,

            dexterityModifier: monsterClass.abilities?.dexterity?.modifier ?? 0,

            roll: null,
            initiative: null,
            originalData: monsterClass
        });
    }

    rollInitiativeButton.disabled = monsters.length === 0;
    renderMonsters();
});

rollInitiativeButton.addEventListener('click', () => {
    monsters = monsters.map(monster => ({
        ...monster,
        initiative: rollD20()
    }));

    monsters.sort((a, b) => b.initiative - a.initiative);

    renderMonsters();
});
