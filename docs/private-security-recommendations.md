# Private Area Security Recommendations

Date de mise à jour : 2026-05-20

Ce document consigne la stratégie recommandée pour gérer le secret `PRIVATE_ADMIN_PASSWORD_HASH` utilisé par la zone privée.

## Principe

`PRIVATE_ADMIN_PASSWORD_HASH` est une donnée sensible.

Elle ne doit pas être définie dans :

- `security.yaml` ;
- `.env` ;
- un fichier committé contenant des valeurs de production ;
- la documentation ;
- un ticket ou une pull request.

Le mot de passe en clair ne doit exister que dans un gestionnaire de mots de passe.

## Développement Local

En développement, `.env.dev` peut contenir un hash de test explicitement non sensible.

Exemple actuel :

```text
Username: private_admin
Password: private-dev-password
```

Ce couple identifiant/mot de passe ne doit jamais être utilisé en production.

## Production

En production, définir `PRIVATE_ADMIN_PASSWORD_HASH` hors Git.

Méthode recommandée pour ce projet :

1. Générer un vrai mot de passe dans un gestionnaire de mots de passe.
2. Lancer la commande Make dédiée.
3. Stocker uniquement le hash dans Symfony Secrets ou dans les variables d'environnement du serveur.
4. Ne jamais stocker le mot de passe en clair sur le serveur.

Commande recommandée :

```bash
make private-admin-secret
```

Cette commande :

- utilise `prod` comme environnement par défaut ;
- génère les clés Symfony Secrets si elles n'existent pas encore ;
- demande le mot de passe deux fois en saisie masquée ;
- génère un hash sans passer le mot de passe en argument de ligne de commande ;
- stocke le hash dans le secret `PRIVATE_ADMIN_PASSWORD_HASH`.

Pour cibler un autre environnement :

```bash
make private-admin-secret PRIVATE_SECRET_ENV=staging
```

Commandes Symfony équivalentes, si une intervention manuelle est nécessaire :

```bash
APP_ENV=prod php bin/console secrets:generate-keys
APP_ENV=prod php bin/console security:hash-password
APP_ENV=prod php bin/console secrets:set PRIVATE_ADMIN_PASSWORD_HASH
```

## Déploiement Du Vault Symfony

Si Symfony Secrets est utilisé en production, la clé privée de déchiffrement ne doit pas être committée.

Deux options acceptables :

- déposer le fichier privé `config/secrets/prod/prod.decrypt.private.php` directement sur le serveur ;
- définir la variable d'environnement `SYMFONY_DECRYPTION_SECRET` sur le serveur.

La seconde option est préférable si l'hébergeur ou le pipeline de déploiement permet de gérer proprement les variables d'environnement sensibles.

## Rotation

Pour changer le mot de passe privé :

1. Générer un nouveau mot de passe dans le gestionnaire de mots de passe.
2. Relancer `make private-admin-secret`.
3. Vérifier que `PRIVATE_ADMIN_PASSWORD_HASH` a été remplacé dans le système de secrets choisi.
4. Redéployer ou vider le cache si nécessaire.
5. Supprimer l'ancien mot de passe du gestionnaire de mots de passe.

## Référence

Documentation officielle Symfony :

```text
https://symfony.com/doc/current/configuration/secrets.html
```
