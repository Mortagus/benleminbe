# Description technique - DnD Initiative Tracker

Date de mise à jour : 2026-06-06

Ce document décrit l'état actuel du projet `DnD Initiative Tracker` dans le site personnel. Il sert de point d'entrée technique : objectif du module, emplacement des fichiers, architecture actuelle et fonctionnement observé.

Les constats d'audit, les améliorations réalisées et les fonctionnalités à venir sont suivis dans [dnd-initiative-tracker-backlog.md](../en-cours/dnd-initiative-tracker-backlog.md). Les contrats techniques détaillés sont documentés dans [dnd-bestiary-pipeline.md](dnd-bestiary-pipeline.md) et [dnd-dom-contracts.md](dnd-dom-contracts.md).

## Description du projet

`DnD Initiative Tracker` est un outil de laboratoire intégré au site Symfony principal. Il aide un Maître du Jeu à préparer une rencontre D&D, ajouter les personnages joueurs, sélectionner les monstres, lancer l'initiative des monstres, générer un ordre de tour exploitable pendant un combat, modifier rapidement les PV en combat, suivre les conditions et les états de combat, puis piloter explicitement le combat round par round.

Le module reste une application front-end légère en JavaScript vanilla, rendue par Twig et branchée via l'importmap Symfony. La rencontre vit encore dans le navigateur pendant la session courante, avec une persistance locale via `localStorage`, et l'import XML joueur passe déjà par un contrôleur et un service PHP dédiés.

Fonctionnalités actuellement présentes :

- création d'une liste de slots monstres ;
- sélection des monstres depuis un catalogue embarqué ;
- calcul automatique de l'initiative des monstres ;
- saisie manuelle des joueurs, PV, CA et initiative ;
- validation des entrées avant les actions principales ;
- génération d'un ordre du tour fusionnant monstres et joueurs ;
- tri automatique par initiative ;
- réordonnancement manuel par glisser-déposer ;
- suivi des tours joués par clic sur une carte ;
- commandes explicites de pilotage du combat : acteur suivant, nouveau round, réinitialisation des tours de ce round et remise à zéro globale de la rencontre ;
- affichage lisible du round courant, de l'acteur actif ou de l'état round terminé ;
- mise en évidence du prochain acteur à jouer ;
- affichage des initiales, PV, CA et initiative dans l'ordre du tour ;
- modification rapide des PV directement depuis l'ordre du tour ;
- suivi visuel des conditions et des états de combat sur les cartes de l'ordre du tour ;
- ajout, retrait manuel et durée en rounds pour les conditions temporaires ;
- activation ou désactivation de règles maison via une popup de règles ;
- persistance locale de la rencontre et restauration manuelle du snapshot.
- import XML d'une fiche joueur avec préremplissage des champs visibles, puis consultation à la demande de la fiche complète via une modale dédiée.

## Emplacement des fichiers

### Intégration Symfony

- Contrôleur : [src/Public/Controller/LabController.php](/var/www/projects/benleminbe/src/Public/Controller/LabController.php:1)
- Route publique : `/lab/dnd-initiative`
- Nom de route : `app_lab_dnd_initiative`
- Route d'import XML : `/lab/dnd-initiative/import-player`
- La réponse d'import est affichée dans une modale de fiche simple, sous forme de tableau clé-valeur, pour ne pas alourdir la ligne joueur.
- Entrée importmap : [importmap.php](/var/www/projects/benleminbe/importmap.php:19)

`LabController` conserve les routes publiques du lab, y compris celles du tracker DnD. La logique de parsing reste isolée dans un service dédié.

### Templates Twig

- Page principale : [templates/lab/dnd/initiative_tracker.html.twig](/var/www/projects/benleminbe/templates/lab/dnd/initiative_tracker.html.twig:1)
- Panneau monstres : [templates/lab/dnd/\_monsters_panel.html.twig](/var/www/projects/benleminbe/templates/lab/dnd/_monsters_panel.html.twig:1)
- Panneau joueurs : [templates/lab/dnd/\_players_panel.html.twig](/var/www/projects/benleminbe/templates/lab/dnd/_players_panel.html.twig:1)
- Panneau ordre du tour : [templates/lab/dnd/\_turn_order_panel.html.twig](/var/www/projects/benleminbe/templates/lab/dnd/_turn_order_panel.html.twig:1)
- Popup de règles : [templates/lab/dnd/\_rules_panels.html.twig](/var/www/projects/benleminbe/templates/lab/dnd/_rules_panels.html.twig:1)

La page principale assemble trois panneaux : monstres, joueurs et ordre du tour. Les templates utilisent des balises `<template>` pour cloner dynamiquement les lignes de formulaire et les cartes d'ordre du tour côté navigateur.

### Modules JavaScript

- Orchestration globale : [assets/scripts/lab/dnd/dnd_initiative.js](/var/www/projects/benleminbe/assets/scripts/lab/dnd/dnd_initiative.js:1)
- Modèle de rencontre : [assets/scripts/lab/dnd/encounter-state.js](/var/www/projects/benleminbe/assets/scripts/lab/dnd/encounter-state.js:1)
- Gestion des monstres : [assets/scripts/lab/dnd/monsters.js](/var/www/projects/benleminbe/assets/scripts/lab/dnd/monsters.js:1)
- Gestion des joueurs : [assets/scripts/lab/dnd/players.js](/var/www/projects/benleminbe/assets/scripts/lab/dnd/players.js:1)
- Calculs d'initiative : [assets/scripts/lab/dnd/initiative.js](/var/www/projects/benleminbe/assets/scripts/lab/dnd/initiative.js:1)
- Modification rapide des PV : [assets/scripts/lab/dnd/hit-points.js](/var/www/projects/benleminbe/assets/scripts/lab/dnd/hit-points.js:1)
- Conditions et états de combat : [assets/scripts/lab/dnd/conditions.js](/var/www/projects/benleminbe/assets/scripts/lab/dnd/conditions.js:1)
- Persistance locale : [assets/scripts/lab/dnd/persistence.js](/var/www/projects/benleminbe/assets/scripts/lab/dnd/persistence.js:1)
- Règles maison configurables : [assets/scripts/lab/dnd/rules.js](/var/www/projects/benleminbe/assets/scripts/lab/dnd/rules.js:1)
- Construction, rendu et pilotage de l'ordre du tour : [assets/scripts/lab/dnd/turn-order.js](/var/www/projects/benleminbe/assets/scripts/lab/dnd/turn-order.js:1)
- Validation des entrées : [assets/scripts/lab/dnd/validation.js](/var/www/projects/benleminbe/assets/scripts/lab/dnd/validation.js:1)
- Bestiaire de monstres embarqué : [assets/scripts/lab/dnd/bestiary.js](/var/www/projects/benleminbe/assets/scripts/lab/dnd/bestiary.js:1)

Responsabilités principales :

- `dnd_initiative.js` crée l'état central, initialise les panneaux et coordonne la génération de l'ordre du tour.
- `encounter-state.js` conserve le modèle de rencontre et expose les mutations métier : monstres, joueurs, conditions, états de combat, règles, ordre du tour, round et acteur actif.
- `monsters.js` expose `MonstersPanel`, qui initialise le panneau monstres, valide ses entrées, rend la liste et remonte les interactions utilisateur vers le modèle.
- `players.js` expose `PlayersPanel`, qui initialise le panneau joueurs, crée les lignes joueurs, valide les entrées et synchronise le formulaire avec le modèle.
- `initiative.js` contient les helpers liés au d20, aux modificateurs et à l'affichage de l'initiative.
- `hit-points.js` isole le parsing et le descriptif des saisies rapides de PV.
- `conditions.js` centralise le catalogue des conditions, les états de combat, le parsing des payloads et les helpers de synchronisation.
- `persistence.js` gère le snapshot local, l'autosave et la restauration de la rencontre.
- `rules.js` expose `RulesPanel`, qui gère la popup de règles et remonte les changements vers le modèle de rencontre.
- `turn-order.js` expose `TurnOrderPanel`, qui initialise le panneau ordre du tour, affiche les erreurs globales, rend les cartes de combat et pilote les commandes explicites de combat.
- `validation.js` centralise les validations des champs monstres, joueurs et rencontre.

### Styles

- Point d'entrée CSS du module : [assets/styles/lab/dnd/lab_dnd_initiative.css](/var/www/projects/benleminbe/assets/styles/lab/dnd/lab_dnd_initiative.css:1)
- Styles de page : [assets/styles/lab/dnd/page.css](/var/www/projects/benleminbe/assets/styles/lab/dnd/page.css:1)
- Styles de panneaux : [assets/styles/lab/dnd/panels.css](/var/www/projects/benleminbe/assets/styles/lab/dnd/panels.css:1)
- Barre d'actions : [assets/styles/lab/dnd/toolbar.css](/var/www/projects/benleminbe/assets/styles/lab/dnd/toolbar.css:1)
- Validation : [assets/styles/lab/dnd/validation.css](/var/www/projects/benleminbe/assets/styles/lab/dnd/validation.css:1)
- Popup de règles : [assets/styles/lab/dnd/rules.css](/var/www/projects/benleminbe/assets/styles/lab/dnd/rules.css:1)
- Monstres : [assets/styles/lab/dnd/monsters.css](/var/www/projects/benleminbe/assets/styles/lab/dnd/monsters.css:1)
- Joueurs : [assets/styles/lab/dnd/players.css](/var/www/projects/benleminbe/assets/styles/lab/dnd/players.css:1)
- Ordre du tour : [assets/styles/lab/dnd/turn-order.css](/var/www/projects/benleminbe/assets/styles/lab/dnd/turn-order.css:1)
- Icônes : [assets/styles/lab/dnd/icons.css](/var/www/projects/benleminbe/assets/styles/lab/dnd/icons.css:1)

Le CSS du module est importé par l'entrée JavaScript `dnd_initiative`. Les règles sont majoritairement scopées sous `.dnd-initiative-page`.

### Données monstres et outillage

- Documentation du pipeline : [dnd-bestiary-pipeline.md](dnd-bestiary-pipeline.md)
- Générateur principal : [tools/dnd/complete_monster_extractor.php](/var/www/projects/benleminbe/tools/dnd/complete_monster_extractor.php:1)
- Source HTML conservée : [tools/dnd/monsters-source.html](/var/www/projects/benleminbe/tools/dnd/monsters-source.html:1)
- Test de contrat : [tools/dnd/validate_bestiary.php](/var/www/projects/benleminbe/tools/dnd/validate_bestiary.php:1)
- Bestiaire embarqué côté front : [assets/scripts/lab/dnd/bestiary.js](/var/www/projects/benleminbe/assets/scripts/lab/dnd/bestiary.js:1)
- Fixture bestiaire pour les tests JS : [tests/fixtures/dnd/bestiary-sample.js](/var/www/projects/benleminbe/tests/fixtures/dnd/bestiary-sample.js:1)

Le navigateur consomme aujourd'hui un bestiaire JavaScript généré et embarqué dans le bundle de la page. Cette approche reste simple pour le lab avec le catalogue actuel. Le pipeline de génération et les critères de chargement futur sont documentés dans [dnd-bestiary-pipeline.md](dnd-bestiary-pipeline.md).

### Nomenclature d'interface

Les libellés visibles restent courts pour préserver la lisibilité de l'outil pendant une session de jeu.

- `monstre` : créature choisie depuis le bestiaire ;
- `joueur` : personnage joueur saisi manuellement ;
- `acteur` : terme générique pour une entrée de l'ordre du tour ;
- `ordre du tour` : liste de combat générée depuis les initiatives ;
- `round` : cycle de combat courant piloté explicitement dans l'interface ;
- `initiative`, `CA`, `PV actuels` et `PV max` : libellés de statistiques ;
- `à jouer` et `joué` : états d'un acteur dans l'ordre du tour.

### Tests

- Configuration Vitest : [vitest.config.mjs](/var/www/projects/benleminbe/vitest.config.mjs:1)
- Tests du modèle de rencontre : [tests/js/lab/dnd/encounter-state.test.js](/var/www/projects/benleminbe/tests/js/lab/dnd/encounter-state.test.js:1)

Les tests JavaScript se lancent avec `npm run test:js` ou `composer js:test`. Ils couvrent aujourd'hui la création de slots monstres, la sélection depuis le bestiaire injecté, les jets d'initiative, le tri, les règles maison, l'état joué/non joué, l'acteur actif, le réordonnancement manuel, les commandes explicites de combat, les PV modifiables en combat, les conditions et états de combat, et la persistance locale.

## Architecture actuelle

L'architecture repose sur une séparation simple entre rendu Twig, modules JavaScript et CSS par zone fonctionnelle.

Flux de données principal :

1. Twig rend la structure initiale et les templates DOM.
2. `dnd_initiative.js` crée un état de rencontre via `new EncounterState()` et initialise les panneaux.
3. Les panneaux DOM possèdent leurs éléments, valident leurs entrées locales et remontent les interactions utilisateur vers le modèle.
4. `encounter-state.js` applique les mutations métier : slots monstres, sélection, PV, conditions, états de combat, règles, joueurs, ordre du tour, round courant, commandes de combat et synchronisation des copies déjà présentes dans l'ordre du tour. Le bestiaire peut être injecté à la création de l'état pour tester le modèle avec une fixture légère.
5. `validation.js` vérifie les entrées avant la création de la liste et la génération de l'ordre du tour.
6. `conditions.js` normalise les conditions temporaires, les états vitaux et les payloads associés, puis `encounter-state.js` synchronise ces données dans l'ordre du tour.
7. `TurnOrderPanel`, `MonstersPanel`, `PlayersPanel` et `RulesPanel` rendent l'état ou les contrôles, sans conserver l'état métier principal.

Sources de vérité actuelles :

- état de rencontre : instance de `EncounterState` créée dans `dnd_initiative.js` ;
- monstres : propriété `monsters` de l'état de rencontre ;
- joueurs : propriété `players` de l'état de rencontre, synchronisée depuis le formulaire joueur ;
- règles actives : propriété `rules` de l'état de rencontre ;
- ordre du tour : propriété `turnOrder` de l'état de rencontre ;
- round courant et acteur actif : propriétés `currentRound` et `activeTurnId` de l'état de rencontre.
- conditions et états de combat : propriétés `conditions` et `combatStatus` sur chaque acteur, synchronisées par `EncounterState`.
- snapshot local : `persistence.js` et `localStorage`, qui restaurent ou réinitialisent la rencontre selon le dernier état sauvegardé.

Le formulaire joueur reste encore un buffer DOM éditable, mais les données utilisées pour générer l'ordre du tour sont synchronisées dans le modèle de rencontre avant calcul.

## Fonctionnement actuel

### Flux principal

1. L'utilisateur indique le nombre de monstres à préparer.
2. L'outil valide ce nombre et crée les slots de monstres.
3. L'utilisateur sélectionne un type de monstre dans chaque slot utile.
4. L'outil active le bouton d'initiative dès qu'au moins un monstre est sélectionné.
5. L'utilisateur lance l'initiative des monstres.
6. L'outil calcule un d20 plus le modificateur d'initiative dérivé de la dextérité du monstre.
7. L'utilisateur ajoute ou retire des joueurs.
8. L'utilisateur saisit pour chaque joueur son nom, sa CA, ses PV actuels, ses PV max et son initiative.
9. L'utilisateur peut ouvrir la popup de règles et activer ou désactiver les règles maison disponibles.
10. L'utilisateur génère l'ordre du tour.
11. L'outil valide les monstres, les joueurs et la présence d'au moins un acteur exploitable.
12. L'outil fusionne monstres et joueurs, applique les règles actives, trie par initiative décroissante et rend les cartes de tour.
13. Le panneau d'ordre du tour expose les commandes explicites de combat et l'état du round courant.
14. L'utilisateur peut modifier rapidement les PV d'un acteur depuis l'ordre du tour avec une saisie compacte.
15. L'utilisateur peut ajouter, retirer et suivre des conditions, ainsi que voir l'état vital de chaque acteur.
16. `Acteur suivant` marque l'acteur courant comme joué puis avance vers le prochain acteur non joué, sans lancer automatiquement un nouveau round.
17. `Nouveau round` incrémente le round courant, remet les tours à joué à zéro, décrémente les conditions temporaires et conserve l'ordre manuel.
18. `Réinitialiser les tours de ce round` remet les tours du round courant à non joué sans changer le round ni les conditions.
19. `Réinitialiser la rencontre` vide l'état de combat global et neutralise l'ancienne sauvegarde locale.
20. L'utilisateur peut cliquer sur une carte pour la marquer comme jouée ou non jouée.
21. L'utilisateur peut réordonner les cartes par glisser-déposer.

### Règles maison configurables

Trois règles sont actuellement pilotables depuis la popup "Règles" :

- ignorer les acteurs dont l'initiative finale est inférieure ou égale à `1` ;
- accorder un tour supplémentaire à un acteur dont l'initiative finale est exactement `20` ;
- départager les égalités d'initiative par modificateur de DEX.

La règle de départage par DEX est désactivée par défaut. Les monstres utilisent le modificateur de DEX extrait du bestiaire. Les joueurs importés récupèrent maintenant leur modificateur d'initiative depuis la fiche importée; les joueurs saisis à la main restent à `0` en attendant la saisie dédiée.

Ces règles sont considérées comme des règles maison volontaires. Elles ne sont pas des bugs connus, mais elles restent candidates à une clarification ou à une extension si plusieurs jeux de règles doivent être supportés.

### Données persistées

La rencontre est persistée localement via `localStorage`.

Un rechargement de page peut restaurer :

- la liste des monstres ;
- les sélections de monstres ;
- les jets d'initiative ;
- les joueurs saisis ;
- les PV modifiés ;
- l'ordre du tour ;
- les statuts joué/non joué ;
- le round courant et l'acteur actif ;
- les règles actives.

Un reset de rencontre sauvegarde un état vide pour empêcher la restauration de l'ancienne rencontre après reload.
