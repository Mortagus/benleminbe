# Évolutions Des Simulations Boids

Note de suivi des évolutions envisagées pour la simulation des boids du Lab. La référence stable de l'existant est [simulations.md](../lab/simulations.md).

Statut au 2026-11-07 : le lot A est livré, le lot B est livré (suivi : TASK-3 dans Backlog.md) avec un curseur limité à 1000 boids, la 3D est un projet séparé, cadré dans boids-3d-projet.md (plan approuvé, suivi TASK-6).

## État Actuel

Livré (commits `6fe7817`, `abab182`, `e4a8086`) :

- logique pure sur nombres bruts dans `flocking.js` (plus de `vector.js` ni de `p5.Vector`), boids `{ x, y, vx, vy, ax, ay }` ;
- séparation, cohésion et alignement progressifs avec des poids réglables ;
- champ de vision avec angle mort, limite aux `maxNeighbours` voisins les plus proches ;
- force de croisière, lissage de l'accélération et du cap dessiné ;
- frontière de rebouclage hors champ ;
- clic gauche répulsif, clic droit attractif ;
- repère de la valeur par défaut sous chaque curseur.

Constat à garder en tête : sans perturbation, le groupe converge vers un bloc unique qui vole en ligne droite (polarisation proche de 1). Le pointeur permet de le casser. Un petit bruit de direction par boid (« wander ») est une piste possible, qui demanderait de passer un générateur aléatoire à `stepFlock`.

Point technique ouvert : des tests JS (DnD, Simon, et même ceux des boids) expirent de façon intermittente sous charge et peuvent bloquer le hook pre-commit. Sans lien avec les boids ; un contournement est en place, voir [vitest-timeouts-notes.md](vitest-timeouts-notes.md).

## Lot B : Performance À Grand Effectif

Objectif initial : pouvoir monter le curseur du nombre de boids jusqu'à 5000. Résultat : tableaux plats et grille spatiale livrés, 5000 boids testés puis abandonnés comme plafond. Le calcul est passé de 1422 ms à environ 35 ms par frame à 5000 boids, mais le rendu complet tombe à 13,5 images par seconde (Chrome headless), et à cette densité le groupe ressemble à un fluide de particules plus qu'à une nuée. Décision de Benj le 2026-11-07 : le curseur va de 100 à 1000 par pas de 50, le reste du calcul est conservé. Un simulateur de fluide serait un autre projet.

Travail réalisé :

- grille spatiale (cellules de la taille du rayon de perception) pour passer d'environ O(n²) à environ O(n) : faite, 35 ms par frame à 5000 boids (logique seule) contre 1422 ms avant ;
- tableaux plats (`Float32Array`) pour éviter une allocation d'objet par boid et par frame : faits ; le double buffer a été écarté après mesure (l'allocation d'un groupe coûte environ 0,05 ms sur 36 ms) ;
- les deux passes par boid (voisins puis séparation) parcourent maintenant la même liste de candidats de la grille ;
- rendu p5 : mesuré, 60 images par seconde jusqu'à 1000 boids, 30 à 2500, 13,5 à 5000 ; pas de rendu simplifié, la limite à 1000 suffit ;
- tests, `sketch.js` et `simulations.md` : adaptés.

## Mobile À Retravailler

Retour de Benj le 2026-11-07, après test en production sur téléphone : le canvas est très petit (peu de boids visibles en même temps) et les contrôles sont pénibles. À reprendre plus tard, aucune décision prise. Suivi : TASK-5 dans Backlog.md. Le tactile ne gère aujourd'hui que le « clic gauche » (voir `simulations.md`).

## Piste 3D

Projet séparé, cadré dans [boids-3d-projet.md](boids-3d-projet.md) : périmètre et décisions validés, plan d'implémentation approuvé (TASK-6 dans Backlog.md). Les anciennes décisions 3, 4 et 5 de cette note sont tranchées (voir ce document).

## Prochaine Action

Lot B et unités des curseurs terminés. Prochaine action : suivre le plan de [boids-3d-projet.md](boids-3d-projet.md), étape en cours indiquée dans TASK-6.
