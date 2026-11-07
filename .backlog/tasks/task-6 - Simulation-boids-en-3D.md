---
id: TASK-6
title: Simulation boids en 3D
status: To Do
assignee: []
created_date: '2026-11-07 09:24'
labels:
  - lab
  - 3d
dependencies: []
documentation:
  - docs/en-cours/boids-3d-projet.md
  - docs/lab/simulations.md
priority: medium
ordinal: 10000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Ajouter au Lab une simulation 3D d’une nuée d’oiseaux (quelques centaines de boids, silhouettes sur un coucher de soleil de novembre fixe), à côté de la simulation 2D qui reste la version pédagogique. Pièce visuelle, caméra seule, règles de flocking actuelles en 3D, ordinateur d’abord. Cadrage, décisions et plan validés le 2026-11-07 dans docs/en-cours/boids-3d-projet.md (méthode project-start). Hors périmètre : battement d’ailes, point d’attraction, inclinaison dans les virages, pointeur 3D, bruit de direction, simulateur de fluide.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [ ] #1 La simulation 3D est visible et fluide sur ordinateur dans `/lab/simulations`, avec caméra orbitale, sans dupliquer le calcul du 2D
- [ ] #2 La nuée reste dans la scène (confinement doux) et garde un comportement de groupe crédible
- [ ] #3 Le ciel est un coucher de soleil fixe, les boids sont une forme 3D simple d’oiseau sans animation
- [ ] #4 Pause, réinitialisation et nombre de boids (100 à 500) sont disponibles, avec textes FR et EN
- [ ] #5 `docs/lab/simulations.md` et `docs/en-cours/boids-3d-projet.md` sont à jour, `make check` passe
<!-- AC:END -->
