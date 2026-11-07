---
id: TASK-6.11
title: 'Boids 3D : évitement prédictif des arbres'
status: To Do
assignee: []
created_date: '2026-11-07 11:02'
updated_date: '2026-11-07 11:03'
labels:
  - lab
  - 3d
dependencies:
  - TASK-6.12
documentation:
  - docs/en-cours/boids-3d-projet.md
parent_task_id: TASK-6
ordinal: 21000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Retour de Benj le 2026-11-07 : les boids pourraient interagir avec les arbres et passer près sans collision, avec un évitement et pas une répulsion. Décision de Benj : évitement prédictif (chaque boid regarde devant lui le long de sa vitesse et ne dévie que si sa trajectoire va toucher un arbre). Cela suppose de recouvrir la zone de vol et la hauteur des arbres, ce qui remplace la décision « la nuée reste au-dessus du décor ».
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [ ] #1 La zone de vol est abaissée jusqu’à la hauteur des arbres, et un sol sûr empêche de le traverser
- [ ] #2 Les boids évitent les arbres sans en traverser aucun, vérifié par des tests sur de nombreuses parties, et aucune force n’agit quand la voie est libre
- [ ] #3 Le coût du calcul avec l’évitement est mesuré et reste acceptable à 500 boids
- [ ] #4 Benj valide visuellement
<!-- AC:END -->
