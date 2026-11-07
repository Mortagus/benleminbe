---
id: TASK-3
title: 'Lot B : simulation boids jusqu’à 5000 boids'
status: In Progress
assignee:
  - '@claude'
created_date: '2026-11-07 07:32'
updated_date: '2026-11-07 08:08'
labels:
  - lab
  - performance
dependencies: []
documentation:
  - docs/lab/simulations.md
  - docs/en-cours/boids-evolutions-notes.md
priority: medium
ordinal: 4000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Le curseur du nombre de boids plafonne à quelques centaines car `stepFlock` est en O(n²) : mesure du 2026-11-07, 15,8 ms/frame à 500 boids, 59 ms à 1000, 1422 ms à 5000 (logique seule, Node 24). Objectif : pouvoir monter à 5000 boids de façon fluide, sans changer le comportement visible de la simulation. Le format de données doit être conçu pour une version générique en N dimensions (stride `dim`), afin de préparer une éventuelle simulation 3D, qui est un projet séparé et hors périmètre ici.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [ ] #1 Le nombre de boids peut être réglé jusqu’à 5000 dans l’interface
- [ ] #2 Le temps de calcul de `stepFlock` est mesuré avant et après et les résultats sont consignés
- [ ] #3 Le rendu p5 à grand effectif est mesuré dans un navigateur et un rendu simplifié est ajouté seulement si la mesure le justifie
- [ ] #4 Les règles de flocking donnent le même résultat qu’avant (tests existants adaptés, comparaison grille / version naïve)
- [ ] #5 `docs/lab/simulations.md` et `docs/en-cours/boids-evolutions-notes.md` sont à jour
- [ ] #6 `make check` passe, ou les échecs sans lien (test DnD instable) sont signalés
<!-- AC:END -->

## Implementation Notes

<!-- SECTION:NOTES:BEGIN -->
2026-11-07 : TASK-3.1 implémentée (critères 1 et 2 cochés, critère 3 en attente de vérification navigateur). Mesure de départ avant lot B : 500 boids 15,8 ms, 1000 boids 59 ms, 5000 boids 1422 ms par frame (logique seule). Après tableaux plats : 3,3 / 13 / 321 ms.

2026-11-07 : TASK-3.2 terminée. 5000 boids : 35 ms par frame de calcul (logique seule), contre 1422 ms au départ. Reste TASK-3.3 : mesure du rendu et curseur ; viser 60 fps à 5000 demandera probablement plus que la grille (calcul seul à ~35 ms).
<!-- SECTION:NOTES:END -->
