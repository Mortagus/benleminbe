---
id: TASK-6.13
title: 'Boids 3D : repères d’échelle (brume, grange, clocher, route et voiture)'
status: To Do
assignee: []
created_date: '2026-11-07 11:03'
labels:
  - lab
  - 3d
dependencies:
  - TASK-6.11
documentation:
  - docs/en-cours/boids-3d-projet.md
parent_task_id: TASK-6
ordinal: 23000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Retour de Benj le 2026-11-07 : la taille de la scène semble indéfinie, il faudrait un élément de taille de référence facilement identifiable. Décision de Benj : les quatre repères proposés. Brume lointaine (perspective atmosphérique cuite dans les couleurs des sommets, puisque le décor est statique), maison ou grange, clocher ou silhouette lointaine, route avec voiture.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [ ] #1 Les objets lointains s’éclaircissent et perdent du contraste vers l’horizon (perspective atmosphérique)
- [ ] #2 Une maison ou grange, un clocher ou une silhouette lointaine, et une route avec une voiture sont dans la scène, à une taille cohérente avec le rapport oiseau/arbre retenu, hors de la zone de vol
- [ ] #3 Les tests de placement du décor couvrent les nouveaux éléments, le budget de triangles reste tenu
- [ ] #4 Benj valide l’aspect
<!-- AC:END -->
