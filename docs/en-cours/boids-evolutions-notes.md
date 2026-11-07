# Évolutions Des Simulations Boids

Note de suivi des évolutions envisagées pour la simulation des boids du Lab. La référence stable de l'existant est [simulations.md](../lab/simulations.md).

Statut au 2026-11-07 : le lot A est livré, le lot B est en cours (suivi : TASK-3 dans Backlog.md, étape 1 faite), la piste 3D est un projet séparé à cadrer.

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

## Lot B : Jusqu'à 5000 Boids

Objectif : pouvoir monter le curseur du nombre de boids jusqu'à 5000.

Pistes techniques :

- grille spatiale (cellules de la taille du rayon de perception) pour passer d'environ O(n²) à environ O(n) ;
- tableaux plats (`Float32Array`) pour éviter une allocation d'objet par boid et par frame ;
- les deux passes actuelles sur le groupe par boid (voisins puis séparation) sont à revoir au passage, avec la grille ;
- le rendu p5 en mode 2D de 5000 triangles peut devenir le goulot : à mesurer, un rendu simplifié à grand effectif est possible ;
- les tests et `sketch.js` seront à adapter, et la doc `simulations.md` à mettre à jour.

Ne pas promettre la fluidité à 5000 avant d'avoir mesuré.

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
| 3   | Code partagé ou dupliqué | `flocking3d.js` séparé (simple, duplication des règles) ou version générique en N dimensions (pas de duplication, plus d'abstraction)                                                            | À trancher : c'est le second cas d'usage réel, ce qui lève la règle « pas d'abstraction avant un second cas »            |
| 4   | Ordre avec le lot B      | Lot B en 2D d'abord, ou lot B conçu dès maintenant avec une dimension paramétrable                                                                                                               | À trancher : le lot B fixe le nombre de composantes par boid (2 ou 3) dans les tableaux plats                            |
| 5   | Méthode                  | Utiliser `~/.ai-context/project-start.md` pour cadrer le chantier, ou procéder directement                                                                                                       | Le chantier est large et touche une dépendance possible : le workflow prévoit de le proposer, sans le lancer sans accord |

### Questions Ouvertes

- Réalisme visé : se contenter des comportements déjà présents (voisins topologiques, angle mort, lissage), ou ajouter le point d'attraction et l'inclinaison dans les virages ?
- Le bruit de direction (« wander ») doit-il entrer dans le périmètre, en 2D comme en 3D ?
- Faut-il une tâche Backlog.md pour le lot B et pour la 3D ? Aucun outil Backlog.md n'était exposé dans la session du 2026-11-06 : la tâche est à créer par Benj ou à reprendre quand le MCP est disponible.

## Prochaine Action

Benj réfléchit aux décisions ci-dessus. À la reprise : trancher les points 3, 4 et 5, puis démarrer le lot B (ou la conception 3D) selon l'ordre retenu.
