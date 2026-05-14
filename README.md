# benlemin.be

Site web personnel de Benjamin Lemin, pensé comme un **CV en ligne** et une présence professionnelle accessible publiquement via le domaine [benlemin.be](https://benlemin.be).

L’objectif principal du projet est de présenter de manière claire mon parcours, mes compétences, mes expériences et les moyens de me contacter, dans un format plus vivant et plus maîtrisé qu’un CV PDF classique.

## Objectifs du projet

Ce site a pour but de :

- présenter mon profil professionnel ;
- mettre en avant mon parcours, mes compétences et mes expériences ;
- fournir un point de contact simple pour les recruteurs, entreprises ou collaborateurs potentiels ;
- construire progressivement une présence en ligne personnelle ;
- servir de base évolutive pour de futurs contenus : projets, portfolio, articles, expérimentations techniques, etc.

Le projet est volontairement orienté vers quelque chose de concret et publiable rapidement, plutôt que vers une phase trop longue de conception abstraite.

## Statut actuel

Le projet est en cours de développement.

Une première version du site est déjà mise en place avec :

- une structure Symfony fonctionnelle ;
- une organisation initiale des templates, assets et configuration ;
- un hébergement prévu chez Infomaniak ;
- un nom de domaine configuré : `benlemin.be` ;
- une première orientation éditoriale autour du concept de CV en ligne.

## Stack technique

Le projet est développé avec les technologies suivantes :

- PHP 8.4
- Symfony 8
- Twig
- Symfony Asset Mapper
- JavaScript
- CSS
- npm pour la gestion des dépendances front-end
- Composer pour la gestion des dépendances PHP

## Vérifications avant commit

Le dépôt fournit un hook Git `pre-commit` versionné dans `.githooks/`.

Pour l'activer après un clone du projet :

```bash
make install-hooks
```

À chaque `git commit`, Git lance ensuite automatiquement :

```bash
make check
```

Le commit est bloqué si une vérification échoue. Le hook peut toujours être contourné ponctuellement avec `git commit --no-verify`, à réserver aux cas exceptionnels.

## CSS de contexte pour ChatGPT

Pour générer un fichier CSS complet à fournir comme contexte dans l'interface web de ChatGPT :

```bash
make gpt_css
```

La commande résout les imports depuis `assets/styles/app.css` et génère le fichier temporaire `var/gpt/css-context.css`.

## Structure du projet

Aperçu simplifié de l’organisation du projet :

```text
benleminbe/
├── assets/          # Fichiers front-end : CSS, JavaScript, images, etc.
├── bin/             # Exécutables Symfony
├── config/          # Configuration Symfony
├── public/          # Point d’entrée public du site
├── src/             # Code PHP applicatif
├── templates/       # Templates Twig
├── translations/    # Fichiers de traduction
├── .env.dev         # Variables d’environnement de développement
├── composer.json    # Dépendances PHP
├── importmap.php    # Configuration des assets via Asset Mapper
├── package.json     # Dépendances front-end
├── makefile         # Commandes utiles pour le développement
└── README.md        # Documentation du projet
```

## Roadmap

Évolutions envisagées :

- finaliser la première version publique du CV en ligne
- améliorer la présentation du parcours professionnel
- ajouter une section projets ou portfolio
- ajouter une page de contact claire et accessible
- optimiser le référencement naturel
- améliorer les performances et l’accessibilité
- documenter le déploiement Infomaniak
- ajouter des tests automatisés sur les parties critiques

## Licence

Ce projet est distribué sous licence indiquée dans le fichier [LICENSE](LICENSE).

## Auteur

**Benjamin Lemin**

Site web : [benlemin.be](https://benlemin.be)
