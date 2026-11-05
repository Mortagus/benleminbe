# Simulations Du Lab

Ce document décrit la page `/lab/simulations` et la simulation des boids.

## Rôle

Présenter des simulations interactives où des règles simples produisent un comportement d'ensemble. La première simulation est celle des boids (modèle de Craig Reynolds, 1986), reprise d'un ancien projet personnel de coding challenges.

## Routes Et Templates

- `app_lab_simulations` -> `/lab/simulations` (`LabController::simulations`)
- Template : `templates/lab/simulations/index.html.twig`
- Carte de présentation sur `/lab` : `templates/lab/index.html.twig`
- Textes FR/EN : clés `simulations.*` et `tools.simulations.*` dans `translations/lab.*.yaml`, libellé de navigation `header.navigation.simulations` dans `translations/layout.*.yaml`

Les routes du Lab ne portent pas de préfixe de langue et ne sont pas dans le sitemap, comme le tracker DnD.

## Code Front

```text
assets/scripts/lab/simulations/boids/
├── vector.js    # helpers vectoriels 2D immuables
├── flocking.js  # règles de Reynolds, logique pure
├── sketch.js    # rendu p5.js (mode instance), canvas responsive
└── main.js      # branchement DOM : pause, reset, curseurs
assets/styles/lab/simulations.css
tests/js/lab/simulations/flocking.test.js
```

Entrypoint Importmap : `lab_simulations_boids`.

Choix de conception :

- p5.js ne gère que le canvas et la boucle `draw`. Les règles (alignement, séparation, cohésion) sont dans `flocking.js`, sans dépendance à p5 ni au DOM, pour rester testables avec Vitest.
- Chaque frame calcule toutes les accélérations à partir d'un même instantané du groupe : le résultat ne dépend pas de l'ordre des boids.
- La vitesse est plafonnée à `maxSpeed` (absent de la version d'origine).
- Les couleurs sont lues depuis les variables CSS du site (`--card`, `--accent-text`) pour suivre le thème clair/sombre.
- Si `prefers-reduced-motion` est actif, la simulation démarre en pause.
- La complexité est en O(n²) par frame, acceptable pour quelques centaines de boids.

## p5.js

- Version 1.11.x, déclarée dans `importmap.php` et téléchargée dans `assets/vendor/` (ignoré par Git).
- Après un clone : `php bin/console importmap:install`. `make deploy` l'exécute avant `asset-map:compile`.
- p5.js 2.x n'est pas utilisé : son paquet ESM entraîne une vingtaine de dépendances transitives dans l'importmap.
- `assets/vendor/` est exclu d'ESLint.
