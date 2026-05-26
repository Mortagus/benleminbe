# Cartographie JavaScript du DnD Initiative Tracker

Ce document décrit le code JavaScript actuel de l'outil `DnD Initiative Tracker`, sans proposer de refonte et sans modifier le comportement.

## Fichiers JavaScript concernés

| Fichier                                            | Rôle                                                                                                                                                                                                                                                                                                                      |
| -------------------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `assets/scripts/lab/dnd/dnd_initiative.js`         | Point d'entrée de l'outil. Crée l'état de rencontre, initialise les panneaux, orchestre la génération de l'ordre du tour et déclenche la persistance locale.                                                                                                                                                              |
| `assets/scripts/lab/dnd/encounter-state.js`        | Module d'état et de règles métier. Crée et modifie la rencontre, les monstres, les joueurs, les règles et l'ordre du tour.                                                                                                                                                                                                |
| `assets/scripts/lab/dnd/monsters.js`               | Panneau DOM des monstres. Crée les emplacements, rend la liste depuis `encounter.monsters`, utilise le catalogue de `EncounterState` et gère sélection/PV/initiative.                                                                                                                                                     |
| `assets/scripts/lab/dnd/players.js`                | Panneau DOM des joueurs. Ajoute/supprime des joueurs, gère la modale d'import XML, conserve la réponse importée sur la ligne, ouvre la fiche détaillée à la demande, synchronise `encounter.players`, préserve le modificateur d'initiative importé, persiste le champ `side` et réhydrate les lignes depuis un snapshot. |
| `assets/scripts/lab/dnd/turn-order.js`             | Panneau DOM de l'ordre du tour. Rend la liste des tours, gère le bouton de génération, les tours joués, les déplacements, le drag and drop, l'aide clavier, les accents de camp et le badge boss.                                                                                                                         |
| `assets/scripts/lab/dnd/rules.js`                  | Panneau DOM des règles. Synchronise les checkboxes de règles avec l'état, ouvre/ferme la modale.                                                                                                                                                                                                                          |
| `assets/scripts/lab/dnd/validation.js`             | Validation des entrées et affichage des erreurs. Les panneaux l'appellent avec des noeuds DOM, puis les règles travaillent sur des données normalisées.                                                                                                                                                                   |
| `assets/scripts/lab/dnd/dtos.js`                   | Contrats JSDoc des DTOs persistables prioritaires : snapshot, monstres, joueurs riches, payload d’import XML, tours et règles.                                                                                                                                                                                            |
| `assets/scripts/lab/dnd/persistence.js`            | Persistance locale du tracker. Lit et écrit `localStorage`, pilote le statut, le prompt de restauration et les modales d'erreur.                                                                                                                                                                                          |
| `assets/scripts/lab/dnd/sound-effects.js`          | Effets sonores réutilisables. Gère le registre de sons, le choix de source, le cache lazy `Audio` et les erreurs de lecture non bloquantes.                                                                                                                                                                               |
| `assets/scripts/lab/dnd/initiative.js`             | Petites fonctions d'initiative: lancer un d20, formater l'initiative, choisir une classe CSS pour critique/échec.                                                                                                                                                                                                         |
| `assets/scripts/lab/dnd/bestiary.js`               | Données générées du bestiaire. Fichier volumineux, explicitement marqué comme généré et à ne pas éditer manuellement.                                                                                                                                                                                                     |
| `src/Public/Controller/LabController.php`          | Contrôleur public du lab. Rend la page d'index et porte aussi les routes du tracker DnD et de son import XML.                                                                                                                                                                                                             |
| `src/Public/Service/Dnd/PlayerXmlImportParser.php` | Service PHP qui lit le XML importé et le convertit en structure exploitable.                                                                                                                                                                                                                                              |

Fichier de test lié:

| Fichier                                    | Rôle                                                                                                                                            |
| ------------------------------------------ | ----------------------------------------------------------------------------------------------------------------------------------------------- |
| `tests/js/lab/dnd/encounter-state.test.js` | Tests Vitest centrés sur `encounter-state.js`: création de monstres, sélection, initiative, règles, ordre du tour, acteur actif et déplacement. |
| `tests/js/lab/dnd/persistence.test.js`     | Tests Vitest centrés sur `persistence.js`: sauvegarde locale, restauration, validation des snapshots et erreurs de stockage.                    |

## Point d'entrée principal

Le point d'entrée applicatif du tracker est `assets/scripts/lab/dnd/dnd_initiative.js`.

Il est déclaré dans `importmap.php` sous le nom `dnd_initiative`, puis chargé par le template `templates/lab/dnd/initiative_tracker.html.twig` avec:

```twig
{{ importmap(['app', 'dnd_initiative']) }}
```

`app` reste le point d'entrée global du site. Le point d'entrée spécifique à l'outil est `dnd_initiative`.

Le fichier instancie `DndInitiativeTrackerApp`, qui sert de coordinateur applicatif. Cette classe possède une instance de `EncounterState`, initialise les panneaux DOM et expose les méthodes appelées par les callbacks des panneaux.

## Chemin d'exécution au chargement de la page

Au chargement de la page:

1. `dnd_initiative.js` importe la CSS principale de l'outil.
2. Il importe les modules d'état, de panneaux et de validation.
3. Il instancie `DndInitiativeTrackerApp`.
4. `DndInitiativeTrackerApp` crée une rencontre avec `new EncounterState()`.
5. `DndInitiativeTrackerApp.start()` instancie `TurnOrderPanel` avec `encounter` et `{ onGenerateTurnOrder }`, puis appelle `start()`.
6. `DndInitiativeTrackerApp.start()` instancie `MonstersPanel` avec `encounter` et `{ onEncounterChange }`, puis appelle `start()`.
7. `DndInitiativeTrackerApp.start()` instancie `PlayersPanel` avec `encounter` et `{ onPlayersChange }`, puis appelle `start()`.
8. `DndInitiativeTrackerApp.start()` instancie `RulesPanel` avec `{ isRuleActive, setRuleActive }`, puis appelle `start()`.
9. `DndInitiativeTrackerApp.start()` instancie `EncounterPersistence` avec l'état et les panneaux, puis appelle `start()`.

Pendant cette initialisation:

1. `TurnOrderPanel.start()` branche le bouton `#generateTurnOrder`, l'aide clavier et prépare `refresh()`.
2. `MonstersPanel.start()` branche `#createMonsters` et `#rollInitiative`.
3. `PlayersPanel.start()` branche `#addPlayer`, branche les joueurs déjà présents dans le DOM, puis appelle `sync()`.
4. `RulesPanel.start()` lit les checkboxes `[data-rule-toggle]`, les aligne sur les règles par défaut, puis branche les changements et l'ouverture/fermeture de la modale.
5. `EncounterPersistence.start()` lit `localStorage`, met à jour le statut de sauvegarde et, si un snapshot valide existe, ouvre le prompt de restauration.

Flux import joueur:

1. `PlayersPanel` ouvre la modale d'import XML via `#importPlayerXml`.
2. Le fichier choisi est envoyé par `fetch` à la route `app_lab_dnd_player_import`.
3. `LabController` appelle `PlayerXmlImportParser`, qui retourne un JSON avec `player`, `warnings` et `raw`.
4. `PlayersPanel` crée une nouvelle ligne joueur, y place les champs de base et le camp du participant, puis conserve la réponse complète sur la ligne.
5. Le bouton compact `[data-player-details-open]` ouvre la modale de consultation de la fiche complète à la demande.

Flux de restauration locale:

1. `EncounterPersistence.start()` lit `localStorage` au chargement.
2. Si un snapshot valide existe, le panneau affiche un prompt discret pour laisser le MJ choisir entre chargement et démarrage à vide.
3. Si le MJ charge la sauvegarde, `restoreFromSnapshot()` injecte le snapshot dans `EncounterState`.
4. `PlayersPanel.hydrateFromEncounter()` reconstruit les lignes joueurs depuis l'état restauré.
5. `RulesPanel.sync()` réaligne les checkboxes sur les règles restaurées.
6. `MonstersPanel.refresh()` et `TurnOrderPanel.refresh()` réaffichent le contenu courant.
7. Le statut de dernière sauvegarde est mis à jour après la restauration.

État initial créé par `EncounterState`:

```js
{
    monsters: [],
    players: [],
    rules: {
        'skip-low-initiative': true,
        'extra-turn-on-twenty': true,
        'break-initiative-ties-with-dexterity': false,
    },
    turnOrder: [],
    currentRound: 1,
    activeTurnId: null,
}
```

Le bestiaire est ajouté comme propriété non énumérable `encounter.bestiary`.

## Chemins d'exécution par action

### Ajouter des joueurs

Action utilisateur: clic sur `#addPlayer`.

Flux:

1. `PlayersPanel` reçoit le clic branché dans `start()`.
2. `createPlayerItem(sync)` clone le template `#playerItemTemplate`.
3. `bindPlayerItemEvents()` branche le bouton de suppression et les événements `input`.
4. Le nouvel item est ajouté à `#playerList`.
5. `sync()` est appelé.
6. `sync()` appelle `refreshPlayerAccessibility(playerList)`.
7. `sync()` lit les joueurs avec `getPlayerActors(playerList)`.
8. `sync()` envoie ces joueurs dans l'état via `encounter.setPlayers(players)`.
9. `sync()` appelle `callbacks.onPlayersChange?.()`, fourni par `DndInitiativeTrackerApp`.
10. Ce callback appelle `DndInitiativeTrackerApp.refreshDisplayedTurnOrder()`, puis `turnOrderPanel.refresh()`.
11. `refreshDisplayedTurnOrder()` persiste ensuite l'état courant.

Point important: `turnOrderPanel.refresh()` rerend `encounter.turnOrder`, mais ne reconstruit pas l'ordre du tour. La reconstruction passe uniquement par `encounter.buildRoundOrder()` dans `generateTurnOrder()`.

### Ajouter des monstres

Action utilisateur: saisie du nombre de monstres puis clic sur `#createMonsters`.

Flux:

1. `MonstersPanel` reçoit le clic branché dans `start()`.
2. Il efface les erreurs du panneau avec `clearValidationState(monsterPanel)`.
3. Il lit `#monsterCount`.
4. Il valide avec `validateMonsterCountInput(monsterCountInput)`.
5. Il affiche les erreurs avec `showMonsterValidationErrors()`.
6. Si erreur, `focusFirstInvalidField()` reçoit le focus et le flux s'arrête.
7. Sinon, `encounter.createMonsterSlots(count)` remplace `encounter.monsters` par des monstres vides.
8. Le bouton `#rollInitiative` est désactivé.
9. `refresh()` rerend la liste des monstres.
10. `callbacks.onEncounterChange?.()` appelle `turnOrderPanel.refresh()`.
11. Le rafraîchissement courant déclenche ensuite la persistance locale.

Sélection d'un monstre dans une ligne:

1. `renderMonsters()` crée les lignes depuis `#monsterItemTemplate`.
2. `renderMonsterOptions()` remplit le `<select>` depuis le catalogue fourni par `encounter.bestiary`, filtré par `#monsterSearch` et `#monsterTypeFilter`. La recherche par nom existe côté DOM/JS mais reste masquée dans l'UI pour le moment.
3. `bindMonsterItemEvents()` branche `change` sur `.monster-select`.
4. Au changement, `handleMonsterSelectionChange()` appelle `encounter.selectMonster(index, selectedSlug)` et remplace le monstre vide par un monstre issu du catalogue de rencontre.
5. Le bouton de lancement d'initiative est activé si `encounter.hasSelectedMonsters()` vaut vrai.
6. `refresh()` rerend la liste.
7. `callbacks.onEncounterChange?.()` rafraîchit l'affichage de l'ordre du tour existant.

Les filtres de catalogue sont globaux au panneau monstres. Ils réduisent les options des sélecteurs sans retirer le monstre déjà sélectionné dans une ligne, même si ce monstre ne correspond plus aux filtres courants.

Modification des PV d'un monstre:

1. `input` sur `.monster-hp input`.
2. `encounter.updateMonsterHitPoints(index, hitPoints)`.
3. `callbacks.onEncounterChange?.()`.

### Lancer les initiatives des monstres

Action utilisateur: clic sur `#rollInitiative`.

Flux:

1. `MonstersPanel` reçoit le clic.
2. `handleRollInitiative()` déclenche le callback `onMonsterInitiativeRoll` sans bloquer le jet.
3. `DndInitiativeTrackerApp` appelle `playSoundEffect('monsterInitiativeRoll')`.
4. `sound-effects.js` choisit aléatoirement un des sons de dés, crée ou réutilise l'objet `Audio`, puis ignore silencieusement les erreurs de lecture.
5. Le bouton de jet reçoit temporairement une classe de chargement audio qui affiche un curseur `progress`.
6. `handleRollInitiative()` lance les initiatives une par une via `encounter.rollMonsterInitiative()`, avec une courte pause entre chaque monstre sélectionné.
7. Chaque monstre sélectionné reçoit:
    - `roll`: résultat de `rollD20()`;
    - `initiative`: `roll + initiativeModifier`.
8. Le panneau est rerendu après chaque jet pour rendre l'apparition des scores progressive.
9. Les monstres sont triés par `compareByInitiative(encounter, a, b)` une fois tous les jets terminés.
10. `refresh()` rerend le panneau des monstres avec les initiatives formatées.
11. `callbacks.onEncounterChange?.()` appelle `turnOrderPanel.refresh()`.

Le format d'affichage vient de `initiative.js`:

1. `formatInitiative(actor)` affiche `-`, un nombre, ou un nombre avec symbole pour 20/1 naturel.
2. `getInitiativeClass(actor)` ajoute une classe CSS pour succès critique ou échec critique.

### Générer l'ordre du tour

Action utilisateur: clic sur `#generateTurnOrder`.

Flux:

1. `TurnOrderPanel` reçoit le clic.
2. Il appelle `callbacks.onGenerateTurnOrder?.()`, fourni par `DndInitiativeTrackerApp`.
3. `DndInitiativeTrackerApp.generateTurnOrder()` efface les validations des trois panneaux.
4. Il valide la présence d'au moins un acteur avec `validateEncounterActors(monsterList, playerList)`.
5. Il valide les monstres avec `monstersPanel.validateForTurnOrder()`.
6. Il valide les joueurs avec `playersPanel.validateForTurnOrder()`.
7. Il affiche les erreurs globales sur le panneau d'ordre du tour.
8. Si une validation échoue, il focalise le premier champ invalide et s'arrête.
9. Sinon, il appelle `playersPanel.sync()` pour pousser les champs joueur dans `encounter.players`.
10. Il appelle `encounter.buildRoundOrder()`.
11. Il appelle `turnOrderPanel.refresh({ focusFirst: true })`.
12. Il déclenche ensuite `persistence.saveEncounter()` pour écrire le snapshot courant.

Dans `encounter.buildRoundOrder()`:

1. Les acteurs sont construits depuis:
    - `getMonsterActors(encounter)` pour les monstres sélectionnés et avec initiative;
    - `encounter.players` pour les joueurs.
2. Les acteurs sont filtrés par `shouldSkipTurn()` selon la règle `skip-low-initiative`.
3. Les acteurs sont dupliqués par `getTurnCount()` si la règle `extra-turn-on-twenty` s'applique.
4. Chaque entrée reçoit `done: false`, `actorId`, et parfois un `id` suffixé `-turn-1`, `-turn-2`.
5. L'ordre est trié avec `compareByInitiative()`.
6. `currentRound` repasse à `1`.
7. `refreshActiveTurn()` met `activeTurnId` sur le premier tour non joué.

Dans `turnOrderPanel.refresh()`:

1. Si `focusFirst` est vrai, le premier tour devient la cible de focus.
2. `renderRoundOrder()` vide puis rerend `#turnOrderList`.
3. Si l'ordre est vide, le placeholder reste visible.
4. Sinon, chaque acteur devient un `<li>` interactif.
5. Le premier acteur non joué reçoit la classe active et le badge visible.
6. Les boutons précédent/suivant, le clic, le clavier et le drag and drop sont branchés à chaque rendu.

### Changer les règles

Action utilisateur: changement d'une checkbox `[data-rule-toggle]`.

Flux:

1. `RulesPanel` reçoit l'événement `change`.
2. Il appelle `callbacks.setRuleActive(ruleId, ruleToggle.checked)`.
3. Dans `DndInitiativeTrackerApp`, ce callback appelle `encounter.setRuleActive(ruleId, active)`.
4. Puis il appelle `turnOrderPanel.refresh()`.

Point important: un changement de règle met à jour `encounter.rules`, mais ne relance pas `encounter.buildRoundOrder()`. Les règles influencent donc clairement la prochaine génération. Sur un ordre déjà généré, le rafraîchissement seul ne recalculera pas les acteurs ignorés, les tours bonus ou le tri.

### Marquer un acteur comme ayant joué

Action utilisateur:

1. clic sur une entrée de l'ordre du tour;
2. ou touche `Enter`;
3. ou touche `Espace`.

Flux:

1. `renderRoundOrder()` branche ces événements sur chaque `<li>`.
2. L'événement appelle `callbacks.onToggleTurnDone(actor.id)`.
3. Dans `TurnOrderPanel`, ce callback appelle `encounter.toggleTurnDone(turnId)`.
4. `encounter.toggleTurnDone()` inverse `turn.done`.
5. `refreshActiveTurn(encounter)` recalcule `activeTurnId`.
6. `pendingFocusTurnId` reçoit le tour modifié.
7. `refresh()` rerend la liste.
8. `focusTurnItem()` remet le focus sur le tour modifié.

### Réordonner l'ordre du tour

Trois moyens existent.

Boutons précédent/suivant:

1. `bindMoveButton()` configure les boutons `[data-turn-move="previous"]` et `[data-turn-move="next"]`.
2. Au clic, il appelle `callbacks.onMoveTurn(actor.id, target.id, placement)`.
3. `encounter.moveTurn(draggedTurnId, targetTurnId, placement)` déplace l'entrée dans `encounter.turnOrder`.
4. `refreshActiveTurn(encounter)` recalcule l'acteur actif.
5. `refresh()` rerend la liste.
6. `onAnnounce()` envoie un message dans la live region.

Clavier:

1. `keydown` sur un `<li>`.
2. `ArrowLeft` appelle `moveActorWithKeyboard(..., 'previous')`.
3. `ArrowRight` appelle `moveActorWithKeyboard(..., 'next')`.
4. Le flux rejoint `callbacks.onMoveTurn()`, puis `encounter.moveTurn()`, puis `refresh()`.

Drag and drop:

1. `dragstart` stocke l'id dans la variable de module `draggedTurnId`.
2. `dragover` autorise le drop.
3. `drop` compare l'acteur déplacé et la cible.
4. `getDropPlacement()` choisit `before` ou `after`.
5. `callbacks.onMoveTurn()` appelle `encounter.moveTurn()`.
6. `refresh()` rerend la liste.

## Dépendances entre fichiers

Vue synthétique:

```text
dnd_initiative.js
├── encounter-state.js
│   ├── initiative.js
│   └── bestiary.js
├── sound-effects.js
├── monsters.js
│   ├── bestiary.js
│   ├── initiative.js
│   ├── encounter-state.js
│   └── validation.js
├── players.js
│   ├── encounter-state.js
│   └── validation.js
├── turn-order.js
│   ├── encounter-state.js
│   └── validation.js
├── rules.js
└── validation.js
```

Dépendances principales:

1. `DndInitiativeTrackerApp` connaît tous les panneaux et connecte leurs callbacks.
2. `encounter-state.js` ne dépend pas du DOM. Il dépend de `initiative.js` et `bestiary.js`.
3. `monsters.js`, `players.js`, `turn-order.js`, `rules.js` dépendent du DOM.
4. `validation.js` expose des validateurs appelés depuis le DOM, des règles pures testables, et les helpers de feedback DOM pour les erreurs et le focus.
5. `bestiary.js` est consommé par `encounter-state.js`; `monsters.js` garde un fallback importé, mais `MonstersPanel` rend les options depuis `encounter.bestiary`.

## Données principales manipulées

### Joueurs

Source principale: DOM du panneau joueurs.

Transformation:

1. `getPlayerActors(playerList)` lit les `.player-item`.
2. `getStartedPlayerForms()` ignore les lignes totalement vides.
3. `readPlayerForm()` extrait les valeurs via les attributs `data-player-field`.
4. `createPlayerActor()` convertit ces valeurs en acteur stocké dans `encounter.players`, puis réutilise le modificateur d'initiative de l'import XML quand il existe.
5. Les acteurs sans nom après fallback sont ignorés.

```js
{
    id: 'player-1',
    type: 'player',
    name: '...',
    armorClass: 14,
    currentHitPoints: 20,
    baseHitPoints: 20,
    initiative: 12,
    roll: 12,
    initiativeModifier: 0,
    done: false,
    importData: null,
}
```

Particularité: pour un joueur, `initiative` et `roll` valent actuellement la même valeur saisie. Lorsqu'un joueur vient d'un import XML, `importData` conserve les warnings et le payload brut utile à la modale de fiche et à la future persistance, et le modificateur d'initiative est repris pour le départage des égalités.

### Monstres

Source principale: `encounter.monsters`.

États possibles:

Monstre vide:

```js
{
    id: 'monster-1',
    slug: null,
    name: 'Monstre 1',
    armorClass: '-',
    currentHitPoints: 0,
    baseHitPoints: 0,
    roll: null,
    initiative: null,
}
```

Monstre sélectionné:

```js
{
    id: 'acolyte-1',
    slug: 'acolyte',
    name: 'Acolyte 1',
    className: 'Acolyte',
    challengeRating: '1/4',
    type: 'Humanoïde (...)',
    armorClass: 10,
    baseHitPoints: 9,
    currentHitPoints: 9,
    abilities: {...},
    initiativeModifier: 0,
    roll: null,
    initiative: null,
}
```

Après lancement d'initiative, `roll` et `initiative` sont remplis.

### Rencontre

Objet central porté par une instance de `EncounterState`:

```js
{
    monsters: [],
    players: [],
    rules: {},
    turnOrder: [],
    currentRound: 1,
    activeTurnId: null,
}
```

`encounter` est créé une fois par `DndInitiativeTrackerApp`, puis passé aux panneaux.

Les contrats de persistance prioritaires sont décrits dans `assets/scripts/lab/dnd/dtos.js` sous forme de typedefs JSDoc. Le fichier contient aussi des helpers purs pour convertir l'état actuel vers ces DTOs et restaurer un snapshot vers l'état runtime, sans encore brancher de persistance `localStorage`.

### Règles

Définies dans `RULES`:

1. `skip-low-initiative`: ignore les acteurs avec initiative `<= 1`, active par défaut.
2. `extra-turn-on-twenty`: ajoute un tour bonus sur `roll === 20`, active par défaut.
3. `break-initiative-ties-with-dexterity`: départage les égalités par modificateur d'initiative, inactive par défaut.

Les règles sont stockées dans `encounter.rules` avec l'id public comme clé.

### Ordre du tour

Source: `encounter.turnOrder`.

Créé par `encounter.buildRoundOrder()` à partir des monstres valides et des joueurs synchronisés.

Chaque entrée ressemble à:

```js
{
    id: 'player-1',
    actorId: 'player-1',
    type: 'player',
    name: 'Lia',
    armorClass: 14,
    currentHitPoints: 20,
    baseHitPoints: 20,
    initiative: 18,
    roll: 18,
    done: false,
}
```

En cas de tour bonus, `id` devient par exemple `player-critical-turn-1`, tandis que `actorId` garde l'id d'origine.

## Fonctions importantes et responsabilités

### `dnd_initiative.js`

| Méthode                       | Responsabilité                                                                                                                                        |
| ----------------------------- | ----------------------------------------------------------------------------------------------------------------------------------------------------- |
| `start()`                     | Initialiser les panneaux dans l'ordre, connecter leurs callbacks et brancher la persistance locale.                                                   |
| `refreshDisplayedTurnOrder()` | Rafraîchir l'ordre du tour affiché sans reconstruire `encounter.turnOrder`, puis persister l'état courant.                                            |
| `setRuleActive()`             | Modifier une règle dans l'état, rafraîchir l'affichage courant et persister l'état courant.                                                           |
| `generateTurnOrder()`         | Orchestrer la génération: nettoyer les validations, valider, synchroniser les joueurs, construire l'ordre, rafraîchir le panneau et persister l'état. |

### `encounter-state.js`

| Élément                              | Responsabilité                                                               |
| ------------------------------------ | ---------------------------------------------------------------------------- |
| `EncounterState`                     | Porter l'état mutable de rencontre et les méthodes métier associées.         |
| `createEncounterState()`             | Créer une instance de `EncounterState`; wrapper conservé pour compatibilité. |
| `createMonsterSlots()`               | Wrapper vers `encounter.createMonsterSlots()`.                               |
| `selectMonster()`                    | Wrapper vers `encounter.selectMonster()`.                                    |
| `updateMonsterHitPoints()`           | Wrapper vers `encounter.updateMonsterHitPoints()`.                           |
| `hasSelectedMonsters()`              | Wrapper vers `encounter.hasSelectedMonsters()`.                              |
| `rollMonsterInitiatives()`           | Wrapper vers `encounter.rollMonsterInitiatives()`.                           |
| `setPlayers()`                       | Wrapper vers `encounter.setPlayers()`.                                       |
| `buildRoundOrder()`                  | Wrapper vers `encounter.buildRoundOrder()`.                                  |
| `toggleTurnDone()`                   | Wrapper vers `encounter.toggleTurnDone()`.                                   |
| `moveTurn()`                         | Wrapper vers `encounter.moveTurn()`.                                         |
| `isRuleActive()` / `setRuleActive()` | Wrappers vers les méthodes de règles de `EncounterState`.                    |
| `compareByInitiative()`              | Méthode de tri par initiative, puis éventuellement par modificateur.         |
| `refreshActiveTurn()`                | Méthode qui définit le premier tour non joué comme tour actif.               |

### `monsters.js`

| Élément                            | Responsabilité                                                                                    |
| ---------------------------------- | ------------------------------------------------------------------------------------------------- |
| `MonstersPanel`                    | Contrôleur DOM du panneau monstres.                                                               |
| `start()`                          | Brancher les actions de création de slots et de jet d'initiative.                                 |
| `refresh()`                        | Rendre la liste de monstres depuis `encounter.monsters`.                                          |
| `validateForTurnOrder()`           | Valider le nombre de monstres et les PV des monstres rendus.                                      |
| `handleCreateMonsterSlots()`       | Valider le nombre demandé, créer les slots et rafraîchir le panneau.                              |
| `handleRollInitiative()`           | Déclencher le feedback sonore, lancer les initiatives progressivement puis rafraîchir le panneau. |
| `rollSelectedMonsterInitiatives()` | Lancer les initiatives des monstres sélectionnés avec une temporisation entre chaque jet.         |
| `playMonsterInitiativeSound()`     | Appeler le callback audio sans bloquer le jet d'initiative.                                       |
| `setRollInitiativeAudioLoading()`  | Appliquer l'état DOM temporaire du curseur de chargement audio.                                   |
| `handleMonsterSelectionChange()`   | Appliquer une sélection de monstre et rafraîchir l'état du panneau.                               |
| `handleMonsterHitPointsChange()`   | Synchroniser les PV modifiés vers `EncounterState`.                                               |
| `getMonsterFilters()`              | Lire les filtres globaux du catalogue depuis le DOM.                                              |
| `renderMonsters()`                 | Construire les `<li>` de monstres et brancher leurs événements.                                   |
| `renderMonsterOptions()`           | Remplir le `<select>` avec le catalogue injecté, groupé par type.                                 |
| `filterMonsterCatalog()`           | Appliquer recherche et type en conservant l'option déjà sélectionnée.                             |
| `renderMonsterTypeFilter()`        | Remplir le filtre de type global depuis le catalogue.                                             |
| `getSortedMonsterGroups()`         | Trier les monstres par nom puis les groupes par type pour les options.                            |
| `bindMonsterItemEvents()`          | Relier une ligne de monstre aux callbacks de sélection et de PV.                                  |

### `players.js`

| Élément                        | Responsabilité                                                                                                                                                               |
| ------------------------------ | ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `PlayersPanel`                 | Contrôleur DOM du panneau joueurs.                                                                                                                                           |
| `start()`                      | Brancher les événements, synchroniser l'état initial et exposer l'API du panneau via l'instance.                                                                             |
| `sync()`                       | Lire le DOM joueur, mettre à jour `EncounterState`, appeler le callback de changement.                                                                                       |
| `hydrateFromEncounter()`       | Reconstruire les lignes joueurs depuis l'état restauré par `EncounterPersistence`.                                                                                           |
| `syncPlayerFormsToEncounter()` | Frontière explicite entre les champs joueur DOM et `encounter.players`.                                                                                                      |
| `handleAddPlayer()`            | Ajouter une ligne joueur puis synchroniser le panneau.                                                                                                                       |
| `validateForTurnOrder()`       | Valider les lignes joueur avant génération de l'ordre du tour.                                                                                                               |
| `createPlayerItem()`           | Cloner le template joueur et brancher ses événements.                                                                                                                        |
| `bindExistingPlayerItems()`    | Brancher les joueurs présents au chargement.                                                                                                                                 |
| `getPlayerActors()`            | Transformer les lignes DOM en acteurs joueur.                                                                                                                                |
| `readPlayerForm()`             | Lire les valeurs d'une ligne joueur depuis les champs `data-player-field`.                                                                                                   |
| `createPlayerActor()`          | Convertir une ligne formulaire normalisée en acteur consommé par `EncounterState`, en préservant le modificateur d'initiative importé et le champ `side` quand ils existent. |
| `refreshPlayerAccessibility()` | Recalculer ids, labels et aria-labels selon l'ordre des joueurs.                                                                                                             |

### `turn-order.js`

| Élément                           | Responsabilité                                                                      |
| --------------------------------- | ----------------------------------------------------------------------------------- |
| `TurnOrderPanel`                  | Contrôleur DOM du panneau d'ordre du tour.                                          |
| `start()`                         | Brancher le bouton de génération et l'aide clavier.                                 |
| `refresh()`                       | Rerendre l'ordre du tour et gérer le focus post-rendu.                              |
| `showEncounterValidationErrors()` | Afficher les erreurs globales de génération.                                        |
| `renderRoundOrder()`              | Vider puis rerendre la liste, en déléguant chaque entrée à `renderTurnOrderItem()`. |
| `renderTurnOrderItem()`           | Créer une entrée, la remplir et brancher ses contrôles.                             |
| `bindMoveButton()`                | Brancher un bouton de déplacement et son annonce accessible.                        |
| `moveActorWithKeyboard()`         | Déplacer un acteur via flèches clavier.                                             |
| `getDropPlacement()`              | Déterminer avant/après pour le drag and drop.                                       |
| `announceTurnOrderChange()`       | Écrire dans la live region.                                                         |
| `getActorSideLabel()`             | Traduire le camp `party`, `ally`, `hostile` ou `neutral` en badge lisible.          |
| `getActorDescription()`           | Décrire une carte en incluant le camp et le statut boss pour l'accessibilité.       |

### `rules.js`

| Élément                                  | Responsabilité                                           |
| ---------------------------------------- | -------------------------------------------------------- |
| `RulesPanel`                             | Contrôleur DOM des checkboxes de règles et de la modale. |
| `start()`                                | Brancher les checkboxes et la modale de règles.          |
| `openRulesModal()` / `closeRulesModal()` | Gérer l'état DOM et le focus de la modale.               |

### `validation.js`

| Fonction                          | Responsabilité                                                |
| --------------------------------- | ------------------------------------------------------------- |
| `validateMonsterCountInput()`     | Valider le nombre de monstres.                                |
| `validateMonsterHitPointsInput()` | Valider les PV actuels d'un monstre.                          |
| `validatePlayerItem()`            | Valider les champs d'un joueur commencé.                      |
| `validateEncounterActors()`       | Empêcher la génération sans acteur.                           |
| `validateIntegerValue()`          | Valider une valeur entière normalisée sans lecture DOM.       |
| `validateCurrentHitPointsLimit()` | Valider que les PV actuels ne dépassent pas les PV max.       |
| `mergeValidationResults()`        | Fusionner plusieurs résultats.                                |
| `hasValidationErrors()`           | Détecter au moins un résultat invalide.                       |
| `focusFirstInvalidField()`        | Donner le focus au premier champ invalide.                    |
| `clearValidationState()`          | Nettoyer les classes et résumés d'erreur dans un scope DOM.   |
| `showValidationErrors()`          | Afficher un résumé d'erreurs et marquer les champs invalides. |

### `dtos.js`

| Élément                           | Responsabilité                                                               |
| --------------------------------- | ---------------------------------------------------------------------------- |
| `ENCOUNTER_SNAPSHOT_VERSION`      | Version initiale du futur format persistable de rencontre.                   |
| `EncounterSnapshotDto`            | Racine persistable pour `localStorage` et futur import/export JSON.          |
| `EncounterMonsterDto`             | Instance de monstre dans une rencontre, liée au catalogue par `slug`.        |
| `EncounterPlayerDto`              | Participant joueur stocké dans la rencontre.                                 |
| `PlayerImportDataDto`             | Payload d'import XML conservé pour consultation, traçabilité et persistance. |
| `TurnEntryDto`                    | Entrée de tour contenant l'identité de l'acteur et l'état joué/non joué.     |
| `RulesStateDto`                   | État des règles optionnelles actives.                                        |
| `createEncounterSnapshotDto()`    | Convertir une instance `EncounterState` en snapshot versionné.               |
| `createEncounterMonsterDto()`     | Convertir un monstre de rencontre actuel en DTO persistable.                 |
| `createEncounterPlayerDto()`      | Convertir un joueur de rencontre actuel en DTO persistable.                  |
| `createPlayerImportDataDto()`     | Normaliser les warnings et le payload brut de l'import XML.                  |
| `createTurnEntryDto()`            | Convertir une entrée actuelle de `turnOrder` en entrée de tour minimale.     |
| `createRulesStateDto()`           | Normaliser l'état des règles connues.                                        |
| `restoreEncounterFromSnapshot()`  | Restaurer une instance `EncounterState` depuis un snapshot persistable.      |
| `createRuntimeMonsterFromDto()`   | Convertir un monstre persistable vers la forme runtime actuelle.             |
| `createRuntimePlayerFromDto()`    | Convertir un joueur persistable vers la forme runtime actuelle.              |
| `createRuntimeTurnOrderFromDto()` | Hydrater les entrées de tour minimales depuis les participants restaurés.    |

Règles de restauration validées:

1. Les entrées de tour orphelines sont ignorées.
2. Un snapshot avec `turnOrder` vide reste vide; `buildRoundOrder()` n'est pas appelé automatiquement.
3. Les monstres sont restaurés depuis les données sauvegardées, sans relecture du bestiaire.

### `persistence.js`

| Élément                       | Responsabilité                                                                                 |
| ----------------------------- | ---------------------------------------------------------------------------------------------- |
| `EncounterPersistence`        | Orchestrer la lecture et l'écriture du snapshot, le prompt de reprise et les modales d'erreur. |
| `start()`                     | Lire `localStorage`, afficher le statut et ouvrir le prompt si une sauvegarde valide existe.   |
| `saveEncounter()`             | Forcer la resynchronisation utile puis écrire le snapshot courant.                             |
| `restoreFromStoredSnapshot()` | Recharger le snapshot présent dans `localStorage` après validation.                            |
| `restoreFromSnapshot()`       | Réinjecter un snapshot validé dans `EncounterState` puis rafraîchir les panneaux.              |
| `readStoredSnapshot()`        | Lire, parser et valider le contenu persistant.                                                 |
| `validateSnapshot()`          | Vérifier le type de snapshot et la compatibilité de version.                                   |

Règles de persistance validées:

1. Une seule sauvegarde locale est utilisée.
2. Le chargement n'est pas automatique: une confirmation est demandée si une sauvegarde valide existe.
3. Les snapshots invalides, corrompus ou d'une version non prise en charge sont ignorés et signalés.
4. Les erreurs `localStorage` sont affichées dans une modale copiable.

### `sound-effects.js`

| Élément                   | Responsabilité                                                                 |
| ------------------------- | ------------------------------------------------------------------------------ |
| `SOUND_EFFECTS`           | Registre des effets sonores disponibles, dont `monsterInitiativeRoll`.         |
| `playSoundEffect()`       | Jouer un effet sonore connu, avec source aléatoire, volume et erreur absorbée. |
| `clearSoundEffectCache()` | Vider le cache audio pour les tests.                                           |

## Endroits faciles à suivre

1. `dnd_initiative.js` donne une bonne vue d'ensemble: un état unique, quatre panneaux initialisés, une classe coordinatrice et une méthode de génération.
2. `encounter-state.js` isole bien une partie importante de la logique métier hors DOM.
3. `EncounterState` et ses fonctions de compatibilité métier sont testables et déjà couverts par Vitest.
4. Les panneaux retournent une petite API explicite: `refresh`, `clearValidation`, `validateForTurnOrder`, `sync`, et pour les joueurs `hydrateFromEncounter()`.
5. Le flux de génération dans `generateTurnOrder()` est linéaire et compréhensible.
6. Les templates DOM ont des ids/classes assez stables et les modules les utilisent directement.

## Endroits demandant trop d'effort de lecture

1. `turn-order.js` contient beaucoup d'interactions, mais le rendu d'une entrée est maintenant séparé entre création, remplissage, contrôles et interactions.
2. `validation.js` reste le point d'entrée des validations de panneaux, mais les règles testables sont séparées de la lecture DOM et du rendu d'erreurs.
3. `monsters.js` garde un rendu de ligne dense mais linéaire; les actions utilisateur et la préparation des options sont maintenant mieux séparées.
4. Les callbacks entre panneaux sont simples mais implicites: `onEncounterChange` et `onPlayersChange` rafraîchissent l'ordre sans le reconstruire.
5. Le mot `refresh()` existe dans plusieurs modules avec des effets différents.
6. L'état `encounter` est central et mutable. `DndInitiativeTrackerApp` et tous les panneaux DOM utilisent maintenant ses méthodes directement.
7. `draggedTurnId` est une variable globale de module dans `turn-order.js`, séparée de `encounter`.
8. Les joueurs sont d'abord des champs DOM, puis deviennent des acteurs dans `encounter.players`; cette frontière est maintenant isolée, avec un champ `side` persistant, mais reste un bon candidat pour la future passe DTO.
9. Certaines règles s'appliquent seulement lors de `buildRoundOrder()`, alors que leur changement déclenche seulement `refresh()`. C'est probablement voulu ou acceptable, mais la lecture peut laisser croire à un recalcul immédiat.

## Diagnostic de maintenabilité JS

| Sujet                          | Fichier / fonction                                                                               | Constat                                                                                                                                                 | Impact sur la compréhension                                                                                                                                          | Suggestion légère                                                                                                                                    |
| ------------------------------ | ------------------------------------------------------------------------------------------------ | ------------------------------------------------------------------------------------------------------------------------------------------------------- | -------------------------------------------------------------------------------------------------------------------------------------------------------------------- | ---------------------------------------------------------------------------------------------------------------------------------------------------- |
| Fichier long                   | `turn-order.js`                                                                                  | Le fichier regroupe initialisation, rendu, clic, clavier, drag and drop, focus et annonces.                                                             | Le lecteur doit encore garder plusieurs comportements en tête dans un seul fichier.                                                                                  | Garder les fonctions groupées par rôle; extraire un sous-contrôleur seulement si un futur changement le justifie.                                    |
| Rendu d'une entrée             | `turn-order.js` / `renderTurnOrderItem()`                                                        | La création, le remplissage, les contrôles et les interactions d'une entrée sont séparés.                                                               | Le flux est plus lisible, mais reste dans le même fichier pour éviter une abstraction prématurée.                                                                    | Conserver cette séparation locale avant d'envisager un `TurnOrderRenderer`.                                                                          |
| Fichier long                   | `validation.js`                                                                                  | Le fichier regroupe les façades DOM de validation, les règles pures, la lecture des champs et le feedback DOM.                                          | La lecture est plus guidée, mais le fichier reste un point de passage central pour les panneaux.                                                                     | Garder ce regroupement tant que les règles restent peu nombreuses; extraire seulement si de nouveaux formulaires complexifient le module.            |
| Responsabilités mélangées      | `validation.js` / `showValidationErrors()`, `clearValidationState()`, `focusFirstInvalidField()` | Le module rend encore les erreurs dans le DOM, mais les règles `validateIntegerValue()` et `validateCurrentHitPointsLimit()` sont testables séparément. | Les futures règles peuvent être ajoutées sans dépendre directement du DOM.                                                                                           | Continuer à isoler les nouvelles règles en fonctions pures, sans déplacer tout le feedback DOM dans un nouveau module pour l'instant.                |
| Responsabilités mélangées      | `monsters.js` / `populateMonsterItem()`                                                          | La fonction remplit tous les champs DOM d'une ligne monstre, mais reste linéaire et limitée au rendu.                                                   | Le rendu d'un monstre demande une lecture complète de la fonction, sans logique métier cachée.                                                                       | Laisser tel quel tant que les futures fonctions de recherche, filtres, portraits ou états de combat ne l'alourdissent pas.                           |
| Nom générique                  | Plusieurs fichiers / `refresh()`                                                                 | `refresh()` existe dans `monsters.js`, `turn-order.js`, et comme callback.                                                                              | Le lecteur doit vérifier le scope pour savoir ce qui est rafraîchi.                                                                                                  | Renommer progressivement dans les objets retournés ou ajouter commentaires d'intention. Exemple: `refreshTurnOrderPanel`, `renderMonsterPanel`.      |
| Dépendances implicites         | `monsters.js`, `turn-order.js`                                                                   | Les templates sont lus au niveau module avec `document.getElementById(...)`.                                                                            | Le module suppose que le DOM existe déjà au moment de l'import.                                                                                                      | Documenter le contrat DOM en commentaire court. Déplacer dans `initialize...` seulement si un test ou un besoin concret le justifie.                 |
| Ordre d'exécution              | `dnd_initiative.js`                                                                              | `turnOrderPanel` est créé avant `monstersPanel` et `playersPanel`, car leurs callbacks rafraîchissent son rendu.                                        | Ce choix est porté par `DndInitiativeTrackerApp.start()`, ce qui rend l'enchaînement plus visible.                                                                   | Garder cet ordre explicite pendant la conversion progressive des panneaux en classes.                                                                |
| Ordre d'exécution              | `rules.js` + `encounter-state.js`                                                                | Changer une règle appelle `refresh()` mais pas `buildRoundOrder()`.                                                                                     | Un lecteur peut croire que l'ordre existant est recalculé.                                                                                                           | Documenter explicitement: les règles sont appliquées à la prochaine génération, ou décider plus tard avec test si le recalcul immédiat est souhaité. |
| Duplication raisonnable        | `players.js` et `validation.js` / `hasStartedPlayer()`, `getPlayerInput()`                       | Deux helpers similaires existent dans deux modules.                                                                                                     | Petite duplication, mais elle évite pour l'instant un couplage artificiel.                                                                                           | Laisser tel quel ou extraire seulement si une troisième duplication apparaît.                                                                        |
| Frontière DOM -> état          | `players.js` / `getPlayerActors()`                                                               | La transformation passe par `readPlayerForm()` puis `createPlayerActor()`.                                                                              | La frontière est plus visible, mais la forme finale reste une structure libre; le modificateur d'initiative importé est conservé pour les acteurs issus d'un import. | Utiliser cette zone comme point de départ pour la future passe DTO joueur.                                                                           |
| Logique métier mélangée au DOM | `validation.js` / `validateEncounterActors()`                                                    | La règle "au moins un acteur" est évaluée depuis le DOM, pas depuis `encounter`.                                                                        | La source de vérité varie selon le moment du flux.                                                                                                                   | Documenter cette exception: avant génération, le DOM est validé pour éviter un état non synchronisé.                                                 |
| Événements DOM                 | `turn-order.js` / `bindTurnOrderItemEvents()`                                                    | Les écouteurs d'une entrée sont regroupés par type: toggle, clavier, drag and drop.                                                                     | Le lecteur doit toujours comprendre que `replaceChildren()` supprime les anciens items et listeners.                                                                 | Garder ces trois groupes locaux tant qu'ils restent courts.                                                                                          |
| État global difficile à suivre | `dnd_initiative.js` / `encounter`                                                                | L'état est mutable et partagé par tous les panneaux.                                                                                                    | Les mutations passent par plusieurs callbacks, surtout entre panneaux.                                                                                               | Ajouter au document ou au code un résumé "source de vérité: `encounter`; source temporaire joueurs: DOM".                                            |
| État global de module          | `turn-order.js` / `draggedTurnId`                                                                | Le drag and drop utilise une variable de module hors `encounter`.                                                                                       | C'est simple, mais séparé du reste de l'état.                                                                                                                        | Le garder local au module sauf si le drag and drop devient plus complexe.                                                                            |
| Tests existants                | `tests/js/lab/dnd/encounter-state.test.js`                                                       | Les fonctions métier principales sont bien couvertes.                                                                                                   | Bonne protection avant clarification de noms ou petites extractions métier.                                                                                          | Garder ces tests comme base avant toute modification de `encounter-state.js`.                                                                        |
| Tests DOM existants            | `monsters.js`, `players.js`, `turn-order.js`, `rules.js`, `validation.js`                        | Des tests ciblés couvrent déjà le rendu, les callbacks, la validation et quelques interactions DOM.                                                     | Les conversions internes peuvent être faites progressivement avec une base de non-régression.                                                                        | Garder ces tests verts et ajouter un test ciblé quand une conversion modifie une interaction non couverte.                                           |
| Fichier généré                 | `bestiary.js`                                                                                    | 17 126 lignes de données générées.                                                                                                                      | Il pollue les recherches et ne doit pas être lu comme du code applicatif.                                                                                            | Le laisser hors refactor; s'appuyer sur le pipeline de génération documenté ailleurs.                                                                |

## Plan de clarification progressif

### 1. Ajouter des commentaires d'intention en haut des modules

- Objectif : expliquer en 2 ou 3 lignes le rôle et les limites de chaque fichier JS.
- Fichiers concernés : `dnd_initiative.js`, `encounter-state.js`, `monsters.js`, `players.js`, `turn-order.js`, `rules.js`, `validation.js`, `initiative.js`.
- Type :
    - documentation
- Risque :
    - faible
- Bénéfice : le lecteur sait tout de suite si le fichier contient de l'orchestration, du DOM, de l'état ou des helpers.
- À ne pas faire : transformer ces commentaires en documentation longue ou en pseudo-architecture.

### 2. Documenter le flux principal dans le code d'entrée

- Objectif : rendre évident que `dnd_initiative.js` crée l'état, initialise les panneaux puis laisse les événements piloter l'outil.
- Fichiers concernés : `dnd_initiative.js`.
- Type :
    - documentation
    - clarification de flux
- Risque :
    - faible
- Bénéfice : le point d'entrée devient une table des matières lisible du tracker.
- À ne pas faire : déplacer les initialisations ou changer l'ordre d'exécution.

### 3. Clarifier les noms de callbacks de panneau

- Objectif : rendre plus explicite ce que font `onEncounterChange` et `onPlayersChange`.
- Fichiers concernés : `dnd_initiative.js`, `monsters.js`, `players.js`.
- Type :
    - renommage
    - clarification de flux
- Risque :
    - faible
- Bénéfice : éviter de croire qu'un changement de joueurs ou monstres reconstruit automatiquement l'ordre du tour.
- À ne pas faire : changer le comportement pour reconstruire l'ordre automatiquement.

### 4. Renommer les petites ambiguïtés locales

- Objectif : enlever les accroches de lecture inutiles.
- Fichiers concernés : `monsters.js`, éventuellement `turn-order.js`.
- Type :
    - renommage
- Risque :
    - faible
- Bénéfice : lecture plus fluide sans refactor.
- À ne pas faire : lancer une grande passe de renommage globale.

Exemples candidats:

- distinguer `refresh()` en commentaire ou par nom plus précis;
- isoler les identifiants de drag and drop dans un futur contrôleur dédié.

### 5. Conserver et enrichir les tests DOM avant de toucher aux interactions

- Objectif : garder les comportements fragiles verrouillés avant extraction ou regroupement.
- Fichiers concernés : tests autour de `players.js`, `turn-order.js`, `validation.js`, `monsters.js` et `rules.js`.
- Type :
    - test
- Risque :
    - faible
- Bénéfice : sécuriser les modifications de lisibilité sur les événements DOM.
- À ne pas faire : chercher une couverture exhaustive ou tester tous les détails CSS.

Tests prioritaires à conserver ou compléter:

- `getPlayerActors()` ignore un joueur vide et transforme un joueur rempli;
- `renderRoundOrder()` affiche placeholder/liste correctement;
- clic ou clavier sur un tour appelle le callback de toggle;
- `showValidationErrors()` marque un champ invalide et remplit le résumé.

### 6. Regrouper les fonctions par responsabilité dans les fichiers existants

- Objectif : améliorer la lecture verticale sans déplacer de code entre fichiers.
- Fichiers concernés : `turn-order.js`, `validation.js`, `monsters.js`.
- Type :
    - clarification de flux
- Risque :
    - faible
- Bénéfice : un lecteur voit plus vite les couches: initialisation, rendu, événements, helpers.
- À ne pas faire : créer de nouveaux modules juste pour classer les fonctions.

### 7. Clarifier la frontière DOM -> état pour les joueurs

- Objectif : rendre explicite que les champs joueur restent la source immédiate jusqu'à `sync()`.
- Fichiers concernés : `players.js`, `dnd_initiative.js`, éventuellement tests.
- Type :
    - documentation
    - test
- Risque :
    - faible
- Bénéfice : moins de confusion autour de `playersPanel.sync()` avant `buildRoundOrder()`.
- État : réalisé en séparant lecture des champs, formulaire joueur normalisé et création de l'acteur envoyé à `EncounterState`.
- À ne pas faire : introduire un store ou une synchronisation bidirectionnelle complexe.

### 8. Clarifier l'effet réel des règles

- Objectif : documenter ou tester que les règles sont appliquées à la génération de l'ordre.
- Fichiers concernés : `rules.js`, `dnd_initiative.js`, `encounter-state.js`, tests existants.
- Type :
    - documentation
    - test
    - clarification de flux
- Risque :
    - moyen
- Bénéfice : éviter une mauvaise interprétation du `refresh()` après changement de règle.
- À ne pas faire : modifier immédiatement le comportement pour recalculer l'ordre existant sans décision fonctionnelle explicite.

### 9. Garder la séparation locale du rendu dans `turn-order.js`

- Objectif : conserver la séparation entre création, remplissage et interactions d'une entrée.
- Fichiers concernés : `turn-order.js`.
- Type :
    - clarification de flux
- Risque :
    - faible
- Bénéfice : séparer "parcourir l'ordre", "remplir une ligne" et "brancher les interactions".
- À ne pas faire : éclater le fichier en plusieurs modules ou introduire une classe de composant.

### 10. Surveiller le remplissage d'une ligne dans `monsters.js`

- Objectif : garder `populateMonsterItem()` lisible sans extraction prématurée.
- Fichiers concernés : `monsters.js`.
- Type :
    - clarification de flux
- Risque :
    - faible
- Bénéfice : conserver un rendu DOM linéaire tant qu'il ne porte pas de nouvelle responsabilité fonctionnelle.
- À ne pas faire : découper le remplissage par principe alors que la fonction reste compréhensible.

### 11. Séparer légèrement les helpers de validation et de feedback DOM

- Objectif : rendre `validation.js` plus navigable sans changer son API publique.
- Fichiers concernés : `validation.js`.
- Type :
    - clarification de flux
    - extraction légère
- Risque :
    - moyen
- Bénéfice : distinguer les validateurs des fonctions qui modifient l'interface.
- À ne pas faire : créer une architecture de validation abstraite ou déplacer toutes les validations hors DOM d'un coup.

### 12. Laisser le bestiaire hors refactor

- Objectif : traiter `bestiary.js` comme une donnée générée, pas comme du code à clarifier manuellement.
- Fichiers concernés : `bestiary.js`, outils de génération seulement si besoin futur.
- Type :
    - documentation
- Risque :
    - faible
- Bénéfice : éviter de perdre du temps dans 17 126 lignes générées.
- À ne pas faire : éditer `bestiary.js` manuellement ou le formater pour la lisibilité humaine.

## Résumé final

### Fichiers JS les plus importants

1. `dnd_initiative.js`: point d'entrée et orchestration.
2. `encounter-state.js`: état et règles métier.
3. `turn-order.js`: rendu et interactions de l'ordre du tour.
4. `monsters.js`: création, sélection et initiatives des monstres.
5. `players.js`: saisie et synchronisation des joueurs.
6. `validation.js`: validations et feedback DOM.

### Chemin d'exécution principal

1. Symfony charge `dnd_initiative` via l'importmap.
2. `dnd_initiative.js` instancie `DndInitiativeTrackerApp`, qui crée `encounter`.
3. `DndInitiativeTrackerApp.start()` initialise les panneaux monstres, joueurs, ordre du tour et règles.
4. Les actions utilisateur modifient `encounter` via les fonctions de `encounter-state.js`.
5. Le clic "Générer l'ordre" déclenche `generateTurnOrder()`.
6. `generateTurnOrder()` valide le DOM, synchronise les joueurs, appelle `buildRoundOrder()`, puis rerend l'ordre du tour.

### Les 5 améliorations les plus sûres à faire en premier

1. Ajouter un commentaire d'intention court en haut de chaque module JS applicatif.
2. Ajouter un commentaire de flux dans `dnd_initiative.js` autour de la création de `encounter` et de l'initialisation des panneaux.
3. Clarifier les noms génériques comme `refresh()` quand une conversion de classe les rend ambigus.
4. Ajouter un test DOM ciblé avant toute modification non couverte de `turn-order.js`.
5. Regrouper les fonctions de `turn-order.js` et `validation.js` par sections sans déplacer de fichier.

### Zones à ne pas toucher sans test préalable

1. `turn-order.js` / `renderRoundOrder()`: beaucoup d'événements et de comportements d'accessibilité.
2. `encounter-state.js` / `buildRoundOrder()`, `compareByInitiative()`, `moveTurn()`: logique métier déjà testée, à garder verrouillée.
3. `validation.js` / `showValidationErrors()` et `focusFirstInvalidField()`: mélange DOM, accessibilité et UX.
4. `players.js` / `getPlayerActors()` et `sync()`: frontière importante entre DOM joueur et état.
5. `rules.js` + `dnd_initiative.js`: effet des règles sur un ordre déjà généré à clarifier avant tout changement fonctionnel.
6. `bestiary.js`: fichier généré, à ne pas modifier manuellement.
