# Simulations Du Lab

Ce document décrit les pages `/lab/simulations` (index), `/lab/simulations/boids` (boids en 2D) et `/lab/simulations/boids3d` (boids en 3D), et les simulations elles-mêmes.

## Rôle

Présenter des simulations interactives où des règles simples produisent un comportement d'ensemble. La première simulation est celle des boids en 2D (modèle de Craig Reynolds, 1986), reprise d'un ancien projet personnel de coding challenges ; la seconde, une nuée d'oiseaux en 3D au coucher du soleil, réutilise le même calcul.

## Routes Et Templates

- `app_lab_simulations` -> `/lab/simulations` (`LabController::simulations`) : index qui présente chaque simulation par une carte ; il ne charge aucun script de simulation. Template : `templates/lab/simulations/index.html.twig`.
- `app_lab_simulations_boids` -> `/lab/simulations/boids` (`LabController::simulationsBoids`) : boids en 2D. Template : `templates/lab/simulations/boids.html.twig`, entrypoint `lab_simulations_boids`. C'était l'adresse `/lab/simulations` avant l'arrivée de la 3D ; l'ancienne adresse répond maintenant par l'index.
- `app_lab_simulations_boids3d` -> `/lab/simulations/boids3d` (`LabController::simulationsBoids3d`) : boids en 3D. Template : `templates/lab/simulations/boids3d.html.twig`, entrypoint `lab_simulations_boids3d`. L'adresse est en minuscules (les adresses sont sensibles à la casse : `/boids3D` donne une 404).
- Le menu « Simulations » reste actif sur les trois pages (routes qui commencent par `app_lab_simulations`).
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

```text
assets/scripts/lab/simulations/boids3d/
├── sketch.js      # rendu p5.js WEBGL : canvas transparent, oiseaux, décor, caméra, entrées souris et tactile
├── main.js        # branchement DOM de la page 3D : pause, réinitialisation, trois curseurs
├── sliders.js     # conversion pure entre la valeur d'un curseur et le paramètre (le pourcentage de meneurs)
├── orientation.js # haut et bas des oiseaux, repère de chaque oiseau (maths pures)
├── camera.js      # caméra orbitale limitée au-dessus du sol (maths pures)
├── landscape.js   # générateur du paysage : sol, arbres, lignes électriques, obstacles (pur, déterministe)
└── roost.js       # chemin de la cible qui attire la nuée (pur, déterministe)
tests/js/lab/simulations/*.test.js  # un fichier par module, plus avoidance, shape et flock-metrics.js
```

Entrypoint Importmap de la 3D : `lab_simulations_boids3d`. Il charge le calcul de `boids/flocking.js`, sans le dupliquer. La page 3D a trois curseurs, qui agissent en direct parce que la simulation relit ses paramètres à chaque image : le nombre de boids (100 à 500, 200 par défaut), la part de meneurs (5 à 100 %, 15 % par défaut) et l'intensité de l'ondulation (0 à 1,5, 0,7 par défaut). Comme le 2D, elle démarre en pause si `prefers-reduced-motion` est actif. Le canvas est transparent : le ciel est le fond CSS `.boids-canvas--sunset` du conteneur, quel que soit le thème du site.

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
- Confinement doux (`confinementWeight`, 0 par défaut donc désactivé en 2D) : à la place du rebouclage, une force ramène les boids vers le centre de l'ellipsoïde inscrit dans le terrain. Elle est nulle jusqu'à `confinementStart` (une fraction du rayon), croît progressivement jusqu'à la surface où elle vaut `maxForce * confinementWeight`, et ne dépasse pas cette valeur au-delà. Les distances sont normalisées par la demi-étendue de chaque axe. Elle sert à la 3D, où une caméra qui tourne rendrait le rebouclage visible. `SCENE_SIZE_3D` (1800×700×700) et `DEFAULT_PARAMETERS_3D` donnent le terrain et les réglages de départ de la 3D : le terrain est large et haut mais peu profond, car la caméra reste à environ 1200 unités de la nuée et une nuée qui bougerait beaucoup en profondeur passerait trop près ou trop loin. La zone confinée n'est pas un ellipsoïde mais une boîte arrondie (`confinementExponent` à 4, contre 2 par défaut) : le fond d'un ellipsoïde est trop étroit pour que la nuée vole bas sur toute la largeur de la scène. Les autres réglages : rayon de perception de 100 px, séparation 46 px avec un poids de 6, vitesse max 4, confinement 4, attraction de la cible `pull` de rayon 1400 et de poids 2, évitement des obstacles de poids 8 sur 50 frames. Les deux derniers (`pointerFraction` et `waveWeight`) sont décrits ci-dessous ; ils sont faits pour être réglés en direct avec deux curseurs. Mesuré sur 6 parties de 3600 frames dans la vraie scène : part verticale de la vitesse 0,48 en médiane, parcours du centre de la nuée d'environ 630 unités en largeur et 230 en hauteur, espacement au 5e centile de 35 px en moyenne, aucun boid isolé plus de 1,3 s, jamais au-delà de 0,95 du chemin vers la surface de la zone.
- Meneurs (`pointerFraction`, 1 par défaut = tous les boids) : seule une fraction des boids perçoit le pointeur (en 3D, la cible, voir `roost.js`), choisie de façon stable et répartie (`isInformed` : les indices sont dispersés par le nombre d'or, donc agrandir la nuée ne change pas qui est informé). Les autres suivent par alignement et cohésion : un changement de direction ne touche plus tous les boids au même instant. Mesuré en renversant la cible d'un coup (200 boids) : avec tous informés, le groupe entier tourne de 45° en 11 images d'écart ; avec 15 % de meneurs, les derniers boids tournent environ 70 images (1,2 s) après la médiane des meneurs. Réduire le rayon de perception n'a aucun effet mesurable sur la forme : les 7 voisins retenus sont déjà les plus proches.
- Onde (`waveWeight`, `waveLength`, `wavePeriod`, désactivée par défaut) : une force verticale qui dépend de la position le long du premier axe et du temps, dont les crêtes avancent à `waveLength / wavePeriod` unités par image. Des boids en crête sont poussés vers le haut, ceux d'une demi-longueur d'onde plus loin vers le bas : la nuée ondule au lieu de bouger comme un bloc. `stepFlock` reçoit le temps en dernier argument. En 3D (meneurs 15 %, onde de poids 0,7) : épaisseur de la nuée (axe court sur axe long, 1 pour une boule) 0,43 en moyenne contre 0,67 sans, polarisation 0,81 contre 0,98, 17 % des boids qui vont en sens vertical opposé à la moyenne contre 0 %, le plus grand groupe tombe jusqu'à 54 % puis se reforme. Un poids de 0,5 ne défait la cohésion que rarement (0 à 10 % du temps) ; à 1,0, la nuée se scinde en colonnes verticales. Renforcer l'attraction des meneurs pour compenser le parcours plus court dégrade la cohésion (des boids isolés 10 à 14 s à partir d'un poids de 3,5).
- Évitement des obstacles (`avoidanceWeight`, 0 par défaut donc inactif en 2D), prédictif et non répulsif : chaque boid suit sa vitesse sur `avoidanceLookahead` frames ; si ce chemin passe à moins de rayon plus `avoidanceClearance` d'une sphère (un cercle en 2D), il est dévié perpendiculairement à sa vitesse, du côté qui s'en éloigne (de côté, ou vers le haut ou le bas si l'obstacle est au-dessus ou au-dessous du chemin), d'autant plus que l'obstacle est proche dans le temps et que le chemin le traverse. Sans menace, aucune force. Seul l'obstacle le plus urgent compte, et un obstacle derrière le boid ne compte pas. L'urgence vaut 1 dès que le chemin touche l'obstacle lui-même ; mesurée contre le rayon plus la marge, un chemin par le bord d'un gros obstacle serait presque ignoré (c'était la première version, trop faible). Le sol est traité de même (`floorLevel`). Un filet de sécurité remet sur la surface un boid qui finit une frame dans un obstacle ou sous le sol et annule sa vitesse entrante ; il compte ses interventions (`stats`) et n'agit presque jamais quand l'évitement fonctionne. Un test de hauteur évite de tester chaque obstacle pour les boids qui volent bien au-dessus : à 500 boids l'évitement coûte 0,1 ms par frame au lieu de 4, sans changer le résultat (test d'équivalence).
- Obstacles et repères : le paysage (`boids3d/landscape.js`) est centré sur le milieu de la scène, alors que les positions de la nuée vont de 0 à la taille du terrain ; `buildEnvironment` convertit les obstacles (des sphères pour les couronnes des arbres : une pour un feuillu, trois pour un conifère) et le niveau du sol vers le repère de la nuée.
- `updateDirections` garde un vecteur direction unitaire par boid et le rapproche de la direction de la vitesse à chaque appel. Contrairement à un angle, il n'a ni saut à 360° ni blocage de cardan, et fonctionne en 2D comme en 3D. Il ne sert qu'au dessin.
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
