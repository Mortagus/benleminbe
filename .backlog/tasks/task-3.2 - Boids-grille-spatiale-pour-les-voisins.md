---
id: TASK-3.2
title: 'Boids : grille spatiale pour les voisins'
status: Done
assignee:
  - '@claude'
created_date: '2026-11-07 07:32'
updated_date: '2026-11-07 08:08'
labels:
  - lab
  - performance
dependencies:
  - TASK-3.1
parent_task_id: TASK-3
ordinal: 6000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Après les tableaux plats, le coût dominant reste la recherche de voisins en O(n²). Une grille de cellules de la taille du rayon de perception ramène le coût à environ O(n) et permet de viser 5000 boids.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [x] #1 Voisins et séparation sont trouvés via la grille, sans allocation par frame
- [x] #2 Un test compare la grille à une version naïve O(n²) sur un même flock et obtient le même résultat
- [x] #3 Le temps de `stepFlock` à 5000 boids est mesuré et consigné
<!-- AC:END -->

## Implementation Plan

<!-- SECTION:PLAN:BEGIN -->
1. `buildGrid(flock, cellSize, size)` : cellules de côté `perceptionRadius` couvrant le terrain plus `BOUNDARY_MARGIN`, tri par comptage (`cellStart`, `items`), générique en `dim`. Pas de recherche à travers le rebouclage : l'implémentation actuelle ne le fait pas non plus.
2. Par boid, rassembler les candidats (les 3^dim cellules voisines) dans un tampon du workspace ; `findNeighbours` et la séparation parcourent cette liste courte au lieu de tout le groupe. Sans grille, les candidats sont tous les autres boids : c'est la version naïve, gardée comme référence pour les tests.
3. `computeAcceleration(flock, index, parameters, grid = null)` et `buildGrid` exportés ; `stepFlock` construit la grille une fois par frame depuis l'instantané.
4. Tests : grille et version naïve donnent les mêmes accélérations (tolérance float) sur des flocks aléatoires, y compris aux bords du terrain et en 3D ; cellules vides, boid hors terrain.
5. Mesurer 500 / 1000 / 2000 / 5000 boids, consigner ; décider du double buffer selon la mesure (allocations par frame).
6. Vérifier : vitest simulations, `make check`, test manuel à l'écran.
<!-- SECTION:PLAN:END -->

## Implementation Notes

<!-- SECTION:NOTES:BEGIN -->
Fait : `buildGrid` (tri par comptage, générique en dim), `gatherCandidates` (3^dim cellules), perception et séparation parcourent la même liste de candidats ; sans grille la liste contient tous les boids (version naïve de référence). Double buffer écarté : allocateFlock coûte ~0,05 ms sur 36 ms à 5000 boids.
Validé : 43 tests simulations (8 nouveaux : grille vs naïf en 2D, aux bords, hors terrain, autres réglages, 3D, frontière de cellule) ; 2 mutations volontaires de la grille détectées ; trajectoires strictement identiques à celles de HEAD (écart 0 px après 100 frames, 150 et 1500 boids) ; make check OK (206 tests).
Mesures logique seule (Node 24, 924x520) : 500 boids 0,8 ms, 1000 boids 1,8 ms, 2000 boids 7 ms, 5000 boids 35 ms par frame, stable après convergence (30-36 ms). Avant : 59 ms à 1000, 1422 ms à 5000.
Note : à 5000 boids le calcul seul dépasse le budget d'une frame à 60 images par seconde (~16 ms).
Prochaine action : TASK-3.3, mesurer le rendu p5 dans un navigateur à 1000, 2500 et 5000 boids.
<!-- SECTION:NOTES:END -->

## Final Summary

<!-- SECTION:FINAL_SUMMARY:BEGIN -->
Grille spatiale ajoutée : voisins et séparation passent par les cellules voisines, sans allocation par boid. Résultat strictement identique à la version naïve (tests d’équivalence, mutations détectées, trajectoires identiques à HEAD). 5000 boids : 1422 ms -> 35 ms par frame (logique seule).
<!-- SECTION:FINAL_SUMMARY:END -->
