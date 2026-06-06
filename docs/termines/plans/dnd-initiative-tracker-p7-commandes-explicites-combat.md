# P7 - Commandes Explicites De Combat

Date de mise à jour : 2026-06-06

Statut : livré le 2026-06-06. Ce document conserve le cadrage initial et les décisions fonctionnelles de `P7` à titre historique.

## Objectif

Clarifier ce que recouvrent les "commandes explicites de pilotage du combat" dans l'UI du DnD Initiative Tracker, afin de les distinguer des réglages, des états visuels et des automatismes plus avancés.

## Références Observees

La recherche sur des outils proches montre un noyau de commandes très stable :

- `Foundry VTT` expose des commandes de base très lisibles : démarrer le combat, passer au tour suivant/précédent, terminer le combat, réinitialiser l'initiative, marquer un acteur caché ou vaincu.
- `Turn Watcher` met davantage l'accent sur les cas tactiques avancés : actions retardées, actions préparées, déplacement d'un acteur dans l'ordre, annulation/rétablissement.
- `DM Combat Companion` garde une logique simple : démarrer le combat, avancer au tour suivant, gérer les réactions et les tours retenus, avec une UI orientée table.
- `Roll20` reste minimaliste et centre l'expérience sur l'avancement du tracker, avec peu de commandes visibles en permanence.

Sources :

- [Combat Encounters | Foundry Virtual Tabletop](https://foundryvtt.com/article/combat/)
- [What is Turn Watcher? | Turn Watcher](https://www.turnwatcher.com/user-guide/turnwatcher)
- [Changing Characters' Initiative Order | Turn Watcher](https://www.turnwatcher.com/user-guide/tutorial/changing-characters-initiative-order)
- [DM Combat Companion](https://combatcompanion.app/)
- [Turn Tracker | Roll20 Wiki](https://wiki.roll20.net/Turn_Tracker)

## Proposition D'UI

Le tracker peut rester lisible en gardant trois niveaux de commandes :

### 1. Commandes principales

Ces commandes doivent rester visibles sans ouvrir de sous-menu.

- `Lancer le combat`
- `Tour suivant`
- `Tour précédent` si la navigation manuelle est utile au MJ
- `Fin de combat`

### 2. Commandes de pilotage

Ces actions servent à corriger ou reprendre la main pendant le combat.

- `Réinitialiser l'initiative`
- `Marquer vaincu`
- `Marquer caché`
- `Réinitialiser la rencontre`

### 3. Commandes avancées

Ces cas doivent rester accessibles, mais pas forcément en permanence dans l'UI de base.

- `Retarder son tour`
- `Préparer une action`
- `Réaction`
- `Passer`
- `Annuler` / `Rétablir` si le produit choisit d'offrir cet historique

## Lecture Fonctionnelle Pour P7

Pour le backlog, `P7` peut se lire comme :

- donner au MJ des boutons explicites pour les actions de combat qui reviennent tout le temps ;
- éviter de lui faire passer par des gestes implicites ou des manipulations trop cachées ;
- garder l'UI simple et orientée table ;
- repousser les mécaniques avancées au second niveau si elles ne sont pas indispensables au socle.

## Recommandation

La version minimale cohérente pour le projet serait :

1. `Lancer le combat`
2. `Tour suivant`
3. `Tour précédent`
4. `Réinitialiser la rencontre`
5. `Fin de combat`

Les commandes comme `retarder`, `préparer`, `réaction` ou `marquer caché` peuvent arriver ensuite, dans un panneau secondaire ou un menu contextuel.

## Point D'Attention

Il faut éviter de mélanger :

- les commandes de combat ;
- les statuts visuels ;
- les réglages de règles ;
- les opérations de persistance.

Si tout remonte au même niveau, l'UI devient vite difficile à lire et à expliquer au MJ.
