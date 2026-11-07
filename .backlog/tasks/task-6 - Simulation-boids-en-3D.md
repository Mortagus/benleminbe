---
id: TASK-6
title: Simulation boids en 3D
status: In Progress
assignee:
  - '@claude'
created_date: '2026-11-07 09:24'
updated_date: '2026-11-07 11:15'
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

2026-11-07 : plan approuvé et complété par Benj (orientation haut/bas, trajectoires 3D, espacement), 8 étapes, TASK-6.5 à 6.8 créées, TASK-6.3 et 6.4 renumérotées en étapes 7 et 8. Étape 3 (TASK-6.5) démarrée. Le mécanisme des trajectoires 3D (Q12, D10) est à décider avec Benj avant TASK-6.6.

2026-11-07 : étape 3 (TASK-6.5, orientation haut/bas) codée et mesurée, en attente du regard de Benj. Étape 4 (TASK-6.6) attend le choix du mécanisme des trajectoires 3D (Q12).

2026-11-07 : Benj choisit la cible mobile en 3D pour les trajectoires (D10, Q12 tranchée). TASK-6.6 démarrée.

2026-11-07 : à la demande de Benj, le décor (étape 5, TASK-6.7) passe avant la fin de l'étape 4 : la page de test n'avait ni sol ni repère haut/bas. TASK-6.6 est codée et mesurée (12 parties), en attente de sa validation visuelle ; TASK-6.5 aussi.

2026-11-07 : décor en silhouettes (TASK-6.7) codé et mesuré, en attente du regard de Benj. Il reste : validation visuelle des étapes 3, 4 et 5 ; caméra au-dessus du sol (TASK-6.8, nécessaire car orbitControl passe sous le sol) ; intégration à la page (TASK-6.3) ; réglage visuel (TASK-6.4).

2026-11-07 : 2e retour de Benj. Scène lisible (TASK-6.7 terminée), trajectoire « vraiment pas mal du tout » (TASK-6.6 terminée avec réserves). Nouvelles demandes : proportion oiseaux/décor et repère d'échelle, plus de hauteur et évitement des arbres, délai de propagation, trajectoire plus ample ; recherche faite, voir le document. TASK-6.5 reste ouverte : Benj n'a pas dit s'il voit encore des retournements. Plan à réviser après ses décisions (Q13 à Q16).

2026-11-07 : décisions Q13 à Q16 de Benj enregistrées ; tâches TASK-6.9 à 6.13 créées (échelle, trajectoire plus ample, meneurs, évitement des arbres, repères d'échelle) ; exécution 6.9, 6.10, 6.12, 6.11, 6.13 puis 6.8, 6.3, 6.4. Comparaison des proportions prête (TASK-6.9).

2026-11-07 : TASK-6.5 (plus de retournement, validé par Benj) et TASK-6.9 (oiseaux à 50 %) terminées. Prochaine étape : TASK-6.10 (trajectoire plus ample et plus de hauteur). Benj constate que la nuée ne s'approche jamais des arbres : normal, la zone de vol est au-dessus du décor jusqu'à TASK-6.11.
<!-- SECTION:NOTES:END -->
