# Simon

Ce document décrit le jeu public Simon tel qu'il existe dans l'univers `Games`.
Il sert de référence stable pour comprendre son routage, son organisation front et ses invariants de stockage local.

## Rôle

Simon est un jeu de mémoire public accessible sans compte. Il propose une séquence de couleurs à reproduire, un clavier configurable, des préférences audio persistées et un meilleur score local.

Le jeu ne cherche pas à:

- synchroniser un score en ligne;
- conserver une partie en cours entre deux visites;
- dépendre d'un backend dédié;
- devenir un moteur générique pour d'autres jeux.

## Routes Publiques

- `app_games_index` -> `/games`, avec `_locale=en` pour la variante anglaise.
- `app_games_simon` -> `/games/simon`, avec `_locale=en` pour la variante anglaise.
- `app_lab_game_simon` -> `/lab/game-simon` en compatibilité, avec redirection HTTP 301 vers `app_games_simon`.

## Points D'Entrée

- [src/Public/Controller/GamesController.php](../../src/Public/Controller/GamesController.php) : expose l'index `Games` et la page Simon.
- [templates/games/index.html.twig](../../templates/games/index.html.twig) : présente Simon comme premier jeu disponible.
- [templates/games/simon.html.twig](../../templates/games/simon.html.twig) : porte le rendu du jeu et les données traduites injectées au front.
- [assets/pages/games_simon.js](../../assets/pages/games_simon.js) : charge le CSS Simon puis l'initialisation JS.
- [assets/scripts/games/simon/index.js](../../assets/scripts/games/simon/index.js) : point d'entrée JS.
- [assets/scripts/games/simon/controller.js](../../assets/scripts/games/simon/controller.js) : orchestrateur DOM, audio, clavier et cycle de jeu.
- [assets/scripts/games/simon/game.js](../../assets/scripts/games/simon/game.js) : état métier du jeu.
- [assets/scripts/games/simon/audio.js](../../assets/scripts/games/simon/audio.js) : moteur audio Web Audio.
- [assets/scripts/games/simon/audio-preferences.js](../../assets/scripts/games/simon/audio-preferences.js) : lecture/écriture des préférences audio.
- [assets/scripts/games/simon/preferences.js](../../assets/scripts/games/simon/preferences.js) : schéma de préférences consolidées et migration.
- [assets/scripts/games/simon/keyboard.js](../../assets/scripts/games/simon/keyboard.js) : touches configurables et capture clavier.
- [assets/scripts/games/simon/storage.js](../../assets/scripts/games/simon/storage.js) : meilleur score local.
- [assets/styles/games/simon.css](../../assets/styles/games/simon.css) : styles dédiés au jeu.
- [translations/games.fr.yaml](../../translations/games.fr.yaml), [translations/games.en.yaml](../../translations/games.en.yaml) : textes publics FR/EN.
- [tests/js/games/](../../tests/js/games) : tests Vitest de logique, stockage et contrôleur.

## Organisation Front

```text
assets/pages/games_simon.js
  -> assets/scripts/games/simon/index.js
       -> startSimonGamePage()
            -> assets/scripts/games/simon/controller.js
                 -> game.js
                 -> audio.js
                 -> audio-preferences.js
                 -> keyboard.js
                 -> preferences.js
                 -> storage.js
                 -> sound-palettes.js
                 -> sound-note-sets.js
```

## Invariants Importants

- Le plateau comporte quatre zones de jeu liées aux indices `0` à `3`.
- La séquence démarre avec une seule étape puis s'allonge après chaque manche réussie.
- Le clavier par défaut reste `A / Z / Q / S`.
- Les contrôles audio restent séparés des contrôles clavier.
- Les préférences consolidées et le meilleur score restent persistés séparément.
- Les clés `localStorage` restent compatibles avec l'ancien préfixe `benleminbe-lab-simon-*` afin de conserver les données existantes.
- Le jeu doit rester jouable à la souris, au tactile et au clavier.

## Tests À Garder

- `tests/js/games/simon.test.js`
- `tests/js/games/simon-controller.test.js`
- `tests/js/games/simon-preferences.test.js`
- `tests/js/games/simon-audio.test.js`
- `tests/js/games/simon-keyboard.test.js`
- `tests/js/games/simon-sound-palettes.test.js`
- `tests/js/games/simon-sound-note-sets.test.js`

## Points De Vigilance

- Conserver la redirection 301 depuis l'ancienne URL du Lab.
- Ne pas renommer les clés `localStorage` sans migration rétrocompatible.
- Garder les imports, templates et tests alignés sur l'univers `Games`.
