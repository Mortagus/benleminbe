# Private Area Next Steps

Date de redaction : 2026-05-20

Ce document sert de note de reprise apres le lot 5.

Le lot 5 a pose la fondation de la zone privee : Symfony Security, login/logout, layout prive, dashboard minimal, entrypoint assets prive, protection robots/noindex et gestion recommandee de `PRIVATE_ADMIN_PASSWORD_HASH`.

## Validation Production - 2026-05-21

La mise en production du socle prive a ete validee le 2026-05-21 sur l'hebergement Infomaniak.

Commandes executees sur le serveur de production :

```bash
make private-admin-secret
make deploy
make check
make private-prod-check
make private-prod-auth-check
git status --short
php bin/console secrets:list --env=prod
find config/secrets -maxdepth 3 -type f -print
find src/var -maxdepth 3 -type f -print
```

Resultats valides :

- `PRIVATE_ADMIN_PASSWORD_HASH` est stocke dans Symfony Secrets pour `prod` ;
- `make deploy` se termine correctement ;
- le cache prod est vide puis rechauffe correctement ;
- Asset Mapper compile les assets publics et prives ;
- le sitemap est regenere avec 62 URLs ;
- `make private-prod-check` passe ;
- `make private-prod-auth-check` passe ;
- `/private` redirige vers `/private/login` sans session ;
- une connexion invalide reste sur le login ;
- une connexion valide ouvre le dashboard prive ;
- le logout invalide la session et renvoie vers le login ;
- `/private` redevient inaccessible apres logout ;
- `secrets:list --env=prod` liste `PRIVATE_ADMIN_PASSWORD_HASH` sans reveler sa valeur.

Fichiers Symfony Secrets constates sur le serveur :

```text
config/secrets/prod/prod.encrypt.public.php
config/secrets/prod/prod.decrypt.private.php
config/secrets/prod/prod.PRIVATE_ADMIN_PASSWORD_HASH.23d73c.php
config/secrets/prod/prod.list.php
```

Point critique :

- `config/secrets/prod/prod.decrypt.private.php` doit rester uniquement sur le serveur et ne doit jamais etre committe.

Points de suivi non bloquants :

- `make check` echoue en production uniquement sur `npx stylelint` avec `Permission denied` ;
- ce probleme concerne l'outillage Node disponible sur le serveur, pas la validation fonctionnelle de la zone privee ;
- `git status --short` signale `config/secrets/` et `src/var/` comme non suivis sur le serveur ;
- `src/var/log/cv-downloads.log` est un fichier de log genere au mauvais emplacement apparent et ne doit pas etre committe ;
- le serveur peut ignorer localement ces chemins via `.git/info/exclude` pour eviter un ajout accidentel.

Exclusion locale recommandee sur le serveur de production :

```bash
cat >> .git/info/exclude <<'EOF'

# Production-only generated files
/config/secrets/
/src/var/
EOF
```

Conclusion :

```text
Le socle prive est valide en production. Les prochains travaux peuvent se concentrer sur un premier module prive reel, apres clarification du besoin.
```

## Lot 6 - Durcissement Et Mise En Production De La Zone Privee

Objectif :

- verifier que la zone privee est exploitable proprement en production avant d'ajouter un vrai module metier.

### 1. Valider Le Cycle Reel De Secret

Actions :

- generer un vrai mot de passe dans un gestionnaire de mots de passe ;
- lancer `make private-admin-secret` sur l'environnement cible ;
- verifier que `PRIVATE_ADMIN_PASSWORD_HASH` est bien stocke via Symfony Secrets ;
- verifier qu'aucun mot de passe en clair n'est ecrit dans Git, dans les logs ou dans la documentation ;
- verifier que la cle privee de dechiffrement n'est pas committee.

Points de controle :

- `config/secrets/*/*.decrypt.private.php` doit rester ignore par Git ;
- le serveur doit disposer soit du fichier prive de dechiffrement, soit de `SYMFONY_DECRYPTION_SECRET` ;
- le hash local de `.env.dev` doit rester uniquement un secret de developpement.

### 2. Durcir Le Deploiement

Actions :

- verifier que la commande de deploiement compile bien les assets publics et prives ;
- verifier que le cache de production se vide et se reconstruit correctement ;
- verifier que `/private` reste absent du sitemap ;
- verifier que `robots.txt` contient bien `Disallow: /private/`.

Commandes utiles :

```bash
make check
php bin/console asset-map:compile --env=prod
php bin/console app:generate-sitemap --env=prod
make private-prod-check
```

### 3. Verifier Le Parcours De Securite

Scenarios a tester :

- acceder a `/private` sans session doit rediriger vers `/private/login` ;
- une connexion valide doit ouvrir le dashboard prive ;
- une connexion invalide doit rester sur le formulaire de login ;
- le logout doit invalider la session et rediriger vers le login ;
- les pages publiques doivent rester accessibles sans authentification.

Points de controle :

- les pages privees doivent contenir `noindex,nofollow` ;
- aucune route privee ne doit etre exposee dans le sitemap ;
- le dashboard prive doit rester minimal tant qu'aucun module metier n'est ajoute.

Commandes utiles depuis un poste local :

```bash
make private-prod-check
make private-prod-auth-check
```

La premiere commande ne demande pas de secret. Elle verifie en production :

- la redirection de `/private` vers `/private/login` sans session ;
- la presence du champ CSRF sur le login ;
- la presence de `noindex,nofollow` sur le login ;
- la presence de `Disallow: /private/` dans `robots.txt` ;
- l'absence de `/private` dans `sitemap.xml` ;
- la disponibilite de l'entrypoint d'assets prive.

La seconde commande demande le mot de passe admin via un prompt masque. Elle verifie :

- qu'une connexion invalide reste sur le login avec le message d'erreur attendu ;
- qu'une connexion valide atteint le dashboard prive ;
- que le dashboard contient `noindex,nofollow` et le lien de deconnexion ;
- que le logout redirige vers le login ;
- que `/private` redevient inaccessible apres logout.

Variables disponibles :

```bash
make private-prod-check PRIVATE_BASE_URL=https://example.com
make private-prod-auth-check PRIVATE_BASE_URL=https://example.com PRIVATE_ADMIN_USERNAME=private_admin
```

### 4. Documenter L'Exploitation

Actions :

- conserver `docs/private-security-recommendations.md` comme reference pour le secret admin ;
- documenter la procedure de rotation du mot de passe ;
- documenter quoi faire en cas de perte de la cle Symfony Secrets ;
- noter les prerequis de production : variable `SYMFONY_DECRYPTION_SECRET` ou fichier prive deploye hors Git.

### 5. Ne Pas Commencer Tout De Suite

A reporter tant que le lot 6 n'est pas valide :

- ajout d'un module metier prive ;
- ajout d'une base de donnees ;
- ajout de roles multiples ;
- ajout de 2FA ;
- refactor global autour de `Shared`.

Ces sujets deviennent pertinents seulement quand le premier vrai besoin prive est clarifie.

## Verification De Fin De Lot

Le lot 6 peut etre considere termine quand :

- `make check` passe localement ;
- le secret de production est cree hors Git ;
- la cle privee Symfony Secrets n'est pas versionnee ;
- `/private` est inaccessible sans login ;
- login/logout fonctionnent sur l'environnement cible ;
- `/private` est absent du sitemap ;
- `robots.txt` bloque `/private/` ;
- la procedure de rotation est claire dans la documentation.

Etat au 2026-05-21 :

- le lot 6 est valide fonctionnellement en production ;
- le seul ecart connu est l'execution de `npx stylelint` sur le serveur de production, a traiter comme sujet d'outillage separe.

## Note De Reprise

Prochaine action recommandee :

```text
Le socle prive est valide. La prochaine phase peut demarrer par la definition du premier besoin metier prive avant d'ajouter stockage, roles, 2FA ou modules plus complexes.
```
