---
id: TASK-6.2
title: 'Boids 3D, étape 2 : rendu minimal p5 WEBGL'
status: Done
assignee:
  - '@claude'
created_date: '2026-11-07 09:24'
updated_date: '2026-11-07 09:40'
labels:
  - lab
  - 3d
dependencies:
  - TASK-6.1
documentation:
  - docs/en-cours/boids-3d-projet.md
parent_task_id: TASK-6
ordinal: 12000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Premier rendu visible de la nuée 3D, en p5 WEBGL isolé dans un fichier dédié pour pouvoir changer de bibliothèque plus tard. Étape 2 du plan de docs/en-cours/boids-3d-projet.md.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [x] #1 Une scène affiche la nuée avec une forme simple d’oiseau orientée, un canvas transparent et un dégradé CSS de coucher de soleil
- [x] #2 La caméra se manipule avec orbitControl, et son comportement au tactile est vérifié ou signalé
- [x] #3 Les images par seconde sont mesurées dans Chrome headless à 200 et 500 boids et consignées
- [x] #4 Benj valide l’aspect visuel
<!-- AC:END -->

## Implementation Notes

<!-- SECTION:NOTES:BEGIN -->
Fait : `boids3d/sketch.js` (p5 WEBGL, canvas transparent, oiseau construit avec buildGeometry, orbitControl), `boids3d/orientation.js` (pure, 12 tests), classe CSS `.boids-canvas--sunset` pour le ciel.
Validé : rotation de p5 conforme aux maths sur 5 cas ; aucune erreur JS ; capture de la scène (oiseaux lisibles, orientés, sur le dégradé) ; orbitControl répond au glisser souris et tactile (émulé) nuée en pause ; images par seconde en Chrome headless à rendu logiciel : 55 à 200 boids, 36 à 500 boids ; make check OK (238 tests).
Non vérifié : zoom à la molette (signal faible), tactile sur un vrai appareil, rendu sur un vrai GPU.
Constat pour l'étape 4 : la nuée est compacte et petite à l'écran.
Prochaine action : Benj valide l'aspect visuel (critère 4) sur la page de test, puis TASK-6.3.

Validation visuelle de Benj le 2026-11-07, en regardant la page de test : ciel validé (couleurs à retravailler plus tard, elles ne reflètent pas exactement celles qu'il avait en tête) ; forme des oiseaux validée pour le moment (elle paraît plate quand les oiseaux font face à la caméra, à améliorer) ; taille et densité de la nuée bonnes ; comportement jugé très réussi.
Réserves hors critères, consignées dans docs/en-cours/boids-3d-projet.md : forme des oiseaux trop plate, couleurs du ciel à reprendre, et surtout décor attendu (sol, arbres, routes, buissons, lignes électriques) absent du cadrage.
<!-- SECTION:NOTES:END -->

## Final Summary

<!-- SECTION:FINAL_SUMMARY:BEGIN -->
Rendu minimal p5 WEBGL : canvas transparent, ciel CSS, oiseau construit avec buildGeometry, orbitControl, orientation par direction lissée. Vérifié : rotation p5 conforme aux maths, aucune erreur JS, 55 fps à 200 boids et 36 à 500 (Chrome headless, rendu logiciel), make check 238 tests. Aspect validé par Benj avec réserves (forme plate, couleurs du ciel) et un décor manquant au cadrage.
<!-- SECTION:FINAL_SUMMARY:END -->
