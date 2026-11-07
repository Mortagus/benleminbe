---
id: TASK-3.3
title: 'Boids : curseur à 5000 et mesure du rendu'
status: To Do
assignee: []
created_date: '2026-11-07 07:32'
labels:
  - lab
  - performance
dependencies:
  - TASK-3.2
parent_task_id: TASK-3
ordinal: 7000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Une fois la logique rapide, le rendu p5 de 5000 triangles peut devenir le goulot. La limite du curseur ne doit monter à 5000 qu’après mesure dans un navigateur, avec un rendu simplifié à grand effectif seulement si nécessaire.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [ ] #1 Le rendu à 1000, 2500 et 5000 boids est mesuré dans un navigateur et consigné
- [ ] #2 Le curseur monte à 5000, ou à la limite justifiée par la mesure
- [ ] #3 `docs/lab/simulations.md` (complexité, structure, limites) et `docs/en-cours/boids-evolutions-notes.md` sont à jour
<!-- AC:END -->
