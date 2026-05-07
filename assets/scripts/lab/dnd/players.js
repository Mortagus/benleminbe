export function createPlayerItem() {
    const template = document.getElementById('playerItemTemplate');

    if (!template) {
        throw new Error('Template #playerItemTemplate introuvable.');
    }

    const fragment = template.content.cloneNode(true);
    const playerItem = fragment.querySelector('.player-item');

    bindPlayerItemEvents(playerItem);

    return playerItem;
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
