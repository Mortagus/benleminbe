# Passkeys De La Zone Privee

Date de mise a jour : 2026-06-26

Ce document est la reference stable de l'implementation Passkey/WebAuthn de la zone privee `/private`.

Il decrit ce qui est en place aujourd'hui, les fichiers qui portent cette solution, les parametres de configuration relies, les choix de securite retenus et les incidents rencontres pendant le chantier.

## Resume

La zone privee conserve:

- le login Symfony classique comme secours;
- une passkey en premier sur la page de login;
- une table Doctrine dediee pour les credentials WebAuthn;
- une gestion manuelle des passkeys deja enregistrees;
- une journalisation diagnostique locale, desactivee en test et en production.

La solution est volontairement simple:

- un seul administrateur logique `private_admin`;
- pas de compte multi-utilisateur;
- pas de TOTP;
- pas de login social;
- pas de recuperation automatique par e-mail;
- pas de contournement de la verification d'origin ou de RP ID.

## Pieces Implantees

### Dependances et bundles

- `web-auth/webauthn-framework` `5.3.5`
- `Webauthn\Bundle\WebauthnBundle`
- `Webauthn\Stimulus\WebauthnStimulusBundle`

### Configuration projet

- `config/packages/webauthn.yaml`
- `config/packages/security.yaml`
- `config/packages/framework.yaml`
- `config/services.yaml`
- `config/bundles.php`

### Controleurs et services

- `src/Private/Security/Controller/PasskeyController.php`
- `src/Private/Controller/SecurityController.php`
- `src/Private/Security/Service/PrivateAdminLoginUserFactory.php`
- `src/Private/Security/Service/PasskeyCeremonyLogger.php`
- `src/Private/Security/Service/PrivateSessionGuard.php`
- `src/Private/Security/Repository/PrivateAdminWebauthnUserRepository.php`
- `src/Private/Security/Repository/PasskeyCredentialRepository.php`
- `src/Private/Security/EventSubscriber/PrivateSecuritySubscriber.php`
- `src/Private/Security/EventSubscriber/PrivateSecurityHeadersSubscriber.php`
- `src/Private/Security/Handler/WebauthnSuccessHandler.php`
- `src/Private/Security/Handler/WebauthnFailureHandler.php`

### Routes

- `GET /private/login`
- `POST /private/login`
- `GET /private/security/passkeys`
- `POST /private/security/passkeys/register/options`
- `POST /private/security/passkeys/register`
- `POST /private/security/passkeys/login/options`
- `POST /private/security/passkeys/login`
- `POST /private/security/passkeys/{id}/delete`

### Entites et persistance

- `src/Entity/Private/PasskeyCredential.php`
- `migrations/Version20260625183000.php`

### Interface et front-end

- `templates/private/login.html.twig`
- `templates/private/security/passkeys/index.html.twig`
- `assets/scripts/private/webauthn.js`
- `assets/scripts/private/private.js`

## Configuration Effective

### Parametres WebAuthn

Les parametres relies a WebAuthn sont definis dans `config/services.yaml`:

- `app.webauthn.rp_id`
- `app.webauthn.origin`
- `app.webauthn.debug_log_enabled`

Valeurs attendues:

- local: `app.webauthn.rp_id = localhost`
- local: `app.webauthn.origin = http://localhost:8000`
- production: `app.webauthn.rp_id = benlemin.be`
- production: `app.webauthn.origin = https://benlemin.be`

La configuration WebAuthn utilise:

- `allowed_origins: ['%app.webauthn.origin%']`
- `allow_subdomains: false`
- `creation_profiles.default.rp.id: '%app.webauthn.rp_id%'`
- `request_profiles.default.rp_id: '%app.webauthn.rp_id%'`
- `passkey_endpoints.enroll` et `passkey_endpoints.manage` sur l'URL publique du site

### Configuration Symfony

La zone privee repose sur:

- le provider memoire `private_admin`;
- `form_login` sur `/private/login`;
- `login_throttling`;
- `logout` gere par le firewall `main`;
- `access_control` qui autorise les routes Passkey publiques mais protege le reste de `/private`.

Le mot de passe de secours est fourni par `PRIVATE_ADMIN_PASSWORD_HASH`.
En local et en test, la configuration de developpement utilise un hash non sensible pour `private_admin`.

### Session et cookies

La session Symfony utilise:

- `storage_factory_id: session.storage.factory.native`
- `cookie_secure: auto`
- `cookie_httponly: true`
- `cookie_samesite: lax`
- `cookie_lifetime: 0`

Le flux WebAuthn depend donc d'une session conservee entre:

- la generation des options;
- le retour du navigateur;
- la validation finale cote backend.

## Flux Reels

### Connexion par passkey

La page `/private/login` affiche en premier le bouton `Se connecter avec une passkey`.

Flux effectif:

1. le navigateur poste vers `/private/security/passkeys/login/options`;
2. le serveur genere les options d'assertion et les stocke en session;
3. le JavaScript convertit `challenge` et `allowCredentials`;
4. le navigateur appelle `navigator.credentials.get()`;
5. le navigateur retourne une assertion au script;
6. le script poste l'assertion vers `/private/security/passkeys/login`;
7. le serveur valide la signature, le credential, le compteur et le user handle;
8. le serveur met a jour le credential et appelle `Security::login()`;
9. Symfony cree la session authentifiee pour `ROLE_PRIVATE_ADMIN`.

### Enregistrement d'une passkey

La page `/private/security/passkeys` est reservee a un administrateur deja connecte par mot de passe.

Flux effectif:

1. le navigateur poste vers `/private/security/passkeys/register/options` avec un token CSRF;
2. le serveur genere les options de creation;
3. le script convertit `challenge`, `user.id` et `excludeCredentials`;
4. le navigateur appelle `navigator.credentials.create()`;
5. le navigateur retourne une credential au script;
6. le script poste la credential finale vers `/private/security/passkeys/register` avec le token CSRF;
7. le serveur retrouve les options en session;
8. le serveur valide l'attestation;
9. le credential est persiste en base;
10. la page est rechargée.

## Regles De Donnees

### Credential WebAuthn

La table `private_passkey_credentials` conserve uniquement les donnees necessaires:

- identifiant de credential encode base64url;
- user handle stable;
- nom lisible;
- type;
- transports;
- compteur;
- informations d'attestation minimales;
- dates de creation et de derniere utilisation.

Les secrets WebAuthn bruts, les cookies de session et les challenges complets ne sont pas stockes en clair dans les logs.

### User WebAuthn

Le repository `PrivateAdminWebauthnUserRepository` renvoie toujours le meme utilisateur logique:

- username: `private_admin`
- user handle: `benlemin-private-admin`
- display name: `Administrateur prive`

Le login programmatique utilise le meme identifiant et le meme hash que le provider memoire Symfony pour eviter une deauthentification au prochain passage du `ContextListener`.

## Incidents Rencontres

### 1. Hostname local incompatible avec le RP ID

Symptome:

- sur `http://127.0.0.1:8000`, le navigateur renvoyait `This is an invalid domain.` apres les options WebAuthn;
- aucun POST final n'etait emis vers `/register` ou `/login`.

Cause:

- le RP ID configure est `localhost`;
- `127.0.0.1` ne correspond pas a cette configuration.

Resolution:

- utiliser `http://localhost:8000` pour les tests locaux;
- afficher cote front un message explicite quand le hostname ne correspond pas au RP ID.

### 2. Token CSRF manquant dans le POST d'enregistrement

Symptome:

- le serveur renvoyait `register_result.csrf_invalid` au retour d'une ceremonie d'enregistrement valide.

Cause:

- le token etait envoye au POST d'options, mais pas au POST final d'enregistrement.

Resolution:

- ajouter `csrfToken` au payload final dans `assets/scripts/private/webauthn.js`.

### 3. Deauthentification immedate apres login Passkey

Symptome:

- le navigateur revenait bien sur le site apres l'authenticator reel;
- puis la page de login reapparaissait comme si la connexion avait echoue.

Cause:

- `Security::login()` utilisait un `InMemoryUser` avec mot de passe `null`;
- le provider memoire Symfony recharge le meme utilisateur avec `PRIVATE_ADMIN_PASSWORD_HASH`;
- Symfony considerait le user different au prochain passage du `ContextListener`.

Resolution:

- introduire `PrivateAdminLoginUserFactory`;
- utiliser cette factory pour la connexion Passkey;
- conserver le hash du provider memoire dans l'utilisateur programmatique.

## Journalisation De Diagnostic

Le fichier `var/log/private-webauthn.log` sert de trace locale pour les cerimonies WebAuthn.

Il contient des entrees JSON lines pour:

- les options de connexion;
- les options d'enregistrement;
- les resultats de connexion;
- les resultats d'enregistrement;
- les echecs de validation;
- le succes final de `Security::login()`.

Les donnees sensibles restent tronquees ou hachees:

- challenge;
- credential ID;
- user handle;
- cookies;
- secrets bruts.

Cette journalisation est desactivee en `test` et en `prod`.

## Tests Et Verifications

Tests disponibles:

- `tests/Functional/Private/PrivateSecurityWebTest.php`
- `tests/Unit/Private/Security/PrivateSessionGuardTest.php`
- `tests/Unit/Private/Security/PrivateAdminLoginUserFactoryTest.php`
- `tests/js/private/webauthn.test.js`

Commandes utiles:

```bash
make check
make cc
php bin/phpunit tests/Unit/Private/Security/PrivateAdminLoginUserFactoryTest.php
npm run test:js -- tests/js/private/webauthn.test.js
```

## Recuperation Manuelle

Si toutes les passkeys sont perdues:

1. ouvrir `/private/login`;
2. se connecter avec le mot de passe de secours;
3. aller sur `/private/security/passkeys`;
4. enregistrer au moins deux nouvelles passkeys;
5. verifier la connexion par passkey avant de quitter la session.

Si le mot de passe de secours doit etre change:

1. generer un nouveau hash avec `make private-admin-secret`;
2. deplacer le hash dans la configuration de deploiement ou les secrets Symfony;
3. redeployer;
4. conserver l'ancien mot de passe dans le gestionnaire de mots de passe le temps de la transition, puis le supprimer.

## Notes De Deploiement

- la production doit etre testee sur l'URL canonique `https://benlemin.be`;
- `www.benlemin.be` ou un autre alias doit etre verifie explicitement si le deploiement le sert;
- il ne faut pas reconfigurer la production pour accepter `localhost` ou `127.0.0.1`;
- les passkeys creees pour `localhost` ne servent pas en production, et inversement.

## Reference Historique

Le rapport detaille de l'investigation locale est conserve dans l'historique:

- [Investigation locale Passkey / WebAuthn](../termines/audits/passkey-local-investigation.md)
