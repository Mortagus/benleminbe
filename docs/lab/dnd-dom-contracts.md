# Contrats DOM - DnD Initiative Tracker

Date de mise à jour : 2026-05-20

Ce document liste les sélecteurs critiques qui relient les templates Twig du DnD Initiative Tracker aux modules JavaScript.

Ces identifiants, classes et attributs `data-*` sont des contrats applicatifs. Les renommer dans Twig nécessite d'adapter le JavaScript correspondant.

## Page Et Entrypoint

Template principal :

```text
templates/lab/dnd/initiative_tracker.html.twig
```

Entrypoint importmap :

```text
dnd_initiative
```

Module principal :

```text
assets/scripts/lab/dnd/dnd_initiative.js
```

## Panneau Monstres

Template :

```text
templates/lab/dnd/_monsters_panel.html.twig
```

Modules consommateurs :

```text
assets/scripts/lab/dnd/monsters.js
assets/scripts/lab/dnd/validation.js
```

Contrats critiques :

| Sélecteur | Rôle |
|-----------|------|
| `.dnd-panel--monsters` | Scope utilisé pour effacer les erreurs du panneau. |
| `#monsterCount` | Nombre de slots monstres à créer et champ validé avant génération. |
| `#monsterSearch` | Filtre global par nom pour réduire les options des sélecteurs de monstres. Le champ existe encore mais reste masqué dans l'UI pour le moment. |
| `#monsterTypeFilter` | Filtre global par type pour réduire les options des sélecteurs de monstres. |
| `#createMonsters` | Bouton de création des slots monstres. |
| `#rollInitiative` | Bouton de jet d'initiative des monstres. |
| `#monsterValidationSummary` | Résumé accessible des erreurs du panneau. |
| `#monsterList` | Conteneur des slots monstres générés. |
| `#monsterItemTemplate` | Template cloné pour chaque slot monstre. |
| `#monsterOptionTemplate` | Template d'option cloné pour le sélecteur de monstre. |
| `.monster-item` | Racine d'un slot monstre. |
| `.monster-select` | Sélecteur du monstre dans le bestiaire. |
| `.monster-meta` | Conteneur d'affichage compact des informations du monstre sélectionné. |
| `.monster-type` | Affichage du type du monstre. |
| `.monster-size` | Affichage de la taille. |
| `.monster-cr` | Affichage du facteur de puissance. |
| `.monster-alignment` | Affichage de l'alignement du monstre sélectionné. |
| `.monster-legendary` | Mention affichée pour un monstre légendaire. |
| `.monster-armor-class` | Affichage de la CA. |
| `.monster-hp input` | PV courants du monstre, validés avant génération de l'ordre. |
| `.monster-hit-points-max` | PV max du monstre. |
| `.monster-initiative-modifier` | Modificateur d'initiative. |
| `.monster-initiative` | Initiative finale affichée. |

## Panneau Joueurs

Templates :

```text
templates/lab/dnd/_players_panel.html.twig
templates/lab/dnd/_player_item.html.twig
```

Modules consommateurs :

```text
assets/scripts/lab/dnd/players.js
assets/scripts/lab/dnd/validation.js
```

Contrats critiques :

| Sélecteur | Rôle |
|-----------|------|
| `.dnd-panel--players` | Scope utilisé pour effacer les erreurs du panneau. |
| `#addPlayer` | Bouton d'ajout d'une ligne joueur. |
| `#importPlayerXml` | Bouton pour ouvrir la modale d'import XML. |
| `#playerImportModal` | Modale de sélection et confirmation d'import XML. |
| `#playerXmlImportInput` | Champ fichier dans la modale d'import. |
| `#playerImportSubmit` | Bouton de confirmation qui lance l'import. |
| `#playerValidationSummary` | Résumé accessible des erreurs du panneau. |
| `#playerImportStatus` | Zone de statut pour afficher le fichier sélectionné. |
| `#playerDetailsModal` | Modale de consultation de la fiche joueur importée. |
| `#playerDetailsTableBody` | Corps du tableau clé-valeur de la fiche joueur. |
| `#playerList` | Conteneur des lignes joueurs. |
| `#playerItemTemplate` | Template cloné pour chaque nouveau joueur. |
| `.player-item` | Racine d'une ligne joueur. |
| `[data-player-details-open]` | Bouton compact qui ouvre la fiche complète du joueur. |
| `.player-remove-button` | Bouton de suppression d'un joueur, renommé dynamiquement pour l'accessibilité. |
| `[data-player-field="name"]` | Nom du joueur. |
| `[data-player-field="armor-class"]` | CA du joueur. |
| `[data-player-field="current-hit-points"]` | PV courants du joueur. |
| `[data-player-field="base-hit-points"]` | PV max du joueur. |
| `[data-player-field="initiative"]` | Initiative saisie du joueur. |
| `[data-player-label="name"]` | Label associé au nom. |
| `[data-player-label="armor-class"]` | Label associé à la CA. |
| `[data-player-label="hit-points"]` | Label de groupe des PV. |
| `[data-player-label="initiative"]` | Label associé à l'initiative. |

Règle importante :

- l'extraction des joueurs doit rester basée sur `data-player-field`, pas sur l'ordre des inputs dans le DOM.

## Panneau Ordre Du Tour

Template :

```text
templates/lab/dnd/_turn_order_panel.html.twig
```

Modules consommateurs :

```text
assets/scripts/lab/dnd/turn-order.js
assets/scripts/lab/dnd/validation.js
```

Contrats critiques :

| Sélecteur | Rôle |
|-----------|------|
| `.dnd-panel--turn-order` | Scope utilisé pour effacer les erreurs du panneau. |
| `#generateTurnOrder` | Bouton de génération de l'ordre du tour. |
| `#turnOrderValidationSummary` | Résumé accessible des erreurs de génération. |
| `#toggleTurnOrderKeyboardHelp` | Bouton d'ouverture/fermeture de l'aide clavier. |
| `#turnOrderKeyboardHelp` | Bloc d'aide clavier. |
| `#turnOrderPlaceholder` | Message affiché quand aucun ordre n'est généré. |
| `#turnOrderList` | Liste ordonnée des acteurs. |
| `#turnOrderLiveRegion` | Région `aria-live` pour annoncer les déplacements clavier/souris. |
| `#turnOrderItemTemplate` | Template cloné pour chaque acteur. |
| `.turn-order-item` | Racine d'une carte acteur, rendue focusable par JavaScript. |
| `[data-turn-move="previous"]` | Bouton de déplacement vers la gauche/avant. |
| `[data-turn-move="next"]` | Bouton de déplacement vers la droite/après. |

Les cartes d'ordre du tour sont reconstruites à chaque refresh. Toute donnée d'état durable doit vivre dans `encounter-state.js`, pas dans le DOM.

## Panneau Règles

Template :

```text
templates/lab/dnd/_rules_panels.html.twig
```

Module consommateur :

```text
assets/scripts/lab/dnd/rules.js
```

Contrats critiques :

| Sélecteur | Rôle |
|-----------|------|
| `#openRulesPanel` | Bouton d'ouverture de la modale de règles. |
| `#rulesModal` | Modale de règles. |
| `#rulesModalTitle` | Titre référencé par `aria-labelledby`. |
| `[data-rules-close]` | Boutons/zones qui ferment la modale. |
| `[data-rule-toggle]` | Toggle d'une règle maison. Sa valeur doit correspondre à un identifiant connu dans `encounter-state.js`. |

## Règles De Maintenance

- Préférer un attribut `data-*` pour tout nouveau contrat de données entre Twig et JavaScript.
- Garder les classes CSS pour le style, sauf lorsqu'une classe est déjà utilisée comme scope de panneau.
- Après modification d'un contrat DOM, chercher le sélecteur dans `assets/scripts/lab/dnd/` et `templates/lab/dnd/`.
- Après modification d'un template DnD, lancer :

```bash
php bin/console lint:twig templates/lab/dnd
npm run test:js
```
