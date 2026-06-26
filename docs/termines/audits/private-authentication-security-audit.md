# Audit de securite de l'authentification privee

Date: 2026-06-25

Périmètre: `/private`, login Symfony existant, logout, sessions, throttling, headers, sitemap, robots, et préparation d'un futur support passkey/WebAuthn.

Ce document consigne l'etat observe pendant l'audit initial. L'architecture finale et les procedures operatoires sont documentees dans [private-passkeys.md](../../private/private-passkeys.md) et les autres notes privees a jour.

## Conclusions rapides

- Le firewall actuel protège bien tout le préfixe `/private` via `access_control`, et aucun route privée ne sort de ce préfixe dans le router.
- Le login Symfony vérifie bien le CSRF, avec le bon `csrf_token_id` et le bon nom de champ.
- Le secret administrateur n'est pas en clair dans Git, mais le hash de dev/test est committé et il reste en bcrypt.
- Le throttling existe déjà, mais la configuration actuelle est très serrée pour un compte unique.
- Les cookies de session sont déjà `HttpOnly` et `SameSite=lax`; `Secure` dépend de l'environnement/proxy.
- Il n'y a pas encore de support passkey/WebAuthn.
- Il n'y a pas encore de headers de sécurité dédiés au clickjacking ou au durcissement HTTP.

## 1. Firewall Et Autorisations

### Vérifié Dans Le Code

- `config/packages/security.yaml` expose un seul provider mémoire pour `private_admin`.
- Le firewall `main` couvre l'authentification privée et utilise `form_login`, `logout` et `login_throttling`.
- `access_control` place d'abord `/private/login` en `PUBLIC_ACCESS`, puis bloque tout le reste de `/private` avec `ROLE_PRIVATE_ADMIN`.
- Le router local confirme que toutes les routes privées connues sont sous `/private`.
- `templates/private/base.html.twig` ajoute `meta robots=noindex,nofollow`.
- `public/robots.txt` bloque `/private/`.
- Le générateur de sitemap n'opt-in que les routes marquées `sitemap.enabled`; aucune route privée n'est marquée ainsi.

### Supposé

- La nav universelle publique qui affiche un lien vers `Privé` est intentionnelle et non un oubli de sécurité; elle expose le point d'entrée, pas les données.

### Dépend De La Production / De L'Hébergement

- Le statut réel des routes privées en prod dépend du reverse proxy, de la détection HTTPS et des cookies de session.
- Les headers d'indexation au niveau HTTP dépendent de la config prod Symfony.

### Non Vérifiable Localement

- La bonne exposition des routes privées via l'hôte de production, les logs serveur et les caches CDN/proxy.

## 2. Login Et CSRF

### Vérifié Dans Le Code

- `src/Private/Controller/SecurityController.php` rend le formulaire de login manuel et renvoie l'utilisateur connecté vers le dashboard.
- Le formulaire Twig utilise `csrf_token('authenticate')`.
- `config/packages/security.yaml` a `enable_csrf: true`, `csrf_parameter: _csrf_token` et `csrf_token_id: authenticate`.
- Le champ utilisateur utilise `autocomplete="username"` et le mot de passe `autocomplete="current-password"`.
- Les erreurs de login affichées dans `templates/private/login.html.twig` restent génériques: `Identifiants invalides.`.
- Les tests manuels via KernelBrowser montrent le même message générique pour mauvais mot de passe et mauvais identifiant.
- Les tests manuels montrent qu'un token CSRF absent ou invalide produit aussi le retour générique au formulaire.

### Supposé

- Le token CSRF expiré se comportera comme un token invalide tant que la session qui le porte n'est plus valide.

### Dépend De La Production / De L'Hébergement

- La politique de session et la durée de vie du cookie peuvent faire expirer un token plus tôt que prévu si le serveur coupe la session.

### Non Vérifiable Localement

- Le comportement en présence d'un proxy ou d'un cache intermédiaire qui réécrirait le formulaire ou les cookies.

## 3. Mot De Passe Et Secret

### Vérifié Dans Le Code

- Le hash administrateur est lu depuis `PRIVATE_ADMIN_PASSWORD_HASH`.
- Ce hash est présent dans `.env.dev` et `.env.test` avec un couple de dev non sensible.
- Le hash actuel est un bcrypt `$2y$13...`.
- Le runtime local supporte Argon2id.
- Le script `tools/private/private-admin-secret.sh` ne logue pas le mot de passe en clair, mais il génère aujourd'hui un hash via `NativePasswordHasher`, donc le secret de dev reste en bcrypt.

### Supposé

- Le changement du hash dans Symfony Secrets ne tue pas les sessions déjà établies; seule la prochaine authentification est affectée.

### Dépend De La Production / De L'Hébergement

- La procédure réelle de rotation dépend du mode de déploiement des secrets Symfony et de l'accès au serveur.

### Non Vérifiable Localement

- L'absence de fuite du hash dans les backups, les exports système et les journaux externes de l'hébergeur.

## 4. Protection Contre Les Attaques De Login

### Vérifié Dans Le Code

- `login_throttling` est actif.
- Le throttling actuel est `max_attempts: 3` sur `30 minutes`.
- Le stockage du throttling est `cache.rate_limiter`, actuellement un `FilesystemAdapter`.
- Les limiters internes `_login_local_main` et `_login_global_main` existent et pointent tous deux vers ce pool.
- Les tests manuels montrent que le 4e essai échoue avant la phase d'identification/mot de passe, donc le throttling est réellement effectif.
- Les messages visibles à l'utilisateur restent génériques.
- Aucun log applicatif custom ne journalise mot de passe, CSRF ou payload WebAuthn, car il n'y a pas encore de flux WebAuthn.

### Supposé

- Le throttling protège à la fois l'identifiant et l'IP via les mécanismes Symfony générés par le firewall.

### Dépend De La Production / De L'Hébergement

- Le compteur du rate limiter survit tant que le cache persiste; un `cache:clear` ou un redéploiement peut le réinitialiser.
- Avec l'actuelle fenêtre de 30 minutes, un unique tiers peut temporairement bloquer l'administrateur depuis la même IP.

### Non Vérifiable Localement

- L'efficacité face au credential stuffing distribué, aux proxys multiples et aux attaques venant d'IP rotatives.

## 5. Sessions Et Cookies

### Vérifié Dans Le Code

- `framework.session` est activé.
- Les cookies ont `cookie_secure: auto`, `cookie_httponly: true`, `cookie_samesite: lax`.
- La stratégie de fixation de session est `migrate`.
- Le logout invalide la session.
- Les tests manuels montrent bien une nouvelle authentification après logout.
- Les cookies de session locaux observés sont `HttpOnly` et `SameSite=lax`.

### Supposé

- La durée côté serveur reste celle du handler PHP par défaut tant qu'aucune politique d'inactivité/expiration absolue n'est ajoutée.

### Dépend De La Production / De L'Hébergement

- `cookie_secure: auto` dépend de la détection HTTPS et donc du reverse proxy, de `DEFAULT_URI` et des trusted proxies.
- La durée d'inactivité réelle dépend aussi de `session.gc_maxlifetime` et du handler de session sur le serveur.

### Non Vérifiable Localement

- La politique exacte de purge des sessions sur l'hébergement de production.
- Le comportement simultané sur plusieurs navigateurs/appareils au long cours.

## 6. Environnement, HTTP Et Headers

### Vérifié Dans Le Code

- Le projet ne configure pas encore de CSP, `X-Frame-Options`, `X-Content-Type-Options`, `Referrer-Policy` ni `Permissions-Policy`.
- En test local, la réponse privée expose seulement `X-Robots-Tag: noindex` via Symfony, plus le `meta robots`.
- En prod, `framework.disallow_search_engine_index` est faux, donc la protection d'indexation repose surtout sur le layout privé, `robots.txt` et le sitemap.
- Le runtime local a `DEFAULT_URI=http://localhost`, ce qui reste compatible avec `localhost` pour les tests WebAuthn navigateur. Les ceremonies WebAuthn HTTP ne sont pas fiables sur `127.0.0.1` dans ce projet; il faut utiliser `localhost` ou HTTPS.

### Supposé

- Une protection `frame-ancestors 'none'` ou équivalent suffira à bloquer l'intégration en iframe sans casser Asset Mapper ni les imports du shell privé.

### Dépend De La Production / De L'Hébergement

- HTTPS réel, en-têtes de proxy, hôte canonique, et éventuelles redirections HTTP->HTTPS.
- La compatibilité finale de WebAuthn dépendra du contexte sécurisé en prod et du réglage des proxies.

### Non Vérifiable Localement

- Le comportement de WebAuthn dans un navigateur réel avec authenticator réel sur l'hébergement de production.

## Plan Retenu

### Architecture Choisie

- Choix: **Option A**.
- On conserve l'identité administrateur actuelle basée sur `private_admin` et le secret `PRIVATE_ADMIN_PASSWORD_HASH`.
- On ajoute uniquement une table dédiée aux passkeys associée à cet identifiant stable.
- L'Option B est écartée parce qu'elle introduit une entité administrateur inutile sans améliorer la sécurité ni la lisibilité du modèle.

### Dépendance WebAuthn

- Bibliothèque retenue: `web-auth/webauthn-framework`.
- Justification: package Symfony officiel du projet WebAuthn, compatible Symfony 6.4/7/8 et PHP >= 8.2, avec release stable récente 5.3.5.
- Les advisories Packagist existent sur des plages plus anciennes, mais pas sur la version stable visée.

### Fichiers Probablement Concernés

- `composer.json`
- `composer.lock`
- `config/packages/security.yaml`
- `config/packages/framework.yaml`
- `src/Private/Controller/SecurityController.php`
- nouveau contrôleur privé dédié aux passkeys
- nouveau service de cérémonie WebAuthn
- nouveau service de stockage/lecture des credentials
- nouveau service de garde de session privée
- `templates/private/login.html.twig`
- nouveau template `templates/private/security/passkeys/*.html.twig`
- `assets/scripts/private/private.js`
- nouveau script JS privé pour les cérémonies WebAuthn
- `assets/styles/private/private.css` et sous-fichiers associés
- nouveau modèle Doctrine pour les passkeys
- nouvelle migration Doctrine
- tests fonctionnels et unitaires privés

### Migrations Prévues

- Une migration pour créer la table dédiée aux credentials WebAuthn.
- Colonnes attendues: identifiant du credential, identifiant utilisateur stable, clé publique, compteur/signaux de sécurité, nom lisible, dates de création et de dernière utilisation, métadonnées minimales.

### Services À Créer

- Un service pour générer et valider les challenges de registration/login via la bibliothèque choisie.
- Un service de persistance des credentials WebAuthn.
- Un service de garde de session pour l'inactivité et l'expiration absolue.
- Un service de normalisation/étiquetage des passkeys pour l'UI.

### Contrôleurs À Modifier Ou Créer

- `SecurityController` pour afficher le login passkey en premier et conserver le secours par mot de passe.
- Un contrôleur privé dédié à la gestion des passkeys.
- Éventuellement un petit contrôleur JSON ou POST pour démarrer/terminer les cérémonies WebAuthn si la bibliothèque n'impose pas un autre schéma.

### Routes À Ajouter

- `/private/security/passkeys`
- `/private/security/passkeys/register`
- `/private/security/passkeys/login`
- `/private/security/passkeys/{id}/delete`

### Flux Utilisateur

- Login passkey d'abord sur `/private/login`.
- Login mot de passe en secours.
- Première passkey enregistrée seulement après login mot de passe réussi.
- Gestion des passkeys sur une page privée dédiée.
- Suppression d'une passkey uniquement depuis une session authentifiée.
- Interdiction de supprimer la dernière passkey sans garde explicite.
- Récupération manuelle documentée via le mot de passe de secours et la rotation des secrets si nécessaire.

### Tests Prévus

- Accès anonyme redirigé vers le login.
- Login mot de passe valide et invalide.
- CSRF absent et invalide.
- Logout et perte de session.
- Throttling et reprise après expiration.
- Non-divulgation d'identifiant.
- Accès/restreinte à la page de passkeys.
- Suppression CSRF d'une passkey.
- Refus des réponses WebAuthn invalides, challenge invalide, origine invalide et RP ID invalide.
- Refus de suppression de la dernière passkey.

### Stratégie De Rollback

- Conserver le mot de passe de secours comme voie de retour.
- Isoler les passkeys dans une table dédiée et une couche service dédiée.
- En cas de problème, désactiver les routes passkey et laisser le login mot de passe fonctionner pendant la correction.
- La migration passkey doit être réversible.

### Risques Ou Limites

- Les cérémonies WebAuthn complètes restent à valider sur un navigateur réel avec authenticator réel.
- La politique de session doit être testée après durcissement pour ne pas casser les usages légitimes.
- Le throttling doit être assez fort pour freiner les essais, mais pas assez agressif pour bloquer l'administrateur trop facilement.
- La détection HTTPS derrière proxy doit être correcte avant d'activer le flux passkey en production.
