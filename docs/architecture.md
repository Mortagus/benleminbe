# Architecture Du Site

Ce document résume l'architecture actuelle du site personnel `benlemin.be`. Il sert de référence stable, distincte des audits historiques conservés dans `docs/termines/`.

## Role Du Projet

Le site est une application Symfony qui présente le profil professionnel de Benjamin Lemin, son parcours, ses projets, ses compétences, ses moyens de contact et quelques outils expérimentaux intégrés au `lab`.

Le projet remplace progressivement un CV PDF statique par une présence en ligne bilingue et maintenable. Les contenus publics sont disponibles en français et en anglais.

## Stack

- PHP 8.4
- Symfony 8
- Twig
- Symfony Asset Mapper et Importmap
- JavaScript sans framework front-end
- CSS découpé par bases, composants, layouts, pages et modules
- Composer pour PHP
- npm pour les outils front-end
- Stylelint, Prettier et Vitest pour les vérifications front-end

Le site ne dépend pas d'une base de données pour les contenus publics actuels.

## Grandes Parties

### Site Public Professionnel

Responsabilité : présenter le profil, les expériences, les projets, les compétences, les pages de contact, la carte de visite web et les pages légales.

Emplacements principaux :

```text
src/Public/
templates/
translations/
assets/pages/
assets/styles/pages/
```

Les contrôleurs publics restent minces. Les contenus textuels publiés sont principalement portés par les fichiers YAML de traduction.

### Zone Privee

Responsabilité : fournir un socle privé protégé par authentification, actuellement minimal.

Emplacements principaux :

```text
src/Private/
templates/private/
assets/styles/private/
config/packages/security.yaml
```

Les règles de gestion du secret admin sont documentées dans [private-security-recommendations.md](private-security-recommendations.md).

### Lab

Responsabilité : héberger des expérimentations utiles sans les sortir de l'écosystème du site.

Le module principal actuel est le DnD Initiative Tracker.

Emplacements principaux :

```text
src/Public/Controller/LabController.php
templates/lab/
assets/scripts/lab/
assets/styles/lab/
tools/dnd/
docs/lab/
```

Le lab reste public, mais séparé du contenu professionnel par ses routes, templates, styles, scripts et documents dédiés.

### Contenus Et Traductions

Responsabilité : fournir les textes publics et conserver le corpus éditorial de travail.

Sources de vérité runtime :

```text
translations/*.fr.yaml
translations/*.en.yaml
```

Corpus éditorial :

```text
docs/pro_exp/
```

Le contrat de contenu est détaillé dans [content-workflow.md](content-workflow.md).

### Assets

Responsabilité : fournir le CSS, le JavaScript et les images sources.

Organisation principale :

```text
assets/
├── app.js
├── pages/
├── scripts/
├── styles/
└── images/
```

`assets/app.js` charge le socle commun du site. Les pages publiques chargent aussi des entrypoints dédiés depuis `assets/pages/` quand elles ont besoin de CSS spécifique.

## Commandes De Verification

Commande principale :

```bash
make check
```

Commandes utiles selon le contexte :

```bash
make reload_assets
make cc
make private-prod-check
make private-prod-auth-check
```

## Documentation Associee

- [Index documentation](README.md)
- [Workflow de contenu](content-workflow.md)
- [Sécurité de la zone privée](private-security-recommendations.md)
- [Documents en cours](en-cours/)
- [Travaux terminés](termines/)
