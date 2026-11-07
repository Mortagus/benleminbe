---
id: TASK-3.3
title: 'Boids : mesure du rendu et réglage du curseur'
status: Done
assignee:
  - '@claude'
created_date: '2026-11-07 07:32'
updated_date: '2026-11-07 08:38'
labels:
  - lab
  - performance
dependencies:
  - TASK-3.2
parent_task_id: TASK-3
ordinal: 7000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Une fois la logique rapide, le rendu p5 de 5000 triangles peut devenir le goulot. La limite du curseur ne doit monter à 5000 qu’après mesure dans un navigateur, avec un rendu simplifié à grand effectif seulement si nécessaire.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [x] #1 Le rendu à 1000, 2500 et 5000 boids est mesuré dans un navigateur et consigné
- [x] #2 Le curseur monte à 5000, ou à la limite justifiée par la mesure
- [x] #3 `docs/lab/simulations.md` (complexité, structure, limites) et `docs/en-cours/boids-evolutions-notes.md` sont à jour
<!-- AC:END -->

## Implementation Notes

<!-- SECTION:NOTES:BEGIN -->
2026-11-07 : curseur `count` passé de max 300 à max 5000 dans `templates/lab/simulations/index.html.twig` (pas 10), non commité à ce stade.
Mesure du rendu complet (calcul + p5) dans Chrome headless, rendu logiciel donc pessimiste par rapport à un vrai GPU, sans erreur JS : 100 boids 60 fps, 300 boids 60 fps, 1000 boids 60 fps, 2500 boids 30 fps, 5000 boids 13,5 fps.
À trancher avec Benj : plafond du curseur ou rendu simplifié ou calcul plus rapide, après son retour sur la fluidité réelle.

Décision de Benj le 2026-11-07 après essai à l'écran : 5000 boids lague et ressemble à une simulation de fluide plus qu'à une nuée ; le curseur est limité à 100-1000 par pas de 50, le reste du calcul est conservé. Limite justifiée par la mesure (60 fps jusqu'à 1000, 30 à 2500, 13,5 à 5000 en Chrome headless).
Validé : make check OK (206 tests) ; lint:twig OK ; essai manuel par Benj.
<!-- SECTION:NOTES:END -->

## Final Summary

<!-- SECTION:FINAL_SUMMARY:BEGIN -->
Rendu mesuré dans Chrome headless (60 fps jusqu’à 1000 boids, 30 à 2500, 13,5 à 5000). Curseur limité à 100-1000 par pas de 50 sur décision de Benj après essai à l’écran ; docs mises à jour ; make check OK.
<!-- SECTION:FINAL_SUMMARY:END -->
