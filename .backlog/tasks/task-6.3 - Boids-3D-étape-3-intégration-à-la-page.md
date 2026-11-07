---
id: TASK-6.3
title: 'Boids 3D, étape 7 : intégration à la page'
status: In Progress
assignee:
  - '@claude'
created_date: '2026-11-07 09:24'
updated_date: '2026-11-07 13:25'
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
- [ ] #7 /lab/simulations est un index des simulations ; le 2D est sur /lab/simulations/boids et la 3D sur /lab/simulations/boids3d (décision de Benj), le menu « Simulations » reste actif sur les trois pages
<!-- AC:END -->

## Implementation Plan

<!-- SECTION:PLAN:BEGIN -->
1. Routes (LabController) : `/simulations` (index, route `app_lab_simulations`, inchangée), `/simulations/boids` (2D, nouvelle), `/simulations/boids3d` (3D, nouvelle), en minuscules. Le gabarit actuel du 2D est déplacé avec `git mv` vers `boids.html.twig` ; un nouvel `index.html.twig` liste les simulations avec les cartes déjà utilisées par le Lab.
2. Menu : « Simulations » actif pour toute route qui commence par `app_lab_simulations`. Le lien de la carte du Lab (`app_lab_simulations`) reste valide.
3. Page 3D : `boids3d.html.twig` avec le conteneur du canvas, pause, réinitialisation, curseurs (nombre de boids 100 à 500 par pas de 50, meneurs en pourcentage, ondulation), aide à la caméra, textes FR et EN (le Lab n'est pas atteignable en anglais aujourd'hui, mais les textes existent dans les deux langues comme partout).
4. JS : `boids3d/main.js` (branchement DOM, démarre en pause si prefers-reduced-motion, comme le 2D) et son entrypoint Importmap `lab_simulations_boids3d` ; aucune dépendance de plus (p5 est déjà chargé par le 2D).
5. Tests : tests fonctionnels PHP (index, 2D, 3D, entrypoints, liens) à adapter à la nouvelle structure.
6. Vérifier dans le navigateur contre le vrai serveur Symfony (assets compilés avec `make cc`) : les trois pages se chargent sans erreur JS, les curseurs agissent en direct, la pause et la réinitialisation fonctionnent, le 2D est inchangé à son nouveau lieu ; mesure des images par seconde.
7. Documentation : docs/lab/simulations.md (routes, gabarits, structure), README/CLAUDE.md si l'adresse y figure.
8. Déploiement en ligne : rien de plus que `make deploy` (p5 déjà vendoré, assets compilés) ; la vérification en production se fait par Benj après déploiement.
<!-- SECTION:PLAN:END -->

## Implementation Notes

<!-- SECTION:NOTES:BEGIN -->
Benj le 2026-11-07 : l'idée est de pouvoir déployer cette version 3D en ligne ; à garder dans cette étape (critère 5 ajouté).

Benj le 2026-11-07 : un curseur pour chacun des deux paramètres (meneurs, ondulation), en temps réel ; à intégrer à la page finale avec la pause, la réinitialisation et le nombre de boids (critère 6 ajouté).
<!-- SECTION:NOTES:END -->
