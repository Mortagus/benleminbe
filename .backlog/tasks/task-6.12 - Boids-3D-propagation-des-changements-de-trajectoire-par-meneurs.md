---
id: TASK-6.12
title: 'Boids 3D : propagation des changements de trajectoire par meneurs'
status: Done
assignee:
  - '@claude'
created_date: '2026-11-07 11:03'
updated_date: '2026-11-07 12:59'
labels:
  - lab
  - 3d
dependencies:
  - TASK-6.10
documentation:
  - docs/en-cours/boids-3d-projet.md
parent_task_id: TASK-6
ordinal: 22000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Retour de Benj le 2026-11-07 : le changement de trajectoire se propage dans le groupe de façon trop rapide, robotique. Cause : la cible attire tous les boids au même instant, il n’y a aucun délai. Décision de Benj : meneurs. Seule une fraction des boids (environ 10 à 20 %) perçoit la cible, les autres suivent par alignement et cohésion. Dans de vraies nuées, quelques oiseaux mènent le virage et l’information traverse la nuée à 20 à 40 m/s.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [x] #1 Seule une fraction réglable des boids perçoit la cible, les autres suivent par les règles de groupe
- [x] #2 Le délai de propagation est mesuré (images entre le virage des meneurs et celui des derniers boids) et consigné, avec la fraction retenue
- [x] #3 Espacement, absence de boids isolés et ampleur de la trajectoire restent tenus ; tests ajoutés
- [x] #4 Benj valide visuellement
<!-- AC:END -->

## Implementation Notes

<!-- SECTION:NOTES:BEGIN -->
2026-11-07 : le paramètre des meneurs est implémenté (`pointerFraction`, sous-ensemble stable et réparti par la suite du nombre d'or ; 1 par défaut donc 2D inchangé ; 7 tests, mutations détectées). Mesuré : à 15 % la nuée s'étire (épaisseur 0,49 contre 0,67) mais la polarisation reste à 0,98. Délai de propagation pas encore mesuré. Le réglage final se décide avec TASK-6.14.

Fait : `pointerFraction` (1 = tous, 0,15 par défaut), `isInformed` (sous-ensemble stable et réparti par le nombre d'or), 7 tests, mutations détectées, 2D inchangé.
Délai de propagation mesuré (cible renversée d'un coup, 200 boids, 3 graines) : tous informés, le groupe tourne de 45 degrés en 11 images d'écart ; 15 % de meneurs : les derniers boids tournent environ 70 images (1,2 s) après la médiane des meneurs. Les vraies nuées : environ 0,5 s.
Critère 3 : espacement 35 px, boid isolé au plus 1,3 s, parcours du centre 0,70 en largeur et 0,65 en hauteur, au-dessus du seuil de 0,4 ; mais réduit par rapport à 1,05 et 1,23 avant les meneurs et l'onde (compromis).
Benj le 2026-11-07 : « je valide » sur les variantes comparées à l'écran, et demande deux curseurs en direct (meneurs, onde).
<!-- SECTION:NOTES:END -->

## Final Summary

<!-- SECTION:FINAL_SUMMARY:BEGIN -->
Fraction de meneurs réglable (15 % par défaut, tests, mutations). Délai de propagation mesuré : environ 70 images (1,2 s) avec 15 % de meneurs contre 11 sans. Parcours du centre réduit (0,70 / 0,65 contre 1,05 / 1,23) mais au-dessus du seuil. Validé par Benj.
<!-- SECTION:FINAL_SUMMARY:END -->
