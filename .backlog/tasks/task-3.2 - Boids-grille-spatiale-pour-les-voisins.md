---
id: TASK-3.2
title: 'Boids : grille spatiale pour les voisins'
status: To Do
assignee: []
created_date: '2026-11-07 07:32'
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
- [ ] #1 Voisins et séparation sont trouvés via la grille, sans allocation par frame
- [ ] #2 Un test compare la grille à une version naïve O(n²) sur un même flock et obtient le même résultat
- [ ] #3 Le temps de `stepFlock` à 5000 boids est mesuré et consigné
<!-- AC:END -->
