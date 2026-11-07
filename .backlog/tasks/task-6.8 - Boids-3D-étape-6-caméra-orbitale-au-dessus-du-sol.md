---
id: TASK-6.8
title: 'Boids 3D, étape 6 : caméra orbitale au-dessus du sol'
status: In Progress
assignee:
  - '@claude'
created_date: '2026-11-07 09:54'
updated_date: '2026-11-07 11:34'
labels:
  - lab
  - 3d
dependencies:
  - TASK-6.13
documentation:
  - docs/en-cours/boids-3d-projet.md
parent_task_id: TASK-6
ordinal: 18000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Avec un sol, orbitControl de p5 (sans limite d’angle) ferait passer la caméra sous le sol. Benj a choisi une orbite autour de la scène au-dessus du sol. Voir D6 de docs/en-cours/boids-3d-projet.md.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [x] #1 Une fonction pure donne la position de la caméra depuis azimut, hauteur d’angle et distance, avec des limites qui interdisent de passer sous le sol, vérifiée par des tests
- [x] #2 La souris, le tactile et la molette modifient cet état, vérifié en navigateur (tactile émulé)
- [ ] #3 Benj valide la manipulation de la caméra
<!-- AC:END -->

## Implementation Plan

<!-- SECTION:PLAN:BEGIN -->
1. Caméra écrite à la main, à la place de orbitControl de p5 (qui n'a pas de limite d'angle). Module pur `boids3d/camera.js` : un état (azimut, hauteur d'angle, distance) autour d'un point visé fixe (le centre de la scène, 200 unités sous le centre de la nuée comme la caméra de départ) ; `cameraPosition(état, cible)` ; `limitCamera` qui garde la caméra au moins 20 unités au-dessus du sol (la limite dépend de la distance), borne la hauteur d'angle (pas de passage par le pôle) et la distance (pas plus loin que l'anneau de collines) ; `orbit` et `zoom`.
2. L'état de départ reproduit la caméra de départ actuelle (hauteur de 91 unités au-dessus du sol, 1150 de distance).
3. Entrées dans `boids3d/sketch.js` : événements pointeur sur le conteneur (souris : glisser pour tourner ; tactile : un doigt tourne, deux doigts pincent pour zoomer ; molette : zoom), `touch-action: none` sur le canvas ; pas de déplacement latéral de la cible. Ajouter un accesseur de position de caméra pour les diagnostics.
4. Tests Vitest sur les fonctions pures avec mutations : la caméra reste au-dessus du sol pour toutes les combinaisons, la hauteur d'angle et la distance sont bornées, l'état de départ reproduit la caméra actuelle.
5. Vérifier dans le navigateur : glisser à fond vers le bas et le haut puis lire la position réelle de la caméra, molette, tactile émulé à un et deux doigts, captures, make check.
6. Limite attendue : sur un vrai tactile, `touch-action: none` empêche de faire défiler la page par-dessus le canvas (lié à TASK-5, mobile).
<!-- SECTION:PLAN:END -->

## Implementation Notes

<!-- SECTION:NOTES:BEGIN -->
Fait : `boids3d/camera.js` (maths pures : cameraPosition, stateFromPosition, limitCamera, orbit, zoom), entrées souris/tactile/molette dans boids3d/sketch.js, orbitControl retiré, accesseur cameraPosition() pour les diagnostics, touch-action: none sur le conteneur.
Règles : caméra au moins 20 unités au-dessus du sol (borne sur la hauteur, donc l'angle minimal dépend de la distance), distance entre 300 et 4000, hauteur d'angle <= 85 degrés.
Validé : 19 tests camera dont une session aléatoire de 5000 gestes, 6 mutations détectées (retrait de la limite du sol : 4 tests en échec) ; dans le navigateur, position réelle lue après des gestes extrêmes : glisser de 2000 px vers le haut (2 fois) -> 20 unités au-dessus du sol, glisser de 4000 px vers le bas -> limite de 85 degrés, molette -> distance entre 300 et 4000, tactile émulé : un doigt tourne, deux doigts écartés rapprochent ; captures des vues extrêmes lisibles ; make check OK (293 tests) ; aucune erreur JS.
Limite : touch-action: none empêche de faire défiler la page en posant le doigt sur le canvas (à traiter avec TASK-5, mobile). Pas de déplacement latéral de la cible (choix de simplicité).
Non vérifié : la manipulation ressentie par Benj (critère 3), un vrai écran tactile.
Prochaine action : Benj valide la caméra ; puis, si Benj est d'accord, TASK-6.11 (évitement des arbres et trajectoire plus près du sol) avant TASK-6.12 (meneurs).
<!-- SECTION:NOTES:END -->
