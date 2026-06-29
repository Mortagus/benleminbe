# Audit de securite du login et de la zone privee

Date: 2026-06-29

Perimetre: `GET/POST /private/login`, login Symfony classique, flux Passkey/WebAuthn, logout, sessions, headers de securite, et actions sensibles de la zone privee.

Ce document consigne la methode de l'audit, les points forts observes, les faiblesses identifiees et les recommandations de correction ou de durcissement.

## Methode

La revue a ete menee en quatre etapes:

1. Relecture du contexte projet et des documents de reference list dans `AGENTS.md`.
2. Inspection du code et des templates relies a la zone privee.
3. Verification de la configuration Symfony effective via `debug:config`.
4. Execution de tests cibles quand c'etait possible.

Fichiers et points inspectes en priorite:

- `config/packages/security.yaml:15-39`
- `src/Private/Controller/SecurityController.php:12-31`
- `templates/private/login.html.twig:13-80`
- `src/Private/Security/EventSubscriber/PrivateSecuritySubscriber.php:22-67`
- `src/Private/Security/EventSubscriber/PrivateSecurityHeadersSubscriber.php:12-35`
- `src/Private/Security/Service/PrivateSessionGuard.php:10-53`
- `assets/scripts/private/webauthn.js:182-307`
- `templates/private/base.html.twig:1-35`
- `tests/Functional/Private/PrivateSecurityWebTest.php:14-156`
- `tests/Unit/Private/Security/PrivateSessionGuardTest.php:13-44`

## Synthese

Je n'ai pas identifie de faille de contournement d'authentification, ni de rupture evidente du cloisonnement `/private`.

Les protections essentielles sont en place:

- CSRF actif sur le login Symfony classique;
- throttling de login deja configure;
- access control qui protege tout `/private` sauf les routes de login WebAuthn;
- expiration de session privee cote application;
- cookies de session `HttpOnly` et `SameSite=lax`;
- invalidation de session a la deconnexion;
- messages d'erreur login generiques;
- majorite des actions mutantes protegees par CSRF dans les modules privees;
- en-tetes anti-cache et anti-clickjacking sur la zone privee.

Les principaux points faibles sont des points de durcissement, pas des bypass immediats:

1. deconnexion en `GET`, donc CSRFable;
2. politique CSP trop faible dans la zone privee;
3. endpoint public de preparation du login Passkey sans limitation specifique;
4. absence de test automatise de bout en bout pour le chemin Passkey reussi.

## Constat Detaille

### 1. Firewall et acces

Le firewall `main` et les regles d'acces couvrent correctement la zone privee.

Evidence:

- le provider unique `private_admin` est defini dans `config/packages/security.yaml:7-13`;
- `form_login` pointe vers `/private/login` et utilise `enable_csrf: true` dans `config/packages/security.yaml:23-27`;
- `access_control` autorise explicitement `/private/login` et `/private/security/passkeys/login(?:/options)?` puis bloque le reste de `/private` dans `config/packages/security.yaml:36-39`;
- le login prive renvoie un utilisateur deja authentifie vers le dashboard dans `src/Private/Controller/SecurityController.php:15-25`.

Lecture securite:

- pas de trou de route visible sur le prefixe `/private`;
- pas d'erreur de classification entre zone publique et zone privee.

### 2. Login classique

Le formulaire de login est correctement durci pour un compte unique:

- jeton CSRF dans `templates/private/login.html.twig:53-80`;
- champ utilisateur et mot de passe avec autocomplete approprie;
- message d'erreur unique `Identifiants invalides.` dans `templates/private/login.html.twig:49-50`;
- throttling configure a `5` tentatives sur `15 minutes` dans `config/packages/security.yaml:28-30`.

Verification effective:

- `php bin/phpunit tests/Unit/Private/Security/PrivateSessionGuardTest.php` a reussi;
- `php bin/console debug:config security` confirme `session_fixation_strategy: migrate`, `enable_csrf: true` et `login_throttling` actif.

Lecture securite:

- pas de fuite d'information evidente via les messages d'erreur;
- pas de session fixation apparente;
- bonne base pour un login de secours.

### 3. Expiration de session privee

La couche applicative qui coupe la session privee est coherente:

- marqueur d'authentification et derniere activite dans `src/Private/Security/Service/PrivateSessionGuard.php:12-53`;
- expiration idle a 1800 secondes et limite absolue a 43200 secondes dans `src/Private/Security/Service/PrivateSessionGuard.php:14-15`;
- invalidation sur requete privee expiree dans `src/Private/Security/EventSubscriber/PrivateSecuritySubscriber.php:33-67`.

Lecture securite:

- le site ne depend pas uniquement de la duree de vie du cookie PHP;
- la logique d'expiration est lisible et testee.

### 4. Headers de securite

Les en-tetes techniques sont poses sur toute requete dont le chemin commence par `/private` dans `src/Private/Security/EventSubscriber/PrivateSecurityHeadersSubscriber.php:19-35`.

Points positifs:

- `X-Robots-Tag: noindex, nofollow`;
- `X-Content-Type-Options: nosniff`;
- `Referrer-Policy: strict-origin-when-cross-origin`;
- `Permissions-Policy` limite a WebAuthn;
- `X-Frame-Options: DENY`;
- `Cache-Control` en `no-store`.

Point faible:

- la CSP ne contient que `frame-ancestors 'none'`.

Lecture securite:

- le clickjacking est bien traite;
- la page privee n'est pas cachee;
- en revanche la politique navigateur ne fournit presque aucune containment contre un futur XSS dans la zone privee.

### 5. Flux Passkey / WebAuthn

Le flux WebAuthn est bien structure dans `assets/scripts/private/webauthn.js:182-307`.

Points positifs:

- le navigateur verifie le contexte securise avant d'activer le bouton;
- le RP ID est verifie cote client avant appel a `navigator.credentials.get/create()`;
- les credentials sont serialises proprement;
- la creation de passkey et le login passent par des endpoints JSON distincts;
- l'enregistrement de passkey reste protege par CSRF.

Point faible:

- le login Passkey commence sur un endpoint public `POST /private/security/passkeys/login/options` qui n'est pas limite par un throttling specifique.

Lecture securite:

- ce n'est pas un bypass d'authentification;
- c'est surtout une faiblesse de robustesse et de bruit operatoire;
- une limitation legere du debit serait utile pour eviter l'abus de generation de challenges et de sessions anonymes.

### 6. Logout

La deconnexion est exposee via `GET /private/logout` dans `src/Private/Controller/SecurityController.php:28-31`.

Lecture securite:

- un `GET` de logout peut etre declenche depuis un autre site;
- l'impact est limite a une deconnexion forcee;
- cela reste une surface CSRFable inutile dans une zone d'administration.

## Findings

| Gravite | Constat | Evidence | Recommandation |
| --- | --- | --- | --- |
| Moyenne | La zone privee ne dispose pas d'une vraie CSP, seulement de `frame-ancestors 'none'`. | `src/Private/Security/EventSubscriber/PrivateSecurityHeadersSubscriber.php:25-30` | Definir une CSP dediee a la zone privee, au minimum `default-src 'self'`, `object-src 'none'`, `base-uri 'self'` et `form-action 'self'`, puis valider la compatibilite avec Asset Mapper / importmap. |
| Faible | La deconnexion est en `GET`, donc CSRFable. | `src/Private/Controller/SecurityController.php:28-31` | Passer la deconnexion en `POST` avec jeton CSRF, ou utiliser le mecanisme CSRF de logout Symfony si un endpoint POST n'est pas desirable. |
| Faible | Le debut du flux Passkey est public et non throttle specifiquement. | `config/packages/security.yaml:37-38`, `assets/scripts/private/webauthn.js:200-223` | Ajouter une limitation de debit legere sur `POST /private/security/passkeys/login/options` et surveiller les volumes anormaux. |

## Observations De Couverture

Le chemin reussi du login Passkey n'a pas de test fonctionnel direct dans la suite inspectee.

Ce que la suite couvre deja:

- login classique invalide et throttling;
- endpoint Passkey login options public;
- rejet d'un payload Passkey malforme;
- acces a la page Passkeys une fois authentifie;
- suppression CSRF d'une passkey;
- invalidation de session au logout.

Ce qui manque:

- un test d'integration qui valide le succes complet du flux `POST /private/security/passkeys/login/options` -> `navigator.credentials.get()` -> `POST /private/security/passkeys/login`;
- un test de non regression pour la reponse du controleur WebAuthn apres authentification reussie.

Recommandation:

- ajouter au moins un test de chemin heureux cote backend pour eviter qu'une future modification du login Passkey casse silencieusement la connexion privee.

## Verification Realisee

Commandes lancees pendant l'audit:

- `php bin/phpunit tests/Unit/Private/Security/PrivateSessionGuardTest.php`
- `php bin/phpunit tests/Functional/Private/PrivateSecurityWebTest.php`
- `php bin/console debug:config security`
- `php bin/console debug:config framework`

Resultats:

- le test unitaire `PrivateSessionGuardTest` passe;
- la suite fonctionnelle privee n'a pas pu aller au bout dans cet environnement, la base de donnees repondant `SQLSTATE[HY000] [2002] Unknown error while connecting`;
- `debug:config` confirme les points de configuration utiles pour l'audit: CSRF, throttling, session fixation `migrate`, cookies de session `HttpOnly`, `SameSite=lax` et cache prive en `no-store`.

## Conclusion

La zone privee est deja correctement cloisonnee sur les bases essentielles.

Le risque principal n'est pas un contournement immediat du login, mais le manque de durcissement autour du runtime prive:

- logout encore en `GET`;
- CSP trop faible;
- endpoint public du flux Passkey sans limitation specifique;
- couverture de test encore incomplete sur le chemin WebAuthn reussi.

Priorite recommandee:

1. durcir la politique de securite navigateur de la zone privee;
2. rendre la deconnexion non CSRFable;
3. ajouter un garde-fou sur le debit du `login/options` Passkey;
4. ajouter un test d'integration du chemin heureux Passkey.
