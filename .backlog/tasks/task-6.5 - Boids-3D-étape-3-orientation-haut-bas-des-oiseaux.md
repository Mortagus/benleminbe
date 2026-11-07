---
id: TASK-6.5
title: 'Boids 3D, étape 3 : orientation haut/bas des oiseaux'
status: To Do
assignee: []
created_date: '2026-11-07 09:54'
labels:
  - lab
  - 3d
dependencies:
  - TASK-6.2
documentation:
  - docs/en-cours/boids-3d-projet.md
parent_task_id: TASK-6
ordinal: 15000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Retour de Benj le 2026-11-07 : les boids font parfois un retournement sans sens dans la vraie vie. Cause : l’orientation actuelle est la rotation la plus courte du nez vers la direction, sans notion de haut ; le roulis saute quand la direction passe près de l’arrière. Il faut un haut et un bas (ventre vers le bas) sans retournement. Voir D11 de docs/en-cours/boids-3d-projet.md.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [ ] #1 Le haut d’un oiseau ne se retourne pas en vol à peu près horizontal, y compris lors d’un demi-tour complet, vérifié par des tests
- [ ] #2 Près de la verticale le haut change progressivement, sans saut brusque, vérifié par des tests
- [ ] #3 La rotation appliquée par p5 donne bien le repère attendu, vérifié dans le navigateur
- [ ] #4 make check passe et Benj ne voit plus de retournement à l’écran
<!-- AC:END -->
