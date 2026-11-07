---
id: TASK-3.1
title: 'Boids : tableaux plats Float32Array avec stride dim'
status: Done
assignee:
  - '@claude'
created_date: '2026-11-07 07:32'
updated_date: '2026-11-07 07:53'
labels:
  - lab
  - performance
dependencies: []
documentation:
  - docs/en-cours/boids-evolutions-notes.md
parent_task_id: TASK-3
ordinal: 5000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Chaque boid est aujourd’hui un objet recréé à chaque frame, ce qui alloue et empêche d’optimiser la mémoire à grand effectif. Passer à des `Float32Array` plats (positions, vitesses, accélérations) avec un stride `dim` (2 pour l’instant) est un préalable à la grille spatiale, et fixe le format pour une version N dimensions future.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [x] #1 Le flock est un ensemble de tableaux plats avec un stride `dim`, et `stepFlock` ne crée plus d’objet par boid et par frame
- [x] #2 Le comportement des règles est inchangé : tests existants adaptés au nouveau format
- [x] #3 `sketch.js` utilise le nouveau format et la simulation reste identique à l’écran
<!-- AC:END -->

## Implementation Plan

<!-- SECTION:PLAN:BEGIN -->
1. Définir le format : `{ count, dim, pos, vel, acc }` avec trois `Float32Array` de taille `count * dim` ; accesseurs minimaux pour les tests.
2. Réécrire `createFlock`, `resizeFlock`, `stepFlock` et les forces sur ce format ; `stepFlock` écrit dans un second jeu de tableaux (double buffer) pour garder le même instantané pour tous les boids.
3. Garder la complexité O(n²) à cette étape : seul le format change, pour isoler les effets (la grille est TASK-3.2).
4. Adapter `sketch.js` (rendu, caps lissés) et `tests/js/lab/simulations/flocking.test.js`.
5. Vérifier : `npx vitest run tests/js/lab/simulations`, puis `make check` (hors test DnD instable) ; mesurer avec le script de benchmark.
<!-- SECTION:PLAN:END -->

## Implementation Notes

<!-- SECTION:NOTES:BEGIN -->
Fait : format `{ count, dim, pos, vel, acc }` en Float32Array, règles écrites pour `dim` quelconque, `stepFlock` sans objet par boid, `resizeFlock` renvoie le même groupe si l'effectif ne change pas, pointeur `{ position, mode }`. Choix : `stepFlock` alloue 3 tableaux par frame au lieu d'un double buffer (à revoir en TASK-3.2 si la mesure le justifie).
Validé : vitest simulations 35/35 ; `make check` OK (198 tests JS) ; comparaison avec HEAD sur 150 boids : écart max 3,4e-3 px après 60 frames (arrondi float32) ; benchmark logique seule : 500 boids 15,8 -> 3,3 ms, 1000 boids 59 -> 13 ms, 5000 boids 1422 -> 321 ms.
Non vérifié : animation et pointeur dans un navigateur (seule une capture headless du rendu initial a été faite, sans erreur JS). `npm run lint:md` échoue déjà sur HEAD, sans lien avec cette tâche.
Prochaine action : vérifier l'animation et le pointeur à l'écran, cocher le critère 3, puis démarrer TASK-3.2.

Vérification manuelle par Benj le 2026-11-07 : animation et clics gauche/droit identiques à avant.
<!-- SECTION:NOTES:END -->

## Final Summary

<!-- SECTION:FINAL_SUMMARY:BEGIN -->
Flock passé en Float32Array plats (pos, vel, acc) avec stride dim ; règles écrites pour dim quelconque ; stepFlock sans objet par boid. Vérifié par vitest (35 tests), make check, comparaison avec HEAD (écart max 3,4e-3 px après 60 frames) et test manuel de Benj à l’écran. Logique seule : 1000 boids 59 -> 13 ms, 5000 boids 1422 -> 321 ms par frame ; toujours O(n²), la grille est TASK-3.2.
<!-- SECTION:FINAL_SUMMARY:END -->
