---
id: TASK-6.5
title: 'Boids 3D, étape 3 : orientation haut/bas des oiseaux'
status: In Progress
assignee:
  - '@claude'
created_date: '2026-11-07 09:54'
updated_date: '2026-11-07 09:59'
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
- [x] #1 Le haut d’un oiseau ne se retourne pas en vol à peu près horizontal, y compris lors d’un demi-tour complet, vérifié par des tests
- [x] #2 Près de la verticale le haut change progressivement, sans saut brusque, vérifié par des tests
- [x] #3 La rotation appliquée par p5 donne bien le repère attendu, vérifié dans le navigateur
- [ ] #4 make check passe et Benj ne voit plus de retournement à l’écran
<!-- AC:END -->

## Implementation Plan

<!-- SECTION:PLAN:BEGIN -->
1. Remplacer `rotationFromXAxis` (rotation la plus courte, sans notion de haut) par un repère complet : direction du boid et vecteur haut. Le haut cible est le haut du monde (en p5, l'axe y pointe vers le bas, donc le haut du monde est (0,-1,0)) projeté perpendiculairement à la direction.
2. `updateUps(ups, directions, factor)` : un vecteur haut unitaire par boid, rapproché de la cible à chaque appel, reprojeté perpendiculairement à la direction pour rester orthogonal ; si la cible n'existe pas (direction verticale), conserver le haut précédent ; un boid nouveau démarre sur la cible. Même convention que `updateDirections` (en place, redimensionnement).
3. `orientationMatrix(direction, up, out)` : matrice 4x4 en colonnes [direction, -haut, haut x direction] pour le modèle de l'oiseau (nez +x, ventre +y, ailes sur z).
4. Brancher dans `boids3d/sketch.js` avec `applyMatrix` à la place de `rotate`.
5. Tests : le haut ne se retourne pas en vol horizontal (cercle complet, demi-tour), continuité près de la verticale, direction exactement verticale, nouveau boid, repère orthonormé et direct, ailes horizontales en vol de croisière.
6. Vérifier dans le navigateur que `applyMatrix` de p5 applique bien la matrice attendue (sonde de la matrice de modèle), capture d'écran, make check.
<!-- SECTION:PLAN:END -->

## Implementation Notes

<!-- SECTION:NOTES:BEGIN -->
Fait : `boids3d/orientation.js` réécrit (`updateUps`, `orientationMatrix`, `WORLD_UP`), rendu branché avec applyMatrix, ancienne rotationFromXAxis supprimée.
Choix : haut lissé par rotation autour de la direction (fraction 0,1 par frame), ancien haut conservé quand la direction est exactement verticale.
Incident : ma première version mélangeait linéairement le haut et sa cible ; un oiseau exactement à l'envers restait coincé (test « recovers a belly-down attitude » en échec). Remplacé par une rotation autour de la direction. Un défaut réinjecté (pas de cas vertical) n'était pas détecté : test ajouté.
Validé : 17 tests orientation, 5 mutations détectées, make check OK (243 tests) ; mesure sur 690 000 boids-frames : retournements >90° en une frame 28 -> 0 (max 178° -> 25°), oiseau dos vers le bas 8,6 % -> 0,7 % ; applyMatrix de p5 conforme (écart nul) ; aucune erreur JS ; ~52 fps à 200 boids en rendu logiciel.
Non vérifié : l'aspect à l'écran, par Benj (critère 4).
Prochaine action : Benj regarde la page de test et valide ; puis TASK-6.6 (comportement 3D), qui attend sa décision sur le mécanisme des trajectoires 3D (Q12).
<!-- SECTION:NOTES:END -->
