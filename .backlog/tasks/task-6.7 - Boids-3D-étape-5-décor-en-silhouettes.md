---
id: TASK-6.7
title: 'Boids 3D, étape 5 : décor en silhouettes'
status: Done
assignee:
  - '@claude'
created_date: '2026-11-07 09:54'
updated_date: '2026-11-07 10:54'
labels:
  - lab
  - 3d
dependencies:
  - TASK-6.6
documentation:
  - docs/en-cours/boids-3d-projet.md
parent_task_id: TASK-6
ordinal: 17000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Benj attendait une scène réaliste avec un sol et des éléments de paysage ; décor retenu le 2026-11-07 : silhouettes sombres sur le ciel, avec sol ou champ et horizon, arbres, lignes électriques aériennes (routes, buissons et bâtiments plus tard). Voir D7 de docs/en-cours/boids-3d-projet.md.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [x] #1 Un générateur pur et déterministe produit sol, horizon, arbres, poteaux et câbles, avec des tests de placement (rien dans la zone de vol, câbles reliés aux poteaux, résultat reproductible)
- [x] #2 Le décor est dessiné par deux géométries fusionnées construites une fois (sol, reste du décor) et le sol est sous la zone de vol : la nuée ne traverse jamais le décor
- [x] #3 Les images par seconde avec et sans décor sont mesurées et consignées
- [x] #4 Benj valide l’aspect du décor
<!-- AC:END -->

## Implementation Plan

<!-- SECTION:PLAN:BEGIN -->
1. Générateur pur et déterministe `boids3d/landscape.js` (graine fixe, PRNG local) : sol (disque plat), couronne de collines à l'horizon, arbres (conifères et feuillus) groupés en bosquets, deux lignes de poteaux avec leurs câbles qui s'affaissent. Sortie : triangles à plat (Float32Array) pour le sol et pour les silhouettes, plus la description structurée (arbres, poteaux, câbles) pour les tests.
2. Hauteur du sol calculée : sous le point le plus bas de la zone de vol (1,1 fois la demi-hauteur de la scène, car la nuée dépasse la zone de 7 % au plus) plus la hauteur du plus grand élément plus une marge de 40. Rien ne doit entrer dans l'ellipsoïde de vol.
3. Rendu : deux géométries fusionnées construites une fois avec buildGeometry (sol, silhouettes), deux appels de dessin par frame, plan lointain de la caméra repoussé pour voir l'horizon. Couleurs : oiseaux plus sombres que le décor, sol un peu plus clair, à régler à l'étape finale.
4. Tests Vitest : déterminisme, nombres finis, sol plat, aucun sommet dans la zone de vol, rien de plus haut que la hauteur maximale ni sous le sol, arbres à l'écart des lignes, câbles reliant bien les poteaux et s'affaissant, budget de triangles.
5. Vérifier : tests, make check, mesure des images par seconde avec et sans décor, capture d'écran, validation de Benj. Limite connue à signaler : orbitControl permet encore de passer sous le sol jusqu'à l'étape caméra.
<!-- SECTION:PLAN:END -->

## Implementation Notes

<!-- SECTION:NOTES:BEGIN -->
Fait : `boids3d/landscape.js` (sol, collines de l'horizon, 78 arbres conifères et feuillus par bosquets, 2 lignes de 11 poteaux avec câbles qui s'affaissent, ~7000 triangles), rendu en deux géométries fusionnées, caméra de départ abaissée près du sol, plan lointain à 15000. Critère 2 révisé : deux appels (sol, reste) et pas un seul.
Incident : p5 garde dans une géométrie construite avec buildGeometry la couleur active à la construction ; mon fill() au dessin n'avait aucun effet et tout sortait à la couleur des oiseaux. Corrigé en fixant la couleur avant chaque construction, constaté sur une capture.
Validé : 18 tests landscape, 5 mutations sur 6 détectées, make check OK (274 tests) ; captures en 3 angles ; 42 fps à 200 boids et 31 à 500 avec décor (52 et 36 sans) en Chrome headless à rendu logiciel ; aucune erreur JS.
Non détecté par les tests : retirer la règle « aucun arbre sous la nuée » (elle ne se déclenche presque jamais avec ce placement) : garde défensive non exercée.
Limite connue : orbitControl permet de passer sous le sol (le sol masque alors l'écran), à corriger à TASK-6.8.
Non vérifié : l'aspect à l'écran par Benj (critère 4), le rendu sur un vrai GPU.
Prochaine action : Benj regarde la page de test ; puis revenir aux étapes restantes (validation visuelle des étapes 3 et 4, caméra TASK-6.8, intégration TASK-6.3, réglage visuel TASK-6.4).

Retour de Benj (2e passe, 2026-11-07) : « la scène est lisible : le paysage, le ciel, le sol, on voit clairement les éléments » : critère 4 validé. Demandes nouvelles consignées dans docs/en-cours/boids-3d-projet.md : proportion oiseau/arbre et repère d'échelle, évitement des arbres, délai de propagation, trajectoire plus ample.
<!-- SECTION:NOTES:END -->

## Final Summary

<!-- SECTION:FINAL_SUMMARY:BEGIN -->
Décor en silhouettes (sol, collines, 78 arbres, 2 lignes de poteaux avec câbles) en deux géométries fusionnées, sol sous la zone de vol. Vérifié par 18 tests, 5 mutations sur 6 détectées, make check, captures sur 3 angles, mesure des fps. Lisibilité validée par Benj ; échelle du décor et repère de taille à reprendre.
<!-- SECTION:FINAL_SUMMARY:END -->
