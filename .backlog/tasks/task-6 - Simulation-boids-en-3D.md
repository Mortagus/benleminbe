---
id: TASK-6
title: Simulation boids en 3D
status: In Progress
assignee:
  - '@claude'
created_date: '2026-11-07 09:24'
updated_date: '2026-11-07 09:54'
labels:
  - lab
  - 3d
dependencies: []
documentation:
  - docs/en-cours/boids-3d-projet.md
  - docs/lab/simulations.md
priority: medium
ordinal: 10000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Ajouter au Lab une simulation 3D d’une nuée d’oiseaux, à côté de la simulation 2D qui reste la version pédagogique. Pièce visuelle : coucher de soleil fixe, oiseaux en forme simple sans animation, décor en silhouettes sombres (sol et horizon, arbres, lignes électriques), caméra orbitale au-dessus du sol, ordinateur d’abord. Le comportement de la nuée, jugé très réussi par Benj, reprend les règles du 2D avec en plus un espacement entre voisins et des trajectoires qui exploitent les 3 dimensions. Cadrage, décisions et plan en 8 étapes validés le 2026-11-07 (puis complétés après le retour de Benj sur l’étape 2) dans docs/en-cours/boids-3d-projet.md. Hors périmètre : battement d’ailes, inclinaison dans les virages, pointeur 3D, simulateur de fluide.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [ ] #1 La simulation 3D est visible et fluide sur ordinateur dans `/lab/simulations`, avec une caméra orbitale qui ne passe jamais sous le sol, sans dupliquer le calcul du 2D
- [ ] #2 Les oiseaux ont un haut et un bas sans retournement, gardent de l’espace avec leurs voisins et ont des trajectoires qui exploitent les 3 dimensions, la nuée restant au-dessus du sol et du décor
- [ ] #3 Le ciel est un coucher de soleil fixe et le décor (sol, horizon, arbres, lignes électriques) est en silhouettes sombres
- [ ] #4 Pause, réinitialisation et nombre de boids (100 à 500) sont disponibles, avec textes FR et EN
- [ ] #5 `docs/lab/simulations.md` et `docs/en-cours/boids-3d-projet.md` sont à jour, `make check` passe
<!-- AC:END -->

## Implementation Notes

<!-- SECTION:NOTES:BEGIN -->
2026-11-07 : étape 1 terminée (TASK-6.1). Prochaine : TASK-6.2, rendu minimal p5 WEBGL.

2026-11-07 : étape 2 codée et commitée, TASK-6.2 attend la validation visuelle de Benj (critère 4).

2026-11-07 : changement de périmètre à trancher. Après la page de test de l'étape 2, Benj attendait une scène réaliste avec sol et éléments de paysage (arbres, routes, buissons, lignes électriques), absents du cadrage ; sans eux l'orbite de la caméra n'a pas de sens. Cela rouvre le rendu (D1), la caméra (D6) et la zone de confinement, et suspend TASK-6.3 et TASK-6.4. Voir docs/en-cours/boids-3d-projet.md, section Évolution de périmètre. Les critères de TASK-6 seront révisés une fois le périmètre décidé.
<!-- SECTION:NOTES:END -->
