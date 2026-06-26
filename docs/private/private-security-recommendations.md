# Recommandations De Securite De La Zone Privee

Date de mise a jour : 2026-06-25

Ce document decrit le role du mot de passe de secours, la gestion du secret admin prive et les points de vigilance autour de la zone privee.

La strategie des passkeys et leur procedure de recuperation sont decrites en detail dans [private-passkeys.md](private-passkeys.md).

## Role Du Mot De Passe

Le mot de passe administrateur prive reste volontairement present comme mecanisme de secours.

Il ne doit pas devenir un second compte "normal" ni un canal de recuperation automatique.

Son role est limite a:

- premier enregistrement des passkeys;
- recuperation manuelle en cas de perte de toutes les passkeys;
- verification de secours dans les smoke tests automatises.

## Secret Et Hash

`PRIVATE_ADMIN_PASSWORD_HASH` est une donnee sensible.

Il ne doit pas etre defini en clair dans:

- `security.yaml`;
- `.env`;
- une documentation publique;
- un ticket ou une revue de code;
- un script de deploiement qui imprimerait la valeur en clair.

Le mot de passe en clair ne doit exister que dans un gestionnaire de mots de passe ou dans un canal de saisie local protege.

## Developpement Local

En developpement, `.env.dev` et `.env.test` peuvent contenir un hash de test explicitement non sensible.

Couple utilise localement:

```text
Username: private_admin
Password: private-dev-password
```

Ce couple ne doit jamais etre reutilise en production.

## Hashing Recommande

La commande `tools/private/private-admin-secret.sh` prefere Argon2id lorsque l'environnement PHP le supporte.

Si Argon2id n'est pas disponible, le script bascule vers l'algorithme effectif de `password_hash()` dans l'environnement courant. Ce fallback doit rester documente et exceptionnel.

Commande recommandee:

```bash
make private-admin-secret
```

Cette commande:

- genere ou met a jour le secret Symfony;
- demande le mot de passe en saisie masquee;
- ne l'affiche pas dans les logs;
- stocke uniquement le hash.

## Rotation

Pour changer le mot de passe prive:

1. Generer un nouveau mot de passe dans le gestionnaire de mots de passe.
2. Lancer `make private-admin-secret`.
3. Verifier que le hash a bien ete mis a jour dans les secrets ou la configuration deploiement.
4. Redemarrer ou vider le cache si necessaire.
5. Supprimer l'ancien mot de passe du gestionnaire de mots de passe.

Les sessions deja authentifiees peuvent rester valides jusqu'a expiration ou logout. Le changement du hash n'est donc pas une purge de session.

## Recuperation Manuelle

Si toutes les passkeys sont perdues:

1. Utiliser le mot de passe de secours pour ouvrir `/private/login`.
2. Enregistrer immediatement au moins deux passkeys depuis `/private/security/passkeys`.
3. Conserver le mot de passe comme recours documente, mais pas comme usage quotidien.

Il n'existe pas de recuperation par e-mail, de reset automatique, de compte multi-utilisateur ni de SaaS externe.

## Variables Et Fichiers Utiles

- `PRIVATE_ADMIN_PASSWORD_HASH`
- `PRIVATE_ADMIN_PASSWORD` uniquement pour automatiser les smoke tests locaux ou de CI, jamais pour la production
- `app.webauthn.rp_id` et `app.webauthn.origin` pour le contexte local WebAuthn si tu utilises un autre hote ou un autre port que le défaut
- `config/secrets/*`
- `tools/private/private-admin-secret.sh`
- `tools/private/private-prod-auth-check.sh`

## Reference

Documentation officielle Symfony:

```text
https://symfony.com/doc/current/configuration/secrets.html
```
