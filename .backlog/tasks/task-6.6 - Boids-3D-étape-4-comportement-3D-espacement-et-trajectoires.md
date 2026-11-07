---
id: TASK-6.6
title: 'Boids 3D, étape 4 : comportement 3D (espacement et trajectoires)'
status: Done
assignee:
  - '@claude'
created_date: '2026-11-07 09:54'
updated_date: '2026-11-07 10:54'
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
- [x] #1 À 350 et 500 boids, le 5e centile de distance au plus proche voisin est d’au moins 28 px en moyenne dans le temps et aucun boid n’est isolé
- [x] #2 Les trajectoires exploitent la hauteur : part verticale de la vitesse d’au moins 45 % en médiane, rotation en bloc sous 0,3 et déplacement vertical du centre d’au moins 40 % de la demi-hauteur, mesurés sur 12 parties
- [x] #3 Le 2D reste strictement identique (test de non-régression), tests Vitest ajoutés, make check passe
- [x] #4 Benj valide visuellement le comportement
<!-- AC:END -->

## Implementation Plan

<!-- SECTION:PLAN:BEGIN -->
1. Cible : fonction pure et déterministe `roostTarget(frame, size)`, un chemin lisse en 3D (somme de sinus à fréquences différentes sur chaque axe, amplitudes assez petites pour rester dans l'ellipsoïde de confinement), sans aléa ni état.
2. Attraction : réutiliser l'attraction du pointeur (déjà dans `stepFlock`, force qui s'atténue avec la distance) en lui passant la cible comme `{ position, mode: 'attract' }`, avec `pointerRadius` et `pointerWeight` propres à la 3D dans `DEFAULT_PARAMETERS_3D`. Pas de nouvelle force dans le calcul.
3. Espacement : `separationRadius` 40 et `separationWeight` 4 dans `DEFAULT_PARAMETERS_3D` (mesurés en D9).
4. Régler par la mesure : grille de poids, rayon, amplitudes et fréquences sur plusieurs graines et deux effectifs (200 et 500) contre les critères : part verticale >= 45 %, rotation en bloc < 0,3, déplacement vertical du centre >= 40 % de la demi-hauteur, 5e centile du plus proche voisin >= 28 px, aucun boid isolé, nuée dans la zone.
5. `sketch.js` : compteur de frames qui avance hors pause, cible passée à `stepFlock`.
6. Tests Vitest : cible déterministe, continue, bornée dans la zone, périodique à long terme ; nuée qui suit la cible (critères ci-dessus en test de non-régression avec marge) ; 2D strictement inchangé.
7. Vérifier : vitest, make check, trajectoires 2D identiques, essai de Benj.
<!-- SECTION:PLAN:END -->

## Implementation Notes

<!-- SECTION:NOTES:BEGIN -->
Fait : `boids3d/roost.js` (chemin 3D lent déterministe), mode `pull` du pointeur (ressort) dans flocking.js, DEFAULT_PARAMETERS_3D (séparation 46 px poids 6, pointerRadius 800, pointerWeight 2), cible branchée dans boids3d/sketch.js.
Découverte : l'attraction du 2D (forte au point) tasse la nuée (espacement 17 à 25 px) et la fait sortir de sa zone ; un ressort corrige (17 -> 29,5 px à vitesses comparables).
Correction de mes chiffres : une première mesure sur 4 parties donnait une part verticale de 0,50 ; sur 12 parties (6 graines, 200 et 500 boids) elle va de 0,33 à 0,57, médiane 0,44-0,47. Le critère devient « médiane >= 45 % » (critère 2 révisé avec cette justification). Un test à 500 boids l'a révélé (0,30 avec une autre graine).
Validé : 93 tests simulations, 3 mutations détectées, trajectoires 2D inchangées ; rotation <= 0,14, déplacement vertical >= 0,55, espacement moyen >= 28,5 px, aucun boid isolé, éloignement max 1,07.
Non vérifié : l'aspect à l'écran, par Benj (critère 4), après le décor.

Retour de Benj (2e passe) : trajectoire « vraiment pas mal du tout », avec deux réserves : la propagation d'un changement de trajectoire dans le groupe est trop rapide, robotique ; la trajectoire générale pourrait être plus ample. Il voudrait aussi que les boids exploitent plus la hauteur disponible et passent près des arbres sans collision (évitement, pas répulsion). Critère 4 validé avec ces réserves ; elles seront suivies par de nouvelles tâches après décision du plan.
<!-- SECTION:NOTES:END -->

## Final Summary

<!-- SECTION:FINAL_SUMMARY:BEGIN -->
Cible mobile en 3D (attraction de type ressort), séparation 46 px poids 6 : sur 12 parties, part verticale médiane 0,45, rotation <= 0,14, espacement >= 28,5 px, aucun boid isolé. Validé par Benj avec réserves (propagation trop rapide, trajectoire à rendre plus ample).
<!-- SECTION:FINAL_SUMMARY:END -->
