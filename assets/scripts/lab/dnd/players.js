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
            const nameInput = getPlayerInput(item, 'name');
            const armorClassInput = getPlayerInput(item, 'armor-class');
            const currentHitPointsInput = getPlayerInput(item, 'current-hit-points');
            const baseHitPointsInput = getPlayerInput(item, 'base-hit-points');
            const initiativeInput = getPlayerInput(item, 'initiative');

            return {
                id: `player-${index + 1}`,
                type: 'player',
                name: nameInput?.value || `Joueur ${index + 1}`,
                armorClass: Number(armorClassInput?.value || 0),
                currentHitPoints: Number(currentHitPointsInput?.value || 0),
                baseHitPoints: Number(baseHitPointsInput?.value || 0),
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

function getPlayerInput(playerItem, fieldName) {
    return playerItem.querySelector(`[data-player-field="${fieldName}"]`);
}
