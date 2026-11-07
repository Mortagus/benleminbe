---
id: TASK-6.1
title: 'Boids 3D, étape 1 : logique pure 3D'
status: To Do
assignee: []
created_date: '2026-11-07 09:24'
labels:
  - lab
  - 3d
dependencies: []
documentation:
  - docs/en-cours/boids-3d-projet.md
parent_task_id: TASK-6
ordinal: 11000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
La simulation 3D réutilise le calcul générique de `flocking.js` (dim = 3), mais il manque ce qui est propre à la 3D et sans rendu : des paramètres par défaut adaptés au volume, une force de confinement qui garde la nuée dans la scène (le rebouclage en tore serait visible avec une caméra orbitale) et des directions lissées pour orienter les boids. Étape 1 du plan de docs/en-cours/boids-3d-projet.md.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [ ] #1 Des paramètres par défaut 3D donnent une nuée cohésive, sans chevauchement visible, vérifié par un test et un benchmark
- [ ] #2 Une force de confinement progressive et plafonnée garde la nuée dans la scène, sans effet sur le 2D (comportement du 2D strictement inchangé)
- [ ] #3 Une fonction pure produit des directions lissées par boid, utilisable en 2D et en 3D
- [ ] #4 Tests Vitest ajoutés pour chaque point, make check passe
<!-- AC:END -->
