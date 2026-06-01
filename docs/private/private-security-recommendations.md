# Recommandations De Securite De La Zone Privee

Date de mise a jour : 2026-05-20

Ce document consigne la strategie recommandee pour gerer le secret `PRIVATE_ADMIN_PASSWORD_HASH` utilise par la zone privee.

## Principe

`PRIVATE_ADMIN_PASSWORD_HASH` est une donnee sensible.

Elle ne doit pas etre definie dans :

- `security.yaml` ;
- `.env` ;
- un fichier committe contenant des valeurs de production ;
- la documentation ;
- un ticket ou une pull request.

Le mot de passe en clair ne doit exister que dans un gestionnaire de mots de passe.

## Developpement Local

En developpement, `.env.dev` peut contenir un hash de test explicitement non sensible.

Exemple actuel :

```text
Username: private_admin
Password: private-dev-password
```

Ce couple identifiant/mot de passe ne doit jamais etre utilise en production.

## Production

En production, definir `PRIVATE_ADMIN_PASSWORD_HASH` hors Git.

Methode recommandee pour ce projet :

1. Generer un vrai mot de passe dans un gestionnaire de mots de passe.
2. Lancer la commande Make dediee.
3. Stocker uniquement le hash dans Symfony Secrets ou dans les variables d'environnement du serveur.
4. Ne jamais stocker le mot de passe en clair sur le serveur.

Commande recommandee :

```bash
make private-admin-secret
```

Cette commande :

- utilise `prod` comme environnement par defaut ;
- genere les cles Symfony Secrets si elles n'existent pas encore ;
- demande le mot de passe deux fois en saisie masquee ;
- genere un hash sans passer le mot de passe en argument de ligne de commande ;
- stocke le hash dans le secret `PRIVATE_ADMIN_PASSWORD_HASH`.

Pour cibler un autre environnement :

```bash
make private-admin-secret PRIVATE_SECRET_ENV=staging
```

Commandes Symfony equivalentes, si une intervention manuelle est necessaire :

```bash
APP_ENV=prod php bin/console secrets:generate-keys
APP_ENV=prod php bin/console security:hash-password
APP_ENV=prod php bin/console secrets:set PRIVATE_ADMIN_PASSWORD_HASH
```

## Deploiement Du Vault Symfony

Si Symfony Secrets est utilise en production, la cle privee de dechiffrement ne doit pas etre committee.

Deux options acceptables :

- deposer le fichier prive `config/secrets/prod/prod.decrypt.private.php` directement sur le serveur ;
- definir la variable d'environnement `SYMFONY_DECRYPTION_SECRET` sur le serveur.

La seconde option est preferable si l'hebergeur ou le pipeline de deploiement permet de gerer proprement les variables d'environnement sensibles.

## Rotation

Pour changer le mot de passe prive :

1. Generer un nouveau mot de passe dans le gestionnaire de mots de passe.
2. Relancer `make private-admin-secret`.
3. Verifier que `PRIVATE_ADMIN_PASSWORD_HASH` a ete remplace dans le systeme de secrets choisi.
4. Redployer ou vider le cache si necessaire.
5. Supprimer l'ancien mot de passe du gestionnaire de mots de passe.

## Reference

Documentation officielle Symfony :

```text
https://symfony.com/doc/current/configuration/secrets.html
```
