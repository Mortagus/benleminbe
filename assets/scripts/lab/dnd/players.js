export function createPlayerItem() {
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

export function bindExistingPlayerRemoveButtons(playerList) {
    const playerItems = playerList.querySelectorAll('.player-item');

    playerItems.forEach(bindPlayerItemEvents);
}

export function getPlayerActors(playerList) {
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

function bindPlayerItemEvents(playerItem) {
    const removeButton = playerItem.querySelector('.player-remove-button');

    removeButton.addEventListener('click', () => {
        playerItem.remove();
    });
}
