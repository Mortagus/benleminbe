# Site Architecture Audit - Phase 8

## Objectif

Transformer les phases 1 a 7 en plan d'action priorise, exploitable par petites interventions.

Cette phase reste une synthese d'audit. Aucun changement applicatif n'a ete effectue.

## Sources Consolidees

Documents consolides :

```text
site-architecture-audit-phase-1.md
site-architecture-audit-phase-2.md
site-architecture-audit-phase-3.md
site-architecture-audit-phase-4.md
site-architecture-audit-phase-5.md
site-architecture-audit-phase-6.md
site-architecture-audit-phase-7.md
```

Perimetres couverts :

- structure globale du site ;
- backend Symfony ;
- templates Twig ;
- CSS, assets et JavaScript ;
- donnees, Markdown et traductions ;
- DnD Initiative Tracker ;
- preparation de la future partie privee.

## Diagnostic General

Le projet est sain pour son etat actuel.

Les points les plus solides :

- le site public professionnel est deja bien separe cote PHP via `App\Public` ;
- les pages publiques sont lisibles et globalement minces cote controllers ;
- les traductions FR/EN sont coherentes ;
- les assets sont organises par base, layout, composants, pages et lab ;
- le DnD est deja isole comme mini-application ;
- le sitemap fonctionne par opt-in ;
- la future partie privee peut etre ajoutee sans refactor massif du public.

Les risques principaux ne sont pas des bugs immediats. Ce sont surtout des risques de croissance :

- laisser `Public`, `Lab` et future partie privee se melanger ;
- ajouter une partie privee sans securite Symfony ;
- laisser plusieurs sources de contenu diverger ;
- continuer a grossir les YAML projets sans contrat documente ;
- faire evoluer le DnD sans clarifier son etat applicatif ;
- creer trop vite des abstractions partagees avant d'avoir plusieurs usages reels.

## Priorite 1 - A Corriger Bientot

Ces actions sont petites, peu risquees et ameliorent directement la proprete ou la fiabilite du projet.

### Nettoyage Public Visible

Tickets proposes :

- Traduire les libelles du theme switcher.
- Confirmer ou remplacer `mailto:contact@example.com`.
- Supprimer le `console.log` AssetMapper restant dans `assets/app.js`.
- Corriger ou clarifier le fallback `home.meta.title` dans `templates/base.html.twig`.
- Aligner `docs/pro_exp/experience_compilation.md` sur la periode Isobar confirmee : July 2017 - February 2018.

Impact :

- peu de risque technique ;
- meilleure coherence visible ;
- moins de placeholders et de signaux de prototype.

### Routes Et Hygiene Simple

Tickets proposes :

- Ajouter `methods: ['GET']` a la route DnD.
- Decider une convention minimale PHP :
  - `declare(strict_types=1)` dans `src/` ;
  - booleens/null en minuscules ;
  - classes applicatives `final` par defaut ;
  - style d'accolades uniforme.
- Appliquer cette convention progressivement, fichier par fichier.

Impact :

- pas de changement fonctionnel majeur ;
- meilleure lisibilite ;
- preparation douce avant croissance.

### Documentation A Mettre A Jour

Tickets proposes :

- Marquer `docs/lab/dnd-initiative-audit.md` comme document historique partiellement obsolete.
- Documenter la generation de `assets/scripts/lab/dnd/monster_classes.js`.
- Documenter que les YAML sont la source de verite du contenu publie.
- Documenter le role de `docs/pro_exp` comme corpus editorial.

Impact :

- evite les confusions futures ;
- reduit le risque de modifier le mauvais fichier ;
- facilite les reprises apres pause.

## Priorite 2 - A Ameliorer Progressivement

Ces actions ont un bon rendement, mais ne doivent pas etre regroupees en gros refactor.

### Backend Symfony

Tickets proposes :

- Extraire la generation vCard dans un service dedie si elle evolue.
- Extraire l'ecriture tracking dans un service dedie si le tracking evolue.
- Remplacer l'injection `KernelInterface` de `ProjectProvider` par `%kernel.project_dir%`.
- Ajouter un cache par locale dans `ProjectProvider`.
- Harmoniser la gestion des slugs inconnus entre projets et experiences.

Ordre recommande :

1. `ProjectProvider` : projectDir + cache locale.
2. Tracking si de nouveaux evenements apparaissent.
3. vCard si plusieurs formats ou donnees apparaissent.

### Templates Et Composants

Tickets proposes :

- Harmoniser l'usage de `trans_default_domain` dans les templates projets.
- Documenter les cles de traduction rendues avec `|raw`.
- Envisager un composant `content_toc` si une troisieme famille de pages longues apparait.
- Envisager un composant de navigation detail si projets et experiences continuent de converger.

Regle de decision :

- extraire seulement apres trois usages ou une complexite repetee ;
- ne pas refactorer les pages legales maintenant.

### CSS Et Assets

Tickets proposes :

- Harmoniser les couleurs litterales restantes avec les tokens CSS.
- Surveiller `components/content.css` si sommaire/progression/ancres grossissent.
- Deplacer les styles tres specifiques a la home hors de `components/cards.css` si le fichier continue de grossir.
- Conserver le modele d'entrypoints dedies pour tout module autonome.

Impact :

- meilleure coherence visuelle ;
- moins de CSS composant qui connait trop les pages ;
- preparation de la future partie privee.

### Contenus Et Traductions

Tickets proposes :

- Documenter le schema attendu de `projects.yaml`.
- Documenter les contrats `ProjectProvider::PROJECTS` et `ExperienceProvider::EXPERIENCES`.
- Ajouter plus tard une commande `app:audit-content` si les contenus continuent de grossir.

Verification possible pour `app:audit-content` :

- chaque projet liste a une fiche detail ;
- chaque carte projet a une fiche ;
- chaque experience a ses cles traduites ;
- chaque projet associe pointe vers une experience existante ;
- les champs obligatoires projets existent ;
- les cles FR/EN restent synchronisees.

## Priorite 3 - A Garder En Tete

Ces sujets sont importants, mais seulement si le perimetre grandit.

### DnD Initiative Tracker

Tickets possibles :

- Factoriser le template joueur initial/template dynamique.
- Ajouter des noms accessibles aux champs dynamiques.
- Ajouter une alternative clavier au drag-and-drop.
- Centraliser les selecteurs critiques Twig/JS.
- Formaliser un modele `encounter` avant sauvegarde/import/export.
- Ajouter quelques tests JS sur les regles et `buildRoundOrder()`.
- Ajouter recherche/filtres/presets monstres si l'usage le justifie.

Ordre recommande :

1. Accessibilite et duplication Twig.
2. Documentation du catalogue monstres.
3. Modele `encounter` seulement avant persistance/import/export.
4. Tests JS quand les regles bougent.

Ce qu'il ne faut pas faire maintenant :

- reecrire le DnD avec un framework front ;
- convertir tout le catalogue monstres en backend ;
- internationaliser le module sans besoin public concret.

### Future Partie Privee

Regle principale :

```text
Ne pas creer de vraie route privee sans Symfony Security.
```

Tickets de fondation :

- Installer `symfony/security-bundle`.
- Installer ou activer CSRF pour login/formulaires.
- Creer `config/packages/security.yaml`.
- Proteger `^/private`.
- Creer login/logout.
- Creer `templates/private/base.html.twig`.
- Creer un entrypoint asset `private`.
- Ajouter `noindex,nofollow` dans le layout prive.
- Ne jamais stocker de donnees privees dans `public/`.

Structure cible :

```text
src/Public
src/Private ou src/Personal
src/Shared seulement si necessaire

templates/private
assets/scripts/private
assets/styles/private
```

Ce qu'il ne faut pas faire maintenant :

- deplacer les providers publics ;
- creer `Shared` avant un vrai besoin ;
- ajouter Doctrine avant de connaitre le premier module prive ;
- mettre un lien prive dans la navigation publique par defaut.

## Decisions Ouvertes

### Boundary Privee

Decision :

- `App\Private` ou `App\Personal`.

Recommendation :

- `App\Private` si l'objectif principal est une boundary claire public/prive ;
- `App\Personal` si le terme "private" semble trop proche du mot-cle PHP dans la lecture quotidienne.

### URL Privee

Decision :

- `/private`, `/admin`, `/personal` ou autre.

Recommendation :

- `/private` pour rester explicite ;
- ne pas compter sur l'obscurite de l'URL pour la securite.

### Authentification Initiale

Decision :

- in-memory/env vars, fichier, ou base de donnees.

Recommendation :

- commencer avec un seul utilisateur administrateur, mot de passe hashe, provider simple ;
- reporter Doctrine tant qu'il n'y a pas de besoin metier de base de donnees.

### Statut Du Lab

Decision :

- lab public durable ou futur ensemble d'outils mixtes public/prive.

Recommendation :

- garder le DnD public pour l'instant ;
- creer `src/Public/Controller/Lab/` seulement quand un deuxieme outil public apparait ou quand le controller grossit.

### Source De Verite Des Contenus

Decision :

- YAML publies ou Markdown source.

Recommendation :

- YAML = source de verite du contenu affiche ;
- `docs/pro_exp` = corpus editorial a maintenir en coherence manuellement.

## Plan De Travail Recommande

### Lot 1 - Nettoyage Court

Objectif :

- fermer les petits signaux de prototype.

Taches :

- supprimer le `console.log` AssetMapper ;
- corriger le fallback `home.meta.title` ;
- confirmer/remplacer l'email de contact ;
- traduire le theme switcher ;
- aligner le Markdown Isobar ;
- ajouter `methods: ['GET']` a la route DnD.

Verification :

- `php bin/console lint:twig templates` ;
- `php bin/console lint:yaml translations docs/pro_exp` ;
- `npx stylelint "assets/styles/**/*.css"` si CSS touche.

### Lot 2 - Documentation Et Contrats

Objectif :

- rendre les sources de verite explicites.

Taches :

- documenter le workflow contenu YAML/Markdown ;
- documenter `projects.yaml` ;
- documenter `ProjectProvider::PROJECTS` ;
- documenter `ExperienceProvider::EXPERIENCES` ;
- documenter la generation du catalogue DnD ;
- marquer l'ancien audit DnD comme historique.

Verification :

- lecture croisee des docs ;
- aucune verification applicative obligatoire si seuls les docs changent.

### Lot 3 - Backend Public Leger

Objectif :

- reduire les responsabilites un peu trop directes sans changer l'architecture.

Taches :

- simplifier `ProjectProvider` avec `projectDir` autowire ;
- ajouter un cache par locale ;
- extraire tracking si un deuxieme evenement apparait ;
- extraire vCard si la business card evolue.

Verification :

- `php bin/console lint:container` ;
- navigation manuelle projets/experiences ;
- verification du tracking uniquement si touche.

### Lot 4 - DnD Stabilisation

Objectif :

- rendre le module plus robuste sans reecriture.

Taches :

- factoriser le template joueur ;
- ajouter des labels accessibles aux champs dynamiques ;
- documenter les selecteurs critiques ;
- ajouter une alternative clavier si le drag/drop devient central ;
- ajouter tests JS cibles seulement si les regles changent.

Verification :

- `php bin/console lint:twig templates/lab/dnd` ;
- `npx stylelint "assets/styles/lab/dnd/**/*.css"` ;
- test manuel d'un combat simple et d'un combat volumineux.

### Lot 5 - Fondation Partie Privee

Objectif :

- preparer une vraie zone privee sans toucher au public.

Precondition :

- valider les decisions ouvertes : namespace, URL, auth, stockage.

Taches :

- installer Symfony Security ;
- creer firewall `^/private` ;
- creer login/logout ;
- creer layout prive ;
- creer entrypoint asset prive ;
- ajouter dashboard minimal ;
- ajouter `Disallow: /private/` et `noindex,nofollow`.

Verification :

- route privee inaccessible sans login ;
- login/logout fonctionnels ;
- routes publiques intactes ;
- sitemap sans routes privees.

## Ce Qu'il Ne Faut Pas Faire Maintenant

- Reorganiser tout `src/` avant d'avoir la partie privee.
- Creer `Shared` pour anticiper des besoins hypothetique.
- Convertir les Markdown en source applicative sans besoin editorial clair.
- Refactorer toutes les pages statiques en composants.
- Ajouter Doctrine seulement pour preparer le terrain.
- Refaire le DnD autour d'un framework front.
- Ajouter un lint/test JS global avant que le code front le justifie.

## Note De Reprise

```text
Phase 8 terminee.

Synthese :
- Architecture actuelle saine pour le site public.
- Principaux risques lies a la croissance future, pas a des bugs immediats.
- Les boundaries Public/Lab/Private doivent rester explicites.
- YAML recommande comme source de verite du contenu publie.
- DnD a stabiliser progressivement, sans reecriture.
- Partie privee a demarrer par la securite, pas par les modules.

Priorite 1 :
- Nettoyer placeholders et signaux de prototype.
- Corriger fallback traduction.
- Aligner Markdown Isobar.
- Ajouter GET explicite a la route DnD.
- Documenter les sources de verite.

Priorite 2 :
- Stabiliser ProjectProvider.
- Clarifier contrats projets/experiences.
- Harmoniser progressivement Twig/CSS/PHP.
- Factoriser seulement les composants qui gagnent un troisieme usage.

Priorite 3 :
- DnD : accessibilite, modele encounter, tests JS si evolution.
- Prive : Symfony Security, layout dedie, entrypoint dedie, stockage hors public.

Decisions ouvertes :
- App\Private ou App\Personal.
- URL racine privee.
- Authentification initiale.
- Stockage initial.
- Statut futur du lab.

Audit complet :
- Phase 1 a Phase 8 terminees.
```
