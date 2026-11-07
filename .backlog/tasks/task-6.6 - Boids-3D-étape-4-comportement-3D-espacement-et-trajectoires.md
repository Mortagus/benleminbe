---
id: TASK-6.6
title: 'Boids 3D, étape 4 : comportement 3D (espacement et trajectoires)'
status: To Do
assignee: []
created_date: '2026-11-07 09:54'
labels:
  - lab
  - 3d
dependencies:
  - TASK-6.5
documentation:
  - docs/en-cours/boids-3d-projet.md
parent_task_id: TASK-6
ordinal: 16000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Retour de Benj le 2026-11-07 : entre 350 et 500 boids ils sont trop rapprochés (un oiseau garde un peu d’espace avec ses voisins), et ils doivent varier leurs trajectoires en exploitant les 3 dimensions alors qu’ils tournent en ronds comme un banc de poissons. Mesures et options en D9 et D10 de docs/en-cours/boids-3d-projet.md ; le mécanisme des trajectoires 3D (Q12) est à décider avec Benj avant d’implémenter.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [ ] #1 À 350 et 500 boids, le 5e centile de distance au plus proche voisin est d’au moins 28 px et aucun boid n’est isolé
- [ ] #2 Les trajectoires exploitent la hauteur : part verticale de la vitesse d’au moins 45 %, rotation en bloc sous 0,3 et déplacement vertical du centre d’au moins 40 % de la demi-hauteur, mesurés sur plusieurs graines
- [ ] #3 Le 2D reste strictement identique (test de non-régression), tests Vitest ajoutés, make check passe
- [ ] #4 Benj valide visuellement le comportement
<!-- AC:END -->
