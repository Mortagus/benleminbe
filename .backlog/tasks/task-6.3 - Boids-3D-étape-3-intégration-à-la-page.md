---
id: TASK-6.3
title: 'Boids 3D, étape 7 : intégration à la page'
status: To Do
assignee: []
created_date: '2026-11-07 09:24'
updated_date: '2026-11-07 09:54'
labels:
  - lab
  - 3d
dependencies:
  - TASK-6.8
documentation:
  - docs/lab/simulations.md
parent_task_id: TASK-6
ordinal: 13000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Intégrer la simulation 3D comme seconde section de `/lab/simulations`, avec son propre entrypoint et les contrôles de départ. Étape 3 du plan de docs/en-cours/boids-3d-projet.md.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [ ] #1 La section 3D est dans `/lab/simulations` avec son entrypoint Importmap, le 2D est inchangé
- [ ] #2 Pause, réinitialisation et nombre de boids (100 à 500, 200 par défaut) fonctionnent, la simulation démarre en pause si prefers-reduced-motion
- [ ] #3 Textes FR et EN ajoutés, `docs/lab/simulations.md` mis à jour
- [ ] #4 make check passe et un essai manuel confirme le fonctionnement
<!-- AC:END -->
