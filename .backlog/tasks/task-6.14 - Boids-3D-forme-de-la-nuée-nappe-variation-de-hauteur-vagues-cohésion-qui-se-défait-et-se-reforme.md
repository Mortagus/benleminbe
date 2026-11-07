---
id: TASK-6.14
title: >-
  Boids 3D : forme de la nuée (nappe, variation de hauteur, vagues, cohésion qui
  se défait et se reforme)
status: In Progress
assignee:
  - '@claude'
created_date: '2026-11-07 12:28'
updated_date: '2026-11-07 13:41'
labels:
  - lab
  - 3d
dependencies:
  - TASK-6.12
documentation:
  - docs/en-cours/boids-3d-projet.md
parent_task_id: TASK-6
ordinal: 24000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Retour de Benj le 2026-11-07 : la trajectoire est très bien, mais la nuée reste une sorte de boule, avec des mouvements fluides mais tous dans une direction trop similaire. Une vraie nuée a parfois cette forme de boule, mais le plus souvent c’est une nappe de quelques oiseaux d’épaisseur, étendue, qui bouge de façon coordonnée avec plus de variation de hauteur au sein du groupe. Il doit y avoir un effet de vague dans le mouvement, des moments où la cohésion disparaît pour se reformer ensuite, des boids qui partent vers le haut pendant que d’autres vont vers le sol. Pistes de Benj : réduire le pourcentage de meneurs (TASK-6.12, pas encore implémenté) et peut-être réduire les capacités de perception des boids.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [x] #1 La forme de la nuée est mesurée (rapport d’épaisseur : axe le plus court sur le plus long de la nuée) et la nuée est le plus souvent une nappe, avec des moments plus ronds
- [x] #2 La cohésion se défait et se reforme : la part des boids dans le plus grand groupe varie dans le temps (mesurée) sans qu’un boid reste isolé durablement
- [x] #3 Au sein de la nuée, des boids montent pendant que d’autres descendent (part des boids qui vont dans le sens opposé à la moyenne, mesurée)
- [x] #4 Les changements de direction se propagent en vague (délai mesuré, avec TASK-6.12)
- [x] #5 La trajectoire d’ensemble, l’espacement entre voisins et l’évitement des arbres sont conservés, le 2D est strictement inchangé, tests ajoutés, make check passe
- [x] #6 Benj valide visuellement
<!-- AC:END -->

## Implementation Notes

<!-- SECTION:NOTES:BEGIN -->
2026-11-07 : mesures de la forme de la nuée (200 boids, 2 graines, 2400 frames, vraie scène) avec des indicateurs : épaisseur (axe court sur axe long, 1 = boule), part des boids en sens vertical opposé à la moyenne, part du plus grand groupe, polarisation.
Constat de départ, qui confirme les observations de Benj : épaisseur 0,67 (axes 77/69/52), polarisation 0,98 (tous dans la même direction), 0,00 de boids en sens opposé, plus grand groupe 1,00 en permanence.
Hypothèses de Benj : (1) moins de meneurs : étire la nuée (épaisseur 0,49, axe long 109) mais la polarisation reste à 0,98 ; (2) perception réduite : aucun effet mesurable (0,69 contre 0,67), le rayon n'est pas le facteur, les 7 voisins retenus étant déjà les plus proches.
Verrou identifié : l'alignement, la nuée bouge comme un corps rigide. Prototype d'une ondulation verticale qui traverse la nuée (waveWeight, waveLength, wavePeriod, désactivée par défaut) : à 0,5 polarisation 0,91, boids opposés 0,06, plus grand groupe jusqu'à 0,67, nappe plus large ; à 1,0 polarisation 0,64, boids opposés 0,26, groupe jusqu'à 0,35, nuée scindée en deux colonnes verticales ; avec alignement réduit, polarisation 0,33-0,44 (trop désordonné).
Code : pointerFraction (meneurs, 1 par défaut) et la vague, avec tests (95 tests flocking, 5 mutations détectées), 2D inchangé, non commité. Page de test : tout réglage modifiable par l'adresse.
Non décidé : réglage final, à choisir par Benj à l'écran ; indicateurs = proxies de l'effet visuel.
Prochaine action : Benj compare les variantes sur la page de test.

Fait : onde verticale (`waveWeight` 0,7, `waveLength` 250, `wavePeriod` 240) et meneurs 15 %, `flock-metrics.js` (épaisseur, polarisation, boids en sens opposé, plus grand groupe, boids isolés), `shape.test.js`, 190 tests de simulation, make check OK (353 tests), 2D inchangé.
Mesuré (6 parties de 3600 frames) : épaisseur 0,43 en moyenne (0,83 au plus) contre 0,67 ; polarisation 0,81 contre 0,98 ; boids en sens vertical opposé 0,17 contre 0 ; plus grand groupe 0,97 en moyenne, 0,54 au pire ; boid isolé au plus 1,3 s ; espacement 35 px ; part verticale médiane 0,48.
Causes : l'alignement rend la nuée rigide (polarisation 0,98) ; réduire la perception n'a aucun effet ; moins de meneurs étire sans varier ; l'onde crée les variations de hauteur. Poids 0,5 trop discret (scissions rares), 1,0 scinde en colonnes verticales : 0,7 retenu.
Critère 5 NON coché : « la trajectoire d'ensemble est conservée » est discutable : le parcours du centre est passé de 1,05 / 1,23 à 0,70 / 0,65 (fraction de la demi-étendue) et les rapprochements des arbres de 0,79 à 0,22 par image, parce que seuls 15 % des boids sentent la cible. Renforcer l'attraction dégrade la cohésion (essais à 3,5 et 5). À confirmer par Benj.
Benj : « je valide » + demande de deux curseurs en direct (meneurs, onde), ajoutés à la page de test ; à reprendre dans la page finale (TASK-6.3).

2026-11-07 : Benj confirme que l'amplitude de la trajectoire est bonne. AC5 coché : make check passe (360 tests JS au commit 6e40a53), 2D inchangé.
<!-- SECTION:NOTES:END -->
