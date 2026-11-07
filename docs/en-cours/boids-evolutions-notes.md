# Évolutions Des Simulations Boids

Note de suivi des évolutions envisagées pour la simulation des boids du Lab. La référence stable de l'existant est [simulations.md](../lab/simulations.md).

Statut au 2026-11-07 : le lot A est livré, le lot B est livré (suivi : TASK-3 dans Backlog.md) avec un curseur limité à 1000 boids, la piste 3D est un projet séparé à cadrer.

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

Idée : une scène 3D avec quelques centaines de boids imitant une nuée d'oiseaux, silhouettes découpées sur un ciel de coucher de soleil.

Éléments de cadrage déjà établis :

- le calcul passe de 2D à 3D pour environ 1,5 fois plus d'arithmétique ; les règles et le test de champ de vision (produit scalaire) restent valables ; la grille voisine passe de 9 à 27 cellules ;
- le gros du travail est le rendu et la scène : caméra, ciel en dégradé, silhouettes, battement d'ailes, inclinaison dans les virages ;
- le rebouclage en tore est peu naturel en 3D : une nuée réelle tourne autour d'un point d'attraction (dortoir) ou est ramenée en douceur vers une zone.

### Décisions À Prendre

| #   | Décision                 | Options                                                                                                                                                                                          | Recommandation                                                                                                           |
| --- | ------------------------ | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------ | ------------------------------------------------------------------------------------------------------------------------ |
| 1   | Bibliothèque de rendu    | p5.js en mode WEBGL (déjà présent, aucune nouvelle dépendance, plus limité pour ciel et effets) ou Three.js (mieux adapté, mais nouvelle dépendance dans l'importmap, plusieurs centaines de Ko) | Commencer en p5 WEBGL ; passer à Three.js si le rendu ne suffit pas, la logique pure restant inchangée                   |
| 2   | Emplacement              | Deuxième simulation sur `/lab/simulations`, à côté du 2D, ou remplacement du 2D                                                                                                                  | Deuxième simulation, le 2D reste comme version pédagogique                                                               |
| 3   | Code partagé ou dupliqué | `flocking3d.js` séparé (simple, duplication des règles) ou version générique en N dimensions (pas de duplication, plus d'abstraction)                                                            | Tranché le 2026-11-07 : version générique en N dimensions (les règles sont écrites sur `dim`)                          |
| 4   | Ordre avec le lot B      | Lot B en 2D d'abord, ou lot B conçu dès maintenant avec une dimension paramétrable                                                                                                               | Tranché le 2026-11-07 : lot B en 2D d'abord, avec un stride `dim` paramétrable dans les tableaux plats                 |
| 5   | Méthode                  | Utiliser `~/.ai-context/project-start.md` pour cadrer le chantier, ou procéder directement                                                                                                       | Le chantier est large et touche une dépendance possible : le workflow prévoit de le proposer, sans le lancer sans accord |

### Questions Ouvertes

- Réalisme visé : se contenter des comportements déjà présents (voisins topologiques, angle mort, lissage), ou ajouter le point d'attraction et l'inclinaison dans les virages ?
- Le bruit de direction (« wander ») doit-il entrer dans le périmètre, en 2D comme en 3D ?
- Faut-il une tâche Backlog.md pour le lot B et pour la 3D ? Aucun outil Backlog.md n'était exposé dans la session du 2026-11-06 : la tâche est à créer par Benj ou à reprendre quand le MCP est disponible.

## Prochaine Action

Finir le lot B (TASK-3.3 : mesure du rendu p5, puis curseur à 5000). La piste 3D est un projet séparé : décisions 1, 2 et 5 à reprendre quand elle sera cadrée.
