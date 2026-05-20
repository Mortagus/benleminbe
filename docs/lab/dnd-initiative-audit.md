# Audit technique - Dnd Initiative

Date de l'audit: 2026-05-09

> Historical note, 2026-05-20:
> This document is kept as an older audit snapshot. Some route names, template paths and implementation details have changed since it was written. For the current consolidated architecture priorities, see `docs/site-architecture-audit-phase-6.md` and `docs/site-architecture-audit-phase-8.md`. For the current monster catalog pipeline, see `docs/lab/dnd-monster-catalog.md`.

## Résumé

`Dnd Initiative` est aujourd'hui un prototype front-end intégré au site Symfony principal via une route dédiée. L'ensemble est lisible, relativement simple à parcourir et déjà assez modulaire pour un prototype. La séparation Twig / JavaScript / CSS est claire, et le flux d'usage principal est compréhensible sans backend métier.

Le niveau de qualité technique est cependant celui d'un prototype avancé, pas encore celui d'un outil stabilisé. La logique métier repose sur des états globaux JavaScript, il n'y a pas de tests automatisés, certaines règles d'initiative s'écartent fortement du comportement attendu dans D&D, et une partie du rendu HTML injecte directement des données utilisateur.

Appréciation globale:

- Lisibilité: bonne
- Modularité initiale: bonne
- Robustesse: moyenne à faible
- Sécurité front-end: faible
- Maintenabilité long terme: moyenne
- Maturité produit: prototype

## Composants actuels

### Intégration Symfony

- Route: [src/Public/Controller/LabController.php](/var/www/projects/benleminbe/src/Public/Controller/LabController.php:12)
- Vue principale: [templates/lab/dnd/initiative_dnd.html.twig](/var/www/projects/benleminbe/templates/lab/dnd/initiative_dnd.html.twig:1)
- Entrée JavaScript importmap: [importmap.php](/var/www/projects/benleminbe/importmap.php:19)

Le contrôleur ne porte aucune logique métier: il expose simplement la page `/lab/initiative-dnd` et délègue tout le comportement à Twig et au JavaScript.

### Structure Twig

La page principale assemble trois panneaux:

- Gestion des monstres: [templates/lab/dnd/_monsters_panel.html.twig](/var/www/projects/benleminbe/templates/lab/dnd/_monsters_panel.html.twig:1)
- Gestion des joueurs: [templates/lab/dnd/_players_panel.html.twig](/var/www/projects/benleminbe/templates/lab/dnd/_players_panel.html.twig:1)
- Ordre du tour: [templates/lab/dnd/_turn_order_panel.html.twig](/var/www/projects/benleminbe/templates/lab/dnd/_turn_order_panel.html.twig:1)

Les templates utilisent des balises `<template>` pour cloner dynamiquement les lignes joueurs et monstres côté navigateur. C'est une approche cohérente pour un prototype sans framework front-end.

### Modules JavaScript

- Orchestration globale: [assets/scripts/lab/dnd/dnd_initiative.js](/var/www/projects/benleminbe/assets/scripts/lab/dnd/dnd_initiative.js:1)
- Gestion des monstres: [assets/scripts/lab/dnd/monsters.js](/var/www/projects/benleminbe/assets/scripts/lab/dnd/monsters.js:1)
- Gestion des joueurs: [assets/scripts/lab/dnd/players.js](/var/www/projects/benleminbe/assets/scripts/lab/dnd/players.js:1)
- Règles d'initiative: [assets/scripts/lab/dnd/initiative.js](/var/www/projects/benleminbe/assets/scripts/lab/dnd/initiative.js:1)
- Construction et rendu de l'ordre du tour: [assets/scripts/lab/dnd/turn-order.js](/var/www/projects/benleminbe/assets/scripts/lab/dnd/turn-order.js:1)
- Référentiel de monstres: [assets/scripts/lab/dnd/monster_classes.js](/var/www/projects/benleminbe/assets/scripts/lab/dnd/monster_classes.js:1)

Responsabilités observées:

- `dnd_initiative.js` relie les boutons et les panneaux, mais centralise aussi les dépendances DOM.
- `monsters.js` stocke l'état global des monstres, génère les slots, applique les sélections et calcule les initiatives.
- `players.js` extrait les données du DOM vers des objets acteurs.
- `initiative.js` contient les règles de calcul simplifiées.
- `turn-order.js` construit la liste de combat, gère le marquage "joué" et le drag-and-drop.

### Styles

- Point d'entrée D&D: [assets/styles/lab/dnd/lab_dnd_initiative.css](/var/www/projects/benleminbe/assets/styles/lab/dnd/lab_dnd_initiative.css:1)
- Import global dans l'application: [assets/styles/app.css](/var/www/projects/benleminbe/assets/styles/app.css:14)

Les styles sont bien découpés par sous-zone (`page`, `panels`, `toolbar`, `monsters`, `players`, `turn-order`, `icons`). En revanche, ils sont chargés globalement avec le reste du site, même hors de la page D&D.

### Pipeline de données monstres

- Générateur principal: [tools/dnd/complete_monster_extractor.php](/var/www/projects/benleminbe/tools/dnd/complete_monster_extractor.php:1)
- Ancien extracteur: [tools/dnd/extract_monsters.php](/var/www/projects/benleminbe/tools/dnd/extract_monsters.php:1)
- Données générées: [tools/dnd/monsters.generated.json](/var/www/projects/benleminbe/tools/dnd/monsters.generated.json:1)
- Données embarquées côté front: [assets/scripts/lab/dnd/monster_classes.js](/var/www/projects/benleminbe/assets/scripts/lab/dnd/monster_classes.js:1)

Le fichier `monster_classes.js` pèse environ `418110` octets et embarque directement l'intégralité du catalogue dans le JavaScript du navigateur. C'est acceptable pour un prototype local, mais coûteux à long terme.

## Fonctionnement actuel

### Flux principal

1. L'utilisateur choisit un nombre de monstres.
2. Le prototype crée des slots vides.
3. L'utilisateur assigne un type de monstre à chaque slot.
4. Le prototype lance les initiatives des monstres.
5. L'utilisateur ajoute manuellement les joueurs et leurs statistiques.
6. Le prototype génère un ordre de tour fusionnant monstres et joueurs.
7. Le tour de table peut être réordonné manuellement par glisser-déposer.

### Données réellement persistées

Aucune donnée n'est persistée côté serveur ou dans le navigateur. Toute la session de combat vit en mémoire dans l'onglet courant:

- état global `monsters` dans [assets/scripts/lab/dnd/monsters.js](/var/www/projects/benleminbe/assets/scripts/lab/dnd/monsters.js:4)
- état global `roundOrder` dans [assets/scripts/lab/dnd/turn-order.js](/var/www/projects/benleminbe/assets/scripts/lab/dnd/turn-order.js:3)

Un rechargement de page efface donc complètement la rencontre.

## Points forts

- Découpage initial clair entre présentation, logique d'orchestration et logique métier.
- Prototype rapidement compréhensible, sans surcouche front-end inutile.
- Templates HTML simples et efficaces pour le clonage dynamique.
- CSS segmenté de manière saine.
- Pipeline d'extraction de monstres déjà en place, ce qui évite la saisie manuelle.
- Fonctionnalités visibles déjà utiles: création de monstres, ajout de joueurs, génération de l'ordre, drag-and-drop.

## Findings

### Critique

1. Injection HTML possible depuis le nom des joueurs dans le rendu de l'ordre du tour.

Référence: [assets/scripts/lab/dnd/turn-order.js](/var/www/projects/benleminbe/assets/scripts/lab/dnd/turn-order.js:55)

Le code utilise `innerHTML` avec `actor.name`, alors que ce nom provient directement d'un champ texte libre. Un nom comme `<img src=x onerror=alert(1)>` serait injecté dans le DOM. Même si l'outil est personnel, c'est une vraie faiblesse de sécurité et une mauvaise base pour une future mise en ligne publique.

2. Les personnages avec une initiative inférieure ou égale à `1` sont exclus du tour.

Références:

- [assets/scripts/lab/dnd/initiative.js](/var/www/projects/benleminbe/assets/scripts/lab/dnd/initiative.js:33)
- [assets/scripts/lab/dnd/turn-order.js](/var/www/projects/benleminbe/assets/scripts/lab/dnd/turn-order.js:12)

`shouldSkipTurn()` retourne `true` pour toute initiative `<= 1`, ce qui supprime purement et simplement ces acteurs du tour. Ce comportement ne correspond pas aux règles standards de D&D et produit des combats incorrects.

### Majeur

3. Une initiative exactement égale à `20` donne automatiquement deux tours.

Référence: [assets/scripts/lab/dnd/initiative.js](/var/www/projects/benleminbe/assets/scripts/lab/dnd/initiative.js:37)

`getTurnCount()` accorde deux tours à toute initiative finale égale à `20`. Ce n'est pas une règle D&D standard. Si c'est un choix expérimental, il doit être explicitement documenté et paramétrable. Sinon, c'est une erreur métier.

4. Le rendu et l'état métier reposent sur des variables globales en mémoire, sans modèle central formalisé.

Références:

- [assets/scripts/lab/dnd/monsters.js](/var/www/projects/benleminbe/assets/scripts/lab/dnd/monsters.js:4)
- [assets/scripts/lab/dnd/turn-order.js](/var/www/projects/benleminbe/assets/scripts/lab/dnd/turn-order.js:3)

Cette approche reste acceptable pour un prototype, mais elle limite vite la testabilité, la persistance, l'annulation, l'historisation et la synchronisation entre panneaux.

5. Le parsing des joueurs dépend de la position des champs dans le DOM.

Référence: [assets/scripts/lab/dnd/players.js](/var/www/projects/benleminbe/assets/scripts/lab/dnd/players.js:28)

`getPlayerActors()` récupère la CA et l'initiative via `fields[1]` et `fields[3]`. La moindre évolution du template peut casser silencieusement l'extraction. Il serait plus robuste de cibler des sélecteurs nommés ou des `data-*`.

### Moyen

6. Les styles D&D sont chargés globalement pour l'ensemble du site.

Référence: [assets/styles/app.css](/var/www/projects/benleminbe/assets/styles/app.css:14)

Le scope CSS est globalement bien contenu par `.dnd-initiative-page`, donc le risque de collision est modéré. En revanche, le coût de chargement est payé sur toutes les pages du site.

7. Le catalogue des monstres est embarqué en dur dans le bundle front-end.

Références:

- [assets/scripts/lab/dnd/monster_classes.js](/var/www/projects/benleminbe/assets/scripts/lab/dnd/monster_classes.js:1)
- [tools/dnd/monsters.generated.json](/var/www/projects/benleminbe/tools/dnd/monsters.generated.json:1)

Le volume n'est pas critique à l'échelle du projet, mais l'approche complique la mise à jour, le chargement conditionnel et la réutilisation serveur.

8. Le pipeline de génération de données manque de clarification.

Références:

- [tools/dnd/complete_monster_extractor.php](/var/www/projects/benleminbe/tools/dnd/complete_monster_extractor.php:1)
- [tools/dnd/extract_monsters.php](/var/www/projects/benleminbe/tools/dnd/extract_monsters.php:1)

Deux extracteurs coexistent, avec des formats de sortie différents et sans documentation d'usage. Cela augmente le risque d'obsolescence ou d'ambiguïté pour une future reprise.

9. Aucun test automatisé n'encadre les règles critiques.

Aucun test n'a été trouvé pour:

- le calcul d'initiative
- la génération de l'ordre du tour
- le parsing du catalogue de monstres
- les interactions principales du prototype

## Dette technique prioritaire

### Priorité 1

- Remplacer le rendu `innerHTML` du tour de table par une construction DOM sécurisée.
- Corriger les règles d'initiative non standard ou les rendre explicitement configurables.
- Ajouter un petit jeu de tests automatisés sur les fonctions `initiative.js` et `turn-order.js`.

### Priorité 2

- Introduire un état central explicite de la rencontre, indépendant du DOM.
- Remplacer les accès par index dans `players.js` par des sélecteurs stables.
- Documenter officiellement la chaîne de génération des monstres.

### Priorité 3

- Charger les données monstres à la demande plutôt qu'en dur dans le bundle.
- Ajouter une persistance locale de session via `localStorage` ou sauvegarde serveur.
- Isoler le CSS D&D pour éviter son chargement global.

## Améliorations possibles

### Court terme

- Bouton "Réinitialiser le combat".
- Validation des champs joueurs et monstres.
- Mise en évidence des valeurs incohérentes: PV restants > PV max, initiative vide, CA manquante.
- Support clavier minimal pour le tour de table.
- Indication visuelle du round courant et du combattant actif.

### Moyen terme

- Sauvegarde et reprise d'une rencontre.
- Système de presets de groupes de monstres.
- Filtres et recherche dans le catalogue de monstres.
- Affichage détaillé d'un monstre sélectionné: taille, vitesse, alignement, caractéristiques.
- Gestion des statuts simples: mort, inconscient, concentration, avantage/désavantage.

### Long terme

- Backend de persistance des rencontres.
- Partage d'une rencontre par URL ou par identifiant.
- Import/export JSON de combats.
- Mode MJ mobile/tablette plus ergonomique.
- Internationalisation si l'outil doit sortir du seul usage francophone.

## Recommandation d'évolution

La meilleure trajectoire n'est pas de "réécrire proprement" tout de suite. Le bon compromis serait plutôt:

1. Stabiliser le prototype existant par quelques corrections ciblées de sécurité et de règles métier.
2. Extraire les règles d'initiative et le modèle de rencontre dans des modules purs testables.
3. Ajouter une persistance légère.
4. Décider ensuite si l'outil doit rester un laboratoire intégré au site perso ou devenir un mini-produit autonome.

## Limites de cet audit

Cet audit repose sur une revue statique du code présent dans le dépôt. Je n'ai trouvé ni tests automatisés existants, ni documentation fonctionnelle décrivant des règles maison éventuelles. Les écarts relevés sur les règles D&D sont donc évalués par rapport au comportement standard attendu d'un tracker d'initiative.
