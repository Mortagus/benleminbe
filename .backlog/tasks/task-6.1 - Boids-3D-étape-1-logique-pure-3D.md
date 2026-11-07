---
id: TASK-6.1
title: 'Boids 3D, étape 1 : logique pure 3D'
status: Done
assignee:
  - '@claude'
created_date: '2026-11-07 09:24'
updated_date: '2026-11-07 09:29'
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
- [x] #1 Des paramètres par défaut 3D donnent une nuée cohésive, sans chevauchement visible, vérifié par un test et un benchmark
- [x] #2 Une force de confinement progressive et plafonnée garde la nuée dans la scène, sans effet sur le 2D (comportement du 2D strictement inchangé)
- [x] #3 Une fonction pure produit des directions lissées par boid, utilisable en 2D et en 3D
- [x] #4 Tests Vitest ajoutés pour chaque point, make check passe
<!-- AC:END -->

## Implementation Plan

<!-- SECTION:PLAN:BEGIN -->
1. Mesurer d'abord : avec les valeurs par défaut du 2D, une nuée 3D de 200 boids est trop clairsemée (très peu de voisins dans le rayon de perception). Choisir un volume de scène et des paramètres 3D par essais chiffrés (voisins moyens, polarisation, boid le plus éloigné du centre), pas au hasard.
2. Confinement : dans `stepFlock`, une force progressive vers le centre quand la position normalisée dans l'ellipsoïde de la scène dépasse un seuil, plafonnée par `maxForce`, ajoutée à l'accélération lissée. Activée par un paramètre (`confinementWeight`, 0 = désactivée, donc rebouclage et comportement 2D strictement inchangés).
3. Directions lissées : fonction pure `updateDirections(directions, flock, factor)` qui garde un vecteur unitaire par boid, le rapproche de la direction de la vitesse, ignore une vitesse nulle, et gère le changement d'effectif ; valable pour dim 2 et 3.
4. `DEFAULT_PARAMETERS_3D` et la taille de scène 3D exportés, avec les valeurs retenues par la mesure.
5. Tests Vitest : confinement (inactif dedans, rappel dehors, plafonné, en 3D, 2D inchangé), nuée 3D confinée sur de nombreuses frames, pas de chevauchement, directions lissées.
6. Vérifier : vitest simulations, test d'invariance du 2D (trajectoires identiques à HEAD), benchmark 3D, `make check`.
<!-- SECTION:PLAN:END -->

## Implementation Notes

<!-- SECTION:NOTES:BEGIN -->
Fait : confinement doux dans stepFlock (`confinementWeight`, `confinementStart`, 0 = désactivé), `SCENE_SIZE_3D` et `DEFAULT_PARAMETERS_3D`, `updateDirections`.
Choix : paramètres 3D déterminés par mesure (12 parties de 800 frames, 100/200/500 boids, 4 graines) : max reach 0,92, distance min 13 px, un seul groupe. Les valeurs du 2D donnaient une nuée trop clairsemée dans ce volume. Confinement ajouté à l'accélération lissée.
Incident : ma première version stockait la position en float32 avant le rebouclage, ce qui changeait la trajectoire 2D (écart 2,9e-5 px dès la frame 20, divergence chaotique à 1500 boids). Détecté par la comparaison avec HEAD, corrigé, et protégé par un test de non-régression à valeurs de référence (les 4 mutations testées, dont celle-ci, sont détectées).
Validé : 63 tests simulations, make check OK (226 tests), trajectoires 2D identiques au commit 4e8bce9 (écart 0 px, 150 et 1500 boids, 100 frames).
Prochaine action : TASK-6.2, rendu minimal p5 WEBGL.
<!-- SECTION:NOTES:END -->

## Final Summary

<!-- SECTION:FINAL_SUMMARY:BEGIN -->
Logique pure 3D : confinement doux désactivé par défaut, paramètres et taille de scène 3D mesurés, directions lissées. 2D strictement inchangé (test de non-régression à valeurs de référence). Vérifié par 63 tests simulations, 4 mutations détectées, make check (226 tests).
<!-- SECTION:FINAL_SUMMARY:END -->
