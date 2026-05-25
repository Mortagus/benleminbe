# Backlog unifié - DnD Initiative Tracker

Date de mise à jour : 2026-05-25

Ce document remplace l'ancien backlog d'audit et la roadmap avancée. Il conserve les tâches déjà réalisées, aligne les fonctionnalités de la roadmap avec l'état réel du projet et liste les évolutions restantes.

Documents descriptifs associés :

- [dnd-initiative-audit.md](../lab/dnd-initiative-audit.md)
- [dnd-bestiary-pipeline.md](../lab/dnd-bestiary-pipeline.md)
- [dnd-dom-contracts.md](../lab/dnd-dom-contracts.md)

## Note De Reprise - 2026-05-25 - Repriorisation Fonctionnelle

Après une discussion d'usage avec un joueur, l'ordre des prochaines priorités change. La sauvegarde locale `P8` reste importante, mais elle n'est plus la prochaine fonctionnalité à livrer.

Nouveaux points ajoutés :

- `P31` : ajouter un son lors du jet d'initiative des monstres, avec un code audio réutilisable pour d'autres événements futurs ;
- `P32` : importer une fiche personnage joueur depuis un XML produit par un outil externe déjà utilisé.

Nouvel ordre fonctionnel recommandé :

1. `P31` : socle audio et son au lancement d'initiative des monstres.
2. `P15` : amélioration de la sélection des monstres, filtres initiaux et affichage des informations du monstre sélectionné.
3. `P32` : import XML de fiches joueurs.
4. `P25` : différenciation visuelle des types d'acteurs.
5. `P19` : conditions.
6. `P20` : marqueurs binaires de combat.
7. `P10` : modification directe des PV pendant le combat.
8. `P7` : commandes explicites de pilotage du combat.

La persistance `P8` et l'import/export JSON `P22` restent dans le backlog, mais repassent après ces priorités d'usage.

## Note De Reprise - 2026-05-25 - P31 Audio

Le point `P31` a été livré pour ajouter un feedback sonore au jet d'initiative des monstres.

Travail livré :

- ajout de `assets/scripts/lab/dnd/sound-effects.js` ;
- ajout du registre `SOUND_EFFECTS` avec un seul identifiant public `monsterInitiativeRoll` ;
- l'effet `monsterInitiativeRoll` choisit aléatoirement entre `dice_roll.mp3` et `dice_roll_2.mp3` ;
- les objets `Audio` sont créés en lazy loading puis mis en cache par source ;
- les erreurs de lecture audio sont absorbées pour ne jamais bloquer le jeu ;
- `MonstersPanel` déclenche un callback `onMonsterInitiativeRoll` sans connaître le module audio ;
- `DndInitiativeTrackerApp` branche ce callback sur `playSoundEffect('monsterInitiativeRoll')` ;
- le bouton de jet d'initiative affiche temporairement un curseur `progress` pendant la promesse audio ;
- ajout de tests Vitest pour le module audio et le callback du panneau monstres.

Vérification passée pendant la passe :

```bash
npm run check:js
```

État de reprise recommandé :

- poursuivre avec `P15` : aides de sélection et affichage des monstres ;
- garder le module audio disponible pour de futurs événements sonores, sans ajouter de toggle UI pour le moment.

## Note De Reprise - 2026-05-25 - Passe DTOs

La session a démarré le chantier DTOs avant la persistance `P8`.

Décision validée :

- limiter la première passe aux DTOs nécessaires pour préparer `localStorage` ;
- ne pas traiter les DTOs secondaires maintenant : champ/résultat de validation et groupes d'options monstres restent des structures locales à revoir plus tard si le besoin augmente ;
- distinguer clairement les données persistables de rencontre des données de catalogue et de rendu.

Travail livré :

- création de `assets/scripts/lab/dnd/dtos.js` avec les typedefs JSDoc prioritaires, sans intégration applicative ;
- ajout de `ENCOUNTER_SNAPSHOT_VERSION` ;
- définition de `EncounterSnapshotDto`, `EncounterMonsterDto`, `EncounterPlayerDto`, `TurnEntryDto` et `RulesStateDto` ;
- mise à jour de la cartographie JS.

Suite livrée :

- ajout de helpers purs de conversion dans `dtos.js` ;
- `createEncounterSnapshotDto()` convertit une instance `EncounterState` en snapshot versionné ;
- `createEncounterMonsterDto()` normalise les monstres de rencontre, y compris les slots vides dont les valeurs d'affichage comme `'-'` deviennent `null` dans le DTO ;
- `createEncounterPlayerDto()` convertit les joueurs actuels de `encounter.players` ;
- `createTurnEntryDto()` produit une entrée de tour minimale avec `id`, `actorId`, `actorType` et `done`, sans recopier les détails d'acteur ;
- `createRulesStateDto()` normalise les règles connues ;
- ajout de tests Vitest dédiés aux helpers DTO.

Restauration livrée :

- ajout de `restoreEncounterFromSnapshot()` pour restaurer une instance `EncounterState` depuis un snapshot ;
- ajout de `createRuntimeMonsterFromDto()` et `createRuntimePlayerFromDto()` pour convertir les DTOs persistables vers les formes runtime actuelles ;
- ajout de `createRuntimeTurnOrderFromDto()` pour hydrater les entrées de tour minimales depuis les participants restaurés ;
- les entrées de tour orphelines sont ignorées ;
- un snapshot avec `turnOrder` vide reste vide, sans appel automatique à `buildRoundOrder()` ;
- les monstres sont restaurés depuis les données sauvegardées, sans relecture du bestiaire ;
- `persistence.js` reste volontairement absent et sera créé uniquement au moment de brancher `localStorage`.

Vérification passée pendant la passe :

```bash
npm run check:js
make check
```

État de reprise recommandé initial :

- prochaine étape : brancher ces helpers dans la sauvegarde locale `P8` ;
- éviter de remplacer tout de suite les structures runtime par les DTOs persistables, car le rendu actuel dépend encore de certaines valeurs d'affichage ;
- créer `persistence.js` seulement à ce moment-là.

## Note De Reprise - 2026-05-25 - Passe `players.js`

La session a continué avec la clarification de la frontière joueurs DOM -> `EncounterState`.

Travail livré :

- ajout d'un test Vitest qui vérifie la synchronisation d'un joueur existant vers `encounter.players` lors d'une modification de champ ;
- extraction d'un handler `handleAddPlayer()` dans `PlayersPanel` ;
- conservation de `PlayersPanel.sync()` comme API publique appelée par le coordinateur ;
- introduction de `syncPlayerFormsToEncounter()` pour nommer explicitement la frontière entre formulaire joueur et état de rencontre ;
- séparation du mapping joueur en deux étapes : `readPlayerForm()` lit les champs `data-player-field`, puis `createPlayerActor()` produit la structure consommée par `EncounterState` ;
- mise à jour de la cartographie JS.

Vérification passée pendant la passe :

```bash
npm run check:js
make check
```

État de reprise recommandé :

- prochaine passe : cadrer les DTO joueurs/monstres avant toute persistance ;
- partir des fonctions `createPlayerActor()` et des constructeurs de monstres dans `encounter-state.js` pour identifier les formes actuelles ;
- ne pas démarrer `P8` localStorage avant d'avoir stabilisé les formes de données à persister.

## Note De Reprise - 2026-05-25 - Passe `monsters.js`

La session a continué avec une passe ciblée sur `monsters.js`, après validation du périmètre avec Benjamin.

Travail livré :

- ajout de tests Vitest sur `MonstersPanel` pour la création valide de slots monstres et le refus d'un nombre invalide ;
- extraction de handlers explicites dans `MonstersPanel` : création de slots, jet d'initiative, sélection d'un monstre et modification des PV ;
- `renderMonsters()` accepte maintenant un catalogue injecté ;
- `MonstersPanel` rend les options depuis `encounter.bestiary`, avec fallback conservé vers le bestiaire importé pour les appels existants ;
- la préparation des options est isolée avec des helpers de groupement et de création d'options ;
- `populateMonsterItem()` n'a pas été découpée davantage : la fonction reste dense mais linéaire et limitée au remplissage DOM ;
- mise à jour de la cartographie JS.

Vérification passée pendant la passe :

```bash
npm run check:js
make check
```

État de reprise recommandé :

- prochaine passe : améliorer la frontière joueurs DOM -> `EncounterState` ;
- passe suivante avant reprise des points `PX` : explorer et cadrer des DTO explicites pour les structures joueur et monstre ;
- objectif de cette passe DTO : préparer un format plus rigoureux pour la sauvegarde locale `P8` et l'import/export `P22`, sans mélanger ce travail avec la persistance elle-même.

## Note De Reprise - 2026-05-25

La session a repris sur la passe de lisibilité recommandée pour `validation.js`.

Travail livré :

- séparation interne entre façades de validation appelées avec des noeuds DOM, règles pures et helpers de feedback DOM ;
- extraction de `validateIntegerValue()` pour valider une valeur entière normalisée sans lecture DOM ;
- extraction de `validateCurrentHitPointsLimit()` pour isoler la règle PV actuels <= PV max ;
- conservation de l'API publique utilisée par `monsters.js`, `players.js`, `turn-order.js` et `dnd_initiative.js` ;
- ajout de tests Vitest ciblés sur les règles pures de validation ;
- mise à jour de la cartographie JS pour refléter la nouvelle responsabilité de `validation.js`.

Vérification passée :

```bash
npm run check:js
make check
```

État de reprise recommandé :

- continuer par petites passes avec tests et documentation ;
- ne plus prioriser `validation.js` sauf nouveau besoin fonctionnel ;
- reprendre ensuite `monsters.js`, car il mélange encore rendu, lecture du bestiaire, branchement d'événements et validation ;
- autre option valable : travailler la frontière joueurs DOM -> `EncounterState`, surtout si la sauvegarde locale `P8` devient la priorité immédiate.

## Note De Reprise - 2026-05-24

La dernière session a porté sur la lisibilité du JavaScript du `DnD Initiative Tracker` et sur la consolidation des vérifications automatiques.

Travail livré :

- introduction d'une orchestration explicite avec `DndInitiativeTrackerApp` dans `dnd_initiative.js` ;
- introduction de `EncounterState` comme modèle mutable central de rencontre ;
- conversion des panneaux DOM en classes explicites : `RulesPanel`, `PlayersPanel`, `MonstersPanel`, `TurnOrderPanel` ;
- suppression des anciens wrappers d'initialisation des panneaux ;
- passe de lisibilité sur `turn-order.js` pour séparer le rendu, les contrôles, le focus, les déplacements et le drag and drop ;
- mise à jour des tests JS pour instancier directement les classes de panneaux ;
- mise à jour de la cartographie JS et de l'audit DnD pour refléter l'état réel ;
- ajout d'ESLint et intégration du lint JS + tests Vitest dans `make check`.

Vérifications passées en fin de session :

```bash
npm run lint:js
npm run check:js
make check
git diff --check
```

État de reprise recommandé initial :

- le dépôt était propre après les commits de fin de session ;
- l'orientation OOP explicite est validée pour les zones qui portent une responsabilité durable, sans chercher à transformer chaque helper pur en classe ;
- la règle de travail validée est de continuer par petites passes, avec tests et documentation mis à jour à chaque passe ;
- la prochaine passe de lisibilité recommandée était `validation.js`, car ce module mélangeait encore règles de validation, inspection DOM, affichage des erreurs et gestion du focus ;
- après cette passe, reprendre `monsters.js` ou la frontière joueurs DOM -> `EncounterState`, selon ce qui paraît le plus difficile à relire.

Plan de reprise réalisé pour `validation.js` le 2026-05-25 :

1. Cartographier les usages actuels depuis `monsters.js`, `players.js`, `turn-order.js` et `dnd_initiative.js`.
2. Séparer sans changer le comportement les fonctions pures de validation des fonctions d'affichage DOM.
3. Ajouter ou ajuster les tests autour des règles de validation qui deviennent isolables.
4. Mettre à jour `docs/lab/dnd-initiative-js-map.md` et cette note si les responsabilités changent.
5. Lancer `npm run check:js` puis `make check`.

## Note De Reprise - 2026-05-21

Une fois le cycle Lighthouse/cache et la validation de la zone privee termines, le prochain projet hors partie professionnelle du site est de reprendre l'amelioration du `DnD Initiative Tracker`.

Objectif de reprise recommande :

- stabiliser l'outil pour un usage reel pendant une session de jeu ;
- conserver l'approche front-end JavaScript vanilla actuelle ;
- commencer par la sauvegarde locale de rencontre (`P8`) avant les fonctions de combat plus avancees ;
- preparer ensuite l'import/export JSON (`P22`), les PV modifiables pendant le combat (`P10`) et les commandes explicites de round/tour (`P7`).

Premiere action recommandee :

```text
Relire l'etat actuel du modèle de rencontre dans `encounter-state.js`, verifier les contrats DOM existants, puis cadrer le format versionne de sauvegarde locale avant d'ecrire le code.
```

## Légende

- `Fait` : fonctionnalité ou correction présente dans le code.
- `Fait - en observation` : présent dans le code, à ajuster après usage réel.
- `Partiel` : une base existe, mais la fonctionnalité attendue n'est pas complète.
- `À faire` : non implémenté ou à reprendre.
- `Plus tard` : utile, mais non prioritaire pour stabiliser le tracker.

## Principes de priorisation

Le backlog est organisé sous forme de tableau pour pouvoir réordonner les points sans déplacer de longues sections descriptives. La colonne `Ordre conseillé` donne l'enchaînement actuel recommandé.

Les points d'architecture, de modèle de données, de tests et de contrats techniques sont volontairement remontés quand ils conditionnent plusieurs fonctionnalités futures. L'objectif est d'éviter de construire les commandes de combat, la sauvegarde ou les PV en direct sur des fondations fragiles.

## Backlog

| Ordre conseillé | ID  | Priorité actuelle | Statut          | Catégorie                  | Point                                                    | Résumé / résultat attendu                                                                                                        | Dépendances / remarques                                                                                      |
|-----------------|-----|-------------------|-----------------|----------------------------|----------------------------------------------------------|----------------------------------------------------------------------------------------------------------------------------------|--------------------------------------------------------------------------------------------------------------|
| 1               | P16 | Réalisé           | ✅ **Fait**      | Architecture               | Documentation et allègement du catalogue de monstres     | Pipeline `tools/dnd/` documenté, ancien extracteur supprimé, génération directe de `bestiary.js` et test de contrat ajoutés.     | Import statique conservé tant que le catalogue reste à l'échelle actuelle.                                   |
| 2               | P9  | Réalisé           | ✅ **Fait**      | JavaScript / Règles        | Tests sur les règles et la génération d'ordre            | Vitest ajouté avec tests unitaires sur slots, sélection, jets, tri, règles maison, état joué/non joué et mutations de rencontre. | La fixture bestiaire de test est générée depuis le même extracteur que le catalogue complet.                 |
| 3               | P18 | Réalisé           | ✅ **Fait**      | Textes                     | Harmonisation des textes et libellés de jeu              | Vocabulaire stabilisé : acteur, joueur, monstre, initiative, ordre du tour, PV actuels, PV max, à jouer et joué.                 | Libellés visibles gardés courts pour ne pas alourdir l'interface.                                            |
| 4               | P6  | Socle livré       | ✅ **Fait**      | Architecture / JavaScript  | Modèle de rencontre explicite et orchestration clarifiée | `encounter-state.js` centralise le modèle et les mutations ; les panneaux deviennent des adaptateurs DOM.                        | Base de la sauvegarde, des commandes de combat, des PV en direct, des tests et de l'import/export.           |
| 5               | P1  | Réalisé           | ✅ **Fait**      | Règles                     | Sélecteur de règles à appliquer                          | Les règles maison sont pilotables via une popup du panneau ordre du tour.                                                        | À ajuster après usage réel si les libellés ou interactions créent une friction.                              |
| 6               | P2  | Réalisé           | ✅ **Fait**      | Sécurité / UX              | Validation des entrées utilisateur                       | Validation JavaScript par panneau avec messages d'erreur et blocage des actions risquées.                                        | Couvre nombre de monstres invalide, absence d'acteur, PV incohérents et joueur incomplet.                    |
| 7               | P3  | Réalisé           | ✅ **Fait**      | Sécurité                   | Construction DOM sûre                                    | Les données dynamiques sont rendues via `textContent`, templates DOM, `replaceChildren()` et APIs DOM sûres.                     | Conserver cette règle locale ; éviter `innerHTML` sauf cas strictement contrôlé.                             |
| 8               | P4  | Réalisé           | ✅ **Fait**      | JavaScript                 | Extraction stable des données joueurs                    | Les joueurs sont lus via `data-player-field` plutôt que par position des champs dans le DOM.                                     | Toute évolution du formulaire doit maintenir ce contrat ou adapter `players.js` et `validation.js`.          |
| 9               | P5  | Réalisé           | ✅ **Fait**      | Fonctionnalité             | Noyau MVP de suivi d'initiative                          | Participants, monstres, jets, tri, ordre du tour, drag-and-drop, tours joués, acteur actif et initiales sont en place.           | Le socle reste en mémoire uniquement et ne couvre pas encore rounds, sauvegarde ou PV directs.               |
| 10              | P11 | Réalisé           | ✅ **Fait**      | Twig                       | Réduction de duplication du template joueur              | Le markup joueur est factorisé dans `_player_item.html.twig` pour la ligne initiale et le template dynamique.                    | Limite les doubles modifications futures du formulaire joueur.                                               |
| 11              | P12 | Réalisé           | ✅ **Fait**      | Accessibilité / Twig       | Accessibilité des formulaires dynamiques                 | Labels reliés, identifiants recalculés, noms accessibles des PV, boutons contextualisés et selects monstres labellisés.          | Maintenir ces attributs lors des prochains changements de formulaire.                                        |
| 12              | P13 | Réalisé           | ✅ **Fait**      | Accessibilité / UX         | Drag-and-drop utilisable autrement qu'à la souris        | Drag souris gauche/droite, déplacement au clavier par flèches, focus conservé, aide clavier repliable et annonces `aria-live`.   | Les boutons de déplacement restent cliquables à la souris mais sont retirés de l'enchaînement `Tab`.         |
| 13              | P31 | Réalisé           | ✅ **Fait**      | UX / Audio                 | Son au jet d'initiative des monstres                     | Un module audio réutilisable joue aléatoirement un des deux sons de dés lors du lancement d'initiative des monstres.             | Pas de toggle UI pour l'instant ; les erreurs audio sont non bloquantes.                                      |
| 14              | P15 | Très haute        | 🟡 **Partiel**  | Fonctionnalité / UX        | Aides de sélection et affichage des monstres             | Améliorer la selectbox, ajouter 1 ou 2 filtres initiaux et enrichir l'affichage des informations du monstre sélectionné.         | Commencer par réduire les 428 options visibles ; recherche/filtres avancés, favoris ou presets plus tard.    |
| 15              | P32 | Très haute        | 🔶 **À faire**  | Import / Joueurs           | Import XML de fiche personnage joueur                    | Importer un fichier XML issu d'un outil externe pour préremplir une fiche joueur aussi complètement que possible.                | Nouveau point ; commencer par analyser des exemples XML et mapper nom, CA, PV, initiative et données utiles. |
| 16              | P25 | Haute             | 🔶 **À faire**  | UX                         | Différenciation visuelle des types d'acteurs             | Distinguer joueurs, alliés, ennemis, boss ou monstres légendaires avec des classes visuelles sobres.                             | À faire après stabilisation du rendu des cartes.                                                             |
| 17              | P19 | Haute             | 🔶 **À faire**  | Fonctionnalité / Règles    | Gestion des conditions                                   | Ajouter/retrouver des conditions, les afficher visuellement et suivre leur durée en rounds.                                      | Dépend idéalement de commandes de round fiables, mais peut être cadré avant `P7`.                            |
| 18              | P20 | Haute             | 🔶 **À faire**  | Fonctionnalité             | Marqueurs binaires de combat                             | Suivre concentration, réaction utilisée, inspiration, avantage et désavantage via toggles visuels.                               | À commencer sans automatisme de règles ; peut partager l'UI des conditions.                                  |
| 19              | P10 | Haute             | 🔶 **À faire**  | Fonctionnalité / UX        | Modification directe des PV pendant le combat            | Modifier les PV depuis l'ordre du tour et accepter des saisies rapides de dégâts/soins comme `-7` ou `+5`.                       | Préparer sans imposer immédiatement les statuts inconscient/mort.                                            |
| 20              | P7  | Haute             | 🔶 **À faire**  | Fonctionnalité / UX        | Commandes explicites de pilotage du combat               | Ajouter acteur suivant, nouveau round, réinitialisation des tours joués et remise à zéro de la rencontre.                        | S'appuie sur le modèle de rencontre et permettra de fiabiliser conditions, journal et durées.                |
| 21              | P8  | Haute différée    | 🔶 **À faire**  | Persistance                | Sauvegarde locale de rencontre                           | Persister et restaurer monstres, joueurs, PV, règles, ordre du tour, round et acteur actif via `localStorage`.                   | Les DTOs sont préparés ; créer `persistence.js` uniquement au moment de brancher `localStorage`.             |
| 22              | P22 | Haute différée    | 🔶 **À faire**  | Persistance / Échange      | Import/export JSON d'une rencontre                       | Exporter et importer un état de rencontre lisible : joueurs, monstres, PV, initiatives, règles et ordre courant.                 | Plus simple après P8 ; utile pour archiver, partager et tester des scénarios reproductibles.                 |
| 23              | P14 | Moyenne           | 🔶 **À faire**  | UX                         | Retours d'état utiles au Maître du Jeu                   | Afficher des messages courts : aucun monstre sélectionné, initiatives non lancées, joueur incomplet, acteur marqué joué.         | Peut être livré par petites touches sans attendre les grosses fonctionnalités.                               |
| 24              | P21 | Moyenne           | 🔶 **À faire**  | Fonctionnalité / Suivi     | Journal de combat                                        | Journaliser début de round, changements de PV, acteur joué, conditions ajoutées ou retirées.                                     | Dépend du modèle, des commandes de combat et idéalement des PV en direct.                                    |
| 25              | P23 | Moyenne           | 🔶 **À faire**  | Préparation / Productivité | Templates de rencontre                                   | Sauvegarder des setups réutilisables : groupe de monstres, PV initiaux, règles actives et notes de préparation.                  | Dépend de P8 ou P22.                                                                                         |
| 26              | P24 | Moyenne           | 🟡 **Partiel**  | Règles                     | Gestion explicite des égalités d'initiative              | Règle optionnelle désactivée par défaut : départager les égalités par modificateur de DEX, avec joueurs à `0` pour l'instant.    | Gestion de la DEX des joueurs à confirmer ; réordonnancement manuel disponible en fallback.                  |
| 27              | P26 | Basse             | 🔶 **À faire**  | UX                         | Portraits optionnels                                     | Remplacer ou compléter les initiales par des portraits optionnels avec initiales en fallback systématique.                       | Attention à ne pas alourdir la préparation d'un combat.                                                      |
| 28              | P17 | Basse             | 🔶 **À faire**  | CSS / UX                   | Affichage desktop et tablette sur grands combats         | Optimiser les combats volumineux pour un écran 24 pouces 16:9, puis vérifier les écrans plus petits et tablettes.                | Mobile hors périmètre immédiat ; à valider avec les nouveaux contrôles de combat et les PV en direct.        |
| 29              | P27 | Plus tard         | ⏳ **Plus tard** | Fonctionnalité             | Notes par participant                                    | Stocker des notes temporaires pour un joueur, monstre ou effet en cours.                                                         | À reconsidérer après les besoins réels de suivi de combat.                                                   |
| 30              | P28 | Plus tard         | ⏳ **Plus tard** | Player-facing              | Vue joueur                                               | Afficher une vue simplifiée partageable avec les joueurs, sans détails réservés au MJ.                                           | À envisager après stabilisation de la vue MJ.                                                                |
| 31              | P29 | Plus tard         | ⏳ **Plus tard** | Player-facing              | Mode plein écran                                         | Afficher le tracker sur un écran secondaire ou une TV.                                                                           | Probablement lié à la future vue joueur.                                                                     |
| 32              | P30 | Plus tard         | ⏳ **Plus tard** | Fonctionnalité avancée     | Créatures temporaires et invocations                     | Gérer les créatures invoquées ou temporaires avec un cycle de vie plus court qu'un participant classique.                        | À traiter après clarification des conditions, rounds et templates de rencontre.                              |

## État fonctionnel synthétique

| Domaine       | Fonctionnalité                                                       | Statut                | Notes                                                                                   |
|---------------|----------------------------------------------------------------------|-----------------------|-----------------------------------------------------------------------------------------|
| Participants  | Gestion séparée des joueurs et monstres                              | Fait                  | Trois panneaux distincts : monstres, joueurs, ordre du tour.                            |
| Participants  | Ajout de joueurs                                                     | Fait                  | Ajout et suppression de lignes joueurs.                                                 |
| Participants  | Import XML de fiche personnage joueur                                | À faire               | Nouveau besoin : importer un XML externe pour préremplir une fiche joueur.              |
| Participants  | Création de plusieurs monstres                                       | Fait                  | Création de slots puis sélection depuis le catalogue.                                   |
| Participants  | Duplication rapide d'un monstre déjà choisi                          | Partiel               | Possible manuellement en choisissant le même monstre plusieurs fois, sans bouton dédié. |
| Monstres      | Catalogue prédéfini                                                  | Fait                  | Bestiaire généré et embarqué dans `bestiary.js`.                                        |
| Monstres      | Recherche ou filtre dans le catalogue                                | À faire prioritaire   | Ajouter 1 ou 2 filtres initiaux pour réduire les 428 monstres visibles.                 |
| Monstres      | Affichage des informations du monstre sélectionné                    | Partiel               | Les informations existent, mais l'affichage doit être amélioré et rendu plus utile.     |
| Initiative    | Jet automatique pour les monstres                                    | Fait                  | d20 + modificateur issu des données monstre.                                            |
| Initiative    | Son au jet d'initiative des monstres                                 | Fait                  | Module audio réutilisable avec deux sons de dés sélectionnés aléatoirement.             |
| Initiative    | Initiative joueurs                                                   | Fait                  | Saisie manuelle.                                                                        |
| Initiative    | Tri automatique par initiative                                       | Fait                  | Tri décroissant à la génération de l'ordre.                                             |
| Initiative    | Gestion configurable des égalités                                    | Partiel               | Règle optionnelle par DEX disponible, désactivée par défaut ; DEX joueurs à confirmer.  |
| Ordre du tour | Réordonnancement manuel                                              | Fait                  | Drag-and-drop souris dans les deux sens et déplacement clavier par flèches.             |
| Ordre du tour | Avancer au prochain acteur                                           | À faire               | Le statut joué existe, mais pas de commande "suivant".                                  |
| Ordre du tour | Acteur actif visible                                                 | Fait                  | Première carte non jouée mise en évidence.                                              |
| Ordre du tour | Tours terminés                                                       | Partiel               | Clic, Entrée ou Espace sur une carte pour basculer joué/non joué.                       |
| PV            | Affichage PV actuels et PV max                                       | Fait                  | Présent dans les formulaires et l'ordre du tour.                                        |
| PV            | Modification manuelle des PV                                         | Partiel               | Possible avant génération, pas encore directement dans l'ordre du tour.                 |
| PV            | Application rapide dégâts/soins                                      | À faire               | Pas encore de saisie `-7` ou `+5`.                                                      |
| PV            | Marquer inconscient ou mort                                          | À faire               | Aucun statut dédié.                                                                     |
| CA            | Affichage de la CA                                                   | Fait                  | Présent pour joueurs, monstres et ordre du tour.                                        |
| Règles        | Sélecteur de règles                                                  | Fait - en observation | Popup de règles maison dans le panneau ordre du tour.                                   |
| Règles        | Durées automatiques de conditions                                    | À faire               | Dépend de la gestion des conditions.                                                    |
| États         | Conditions visuelles                                                 | À faire               | Aucun système de conditions.                                                            |
| États         | Marqueurs concentration, réaction, inspiration, avantage/désavantage | À faire               | Aucun marqueur binaire.                                                                 |
| Affichage     | Initiales des participants                                           | Fait                  | Initiale calculée depuis le nom de l'acteur.                                            |
| Affichage     | Portraits                                                            | À faire               | Seules les initiales existent.                                                          |
| Affichage     | Couleurs par type d'acteur                                           | À faire prioritaire   | Prochaine priorité après les aides monstres et l'import XML.                            |
| Persistance   | Sauvegarde locale                                                    | À faire               | Aucun `localStorage` pour la rencontre.                                                 |
| Persistance   | Import/export JSON                                                   | À faire               | Aucun format d'échange.                                                                 |
| Préparation   | Templates de rencontre                                               | À faire               | Aucun preset de rencontre sauvegardable.                                                |
| Suivi         | Journal de combat                                                    | À faire               | Aucun historique des rounds ou changements de PV.                                       |
| Suivi         | Notes par participant                                                | Plus tard             | Non nécessaire pour stabiliser le MVP.                                                  |
| Vues          | Vue joueur                                                           | Plus tard             | Aucune vue simplifiée publique.                                                         |
| Vues          | Mode plein écran                                                     | Plus tard             | Aucun mode écran secondaire/TV.                                                         |
| Invocation    | Créatures temporaires et invocations                                 | Plus tard             | Pas de modèle dédié.                                                                    |
