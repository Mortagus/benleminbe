---
id: TASK-6.3
title: 'Boids 3D, étape 7 : intégration à la page'
status: To Do
assignee: []
created_date: '2026-11-07 09:24'
updated_date: '2026-11-07 12:59'
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
- [ ] #5 La version 3D est déployable en ligne avec le site (make deploy : p5 et les entrypoints compilés, aucune dépendance de plus) et fonctionne en production, vérifié après déploiement
- [ ] #6 Deux curseurs agissent en direct sur la nuée : le pourcentage de meneurs (`pointerFraction`) et l’intensité de l’ondulation (`waveWeight`), avec leurs textes FR et EN
<!-- AC:END -->

## Implementation Notes

<!-- SECTION:NOTES:BEGIN -->
Benj le 2026-11-07 : l'idée est de pouvoir déployer cette version 3D en ligne ; à garder dans cette étape (critère 5 ajouté).

Benj le 2026-11-07 : un curseur pour chacun des deux paramètres (meneurs, ondulation), en temps réel ; à intégrer à la page finale avec la pause, la réinitialisation et le nombre de boids (critère 6 ajouté).
<!-- SECTION:NOTES:END -->
