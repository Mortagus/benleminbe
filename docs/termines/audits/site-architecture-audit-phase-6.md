# Site Architecture Audit - Phase 6

## Objectif

Auditer l'outil DnD Initiative Tracker comme mini-application separee du site professionnel.

Cette phase reste un audit. Aucun changement applicatif n'a ete effectue.

## Verification Technique

Commandes executees :

```bash
find templates/lab/dnd assets/scripts/lab/dnd assets/styles/lab/dnd docs/lab -type f | sort
wc -l templates/lab/dnd/*.twig assets/scripts/lab/dnd/*.js assets/styles/lab/dnd/*.css docs/lab/*.md
php bin/console debug:router app_lab_dnd_initiative
php bin/console lint:twig templates/lab/dnd
npx stylelint "assets/styles/lab/dnd/**/*.css"
wc -c assets/scripts/lab/dnd/monster_classes.js
```

Resultats :

```text
Route DnD : /lab/dnd-initiative
Twig DnD : OK, 5 fichiers valides
stylelint DnD : OK, aucune erreur remontee
monster_classes.js : 418110 octets
```

## Perimetre Audite

Fichiers principaux :

```text
src/Public/Controller/LabController.php
importmap.php

templates/lab/dnd/
  initiative_tracker.html.twig
  _monsters_panel.html.twig
  _players_panel.html.twig
  _rules_panels.html.twig
  _turn_order_panel.html.twig

assets/scripts/lab/dnd/
  dnd_initiative.js
  initiative.js
  monster_classes.js
  monsters.js
  players.js
  rules.js
  turn-order.js
  validation.js

assets/styles/lab/dnd/
  lab_dnd_initiative.css
  page.css
  panels.css
  toolbar.css
  validation.css
  rules.css
  monsters.css
  players.css
  turn-order.css
  icons.css

tools/dnd/
  complete_monster_extractor.php
  extract_monsters.php
  monsters-source.html
  monsters.generated.json
  monsters.html

docs/lab/
  dnd-initiative-audit.md
  dnd-initiative-tracker-backlog.md
```

## Vue Generale

Le DnD Initiative Tracker est actuellement une mini-application front-end integree au site Symfony.

Flux principal :

1. Le MJ choisit un nombre de monstres.
2. L'outil cree des emplacements de monstres.
3. Le MJ choisit les monstres depuis un catalogue.
4. L'outil lance l'initiative des monstres.
5. Le MJ renseigne les joueurs.
6. L'outil genere un ordre du tour.
7. Le MJ peut marquer les acteurs comme joues et reordonner la liste.

Constat global :

- le module est bien isole dans `templates/lab/dnd`, `assets/scripts/lab/dnd` et `assets/styles/lab/dnd` ;
- le controller ne porte pas de logique metier ;
- l'entree AssetMapper dediee evite de charger le JavaScript DnD partout ;
- les styles DnD sont charges par l'entree DnD, pas par le CSS global ;
- le module est plus mature qu'un simple prototype, mais pas encore structure comme une application front stabilisee.

## Integration Symfony

Route actuelle :

```text
app_lab_dnd_initiative
/lab/dnd-initiative
methods: ANY
controller: App\Public\Controller\LabController::initiativeDnd()
```

Points solides :

- le controller est minimal ;
- la route est clairement separee du parcours professionnel ;
- le module n'interfere pas avec les providers projets/experiences ;
- l'absence de locale est coherente si l'outil reste un lab francophone ou personnel.

Points de vigilance :

- la route accepte actuellement toutes les methodes HTTP ;
- `LabController` est dans `App\Public`, ce qui est acceptable aujourd'hui, mais deviendra moins lisible si plusieurs outils de lab apparaissent ;
- le nom du controller est generique alors que le module DnD commence deja a avoir un vrai perimetre applicatif.

Recommandation :

- ajouter `methods: ['GET']` lors d'un nettoyage route ;
- garder `App\Public` pour le moment ;
- si d'autres outils lab apparaissent, creer une arborescence plus explicite, par exemple `App\Public\Controller\Lab\DndInitiativeController`.

## Entrypoints Assets

`importmap.php` declare deux entrees :

```text
app
dnd_initiative
```

La page DnD charge :

```twig
{{ importmap(['app', 'dnd_initiative']) }}
```

Points solides :

- le socle global du site reste charge via `app` ;
- l'outil DnD charge son JavaScript et son CSS via `dnd_initiative` ;
- le modele est reutilisable pour de futurs modules autonomes ;
- l'ancien risque de CSS DnD charge globalement n'est plus d'actualite.

Point de vigilance :

- les deux entrypoints partagent tout de meme le layout global, le header, le footer, le theme switcher et les scripts globaux ;
- c'est coherent pour une page publique de lab, mais a reevaluer si le lab devient prive ou plein ecran.

## Templates Twig

### Structure

La page principale assemble trois panneaux :

- monstres ;
- joueurs ;
- ordre du tour.

Les templates utilisent des balises `<template>` pour cloner les lignes dynamiques cote navigateur.

Points solides :

- le decoupage par panneau est clair ;
- les id et classes critiques sont visibles dans Twig ;
- les templates restent simples ;
- les blocs de validation existent par panneau ;
- les textes sont comprehensibles pour un usage francophone.

### Couplage Avec JavaScript

Le contrat Twig/JavaScript repose sur :

- ids HTML : `monsterCount`, `createMonsters`, `monsterList`, `playerList`, `turnOrderList`, etc. ;
- classes CSS : `.monster-item`, `.player-item`, `.turn-order-item`, etc. ;
- attributs `data-*` : `data-player-field`, `data-rule-toggle`, `data-rules-close`.

Constat :

- ce couplage est normal pour une application vanilla JS ;
- il est assez explicite ;
- les `data-player-field` ont deja stabilise l'extraction des joueurs.

Risque :

- les selecteurs sont disperses entre plusieurs fichiers JS ;
- un changement Twig peut casser une interaction sans erreur serveur.

Recommandation :

- conserver les `data-*` comme contrat prioritaire ;
- si le module grossit, centraliser les selecteurs critiques dans un petit module JS dedie.

### Duplication Du Joueur Initial

Le premier joueur dans `#playerList` et le template `#playerItemTemplate` dupliquent presque le meme HTML.

Risque :

- une modification du formulaire joueur doit etre reportee deux fois ;
- l'etat initial et les joueurs ajoutes peuvent diverger.

Recommandation :

- extraire un partial Twig local pour une ligne joueur ;
- ou generer le premier joueur en JavaScript a partir du meme template.

### Textes En Dur

Tous les textes DnD sont en dur dans Twig ou JavaScript.

Constat :

- c'est coherent si le lab reste francophone ;
- ce serait insuffisant si l'outil devient public multilingue comme les pages professionnelles.

Recommandation :

- ne pas internationaliser maintenant ;
- documenter que le module DnD est actuellement hors workflow de traduction public.

## JavaScript

### Organisation Actuelle

Roles des fichiers :

```text
dnd_initiative.js : orchestration de page
monsters.js       : etat et rendu des monstres
players.js        : creation et extraction des joueurs
turn-order.js     : construction et rendu de l'ordre du tour
rules.js          : regles maison et modale de configuration
validation.js     : validations et affichage des erreurs
initiative.js     : jet de d20 et formatage initiative
monster_classes.js: catalogue embarque de monstres
```

Points solides :

- les fichiers sont courts, sauf le catalogue de donnees ;
- les responsabilites sont deja separees par theme ;
- le rendu dynamique utilise `textContent`, `replaceChildren`, `createElement` et des templates DOM ;
- l'ancien probleme d'injection via `innerHTML` n'est plus present ;
- l'extraction des joueurs se fait via `data-player-field`, pas via positions de champs ;
- les regles maison sont configurables via la modale.

### Etat Applicatif

Etat actuel :

```text
monsters.js   -> let monsters = []
turn-order.js -> let roundOrder = []
rules.js      -> activeRules = Set(...)
```

Constat :

- l'etat par module reste simple a lire ;
- il suffit au fonctionnement actuel ;
- il permet un prototype efficace sans framework.

Limites :

- il n'existe pas de modele central de rencontre ;
- le DOM reste parfois une source temporaire de verite, par exemple pour synchroniser les PV monstres ;
- la persistance, l'annulation, l'import/export et les tests unitaires seront plus difficiles sans modele explicite.

Recommandation :

- ne pas refactorer tout de suite ;
- si une fonctionnalite de sauvegarde ou d'import/export arrive, commencer par formaliser un objet `encounter` ;
- extraire d'abord les fonctions pures autour des acteurs, des PV et de l'ordre du tour.

### Regles Metier

Regles actuelles :

- ignorer les initiatives tres basses ;
- accorder un tour supplementaire sur initiative 20.

Ces regles sont des regles maison et elles sont maintenant pilotables via l'interface.

Constat :

- le backlog DnD precise deja que `shouldSkipTurn()` est volontaire ;
- l'ancienne note d'audit qui presentait ces regles comme bugs est donc obsolete ;
- le fait de les rendre configurables est une bonne direction.

Point de vigilance :

- `buildRoundOrder()` depend de l'etat de `rules.js`, donc son comportement n'est pas purement determine par ses arguments ;
- cela limite legerement la testabilite.

Recommandation :

- si des tests sont ajoutes, permettre a `buildRoundOrder()` de recevoir les regles actives en parametre ;
- garder les regles maison documentees dans l'interface ou le backlog.

### Validation

Points solides :

- validations centralisees dans `validation.js` ;
- messages par panneau ;
- blocage des actions invalides ;
- focus du premier champ invalide ;
- marquage `aria-invalid`.

Points de vigilance :

- les messages sont en dur en francais ;
- les validations sont surtout declenchees au clic, pas en continu ;
- certaines bornes sont techniques plutot que metier, par exemple `MAX_MONSTER_COUNT = 30`.

Recommandation :

- conserver l'etat actuel ;
- ajuster seulement apres retours d'usage ;
- eviter d'ajouter une couche de validation plus complexe sans besoin concret.

## Catalogue De Monstres

Fichier embarque :

```text
assets/scripts/lab/dnd/monster_classes.js
418110 octets
428 monstres
```

Pipeline present :

```text
tools/dnd/complete_monster_extractor.php
tools/dnd/extract_monsters.php
tools/dnd/monsters-source.html
tools/dnd/monsters.generated.json
tools/dnd/monsters.html
```

Constat :

- `monster_classes.js` ressemble a une donnee generee ;
- `monsters.generated.json` existe comme sortie intermediaire ;
- deux extracteurs coexistent ;
- le fichier JS embarque ne contient pas de commentaire clair indiquant sa source ou sa commande de regeneration.

Risque :

- future maintenance difficile si la source des donnees est oubliee ;
- modification manuelle accidentelle du fichier genere ;
- chargement du catalogue complet meme pour une rencontre simple.

Recommandation :

- documenter la source et la commande de generation ;
- identifier l'extracteur actuel et l'ancien extracteur ;
- ajouter un commentaire en tete du fichier genere lors d'une prochaine generation ;
- ne pas optimiser le chargement tant que le module reste limite a une page.

## CSS Et Responsive

Points solides :

- styles isoles sous `.dnd-initiative-page` ;
- fichiers CSS decoupes par zone fonctionnelle ;
- layout principal en grille deux colonnes puis une colonne ;
- listes joueurs/monstres adaptees sous `768px` ;
- ordre du tour horizontal scrollable, ce qui reste simple pour un prototype.

Points de vigilance :

- plusieurs couleurs d'etat sont litterales, notamment erreurs et initiatives critiques ;
- l'ordre du tour horizontal peut devenir moins ergonomique avec beaucoup d'acteurs ;
- les cartes de tour sont cliquables mais visuellement proches de cartes statiques ;
- le drag-and-drop HTML natif est moins previsible sur mobile.

Recommandation :

- garder le CSS actuel ;
- tester un combat volumineux sur mobile avant tout refactor ;
- introduire des tokens pour les couleurs d'etat seulement si les modes/theme posent probleme.

## Accessibilite

Points solides :

- boutons avec `type="button"` ;
- boutons icones joueurs avec `aria-label` ;
- blocs de validation avec `role="alert"` ;
- modale de regles avec `role="dialog"` et `aria-modal` ;
- fermeture de la modale via Escape et bouton fermer ;
- retour du focus au bouton d'ouverture de la modale.

Points de vigilance :

- la modale n'a pas de focus trap ;
- les labels joueurs n'ont pas de `for`/`id` ;
- le select monstre par ligne n'a pas de label accessible explicite ;
- les cartes de l'ordre du tour sont cliquables mais ne sont pas focusables au clavier ;
- le reordonnancement drag-and-drop n'a pas d'alternative clavier ;
- l'acteur actif pourrait etre mieux annonce aux technologies d'assistance.

Recommandation :

- ajouter des noms accessibles aux champs dynamiques ;
- rendre les cartes de tour operables au clavier si le clic reste l'interaction principale ;
- ajouter des boutons monter/descendre si le reordonnancement devient important ;
- ne pas chercher une conformite exhaustive avant d'avoir stabilise le modele de tour.

## Documentation Existante

Deux documents existent deja :

```text
docs/lab/dnd-initiative-audit.md
docs/en-cours/dnd-initiative-tracker-backlog.md
```

Constat :

- le backlog est globalement coherent avec l'etat actuel ;
- l'ancien audit contient des observations aujourd'hui corrigees ou obsoletes :
    - chemin de template ancien ;
    - styles DnD presentes comme charges globalement ;
    - usage de `innerHTML` ;
    - extraction joueur par index ;
    - regles maison presentees comme bugs plutot que comme choix configurables.

Recommandation :

- garder l'ancien audit comme historique ;
- considerer le backlog et cette phase 6 comme references plus recentes ;
- ajouter une courte mention "document historique" dans `docs/lab/dnd-initiative-audit.md` lors d'un futur nettoyage documentaire.

## Outillage Et Tests

Outillage actuel :

- lint Twig via Symfony ;
- stylelint pour CSS ;
- pas de lint JavaScript ;
- pas de tests JavaScript.

Constat :

- le module est encore testable manuellement ;
- les fonctions de regles et d'ordre du tour commencent a justifier des tests ;
- ajouter une grosse stack front serait disproportionne.

Recommandation :

- si les regles continuent d'evoluer, ajouter quelques tests JS tres cibles ;
- viser en premier `buildRoundOrder()`, `shouldSkipTurn()`, `getTurnCount()` et les validations numeriques ;
- envisager un lint JS seulement si le volume de code front augmente.

## Recommandations Priorisees

### Priorite 1 - Stabilisation Courte

- Ajouter `methods: ['GET']` a la route DnD.
- Reduire la duplication du template joueur.
- Ajouter des noms accessibles aux champs dynamiques.
- Marquer l'ancien audit DnD comme historique ou partiellement obsolete.
- Documenter la generation de `monster_classes.js`.

### Priorite 2 - Maintenabilite Progressive

- Centraliser les selecteurs critiques Twig/JS.
- Decouper l'orchestration par panneau si `dnd_initiative.js` grossit.
- Formaliser un modele `encounter` avant toute sauvegarde/import/export.
- Rendre l'ordre du tour pilotable au clavier si le module est utilise en vraie session.

### Priorite 3 - Evolution Produit

- Ajouter une sauvegarde locale de rencontre.
- Ajouter recherche/filtres/presets pour les monstres.
- Ajouter tests JS sur regles et ordre du tour.
- Prevoir import/export JSON apres clarification du modele de rencontre.
- Internationaliser seulement si le module sort du contexte lab francophone.

## Note De Reprise

```text
Phase 6 terminee.

Sources analysees :
- src/Public/Controller/LabController.php
- importmap.php
- templates/lab/dnd/*
- assets/scripts/lab/dnd/*
- assets/styles/lab/dnd/*
- tools/dnd/*
- docs/lab/dnd-initiative-audit.md
- docs/en-cours/dnd-initiative-tracker-backlog.md

Verifications :
- php bin/console debug:router app_lab_dnd_initiative : OK
- php bin/console lint:twig templates/lab/dnd : OK
- npx stylelint "assets/styles/lab/dnd/**/*.css" : OK
- monster_classes.js pese 418110 octets.

Constats principaux :
- DnD est bien isole comme mini-application lab.
- Controller minimal, logique cote front.
- Entree AssetMapper dediee et CSS DnD charge via dnd_initiative.
- JS decoupe par theme mais etat conserve par modules.
- Rendu DOM actuellement sain, sans innerHTML detecte.
- Regles maison configurables via modale.
- Catalogue monstres volumineux et probablement genere.

Findings concrets :
- Route DnD accepte toutes les methodes HTTP.
- Duplication du markup joueur initial/template.
- Labels dynamiques incomplets cote accessibilite.
- Drag-and-drop sans alternative clavier.
- Pas de focus trap dans la modale.
- Selecteurs DOM disperses.
- Pas de tests JS sur les regles.
- Ancien audit docs/lab/dnd-initiative-audit.md partiellement obsolete.

Actions possibles :
- Ajouter methods GET a la route.
- Factoriser le template joueur.
- Ajouter aria-label/ids aux champs dynamiques.
- Documenter la generation des monstres.
- Centraliser les selecteurs si le module grossit.
- Ajouter tests JS cibles si les regles evoluent.

Phase suivante :
- Phase 7 - Preparation De La Partie Privee.
```
