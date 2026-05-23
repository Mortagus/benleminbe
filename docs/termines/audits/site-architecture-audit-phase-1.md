# Site Architecture Audit - Phase 1

## Objectif

Cartographier la structure actuelle du site, identifier les grands modules existants et reperer les premieres boundaries utiles pour les phases suivantes.

Cette phase reste volontairement globale. Les constats ci-dessous ne remplacent pas les audits detailles backend, Twig, CSS/JS, donnees ou DnD prevus dans les phases suivantes.

## Structure Generale

Le projet est une application Symfony avec AssetMapper, Twig, traductions YAML et quelques outils/documentations annexes.

Zones principales :

- `src/` : code PHP applicatif, controllers, services et commandes.
- `templates/` : templates Twig, composants et pages.
- `assets/` : CSS, JavaScript et images sources.
- `translations/` : textes localises FR/EN pour les pages publiques.
- `docs/` : documentation de travail, sources editoriales professionnelles et documents d'audit.
- `public/` : point d'entree HTTP, assets publics generes et fichiers telechargeables.
- `tools/` : scripts/outils hors runtime principal, notamment autour du DnD.
- `config/` : configuration Symfony.

## Modules Reperes

### Site Public Professionnel

Responsabilite : presenter les informations professionnelles, projets, experiences, competences, contact, carte et pages legales.

Code principal :

- `src/Public/Controller/HomeController.php`
- `src/Public/Controller/PageController.php`
- `src/Public/Controller/BusinessCardController.php`
- `src/Public/Controller/ProjectsController.php`
- `src/Public/Controller/ExperiencesController.php`
- `src/Public/Service/CvProvider.php`
- `src/Public/Service/ProjectProvider.php`
- `src/Public/Service/ExperienceProvider.php`

Templates :

- `templates/home/`
- `templates/pages/`
- `templates/projects/`
- `templates/experiences/`
- `templates/components/`

Assets :

- `assets/styles/pages/`
- `assets/styles/components/`
- `assets/styles/layout/`
- `assets/styles/base/`
- `assets/scripts/`

Traductions :

- `translations/home.*.yaml`
- `translations/about.*.yaml`
- `translations/projects.*.yaml`
- `translations/experiences.*.yaml`
- `translations/skills.*.yaml`
- `translations/contact.*.yaml`
- `translations/card.*.yaml`
- `translations/legal.*.yaml`
- `translations/layout.*.yaml`

### Lab / DnD Initiative Tracker

Responsabilite : fournir un outil interactif distinct du site vitrine.

Code principal :

- `src/Public/Controller/LabController.php`

Templates :

- `templates/lab/dnd/initiative_tracker.html.twig`
- `templates/lab/dnd/_monsters_panel.html.twig`
- `templates/lab/dnd/_players_panel.html.twig`
- `templates/lab/dnd/_rules_panels.html.twig`
- `templates/lab/dnd/_turn_order_panel.html.twig`

Assets :

- `assets/scripts/lab/dnd/`
- `assets/styles/lab/dnd/`

Documentation :

- `docs/lab/dnd-initiative-audit.md`
- `docs/en-cours/dnd-initiative-tracker-backlog.md`

Outils :

- `tools/dnd/`

### Tracking Minimal

Responsabilite : journaliser les telechargements de CV.

Code principal :

- `src/Public/Controller/TrackingController.php`
- `assets/scripts/tracking.js`

Observation de phase 1 : le tracking est actuellement dans l'espace `Public`, mais il ecrit directement dans `var/log`. La repartition exacte des responsabilites sera a verifier en phase backend.

### Commandes

Responsabilite : operations CLI applicatives.

Code principal :

- `src/Command/GenerateSitemapCommand.php`

Observation de phase 1 : la commande reutilise `ProjectProvider` et `ExperienceProvider`, ce qui indique que ces providers servent deja a la fois au rendu web et a des operations techniques.

### Future Partie Privee

Etat actuel : aucun espace dedie n'existe encore.

Il n'y a pas encore de namespace, dossier de templates, route prefixee, assets ou configuration de securite specifiques pour une partie privee.

Candidates possibles a discuter plus tard :

- `src/Private/`
- `src/Admin/`
- `src/Personal/`
- `templates/private/`
- `assets/styles/private/`
- `assets/scripts/private/`

La phase 7 devra proposer une boundary cible claire.

## Routes Relevees

Routes publiques localisees :

- `/` vers redirection locale par defaut.
- `/{_locale}` pour la home.
- `/{_locale}/about`
- `/{_locale}/contact`
- `/{_locale}/skills`
- `/{_locale}/projects`
- `/{_locale}/projects/{project}`
- `/{_locale}/experiences`
- `/{_locale}/experiences/{experience}`
- `/{_locale}/terms-and-conditions`
- `/{_locale}/privacy-policy`
- `/{_locale}/legal-notice`

Routes publiques non localisees ou speciales :

- `/card` et `/en/card`, definies avec chemins localises explicites.
- `/contact/benjamin-lemin.vcf`
- `/track/cv-download`

Route lab :

- `/lab/dnd-initiative`

Observation de phase 1 : les routes professionnelles suivent majoritairement un modele localise par `/{_locale}`. Le lab est separe par un prefixe `/lab`, mais il vit encore dans le namespace PHP `App\Public`.

## Repartition Actuelle Des Responsabilites

### Backend

Le code backend est actuellement concentre dans :

- `src/Public/Controller`
- `src/Public/Service`
- `src/Command`

Les controllers publics sont globalement minces pour les pages simples, mais certains controllers contiennent deja de la logique concrete :

- `BusinessCardController` construit une vCard directement.
- `TrackingController` construit une ligne de log et ecrit directement dans le filesystem.
- `ProjectsController` et `ExperiencesController` orchestrent des providers et des relations entre projets et experiences.

Ces points ne sont pas forcement problematiques a ce stade, mais ils seront a auditer en phase 2.

### Templates

La separation Twig est lisible :

- `templates/components` pour les composants globaux.
- `templates/pages` pour les pages statiques.
- `templates/projects` et `templates/experiences` pour les contenus professionnels structures.
- `templates/lab/dnd` pour l'outil DnD.

La boundary du module DnD est plus nette dans les templates que dans le namespace PHP.

### Assets

L'organisation CSS/JS montre deja une intention de separation :

- `assets/styles/base`
- `assets/styles/layout`
- `assets/styles/components`
- `assets/styles/pages`
- `assets/styles/lab/dnd`
- `assets/scripts/lab/dnd`

Le point a verifier plus tard sera la frontiere entre composants globaux et styles specifiques de page, surtout apres l'ajout de composants transversaux comme le sommaire et l'indicateur de progression.

### Donnees Et Contenus

Les contenus professionnels existent a deux niveaux :

- sources Markdown dans `docs/pro_exp`;
- YAML de traduction dans `translations`.

Les providers consomment principalement les YAML et des constantes PHP. Les fichiers Markdown ne sont pas lus au runtime.

Cette dualite semble volontaire a court terme, mais elle cree une question de source de verite a traiter en phase 5.

## Premiers Constats

### Points Forts

- La structure generale est comprehensible.
- Le site public professionnel est deja regroupe dans `src/Public`.
- Les templates sont separes par domaine fonctionnel.
- Les assets DnD disposent deja d'un espace dedie.
- Les traductions sont explicitement separees par domaine.
- Les routes principales sont simples et lisibles.

### Risques Ou Incoherences Visibles

- Le prefixe PHP `App\Public` contient aussi le `LabController`, alors que le lab n'est pas exactement une page professionnelle publique.
- La future partie privee n'a pas encore de boundary preparee.
- Le tracking et la vCard melangent de la logique de construction/ecriture dans les controllers.
- Les providers publics servent aussi a la generation du sitemap, ce qui peut etre correct mais merite une verification de responsabilite.
- Les contenus professionnels sont dupliques entre docs Markdown et YAML runtime.
- Les routes publiques sont majoritairement localisees, mais certains chemins utilisent un modele different (`/card`, `/en/card`, `/lab/dnd-initiative`, `/track/cv-download`).

## Note Sur Le LabController

La presence de `LabController` dans `App\Public` est defendable : le lab est actuellement accessible publiquement, donc il peut logiquement appartenir au perimetre public.

La remarque de phase 1 porte surtout sur la signification du mot `Public`.

Deux lectures sont possibles :

- `Public` comme niveau d'accessibilite HTTP : tout ce qui est publiquement accessible vit dans `App\Public`.
- `Public` comme domaine fonctionnel du site professionnel public : les labs/outils publics pourraient alors avoir leur propre boundary.

Les deux lectures sont valables. Il faut surtout choisir une convention explicite avant l'ajout de la future partie privee.

### Option 1 - Garder Le LabController Dans App\Public\Controller

Structure :

```text
src/
  Public/
    Controller/
      HomeController.php
      PageController.php
      ProjectsController.php
      ExperiencesController.php
      LabController.php
```

Avantages :

- simple ;
- coherent si `Public` signifie "accessible publiquement" ;
- aucun refactor necessaire.

Limite :

- risque que `App\Public\Controller` melange progressivement pages professionnelles, labs publics, tracking et autres outils.

### Option 2 - Garder Le Lab Dans App\Public, Mais Avec Un Sous-Dossier Dedie

Structure recommandee a court terme :

```text
src/
  Public/
    Controller/
      HomeController.php
      PageController.php
      ProjectsController.php
      ExperiencesController.php
      TrackingController.php
      Lab/
        DndInitiativeController.php
```

Namespace :

```php
namespace App\Public\Controller\Lab;
```

Fichiers rattaches conserves dans leurs dossiers actuels :

```text
templates/lab/dnd/
assets/scripts/lab/dnd/
assets/styles/lab/dnd/
docs/lab/
tools/dnd/
```

Avantages :

- conserve l'idee que le lab est public ;
- separe mieux les pages professionnelles des outils publics ;
- prepare l'arrivee d'autres outils publics ;
- refactor limite.

Limite :

- ajoute un niveau de dossier supplementaire, mais avec un benefice de lisibilite.

### Option 3 - Creer Un Domaine App\Lab

Structure :

```text
src/
  Public/
    Controller/
      HomeController.php
      ProjectsController.php
      ExperiencesController.php

  Lab/
    Controller/
      DndInitiativeController.php
```

Avantages :

- boundary fonctionnelle claire ;
- aligne le PHP avec `templates/lab` et `assets/.../lab` ;
- interessant si les labs deviennent une famille de modules.

Limite :

- decision architecturale plus forte ;
- peut etre premature si le lab reste un seul outil public.

### Option 4 - Organisation Future Par Domaines

Structure cible possible si le site grandit fortement :

```text
src/
  Professional/
    Controller/
    Service/

  Lab/
    Controller/
    Service/

  Private/
    Controller/
    Service/

  Shared/
    Service/
```

Avantage :

- separation conceptuelle tres nette.

Limite :

- trop structurant pour l'etat actuel du projet.

### Recommandation De Phase 1

Recommandation pragmatique : option 2.

Garder le lab dans `App\Public`, mais deplacer a terme le controller vers un sous-namespace dedie :

```text
src/Public/Controller/Lab/DndInitiativeController.php
```

Cette option respecte le fait que l'outil est public tout en evitant que les controllers du site professionnel et les controllers d'outils se melangent trop.

## Points A Auditer Ensuite

Phase 2 devra verifier :

- si `src/Public` doit rester le seul namespace applicatif actuel ;
- si un namespace `Lab` devrait exister avant la future partie privee ;
- si `TrackingController` et `BusinessCardController` doivent extraire de la logique dans des services ;
- si les providers actuels ont une responsabilite stable ou s'ils melangent configuration, donnees et presentation ;
- comment preparer les boundaries `Public`, `Lab` et `Private`.

## Note De Reprise

```text
Phase 1 terminee.

Structure analysee :
- Symfony avec src/Public, templates, assets, translations, docs, tools et config.
- Site professionnel public organise autour de controllers/services Public, templates pages/projects/experiences et traductions YAML.
- DnD Initiative Tracker isole cote templates/assets/tools, mais expose via App\Public\Controller\LabController.
- Future partie privee absente pour le moment.

Modules reperes :
- Public professional website.
- Lab / DnD Initiative Tracker.
- Tracking CV download.
- Sitemap generation command.
- Docs/pro_exp as editorial source material.

Risques ou incoherences visibles :
- Lab dans namespace Public.
- Pas encore de boundary pour Private.
- Logic vCard et tracking directement dans controllers.
- Providers utilises par web et CLI.
- Contenus dupliques entre docs Markdown et YAML.
- Modeles de routes localisees et non localisees melanges.

Points a verifier ensuite :
- Responsabilites exactes des controllers et services.
- Opportunite d'un namespace Lab.
- Preparation d'une boundary Private.
- Role futur des providers et des sources Markdown/YAML.

Phase suivante :
- Phase 2 - Architecture Backend Symfony.
```
