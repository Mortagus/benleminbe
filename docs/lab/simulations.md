# Simulations Du Lab

Ce document décrit la page `/lab/simulations` et la simulation des boids.

## Rôle

Présenter des simulations interactives où des règles simples produisent un comportement d'ensemble. La première simulation est celle des boids (modèle de Craig Reynolds, 1986), reprise d'un ancien projet personnel de coding challenges.

## Routes Et Templates

- `app_lab_simulations` -> `/lab/simulations` (`LabController::simulations`)
- Template : `templates/lab/simulations/index.html.twig`
- Carte de présentation sur `/lab` : `templates/lab/index.html.twig`
- Textes FR/EN : clés `simulations.*` et `tools.simulations.*` dans `translations/lab.*.yaml` (les unités affichées à côté des valeurs des curseurs sont dans `simulations.boids.controls.units.*` : `px` pour le rayon de perception, `°` pour le champ de vision, `px/image` ou `px/frame` pour la vitesse ; les autres réglages sont des comptes, des multiplicateurs ou des ratios, sans unité), libellé de navigation `header.navigation.simulations` dans `translations/layout.*.yaml`

Les routes du Lab ne portent pas de préfixe de langue et ne sont pas dans le sitemap, comme le tracker DnD.

## Code Front

```text
assets/scripts/lab/simulations/boids/
├── flocking.js  # règles de Reynolds, grille spatiale et force du pointeur, logique pure sur tableaux plats
├── sketch.js    # rendu p5.js (mode instance), canvas responsive
└── main.js      # branchement DOM : pause, reset, curseurs, clics sur le canvas
assets/styles/lab/simulations.css
tests/js/lab/simulations/flocking.test.js
```

Entrypoint Importmap : `lab_simulations_boids`.

Choix de conception :

- p5.js ne gère que le canvas et la boucle `draw`. Les règles (alignement, séparation, cohésion) sont dans `flocking.js`, sans dépendance à p5 ni au DOM, pour rester testables avec Vitest.
- Le groupe est stocké dans des `Float32Array` plats (`pos`, `vel`, `acc`) avec `dim` valeurs par boid (2 ici) : le boid `i` occupe les cases `i * dim` à `i * dim + dim - 1`. Il n'y a plus d'objet par boid, donc plus d'allocation par boid et par frame. Les règles sont écrites pour un nombre quelconque de dimensions (`dim` est déduit de `size`, par exemple `[largeur, hauteur]`), ce qui prépare une éventuelle version 3D ; seul `sketch.js` suppose le 2D pour le dessin. Les calculs utilisent des nombres bruts, sans `p5.Vector` ni helpers vectoriels : p5 reste cantonné au rendu et la logique reste testable sans canvas.
- Chaque frame calcule toutes les accélérations à partir d'un même instantané du groupe : `stepFlock` renvoie un nouveau groupe et laisse l'ancien intact, donc le résultat ne dépend pas de l'ordre des boids. `resizeFlock` renvoie le même groupe quand l'effectif ne change pas.
- Chaque règle a un poids réglable (alignement, séparation, cohésion) et renvoie une accélération d'au plus `maxForce`, dont l'intensité varie progressivement avec la distance. Il n'y a pas de seuil qui active ou coupe brutalement une force, ce qui évite l'effet « tremblant » :
  - la séparation agit dans un rayon d'espace personnel (`separationRadius`, plus court que la perception) et s'estompe jusqu'à zéro au bord de ce rayon ;
  - la cohésion est d'autant plus forte que le boid est loin du centre de ses voisins ;
  - l'alignement corrige proportionnellement à l'écart de vitesse avec les voisins.
- Perception : un voisin n'est pris en compte pour l'alignement et la cohésion que s'il est dans `perceptionRadius`, dans le champ de vision `perceptionAngle` (en degrés, centré sur le cap ; au-delà de 360° le boid voit tout, en dessous il a un angle mort derrière lui) et parmi les `maxNeighbours` plus proches. Le test d'angle utilise un produit scalaire, sans trigonométrie par paire. La séparation voit tous les boids de l'espace personnel, quel que soit le champ de vision.
- Une force de croisière ramène la vitesse vers `maxSpeed * cruiseSpeedRatio` : sans elle, l'angle mort place les voisins visibles surtout devant le boid, la cohésion tire toujours vers l'avant et tout le groupe finit à la vitesse maximale.
- L'accélération de chaque frame est mélangée avec celle de la frame précédente (`smoothing`, de 0 à 1) : plus la valeur est haute, plus les virages sont lents et fluides. Chaque boid porte donc `ax` et `ay`. La force du pointeur n'est pas lissée, pour que les clics restent réactifs.
- Le cap dessiné est lissé séparément dans `sketch.js` (`smoothAngle`), sans effet sur la simulation.
- La frontière de rebouclage est hors champ : un boid ne réapparaît de l'autre côté qu'après avoir dépassé `BOUNDARY_MARGIN` pixels au-delà du canvas, donc la téléportation n'est pas visible.
- Souris : clic gauche maintenu = le curseur repousse les boids, clic droit maintenu = il les attire (rayon `pointerRadius`). Le pointeur est `{ position, mode }`, avec autant de coordonnées que de dimensions. Le tactile ne gère que le « clic gauche » ; le navigateur peut annuler le geste pour défiler la page.
- La vitesse est plafonnée à `maxSpeed` (absent de la version d'origine).
- Les couleurs sont lues depuis les variables CSS du site (`--card`, `--accent-text`) pour suivre le thème clair/sombre.
- Si `prefers-reduced-motion` est actif, la simulation démarre en pause.
- Les voisins sont cherchés à travers une grille rebâtie à chaque frame (`buildGrid`) : des cellules de côté `perceptionRadius` (au moins aussi grand que le rayon de séparation), remplies par tri par comptage, sans tableau par cellule. Pour chaque boid, `gatherCandidates` rassemble dans un tampon les boids de sa cellule et des cellules voisines (3^dim), puis la perception et la séparation parcourent cette courte liste. Sans grille, les candidats sont tous les autres boids : c'est la version naïve, gardée comme référence pour les tests, qui donne exactement le même résultat. La grille ne regarde pas à travers le rebouclage, comme avant. Les boids hors de la zone couverte sont rangés dans la cellule de bord la plus proche, ce qui ne fait manquer aucun voisin.
- Coût mesuré le 2026-11-07, logique seule (Node 24, canvas 924×520, `maxNeighbours` à 7) : environ 0,8 ms par frame à 500 boids, 1,8 ms à 1000, 7 ms à 2000 et 35 ms à 5000, soit à peu près linéaire au lieu de quadratique (avant la grille : 59 ms à 1000 et 1422 ms à 5000). À 5000 boids le calcul seul dépasse le budget d'une frame à 60 images par seconde. Rendu complet (calcul et p5) mesuré le 2026-11-07 dans Chrome headless, rendu logiciel donc pessimiste : 60 images par seconde jusqu'à 1000 boids, 30 à 2500, 13,5 à 5000. Le curseur du nombre de boids est donc limité à 1000 (de 100 à 1000, par pas de 50) : au-delà, le groupe ressemble plus à un fluide de particules qu'à une nuée, pour une fluidité dégradée.

## p5.js

- Version 1.11.x, déclarée dans `importmap.php` et téléchargée dans `assets/vendor/` (ignoré par Git).
- Après un clone : `php bin/console importmap:install`. `make deploy` l'exécute avant `asset-map:compile`.
- p5.js 2.x n'est pas utilisé : son paquet ESM entraîne une vingtaine de dépendances transitives dans l'importmap.
- `assets/vendor/` est exclu d'ESLint.
