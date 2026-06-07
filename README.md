# benlemin.be

Code source de mon site web personnel : [benlemin.be](https://benlemin.be).

Le site sert à présenter mon profil de développeur web senior, mon parcours, mes projets représentatifs et les moyens de me contacter. Il remplace progressivement le simple CV PDF par une présence en ligne plus complète, bilingue et maintenable.

Il joue aussi le rôle de CV vivant en ligne : le contenu peut évoluer avec mon parcours, mais le projet reste également un espace d'expérimentation. Certaines fonctionnalités annexes, regroupées notamment dans le `lab`, me permettent de tester des idées, des interfaces et des outils concrets directement dans le code du site, sans les séparer de son écosystème technique.

## Contenu du site

Le site contient notamment :

- une page d'accueil orientée missions freelance et développement web sur mesure ;
- un parcours professionnel détaillé, avec expériences, responsabilités et technologies ;
- un portfolio de projets représentatifs issus de différents contextes métier ;
- une page compétences autour de PHP, Symfony, Drupal, JavaScript, intégration, reprise d'existant et qualité logicielle ;
- une page à propos ;
- une page de contact ;
- une carte de visite web avec export vCard ;
- des CV PDF en français et en anglais ;
- des pages légales ;
- un espace `lab` pour des expérimentations publiques rattachées au code du site, dont un outil de suivi d'initiative pour D&D.

Le site est disponible en français et en anglais, avec un sélecteur de langue, un thème clair/sombre et un sitemap généré depuis les routes du site.

## Stack technique

Le projet est développé avec :

- PHP 8.4 ;
- Symfony 8 ;
- Twig ;
- Symfony Asset Mapper / Importmap ;
- JavaScript sans framework front-end ;
- CSS organisé par bases, composants, layouts et pages ;
- Composer pour les dépendances PHP ;
- npm pour les outils front-end ;
- Stylelint, Prettier et Vitest pour les vérifications front-end.

Le projet ne dépend pas d'une base de données pour les contenus actuels : les données structurées et les textes sont fournis par du code PHP, des templates Twig et des fichiers de traduction YAML.

## Démarrage local

Installer les dépendances PHP et front-end :

```bash
composer install
npm install
```

Lancer le serveur Symfony local :

```bash
make serv
```

Par défaut, l'application est ensuite accessible via l'URL indiquée par Symfony CLI.

## Commandes utiles

Lancer les vérifications principales :

```bash
make check
```

Cette commande vérifie notamment :

- les métadonnées Composer ;
- la syntaxe PHP ;
- les fichiers YAML ;
- les templates Twig ;
- le conteneur Symfony ;
- le format des fichiers Markdown, vérifiable à part avec `npm run lint:md` ;
- les fichiers CSS avec Stylelint ;
- les fichiers JavaScript avec ESLint ;
- les tests JavaScript avec Vitest.

Recompiler les assets :

```bash
make reload_assets
```

Nettoyer le cache après compilation des assets :

```bash
make cc
```

Générer un fichier CSS de contexte pour un échange avec ChatGPT :

```bash
make gpt_css
```

La commande génère le fichier temporaire `var/gpt/consolidated-css-context.css` à partir de l'ensemble des fichiers CSS de `assets/styles`.

Générer un document Markdown de contexte pour partager l'état courant du projet :

```bash
make gpt_docs
```

La commande génère le fichier temporaire `var/gpt/consolidated-markdown-context.md` à partir des documents de référence, de la documentation de la zone privée, du lab et des travaux en cours utiles.

## Hooks Git

Le dépôt fournit un hook Git `pre-commit` versionné dans `.githooks/`.

Pour l'activer après un clone du projet :

```bash
make install-hooks
```

À chaque `git commit`, Git lance ensuite automatiquement :

```bash
make check
```

Le commit est bloqué si une vérification échoue. Le hook peut être contourné ponctuellement avec `git commit --no-verify`, à réserver aux cas exceptionnels.

## Structure du projet

Aperçu simplifié :

```text
benleminbe/
├── assets/              # CSS, JavaScript et images sources
├── bin/                 # Exécutables Symfony
├── config/              # Configuration Symfony
├── docs/                # Documentation stable, chantiers en cours et travaux terminés
├── public/              # Point d'entrée HTTP, assets compilés, CV et fichiers publics
├── src/                 # Code PHP applicatif
├── templates/           # Templates Twig
├── tools/               # Scripts utilitaires
├── translations/        # Traductions FR/EN
├── composer.json        # Dépendances PHP
├── importmap.php        # Entrées JavaScript Asset Mapper
├── package.json         # Outils front-end
├── makefile             # Commandes de développement et de déploiement
└── README.md            # Documentation du projet
```

La documentation détaillée commence dans [`docs/documentation-index.md`](docs/documentation-index.md). La vue stable de l'architecture est dans [`docs/project-architecture.md`](docs/project-architecture.md).

## Roadmap

Évolutions possibles :

- enrichir les fiches projets avec plus de contexte métier et technique ;
- ajouter des tests automatisés sur les comportements JavaScript les plus sensibles ;
- améliorer progressivement l'accessibilité et les performances ;
- compléter la documentation de déploiement ;
- faire évoluer les outils du `lab` quand ils deviennent utiles au quotidien.

## Licence

Ce projet est distribué sous licence MIT. Voir le fichier [LICENSE](LICENSE).

## Auteur

**Benjamin Lemin**

Site web : [benlemin.be](https://benlemin.be)
