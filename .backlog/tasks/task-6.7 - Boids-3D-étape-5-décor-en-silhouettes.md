---
id: TASK-6.7
title: 'Boids 3D, étape 5 : décor en silhouettes'
status: To Do
assignee: []
created_date: '2026-11-07 09:54'
labels:
  - lab
  - 3d
dependencies:
  - TASK-6.6
documentation:
  - docs/en-cours/boids-3d-projet.md
parent_task_id: TASK-6
ordinal: 17000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Benj attendait une scène réaliste avec un sol et des éléments de paysage ; décor retenu le 2026-11-07 : silhouettes sombres sur le ciel, avec sol ou champ et horizon, arbres, lignes électriques aériennes (routes, buissons et bâtiments plus tard). Voir D7 de docs/en-cours/boids-3d-projet.md.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [ ] #1 Un générateur pur et déterministe produit sol, horizon, arbres, poteaux et câbles, avec des tests de placement (rien dans la zone de vol, câbles reliés aux poteaux, résultat reproductible)
- [ ] #2 Le décor est dessiné par un seul appel de géométrie fusionnée et le sol est sous la zone de vol : la nuée ne traverse jamais le décor
- [ ] #3 Les images par seconde avec et sans décor sont mesurées et consignées
- [ ] #4 Benj valide l’aspect du décor
<!-- AC:END -->
