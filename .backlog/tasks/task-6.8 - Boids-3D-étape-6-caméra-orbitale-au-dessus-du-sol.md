---
id: TASK-6.8
title: 'Boids 3D, étape 6 : caméra orbitale au-dessus du sol'
status: To Do
assignee: []
created_date: '2026-11-07 09:54'
updated_date: '2026-11-07 11:03'
labels:
  - lab
  - 3d
dependencies:
  - TASK-6.13
documentation:
  - docs/en-cours/boids-3d-projet.md
parent_task_id: TASK-6
ordinal: 18000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Avec un sol, orbitControl de p5 (sans limite d’angle) ferait passer la caméra sous le sol. Benj a choisi une orbite autour de la scène au-dessus du sol. Voir D6 de docs/en-cours/boids-3d-projet.md.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [ ] #1 Une fonction pure donne la position de la caméra depuis azimut, hauteur d’angle et distance, avec des limites qui interdisent de passer sous le sol, vérifiée par des tests
- [ ] #2 La souris, le tactile et la molette modifient cet état, vérifié en navigateur (tactile émulé)
- [ ] #3 Benj valide la manipulation de la caméra
<!-- AC:END -->
