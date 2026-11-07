---
id: TASK-6.9
title: 'Boids 3D : échelle et proportions oiseaux/décor'
status: In Progress
assignee:
  - '@claude'
created_date: '2026-11-07 11:02'
updated_date: '2026-11-07 11:03'
labels:
  - lab
  - 3d
dependencies:
  - TASK-6.7
documentation:
  - docs/en-cours/boids-3d-projet.md
parent_task_id: TASK-6
ordinal: 19000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Retour de Benj le 2026-11-07 : une proportion entre la taille des oiseaux et celle d’un arbre serait bienvenue, mais il ne sait pas si ça rendrait bien. Aujourd’hui un arbre fait 3 à 7 fois la longueur d’un oiseau (environ 50 à 100 fois dans la réalité). Une comparaison a été préparée sur la page de test (option `birdScale`, oiseaux à 100, 50, 25 et 10 %) ; Benj choisit à l’écran, puis le choix est appliqué.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [ ] #1 Benj choisit un rapport de taille oiseau/arbre après avoir comparé les variantes à l’écran
- [ ] #2 Le rapport choisi est appliqué (taille des oiseaux, des arbres et des poteaux, nombre d’oiseaux) et reste lisible
- [ ] #3 Tests et make check passent, docs à jour
<!-- AC:END -->

## Implementation Notes

<!-- SECTION:NOTES:BEGIN -->
Comparaison préparée le 2026-11-07 : option `birdScale` du rendu (boids3d/sketch.js, sans toucher au calcul) et sélecteur sur la page de test (100, 50, 25, 10 %, soit des arbres de 5, 10, 20 et 50 fois un oiseau). Au lieu d'agrandir le décor (x2, x4, x10) comme annoncé : un décor plus grand entrerait dans la zone de vol ou sortirait du cadre ; rapetisser les oiseaux donne le même rapport avec le même cadrage.
Constat sur captures : 25 % = nuage de points proche d'une photo de murmuration lointaine ; 10 % = poussière, la nuée disparaît.
Prochaine action : Benj choisit un rapport à l'écran (http://127.0.0.1:8766/index.html?birdScale=0.25 par exemple), puis application.
<!-- SECTION:NOTES:END -->
