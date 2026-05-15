# Description technique - DnD Initiative Tracker

Date de mise à jour : 2026-05-15

Ce document décrit l'état actuel du projet `DnD Initiative Tracker` dans le site personnel. Il sert de point d'entrée technique : objectif du module, emplacement des fichiers, architecture actuelle et fonctionnement observé.

Les constats d'audit, les améliorations réalisées et les fonctionnalités à venir sont suivis dans [dnd-initiative-tracker-backlog.md](/var/www/projects/benleminbe/docs/lab/dnd-initiative-tracker-backlog.md:1).

## Description du projet

`DnD Initiative Tracker` est un outil de laboratoire intégré au site Symfony principal. Il aide un Maître du Jeu à préparer une rencontre D&D, ajouter les personnages joueurs, sélectionner les monstres, lancer l'initiative des monstres et générer un ordre de tour exploitable pendant un combat.

Le module est actuellement une application front-end légère en JavaScript vanilla, rendue par Twig et branchée via l'importmap Symfony. Il ne possède pas encore de backend métier dédié : les données de rencontre vivent dans le navigateur pendant la session courante.

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
- mise en évidence du prochain acteur à jouer ;
- affichage des initiales, PV, CA et initiative dans l'ordre du tour ;
- activation ou désactivation de règles maison via une popup de règles.

## Emplacement des fichiers

### Intégration Symfony

- Contrôleur : [src/Public/Controller/LabController.php](/var/www/projects/benleminbe/src/Public/Controller/LabController.php:1)
- Route publique : `/lab/dnd-initiative`
- Nom de route : `app_lab_dnd_initiative`
- Entrée importmap : [importmap.php](/var/www/projects/benleminbe/importmap.php:19)

Le contrôleur expose uniquement la page du lab. Il ne porte pas de logique métier D&D.

### Templates Twig

- Page principale : [templates/lab/dnd/initiative_tracker.html.twig](/var/www/projects/benleminbe/templates/lab/dnd/initiative_tracker.html.twig:1)
- Panneau monstres : [templates/lab/dnd/_monsters_panel.html.twig](/var/www/projects/benleminbe/templates/lab/dnd/_monsters_panel.html.twig:1)
- Panneau joueurs : [templates/lab/dnd/_players_panel.html.twig](/var/www/projects/benleminbe/templates/lab/dnd/_players_panel.html.twig:1)
- Panneau ordre du tour : [templates/lab/dnd/_turn_order_panel.html.twig](/var/www/projects/benleminbe/templates/lab/dnd/_turn_order_panel.html.twig:1)
- Popup de règles : [templates/lab/dnd/_rules_panels.html.twig](/var/www/projects/benleminbe/templates/lab/dnd/_rules_panels.html.twig:1)

La page principale assemble trois panneaux : monstres, joueurs et ordre du tour. Les templates utilisent des balises `<template>` pour cloner dynamiquement les lignes de formulaire et les cartes d'ordre du tour côté navigateur.

### Modules JavaScript

- Orchestration globale : [assets/scripts/lab/dnd/dnd_initiative.js](/var/www/projects/benleminbe/assets/scripts/lab/dnd/dnd_initiative.js:1)
- Modèle de rencontre : [assets/scripts/lab/dnd/encounter-state.js](/var/www/projects/benleminbe/assets/scripts/lab/dnd/encounter-state.js:1)
- Gestion des monstres : [assets/scripts/lab/dnd/monsters.js](/var/www/projects/benleminbe/assets/scripts/lab/dnd/monsters.js:1)
- Gestion des joueurs : [assets/scripts/lab/dnd/players.js](/var/www/projects/benleminbe/assets/scripts/lab/dnd/players.js:1)
- Calculs d'initiative : [assets/scripts/lab/dnd/initiative.js](/var/www/projects/benleminbe/assets/scripts/lab/dnd/initiative.js:1)
- Règles maison configurables : [assets/scripts/lab/dnd/rules.js](/var/www/projects/benleminbe/assets/scripts/lab/dnd/rules.js:1)
- Construction et rendu de l'ordre du tour : [assets/scripts/lab/dnd/turn-order.js](/var/www/projects/benleminbe/assets/scripts/lab/dnd/turn-order.js:1)
- Validation des entrées : [assets/scripts/lab/dnd/validation.js](/var/www/projects/benleminbe/assets/scripts/lab/dnd/validation.js:1)
- Bestiaire de monstres embarqué : [assets/scripts/lab/dnd/bestiary.js](/var/www/projects/benleminbe/assets/scripts/lab/dnd/bestiary.js:1)

Responsabilités principales :

- `dnd_initiative.js` crée l'état central, initialise les panneaux et coordonne la génération de l'ordre du tour.
- `encounter-state.js` conserve le modèle de rencontre et expose les mutations métier : monstres, joueurs, règles, ordre du tour, round et acteur actif.
- `monsters.js` initialise le panneau monstres, valide ses entrées, rend la liste et remonte les interactions utilisateur vers le modèle.
- `players.js` initialise le panneau joueurs, crée les lignes joueurs, valide les entrées et synchronise le formulaire avec le modèle.
- `initiative.js` contient les helpers liés au d20, aux modificateurs et à l'affichage de l'initiative.
- `rules.js` gère la popup de règles et remonte les changements vers le modèle de rencontre.
- `turn-order.js` initialise le panneau ordre du tour, affiche les erreurs globales et rend les cartes de combat.
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

- Documentation du pipeline : [docs/lab/dnd-bestiary-pipeline.md](/var/www/projects/benleminbe/docs/lab/dnd-bestiary-pipeline.md:1)
- Générateur principal : [tools/dnd/complete_monster_extractor.php](/var/www/projects/benleminbe/tools/dnd/complete_monster_extractor.php:1)
- Source HTML conservée : [tools/dnd/monsters-source.html](/var/www/projects/benleminbe/tools/dnd/monsters-source.html:1)
- Test de contrat : [tools/dnd/validate_bestiary.php](/var/www/projects/benleminbe/tools/dnd/validate_bestiary.php:1)
- Bestiaire embarqué côté front : [assets/scripts/lab/dnd/bestiary.js](/var/www/projects/benleminbe/assets/scripts/lab/dnd/bestiary.js:1)
- Fixture bestiaire pour les tests JS : [tests/fixtures/dnd/bestiary-sample.js](/var/www/projects/benleminbe/tests/fixtures/dnd/bestiary-sample.js:1)

Le navigateur consomme aujourd'hui un bestiaire JavaScript généré et embarqué dans le bundle de la page. Cette approche reste simple pour le lab avec le catalogue actuel. Le pipeline de génération et les critères de chargement futur sont documentés dans [dnd-bestiary-pipeline.md](/var/www/projects/benleminbe/docs/lab/dnd-bestiary-pipeline.md:1).

### Nomenclature d'interface

Les libellés visibles restent courts pour préserver la lisibilité de l'outil pendant une session de jeu.

- `monstre` : créature choisie depuis le bestiaire ;
- `joueur` : personnage joueur saisi manuellement ;
- `acteur` : terme générique pour une entrée de l'ordre du tour ;
- `ordre du tour` : liste de combat générée depuis les initiatives ;
- `initiative`, `CA`, `PV actuels` et `PV max` : libellés de statistiques ;
- `à jouer` et `joué` : états d'un acteur dans l'ordre du tour.

### Tests

- Configuration Vitest : [vitest.config.mjs](/var/www/projects/benleminbe/vitest.config.mjs:1)
- Tests du modèle de rencontre : [tests/js/lab/dnd/encounter-state.test.js](/var/www/projects/benleminbe/tests/js/lab/dnd/encounter-state.test.js:1)

Les tests JavaScript se lancent avec `npm run test:js` ou `composer js:test`. Ils couvrent aujourd'hui la création de slots monstres, la sélection depuis le bestiaire injecté, les jets d'initiative, le tri, les règles maison, l'état joué/non joué, l'acteur actif et le réordonnancement manuel.

## Architecture actuelle

L'architecture repose sur une séparation simple entre rendu Twig, modules JavaScript et CSS par zone fonctionnelle.

Flux de données principal :

1. Twig rend la structure initiale et les templates DOM.
2. `dnd_initiative.js` crée un état de rencontre via `createEncounterState()` et initialise les panneaux.
3. Les panneaux DOM possèdent leurs éléments, valident leurs entrées locales et remontent les interactions utilisateur vers le modèle.
4. `encounter-state.js` applique les mutations métier : slots monstres, sélection, PV, règles, joueurs et ordre du tour. Le bestiaire peut être injecté à la création de l'état pour tester le modèle avec une fixture légère.
5. `validation.js` vérifie les entrées avant la création de la liste et la génération de l'ordre du tour.
6. `turn-order.js`, `monsters.js`, `players.js` et `rules.js` rendent l'état ou les contrôles, sans conserver l'état métier principal.

Sources de vérité actuelles :

- état de rencontre : objet créé par `createEncounterState()` dans `encounter-state.js` ;
- monstres : propriété `monsters` de l'état de rencontre ;
- joueurs : propriété `players` de l'état de rencontre, synchronisée depuis le formulaire joueur ;
- règles actives : propriété `rules` de l'état de rencontre ;
- ordre du tour : propriété `turnOrder` de l'état de rencontre ;
- round courant et acteur actif : propriétés `currentRound` et `activeTurnId` de l'état de rencontre.

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
13. L'utilisateur peut cliquer sur une carte pour la marquer comme jouée ou non jouée.
14. L'utilisateur peut réordonner les cartes par glisser-déposer.

### Règles maison configurables

Deux règles sont actuellement pilotables depuis la popup "Règles" :

- ignorer les acteurs dont l'initiative finale est inférieure ou égale à `1` ;
- accorder un tour supplémentaire à un acteur dont l'initiative finale est exactement `20`.

Ces règles sont considérées comme des règles maison volontaires. Elles ne sont pas des bugs connus, mais elles restent candidates à une clarification ou à une extension si plusieurs jeux de règles doivent être supportés.

### Données persistées

Aucune donnée de rencontre n'est persistée côté serveur ou dans le navigateur.

Un rechargement de page efface :

- la liste des monstres ;
- les sélections de monstres ;
- les jets d'initiative ;
- les joueurs saisis ;
- les PV modifiés ;
- l'ordre du tour ;
- les statuts joué/non joué.

Les règles actives sont également conservées uniquement en mémoire pendant la session courante.
