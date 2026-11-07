---
id: TASK-6.12
title: 'Boids 3D : propagation des changements de trajectoire par meneurs'
status: To Do
assignee: []
created_date: '2026-11-07 11:03'
labels:
  - lab
  - 3d
dependencies:
  - TASK-6.10
documentation:
  - docs/en-cours/boids-3d-projet.md
parent_task_id: TASK-6
ordinal: 22000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Retour de Benj le 2026-11-07 : le changement de trajectoire se propage dans le groupe de façon trop rapide, robotique. Cause : la cible attire tous les boids au même instant, il n’y a aucun délai. Décision de Benj : meneurs. Seule une fraction des boids (environ 10 à 20 %) perçoit la cible, les autres suivent par alignement et cohésion. Dans de vraies nuées, quelques oiseaux mènent le virage et l’information traverse la nuée à 20 à 40 m/s.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [ ] #1 Seule une fraction réglable des boids perçoit la cible, les autres suivent par les règles de groupe
- [ ] #2 Le délai de propagation est mesuré (images entre le virage des meneurs et celui des derniers boids) et consigné, avec la fraction retenue
- [ ] #3 Espacement, absence de boids isolés et ampleur de la trajectoire restent tenus ; tests ajoutés
- [ ] #4 Benj valide visuellement
<!-- AC:END -->
