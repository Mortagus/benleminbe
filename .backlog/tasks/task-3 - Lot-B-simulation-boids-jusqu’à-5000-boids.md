---
id: TASK-3
title: 'Lot B : performance des boids à grand effectif (curseur 100-1000)'
status: Done
assignee:
  - '@claude'
created_date: '2026-11-07 07:32'
updated_date: '2026-11-07 08:38'
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
- [x] #1 Le temps de calcul de `stepFlock` est mesuré avant et après et les résultats sont consignés
- [x] #2 Le rendu p5 à grand effectif est mesuré dans un navigateur et un rendu simplifié est ajouté seulement si la mesure le justifie
- [x] #3 Les règles de flocking donnent le même résultat qu’avant (tests existants adaptés, comparaison grille / version naïve)
- [x] #4 `docs/lab/simulations.md` et `docs/en-cours/boids-evolutions-notes.md` sont à jour
- [x] #5 `make check` passe, ou les échecs sans lien (test DnD instable) sont signalés
- [x] #6 Le nombre de boids est réglable de 100 à 1000 dans l’interface (plafond de 5000 abandonné après mesure et essai, voir les notes)
<!-- AC:END -->

## Implementation Notes

<!-- SECTION:NOTES:BEGIN -->
2026-11-07 : TASK-3.1 implémentée (critères 1 et 2 cochés, critère 3 en attente de vérification navigateur). Mesure de départ avant lot B : 500 boids 15,8 ms, 1000 boids 59 ms, 5000 boids 1422 ms par frame (logique seule). Après tableaux plats : 3,3 / 13 / 321 ms.

2026-11-07 : TASK-3.2 terminée. 5000 boids : 35 ms par frame de calcul (logique seule), contre 1422 ms au départ. Reste TASK-3.3 : mesure du rendu et curseur ; viser 60 fps à 5000 demandera probablement plus que la grille (calcul seul à ~35 ms).

2026-11-07 : lot B terminé. Calcul 1422 -> 35 ms par frame à 5000 boids (logique seule), trajectoires identiques à l'implémentation précédente, rendu 60 fps jusqu'à 1000 boids. Plafond du curseur fixé à 1000 (de 100 à 1000, pas de 50) sur décision de Benj après essai. La 3D et un éventuel simulateur de fluide sont des projets séparés, non planifiés.
<!-- SECTION:NOTES:END -->

## Final Summary

<!-- SECTION:FINAL_SUMMARY:BEGIN -->
Tableaux plats Float32Array à stride dim, grille spatiale, mesures du calcul et du rendu, curseur limité à 100-1000 ; comportement identique, docs et make check à jour.
<!-- SECTION:FINAL_SUMMARY:END -->
