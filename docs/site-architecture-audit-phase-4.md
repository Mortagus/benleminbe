# Site Architecture Audit - Phase 4

## Objectif

Auditer l'organisation des assets, du CSS et du JavaScript cote public, pages professionnelles et module DnD.

Cette phase reste un audit. Aucun changement applicatif n'a ete effectue.

## Verification Technique

Commandes executees :

```bash
npx stylelint "assets/styles/**/*.css"
php bin/console debug:asset-map
```

Resultats :

```text
stylelint : OK, aucune erreur remontee.
asset-map : OK, assets mappes et entree dediee pour app.js et dnd_initiative.js.
```

## Vue Generale Des Assets

Structure actuelle :

```text
assets/
  app.js

  scripts/
    content-toc.js
    mobile-menu.js
    reading-progress.js
    theme-switcher.js
    tracking.js
    lab/
      dnd/
        dnd_initiative.js
        initiative.js
        monster_classes.js
        monsters.js
        players.js
        rules.js
        turn-order.js
        validation.js

  styles/
    app.css
    base/
    components/
    layout/
    pages/
    lab/
      dnd/
```

Constat global :

- la separation entre styles globaux, composants, layouts, pages et lab est claire ;
- le module DnD possede son propre point d'entree JavaScript ;
- les styles DnD sont isoles dans `assets/styles/lab/dnd/` ;
- les scripts globaux restent courts et centres sur un comportement ;
- la structure actuelle est saine pour un site public de taille moderee.

## Points D'Entree

### app.js

Responsabilites actuelles :

- charger le CSS global via `styles/app.css` ;
- activer les comportements globaux :
  - menu mobile ;
  - changement de theme ;
  - tracking du telechargement CV ;
  - sommaire des pages longues ;
  - indicateur de progression de lecture.

Constat :

- le role de `app.js` est comprehensible ;
- les comportements globaux sont importes explicitement ;
- le fichier contient encore le `console.log` par defaut d'AssetMapper.

Risque :

- le `console.log` n'est pas bloquant, mais il donne un signal brouillon en production.

Recommandation :

- supprimer le `console.log` d'AssetMapper lors d'une prochaine petite passe de nettoyage.

### dnd_initiative.js

Responsabilites actuelles :

- charger les styles du module DnD ;
- initialiser les modules joueurs, monstres, regles, validation et ordre de tour ;
- orchestrer l'interface de l'outil Initiative Tracker.

Constat :

- le DnD est bien traite comme une mini-application separee ;
- l'entree dediee evite de charger les styles DnD sur les pages professionnelles ;
- c'est une bonne base si d'autres outils de lab apparaissent plus tard.

## CSS Global

### Forces

- `base/tokens.css` centralise les variables de couleur, d'espacement, de rayon et d'ombre ;
- `layout/header.css` et `layout/footer.css` separent bien les structures communes ;
- `pages/` contient les styles propres aux pages publiques ;
- `components/` regroupe les primitives reutilisees ;
- les styles DnD sont hors du flux public principal.

### Points De Vigilance

#### components/cards.css

Le fichier contient a la fois :

- des styles de cartes generiques ;
- des variantes de cartes projet ;
- des styles specifiques a `.landing-page`.

Ce n'est pas un probleme immediat, mais la frontiere entre composant et page commence a devenir floue.

Recommendation :

- garder les styles generiques dans `components/cards.css` ;
- deplacer progressivement les variantes tres liees a la home vers `pages/home.css` si le fichier grossit.

#### components/content.css

Le fichier contient :

- des styles generiques de contenu ;
- les styles du sommaire fixe ;
- les styles de l'indicateur de lecture.

Constat :

- le choix est acceptable aujourd'hui, car ces styles servent a plusieurs pages longues ;
- si les pages longues deviennent centrales, le fichier pourrait devenir un espace fourre-tout.

Recommendation :

- conserver l'etat actuel tant que les comportements restent limites aux details projets/experiences ;
- envisager `components/long-form.css` ou `components/content-navigation.css` si le sommaire, les ancres et la progression prennent plus de place.

#### project-detail.css et experience-detail.css

Les deux fichiers partagent des intentions proches :

- navigation precedent/suivant ;
- mise en page detail ;
- sections de contenu long ;
- meta-informations.

Constat :

- la duplication reste lisible ;
- les styles projet utilisent encore quelques couleurs litterales la ou experience s'appuie davantage sur les tokens.

Recommendation :

- ne pas fusionner maintenant ;
- harmoniser d'abord les tokens de couleur ;
- extraire seulement si une troisieme famille de pages longues reutilise les memes patterns.

#### typography.css

Le fichier contient actuellement tres peu de logique.

Constat :

- ce n'est pas grave ;
- il peut rester comme point d'extension futur ;
- il ne faut pas le remplir artificiellement.

## Chargement CSS

`styles/app.css` importe tous les styles publics principaux.

Constat :

- c'est simple et coherent pour le site actuel ;
- le cout mental est faible ;
- le lab DnD reste bien separe grace a son entree dediee.

Risque futur :

- si la partie privee grossit, charger tous les styles publics et prives dans une seule entree deviendra moins clair.

Recommendation :

- garder `app.css` pour le socle public ;
- prevoir une entree dediee pour les futurs modules prives si leur CSS devient autonome ;
- conserver le modele deja utilise par le DnD comme reference.

## JavaScript Global

### mobile-menu.js

Constat :

- comportement clair ;
- bon usage des attributs `data-*` ;
- fermeture au clic sur lien, clic exterieur et touche Escape ;
- responsabilite bien limitee.

Pas de refactor recommande maintenant.

### theme-switcher.js

Constat :

- logique simple et fonctionnelle ;
- dependance raisonnable a `localStorage` ;
- le comportement est suffisamment isole.

Points de vigilance :

- les libelles visibles du theme switcher sont dans Twig et restent a traduire ;
- la fermeture du menu pourrait etre harmonisee avec le menu mobile si le composant devient plus complexe.

### content-toc.js

Constat :

- comportement cible et comprehensible ;
- adaptation mobile correcte ;
- fermeture au choix d'une section, clic exterieur et touche Escape.

Point de vigilance :

- le script utilise `querySelector` et suppose donc un seul sommaire par page.

Recommendation :

- documenter implicitement ce contrat en gardant un seul sommaire par page ;
- passer a `querySelectorAll` seulement si une page a plusieurs zones de contenu longues.

### reading-progress.js

Constat :

- implementation discrete ;
- activation limitee aux pages detail projet et experience ;
- l'element est injecte dynamiquement, ce qui evite d'alourdir les templates.

Pas de refactor recommande maintenant.

### tracking.js

Constat :

- le script est court ;
- il utilise `sendBeacon`, ce qui est adapte a un clic de telechargement.

Point de vigilance :

- l'URL `/track/cv-download` est codee en dur dans le JavaScript.

Recommendation :

- si d'autres evenements de tracking apparaissent, passer l'URL via un attribut `data-tracking-url` dans le HTML ;
- ne pas generaliser maintenant pour un seul evenement.

## JavaScript DnD

Le module DnD est le morceau JavaScript le plus dense du site.

### Forces

- les responsabilites sont deja decoupees par fichier :
  - joueurs ;
  - monstres ;
  - initiative ;
  - ordre de tour ;
  - regles ;
  - validation ;
- le point d'entree `dnd_initiative.js` joue un role d'orchestrateur ;
- le module est separe du JavaScript public global ;
- le code reste comprehensible malgre la densite fonctionnelle.

### Points De Vigilance

#### Couplage DOM

Les scripts DnD dependent fortement :

- des ids HTML ;
- des classes CSS ;
- des templates Twig ;
- de certains textes ou symboles affiches.

Ce couplage est normal pour une petite application sans framework, mais il doit etre traite comme un contrat.

Recommendation :

- si le DnD evolue, centraliser les selecteurs importants dans un petit module dedie ;
- documenter les ids/classes critiques dans le template ou dans le fichier d'audit DnD futur.

#### Textes En Dur

De nombreux messages utilisateur sont directement dans le JavaScript :

- erreurs de validation ;
- libelles d'actions ;
- messages d'etat ;
- textes de confirmation ou d'aide.

Constat :

- c'est acceptable si l'outil DnD reste un lab francophone ;
- cela deviendra limitant si le module doit rejoindre la partie publique multilingue.

Recommendation :

- ne pas traduire maintenant ;
- prevoir une petite couche de messages/configuration si le DnD devient public ou multilingue.

#### monster_classes.js

Le fichier est tres volumineux et contient les donnees de monstres/classes.

Constat :

- il ressemble davantage a un fichier de donnees genere ou importe qu'a du code applicatif classique ;
- il est logique de le garder hors du coeur public ;
- il ne doit probablement pas etre edite manuellement au fil de l'eau.

Recommendation :

- identifier et documenter sa source ;
- si le fichier est genere, ajouter un commentaire ou une note de maintenance ;
- envisager un format donnees separe seulement si la maintenance devient penible.

#### rules.js et turn-order.js

Constat :

- `rules.js` melange logique metier de regles et gestion de modale ;
- `turn-order.js` melange etat, rendu et interactions drag/drop ;
- ce melange reste acceptable pour la taille actuelle.

Recommendation :

- ne pas refactorer preventivement ;
- separer logique pure et rendu uniquement si de nouvelles regles ou tests apparaissent.

## Outillage Frontend

Outillage actuel :

- `prettier` ;
- `stylelint` ;
- `stylelint-config-standard`.

Constat :

- le CSS est outille correctement ;
- il n'existe pas encore de lint ou test JavaScript ;
- le projet reste suffisamment petit pour que ce manque ne soit pas critique.

Recommendation :

- ne pas ajouter d'outil juste pour ajouter un outil ;
- envisager ESLint ou tests JS seulement si le DnD devient plus central ou si la partie privee introduit davantage de logique front.

## Recommandations Priorisees

### Priorite 1 - Nettoyage Simple

- Supprimer le `console.log` par defaut dans `assets/app.js`.
- Garder le modele actuel : `app.js` pour le public, entree dediee pour les modules autonomes.
- Ne pas fusionner les CSS detail projet/experience pour le moment.

### Priorite 2 - Stabilisation Progressive

- Harmoniser les couleurs litterales restantes avec les tokens CSS.
- Surveiller `components/content.css` si les pages longues prennent plus d'importance.
- Replacer les styles tres specifiques a la home hors de `components/cards.css` si le fichier continue de grossir.
- Passer l'URL de tracking via HTML si plusieurs evenements de tracking apparaissent.

### Priorite 3 - Evolution Future

- Centraliser les selecteurs DOM critiques du DnD si l'outil continue d'evoluer.
- Documenter la source de `monster_classes.js`.
- Ajouter un lint/test JS seulement si la logique front devient plus sensible.
- Prevoir des entrees assets dediees pour les futurs modules prives.

## Note De Reprise

```text
Phase 4 terminee.

Assets audites :
- app.js
- importmap.php
- styles/app.css
- styles/base/*
- styles/components/*
- styles/layout/*
- styles/pages/*
- styles/lab/dnd/*
- scripts globaux
- scripts lab/dnd/*

Verifications :
- npx stylelint "assets/styles/**/*.css" : OK
- php bin/console debug:asset-map : OK

Constats principaux :
- Organisation CSS globale saine.
- Entree DnD separee, bon precedent pour les futurs modules autonomes.
- Scripts globaux courts et bien limites.
- DnD traite comme mini-application, avec un couplage DOM fort mais comprehensible.

Findings concrets :
- app.js contient encore le console.log par defaut d'AssetMapper.
- components/cards.css contient des styles specifiques a la landing page.
- components/content.css regroupe contenu, sommaire et progression de lecture.
- project-detail.css utilise encore quelques couleurs litterales.
- content-toc.js suppose un seul sommaire par page.
- tracking.js code l'URL /track/cv-download en dur.
- DnD contient beaucoup de textes FR en dur et un gros fichier de donnees monster_classes.js.

Recommandations principales :
- Nettoyer app.js.
- Garder les entrees assets separees par domaine autonome.
- Harmoniser progressivement les tokens CSS.
- Ne pas refactorer le DnD avant que son perimetre evolue.
- Documenter la source de monster_classes.js.

Phase suivante :
- Phase 5 - Donnees, Markdown, Traductions Et Contenus.
```
