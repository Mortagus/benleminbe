---
id: TASK-5
title: 'Boids : retravailler l’affichage et les contrôles sur mobile'
status: To Do
assignee: []
created_date: '2026-11-07 09:05'
labels:
  - lab
  - mobile
  - ux
dependencies: []
references:
  - assets/styles/lab/simulations.css
  - assets/scripts/lab/simulations/boids/sketch.js
documentation:
  - docs/lab/simulations.md
  - docs/en-cours/boids-evolutions-notes.md
priority: low
ordinal: 9000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Retour de Benj après déploiement en production le 2026-11-07, testé sur téléphone : la simulation fonctionne bien, mais le canvas est très petit (sa hauteur vaut 9/16 de sa largeur, soit environ 220 px sur un écran de 390 px de large, voir `CANVAS_RATIO` dans `sketch.js`), on voit donc peu de boids à la fois, et les contrôles sont pénibles à utiliser. À traiter plus tard ; aucune décision de design n’est prise.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [ ] #1 Sur un téléphone, le canvas occupe une zone assez grande pour que le groupe soit lisible, vérifié sur un appareil ou une émulation mobile
- [ ] #2 Les réglages sont utilisables au doigt sans effort excessif (cibles tactiles, encombrement, défilement)
- [ ] #3 Le clic gauche et droit de la souris ont un équivalent tactile ou leur absence est documentée
- [ ] #4 L’affichage sur ordinateur n’est pas dégradé
<!-- AC:END -->
