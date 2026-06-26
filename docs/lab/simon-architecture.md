# Architecture Du Simon

Ce document décrit l'implémentation actuelle du jeu Simon public du Lab. Il sert de référence stable pour comprendre où se trouve chaque morceau du code, comment une partie se déroule et quelles interfaces ne doivent pas être cassées lors d'une évolution.

Le code source reste la source de vérité. Cette page documente ce qui est réellement présent dans le dépôt, pas une architecture idéale ou future.

## 1. Rôle Du Module

Le Simon est un jeu public intégré au Lab de `benlemin.be`. Il propose un jeu de mémoire à séquence classique avec quatre zones interactives, un score local, des contrôles souris/tactile/clavier, une configuration des touches, des préférences audio persistées et plusieurs palettes sonores.

Périmètre actuel:

- quatre zones de jeu identifiées par les zones logiques `top-left`, `top-right`, `bottom-left`, `bottom-right`;
- séquence générée aléatoirement et rallongée après chaque manche réussie;
- phases de préparation, démonstration, tour joueur, succès et défaite;
- contrôles clavier configurables;
- bouton mute/unmute;
- réglage de volume, durée des notes et réverbération;
- sélection de palette sonore;
- sélection de jeu de notes;
- aperçu sonore de la palette active;
- meilleur score local conservé séparément;
- traductions publiques FR/EN.

Ce module ne cherche pas à:

- proposer plusieurs modes de jeu;
- gérer un compte utilisateur;
- synchroniser un score en ligne;
- conserver une partie en cours;
- parler à un backend;
- gérer des samples audio externes;
- servir de moteur audio générique pour tout le site.

## 2. Points D'Entrée Et Emplacements Principaux

| Responsabilité          | Fichier(s)                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                  | Rôle                                                                                                                           |
| ----------------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------ |
| Route Symfony et rendu  | [`src/Public/Controller/LabController.php`](/var/www/projects/benleminbe/src/Public/Controller/LabController.php)                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                           | Expose la route publique `/lab/game-simon` via `gameSimon()`. Le même contrôleur porte aussi l'index du Lab et le tracker DnD. |
| Point d'entrée Lab      | [`templates/lab/index.html.twig`](/var/www/projects/benleminbe/templates/lab/index.html.twig)                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                               | Contient le lien vers la page Simon depuis la section Jeux du Lab.                                                             |
| Template du jeu         | [`templates/lab/games/simon.html.twig`](/var/www/projects/benleminbe/templates/lab/games/simon.html.twig)                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                   | Porte la structure HTML, les `data-*`, le JSON de configuration et les textes traduits. Le bouton principal de départ et le bouton audio sont placés sous le plateau. |
| Entrypoint Asset Mapper | [`importmap.php`](/var/www/projects/benleminbe/importmap.php)                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                               | Déclare l'entrypoint `page_lab_game_simon`.                                                                                    |
| Entrypoint page         | [`assets/pages/lab_game_simon.js`](/var/www/projects/benleminbe/assets/pages/lab_game_simon.js)                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                             | Charge le CSS dédié puis l'initialisation JavaScript du jeu.                                                                   |
| Initialisation JS       | [`assets/scripts/lab/games/simon/index.js`](/var/www/projects/benleminbe/assets/scripts/lab/games/simon/index.js)                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                           | Lance `startSimonGamePage()`.                                                                                                  |
| Orchestrateur principal | [`assets/scripts/lab/games/simon/controller.js`](/var/www/projects/benleminbe/assets/scripts/lab/games/simon/controller.js)                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                 | Coordonne DOM, temporisations, audio, clavier, préférences et cycle de jeu.                                                    |
| État métier du jeu      | [`assets/scripts/lab/games/simon/game.js`](/var/www/projects/benleminbe/assets/scripts/lab/games/simon/game.js)                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                             | Porte la séquence, le niveau, l'index joueur, la phase et le meilleur score mémorisé en mémoire.                               |
| Moteur audio            | [`assets/scripts/lab/games/simon/audio.js`](/var/www/projects/benleminbe/assets/scripts/lab/games/simon/audio.js)                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                           | Gère Web Audio, le volume effectif, les palettes, les jeux de notes, l'aperçu et la réverbération.                             |
| Préférences audio       | [`assets/scripts/lab/games/simon/audio-preferences.js`](/var/www/projects/benleminbe/assets/scripts/lab/games/simon/audio-preferences.js)                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                   | Encapsule la lecture et l'écriture de la tranche audio des préférences consolidées.                                            |
| Préférences consolidées | [`assets/scripts/lab/games/simon/preferences.js`](/var/www/projects/benleminbe/assets/scripts/lab/games/simon/preferences.js)                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                               | Définit le schéma de préférence, les valeurs par défaut, la validation, la migration et la réinitialisation.                   |
| Contrôles clavier       | [`assets/scripts/lab/games/simon/keyboard.js`](/var/www/projects/benleminbe/assets/scripts/lab/games/simon/keyboard.js)                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                     | Gère le mapping `event.key` -> zone logique, la capture, la validation et la persistance.                                      |
| Registre palettes       | [`assets/scripts/lab/games/simon/sound-palettes.js`](/var/www/projects/benleminbe/assets/scripts/lab/games/simon/sound-palettes.js)                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                         | Décrit les palettes disponibles et leurs paramètres sonores.                                                                   |
| Registre notes          | [`assets/scripts/lab/games/simon/sound-note-sets.js`](/var/www/projects/benleminbe/assets/scripts/lab/games/simon/sound-note-sets.js)                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                       | Décrit les jeux de notes disponibles.                                                                                          |
| Score local             | [`assets/scripts/lab/games/simon/storage.js`](/var/www/projects/benleminbe/assets/scripts/lab/games/simon/storage.js)                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                       | Sauve et lit le meilleur score séparément des préférences.                                                                     |
| Styles dédiés           | [`assets/styles/lab/games/simon.css`](/var/www/projects/benleminbe/assets/styles/lab/games/simon.css)                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                       | Gère la mise en page, les états visuels, le responsive et les réductions d'animation.                                          |
| Traductions FR/EN       | [`translations/lab.fr.yaml`](/var/www/projects/benleminbe/translations/lab.fr.yaml), [`translations/lab.en.yaml`](/var/www/projects/benleminbe/translations/lab.en.yaml)                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                    | Portent tous les textes publics du jeu.                                                                                        |
| Tests JS                | [`tests/js/lab/games/simon.test.js`](/var/www/projects/benleminbe/tests/js/lab/games/simon.test.js), [`tests/js/lab/games/simon-controller.test.js`](/var/www/projects/benleminbe/tests/js/lab/games/simon-controller.test.js), [`tests/js/lab/games/simon-preferences.test.js`](/var/www/projects/benleminbe/tests/js/lab/games/simon-preferences.test.js), [`tests/js/lab/games/simon-audio.test.js`](/var/www/projects/benleminbe/tests/js/lab/games/simon-audio.test.js), [`tests/js/lab/games/simon-keyboard.test.js`](/var/www/projects/benleminbe/tests/js/lab/games/simon-keyboard.test.js), [`tests/js/lab/games/simon-sound-palettes.test.js`](/var/www/projects/benleminbe/tests/js/lab/games/simon-sound-palettes.test.js), [`tests/js/lab/games/simon-sound-note-sets.test.js`](/var/www/projects/benleminbe/tests/js/lab/games/simon-sound-note-sets.test.js) | Sécurisent l'état du jeu, le cycle de manche, le stockage, le clavier, l'audio et les registres de sons.                       |

## 3. Architecture JavaScript

Le Simon suit une séparation légère, mais nette:

```text
assets/pages/lab_game_simon.js
  -> assets/scripts/lab/games/simon/index.js
       -> startSimonGamePage()
            -> assets/scripts/lab/games/simon/controller.js
                 -> game.js
                 -> audio.js
                 -> audio-preferences.js
                 -> keyboard.js
                 -> preferences.js
                 -> storage.js
                 -> sound-palettes.js
                 -> sound-note-sets.js
```

### Rôle Des Modules

- `assets/pages/lab_game_simon.js` charge uniquement le CSS et l'entrypoint JS de la page.
- `assets/scripts/lab/games/simon/index.js` appelle `startSimonGamePage()` sans logique supplémentaire.
- `controller.js` est le chef d'orchestre. Il:
  - lit la configuration traduite injectée dans le DOM;
  - crée ou reçoit les dépendances du jeu;
  - branche les événements DOM;
  - applique les préférences;
  - pilote les phases du jeu;
  - met à jour le rendu;
  - orchestre l'audio et le clavier;
  - protège les transitions asynchrones avec un identifiant de session.
- `game.js` est volontairement compact. Il porte l'état métier:
  - séquence courante;
  - niveau;
  - index du joueur;
  - phase;
  - meilleur score.
- `audio.js` encapsule le moteur Web Audio et ne connaît pas le DOM.
- `preferences.js` centralise le schéma de stockage et la migration.
- `audio-preferences.js` expose une API pratique pour la tranche audio.
- `keyboard.js` transforme les touches reçues en zones logiques et gère la capture d'une nouvelle touche.
- `sound-palettes.js` et `sound-note-sets.js` sont des registres de configuration, pas des moteurs.
- `storage.js` ne sert qu'au meilleur score local.

## 4. Cycle De Vie D'Une Partie

### Machine À États Réelle

```text
idle
  -> préparation
  -> demo
  -> player
  -> success
  -> préparation
  -> demo
  -> player
  -> ...

player
  -> failure
  -> idle via nouvelle partie
```

La phase courante est exposée dans `data-simon-phase` sur la racine de page et dans `SimonGame.phase`.

### Détail Des États

| État          | Rôle                                                                                                              | Contrôles                                                                 | Transitions                                  |
| ------------- | ----------------------------------------------------------------------------------------------------------------- | ------------------------------------------------------------------------- | -------------------------------------------- |
| `idle`        | État initial avant toute partie. Le score et l'interface sont affichés, mais les pads restent inactifs.           | Pads bloqués. Clavier de jeu ignoré.                                      | `startNewGame()`                             |
| `preparation` | Prépare une nouvelle manche ou la manche suivante. Le plateau reçoit aussi le focus après le clic sur "Démarrer". | Pads bloqués. Le clavier de jeu est ignoré.                               | délai de préparation, puis `demo`            |
| `demo`        | Le jeu rejoue la séquence complète avant de laisser l'utilisateur répondre.                                       | Pads bloqués.                                                             | fin de séquence, puis `player`               |
| `player`      | Le joueur reproduit la séquence. Chaque saisie est validée immédiatement.                                         | Pads et clavier actifs.                                                   | `success` ou `failure`                       |
| `success`     | La manche vient d'être gagnée. Le score est persistant et un feedback audio de réussite est joué.                 | Pads bloqués.                                                             | délai de succès, puis nouvelle `preparation` |
| `failure`     | Une erreur termine immédiatement la partie.                                                                       | Pads bloqués. Le bouton de démarrage devient un bouton de recommencement. | retour manuel via `startNewGame()`           |

### Flux Concret

```text
clic sur "Démarrer"
  -> cancelSession()
  -> startNewGame()
  -> setPreparationState()
  -> focus du plateau
  -> unlock audio si possible
  -> playStart()
  -> délai de préparation
  -> phase demo
  -> délai d'introduction
  -> lecture de chaque pad
  -> phase player
  -> validation immédiate des saisies
  -> manche réussie ou défaite
```

Le même enchaînement est réutilisé après une manche gagnée. La seule différence fonctionnelle est le délai de préparation suivant, plus court que le démarrage initial.

## 5. Gestion Des Temporisations Et De L'Annulation

Les temporisations sont centralisées dans `controller.js` via `createTiming()`.

### Délais Actuels

| Délais                    | Mode normal | `prefers-reduced-motion` |
| ------------------------- | ----------: | -----------------------: |
| `initialPreparationDelay` |      950 ms |                   240 ms |
| `nextPreparationDelay`    |      700 ms |                   180 ms |
| `demoLeadInDelay`         |      420 ms |                   120 ms |
| `flashDuration`           |      280 ms |                   160 ms |
| `gapDuration`             |      120 ms |                    60 ms |
| `activePadDuration`       |      160 ms |                   120 ms |
| `successDelay`            |      620 ms |                   320 ms |
| `failureDelay`            |      640 ms |                   380 ms |

### Mécanisme D'Annulation

- `wait()` renvoie une promesse basée sur `setTimeout`.
- Le contrôleur ne stocke pas de poignées de timer.
- Chaque nouvelle partie appelle `cancelSession()`, qui incrémente `sessionId`.
- Les chaînes asynchrones capturent l'identifiant de session courant.
- Après chaque attente, `isSessionStale(sessionId)` vérifie si la partie a été remplacée.
- Si la session est devenue obsolète, le code sort sans réécrire l'état du nouveau jeu.

Ce mécanisme protège notamment:

- un redémarrage pendant la préparation;
- un redémarrage pendant la démonstration;
- un redémarrage juste après la dernière saisie correcte;
- le lancement d'une nouvelle manche pendant qu'un ancien enchaînement attend encore.

## 6. Contrôles Utilisateur

### Souris Et Tactile

- Les quatre zones sont de vrais boutons HTML.
- Chaque bouton porte `data-simon-pad` avec l'index métier `0` à `3`.
- Le clic déclenche `handlePlayerInput(index)`.
- Les boutons de la colonne droite restent liés au même moteur de jeu que la souris.

### Clavier

- Le moteur utilise `event.key`, pas `event.code`.
- Le mapping est donc lié à la touche réellement reçue par le navigateur, puis normalisé en majuscule.
- Les identifiants logiques restent indépendants des libellés visibles.
- Les zones logiques sont:
  - `top-left`
  - `top-right`
  - `bottom-left`
  - `bottom-right`

### Mapping Par Défaut

- `top-left` -> `A`
- `top-right` -> `Z`
- `bottom-left` -> `Q`
- `bottom-right` -> `S`

### Capture Et Validation

`keyboard.js` gère la capture d'une nouvelle touche.

Règles appliquées:

- une touche ne peut pas être utilisée par deux zones à la fois;
- les modificateurs seuls sont rejetés;
- `Ctrl`, `Alt` et `Meta` sont rejetés quand ils forment un raccourci;
- `Escape`, `Enter`, `Space`, `Tab`, les touches de navigation et les touches de fonction ne sont pas admises;
- une touche vide ou multicaractère est rejetée;
- la capture est interrompue proprement lorsqu'elle est annulée ou validée.

### Quand Les Entrées Sont Ignorées

Le contrôleur ignore les entrées clavier lorsque:

- la phase n'est pas `player`;
- la touche est répétée;
- la capture clavier est en cours;
- le focus est dans un élément interactif (`button`, `input`, `select`, `textarea`, `contenteditable`, etc.);
- la touche correspond à un raccourci avec `Ctrl`, `Alt` ou `Meta`;
- la touche ne correspond à aucune zone configurée.

## 7. Audio Et Palettes Sonores

### Vue D'Ensemble

`SimonAudio` est le moteur audio réel. Il:

- crée un `AudioContext` à la demande lors du déverrouillage;
- refuse de jouer si l'audio est désactivé, verrouillé, absent ou si le volume est nul;
- applique un volume global;
- applique une durée de notes en pourcentage;
- applique une réverbération simple via un graphe dry/wet;
- sait jouer les pads, le démarrage, la réussite, l'erreur et l'aperçu de palette.

`SimonGameController` pilote ce moteur à partir des préférences locales.

### Règles Audio Importantes

- le son ne démarre pas avant une interaction explicite de l'utilisateur;
- `unlock()` est appelé seulement quand le jeu a une chance réelle de produire du son;
- `setEnabled(false)` coupe aussi l'aperçu en cours;
- `setPalette()` et `setNoteSet()` annulent l'aperçu en cours;
- `setReverb()` recalcule le mix de sortie;
- `setNoteDuration()` ajuste la longueur effective des notes sans changer leur contenu musical;
- l'aperçu utilise le registre de palette sélectionné et le jeu de notes actif;
- l'aperçu ne touche ni la séquence, ni le score, ni la phase du jeu.

### Registre Des Palettes

| Identifiant  | Nom public  | Intention                                                     |
| ------------ | ----------- | ------------------------------------------------------------- |
| `classic`    | Classique   | Sons simples, ronds et lisibles. C'est la palette par défaut. |
| `arcade`     | Arcade      | Sons courts, francs, légèrement rétro.                        |
| `crystal`    | Cristal     | Sons plus brillants et légers.                                |
| `synthwave`  | Synthwave   | Sons électroniques plus riches.                               |
| `percussion` | Percussions | Sons courts et plus rythmiques.                               |

Chaque palette décrit:

- les quatre pads;
- les séquences de feedback `start`, `success` et `error`;
- un `previewGap` utilisé par l'aperçu.

### Jeux De Notes

Le jeu de notes est indépendant de la palette. Il fournit les fréquences de base des quatre zones et les séquences de feedback associées.

| Identifiant  | Nom public   | Intention                             |
| ------------ | ------------ | ------------------------------------- |
| `major`      | Majeur       | Référence claire et lumineuse.        |
| `minor`      | Mineur       | Couleur plus sombre.                  |
| `pentatonic` | Pentatonique | Lecture simple et directe.            |
| `dorian`     | Dorien       | Couleur modale légèrement différente. |
| `blues`      | Blues        | Couleur plus expressive.              |

### Aperçu Sonore

L'aperçu:

- joue les quatre pads dans l'ordre `0 -> 1 -> 2 -> 3`;
- respecte le volume actuel;
- respecte le mute actuel;
- peut être annulé par un changement de palette ou par un nouvel aperçu;
- n'affecte pas le jeu en cours.

Le bouton d'aperçu est désactivé quand le son est coupé ou indisponible.

### Réverbération Et Durée Des Notes

- la durée des notes est stockée sous forme de pourcentage et multiplie la durée de base fournie par la palette;
- la réverbération est un pourcentage simple qui module le mix wet/dry;
- le graphe de sortie crée un convolver si le navigateur le permet;
- si le navigateur ne fournit pas ces capacités, l'audio continue sans réverbération mais sans casser l'interface.

## 8. Préférences Locales Et Persistance

### Clés De Stockage

- préférence consolidée: `benleminbe-lab-simon-preferences`
- ancien stockage clavier: `benleminbe-lab-simon-keyboard-bindings`
- ancien stockage audio: `benleminbe-lab-simon-audio-preferences`
- meilleur score local, séparé des préférences: `benleminbe-lab-simon-best-score`

### Schéma Actuel

```json
{
  "version": 1,
  "keyboard": {
    "bindings": {
      "top-left": "A",
      "top-right": "Z",
      "bottom-left": "Q",
      "bottom-right": "S"
    }
  },
  "audio": {
    "muted": false,
    "volume": 75,
    "palette": "classic",
    "noteSet": "major",
    "noteDuration": 100,
    "reverb": 12
  }
}
```

### Comportement De Chargement

`loadSimonPreferences()` suit cet ordre:

1. lire la structure consolidée;
2. si elle est absente, tenter la migration depuis les anciennes clés;
3. normaliser et valider les données;
4. sauvegarder la structure consolidée si une migration a réussi;
5. retirer les anciennes clés après migration réussie;
6. retomber sur les valeurs par défaut si rien n'est récupérable.

### Validation Et Normalisation

Le chargement corrige ou rejette notamment:

- le JSON invalide;
- une structure de type incorrect;
- une version inconnue ou future;
- une palette inconnue;
- un jeu de notes inconnu;
- un volume hors plage;
- une durée de note hors plage;
- une réverbération hors plage;
- un mapping clavier incomplet, dupliqué ou invalide.

Quand c'est possible, la validation conserve la partie saine d'une préférence au lieu de tout jeter.

### Différence Avec Le Meilleur Score

Le meilleur score reste séparé dans `storage.js`.

Motif actuel:

- c'est une donnée de progression, pas une préférence utilisateur;
- elle n'a pas besoin de partager le même schéma que les réglages;
- elle est plus simple à sauvegarder et à lire isolément.

## 9. Contrat DOM Et Accessibilité

### Contrats DOM Importants

| Sélecteur / attribut               | Usage                                                                                                         |
| ---------------------------------- | ------------------------------------------------------------------------------------------------------------- |
| `data-simon-page`                  | Racine de la page Simon. Sert aussi de point d'accrochage pour `data-simon-phase` et les états audio/clavier. |
| `data-simon-phase`                 | État courant du jeu utilisé par le contrôleur et par le CSS.                                                  |
| `data-simon-config`                | JSON de configuration traduit transmis au contrôleur.                                                         |
| `data-simon-stage`                 | Plateau focusable après lancement d'une partie.                                                               |
| `data-simon-board`                 | Conteneur des quatre pads et du bloc central.                                                                 |
| `data-simon-status`                | Annonce textuelle de l'état du jeu et statut compact au centre du plateau.                                   |
| `data-simon-level`                 | Niveau courant affiché au centre du plateau.                                                                  |
| `data-simon-best`                  | Meilleur score local affiché au centre du plateau.                                                            |
| `data-simon-pad`                   | Bouton d'une zone de jeu.                                                                                     |
| `data-simon-start`                 | Bouton principal de lancement ou de relance de partie.                                                        |
| `data-simon-sound`                 | Bouton mute/unmute placé à côté du démarrage.                                                                  |
| `data-simon-volume`                | Curseur de volume.                                                                                            |
| `data-simon-note-duration`         | Curseur de durée des notes.                                                                                   |
| `data-simon-reverb`                | Curseur de réverbération.                                                                                     |
| `data-simon-audio-palette`         | Sélecteur de palette sonore.                                                                                  |
| `data-simon-audio-note-set`        | Sélecteur de jeu de notes.                                                                                    |
| `data-simon-audio-palette-preview` | Bouton d'aperçu sonore.                                                                                       |
| `data-simon-keyboard-edit`         | Bouton d'édition d'une touche.                                                                                |
| `data-simon-keyboard-reset`        | Réinitialisation du mapping clavier.                                                                          |
| `data-simon-keyboard-summary`      | Résumé lisible du mapping actif.                                                                              |
| `data-simon-keyboard-key`          | Valeur affichée d'une touche configurée.                                                                      |
| `data-simon-keyboard-zone`         | Zone logique liée à une touche configurée.                                                                    |

Renommer un de ces attributs implique d'adapter le contrôleur JS et, souvent, les tests.

### Choix D'Accessibilité Réels

- les pads sont de vrais boutons;
- le bouton audio est un vrai bouton HTML avec état accessible;
- le plateau peut recevoir le focus programmatique après démarrage;
- les statuts importants sont annoncés textuellement;
- les sélecteurs et curseurs disposent de labels visibles;
- le jeu ne dépend pas uniquement des couleurs;
- `prefers-reduced-motion` réduit les transitions et certaines animations;
- les descriptions textuelles des touches, des palettes et des jeux de notes restent visibles dans l'interface.

## 10. Tests Et Couverture

### Tests Existants

| Fichier                                            | Ce qu'il protège                                                                                     |
| -------------------------------------------------- | ---------------------------------------------------------------------------------------------------- |
| `tests/js/lab/games/simon.test.js`                 | Le cœur métier du jeu et le meilleur score local.                                                    |
| `tests/js/lab/games/simon-controller.test.js`      | Le contrôleur DOM, les états, le focus du plateau, les contrôles audio/clavier et les transitions.   |
| `tests/js/lab/games/simon-preferences.test.js`     | Le stockage consolidé, la validation, la migration, le reset et les cas localStorage défaillant.     |
| `tests/js/lab/games/simon-audio.test.js`           | Le moteur audio, les palettes, les jeux de notes, le volume, la durée, la réverbération et l'aperçu. |
| `tests/js/lab/games/simon-keyboard.test.js`        | Le mapping clavier, la capture, la validation et la persistance.                                     |
| `tests/js/lab/games/simon-sound-palettes.test.js`  | Le registre des palettes et sa normalisation.                                                        |
| `tests/js/lab/games/simon-sound-note-sets.test.js` | Le registre des jeux de notes et sa normalisation.                                                   |

### Zones Peu Ou Pas Couvertes

- pas de test e2e navigateur réel pour la saisie complète d'une partie;
- pas de visual regression sur le rendu CSS;
- pas de vérification du rendu audio réel sur un périphérique audio du navigateur, les tests utilisent des doubles Web Audio.

## 11. Guide De Modification

| Besoin                                            | Fichiers / zone à consulter d'abord                                                                                                                                                                                                                                                       |
| ------------------------------------------------- | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Changer le timing des manches                     | `assets/scripts/lab/games/simon/controller.js`, puis `tests/js/lab/games/simon-controller.test.js`                                                                                                                                                                                        |
| Ajouter une palette sonore                        | `assets/scripts/lab/games/simon/sound-palettes.js`, `assets/scripts/lab/games/simon/audio.js`, `templates/lab/games/simon.html.twig`, `translations/lab.fr.yaml`, `translations/lab.en.yaml`, `tests/js/lab/games/simon-sound-palettes.test.js`, `tests/js/lab/games/simon-audio.test.js` |
| Modifier le mapping clavier par défaut            | `assets/scripts/lab/games/simon/keyboard.js`, `assets/scripts/lab/games/simon/preferences.js`, `templates/lab/games/simon.html.twig`, `tests/js/lab/games/simon-keyboard.test.js`, `tests/js/lab/games/simon-preferences.test.js`                                                         |
| Ajouter une préférence locale                     | `assets/scripts/lab/games/simon/preferences.js`, `assets/scripts/lab/games/simon/audio-preferences.js`, `assets/scripts/lab/games/simon/controller.js`, `tests/js/lab/games/simon-preferences.test.js`                                                                                    |
| Modifier les textes visibles                      | `translations/lab.fr.yaml`, `translations/lab.en.yaml`, puis `templates/lab/games/simon.html.twig` si le JSON de config doit suivre                                                                                                                                                       |
| Modifier le rendu du plateau                      | `templates/lab/games/simon.html.twig`, `assets/styles/lab/games/simon.css`                                                                                                                                                                                                                |
| Ajouter un test de logique de jeu                 | `tests/js/lab/games/simon.test.js`                                                                                                                                                                                                                                                        |
| Modifier la route ou le point d'entrée de la page | `src/Public/Controller/LabController.php`, `templates/lab/index.html.twig`, `importmap.php`, `assets/pages/lab_game_simon.js`                                                                                                                                                             |

## 12. Invariants Et Points D'Attention

- les entrées joueur sont bloquées hors de la phase `player`;
- une nouvelle partie invalide les chaînes asynchrones précédentes grâce à `sessionId`;
- le moteur ne dépend pas de la touche affichée dans l'interface, mais de la zone logique associée;
- mute, volume, durée des notes et réverbération s'appliquent à tous les sons via la même couche audio;
- une préférence invalide revient à une valeur sûre sans bloquer la partie;
- l'aperçu sonore ne modifie ni le score, ni la séquence, ni la phase du jeu;
- les textes publics restent portés par les traductions FR/EN;
- le meilleur score local reste séparé des préférences utilisateur.
