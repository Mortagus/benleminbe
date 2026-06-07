# Deploiement Et Verification

Ce document decrit les cibles principales du `makefile` et la procedure de deploiement associee.

## Cibles Principales

### `make check`

Verification locale de reference. Elle couvre:

- les metadonnees Composer ;
- la syntaxe PHP ;
- les fichiers YAML ;
- les templates Twig ;
- le conteneur Symfony ;
- le format Markdown, avec diff affiché en cas d'ecart ;
- les fichiers CSS ;
- les fichiers JavaScript ;
- les tests JavaScript.

Quand cette verification passe, lancer ensuite:

```bash
make cc
```

Cette seconde commande recompile les assets et nettoie le cache, ce qui permet de voir plus vite le rendu applique dans le navigateur apres une modification.

### `make reload_assets`

Recompile les assets avec Asset Mapper en environnement de developpement.

Usage typique:

```bash
make reload_assets
```

### `make cc`

Nettoie le cache apres recompilation des assets.

Usage typique:

```bash
make cc
```

### `make gpt_docs`

Compile un document Markdown de contexte pour ChatGPT.

Le fichier généré est `var/gpt/consolidated-markdown-context.md`.

Il agrège les documents de référence du projet, la documentation de la zone privée, la documentation du lab et les travaux en cours utiles pour partager rapidement l'état actuel du dépôt.

### `make migrate`

Execute les migrations Doctrine dans l'environnement courant.

Par defaut, la cible utilise `dev`. Pour viser un autre environnement, passer `MIGRATION_ENV`.

Usage typique:

```bash
make migrate
```

Pour executer les migrations en production:

```bash
make migrate MIGRATION_ENV=prod
```

### `make deploy`

Procedure de deploiement standard du site.

Ordre des operations:

1. `git pull --rebase`
2. `composer install --no-dev --optimize-autoloader --no-interaction`
3. preparation du fichier de log `var/log/cv-downloads.log`
4. `make migrate MIGRATION_ENV=prod`
5. `cache:clear --env=prod`
6. `cache:warmup --env=prod`
7. `asset-map:compile --env=prod`
8. generation du sitemap
9. `make private-prod-check`

Usage typique:

```bash
make deploy
```

### `make private-prod-check`

Smoke test public de la zone privee en production.

Verifications:

- `/private` redirige vers `/private/login` sans session ;
- la page de login expose le CSRF ;
- la page de login affiche bien le titre attendu ;
- les routes privees principales redirigent vers le login sans session ;
- la base de donnees de production est joignable via Doctrine avec une requete simple ;
- `robots.txt` bloque `/private/` ;
- `sitemap.xml` n'expose aucune URL privee ;
- l'entrypoint prive est disponible ;
- l'entrypoint prive reference le CSS prive, le helper de copie et le theme switcher.

Usage typique:

```bash
make private-prod-check
```

Variables utiles:

```bash
make private-prod-check PRIVATE_BASE_URL=https://example.com
```

### `make private-prod-auth-check`

Smoke test authentifie de la zone privee en production.

Verifications:

- une connexion invalide reste sur le login avec le message d'erreur attendu ;
- une connexion valide ouvre le dashboard prive ;
- le dashboard contient le lien de deconnexion ;
- le dashboard reste marque `noindex,nofollow` ;
- le logout invalide la session ;
- `/private` redevient inaccessible apres logout.

Usage typique:

```bash
make private-prod-auth-check
```

Variables utiles:

```bash
make private-prod-auth-check PRIVATE_BASE_URL=https://example.com PRIVATE_ADMIN_USERNAME=private_admin
```

### `make private-admin-secret`

Crée ou met a jour le hash du mot de passe admin prive dans les Symfony Secrets.

### `make pagespeed_audit`

Lance la collecte PageSpeed selon les parametres passes en variables d'environnement.

## Procedure De Reference

Pour un deploiement standard:

```bash
make deploy
```

Si la partie privee doit etre verifiee manuellement apres un incident ou un changement de config, lancer aussi:

```bash
make private-prod-auth-check
```

## Remarques

- les scripts shell d'implementation des verifications privees vivent dans `tools/private/` ;
- les cibles de deploiement supposent que les secrets prod et les identifiants base de donnees sont deja valides ;
- en cas de probleme sur `/private/network`, verifier d'abord l'etat de la base avant d'interpréter un défaut d'affichage ;
- `make private-prod-check` ne demande pas de secret et peut servir de smoke test rapide apres un deploiement ;
- il inclut aussi un test d'accessibilite base sur `php bin/console dbal:run-sql 'SELECT 1' --env=prod --no-interaction`.
