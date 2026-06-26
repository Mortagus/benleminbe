# Investigation Locale Passkey / WebAuthn

Date: 2026-06-26

## Resume Executif

Etat observe:

- Le login Symfony classique fonctionne localement sur `http://localhost:8000/private/login` avec le compte de developpement.
- Les endpoints d'options WebAuthn repondent `200 OK` pour la connexion et l'enregistrement.
- Sur `http://127.0.0.1:8000`, les flux connexion et enregistrement Passkey echouent dans le navigateur avec `This is an invalid domain.` apres l'appel `/options`, avant toute requete de resultat vers le backend.
- Sur `http://localhost:8000`, l'erreur `invalid domain` ne se produit pas dans Chrome headless. Le flux s'arrete ensuite a l'etape navigateur, ce qui est coherent avec l'absence d'authenticator interactif utilisable dans l'environnement headless.
- Lors du test manuel avec authenticator reel, un second echec a ensuite ete observe sur le retour d'enregistrement: `register_result.csrf_invalid`.
- Lors du test manuel de connexion avec authenticator reel, le backend valide l'assertion WebAuthn et renvoie bien une redirection, mais la session ne reste pas authentifiee sur la requete suivante.

Cause principale:

- Cause confirmee avec confiance elevee: mismatch entre le hostname reel `127.0.0.1` et le relying party ID configure `localhost`.
- Le navigateur recoit `rpId: "localhost"` pour la connexion et `rp.id: "localhost"` pour l'enregistrement. Une page ouverte sur `127.0.0.1` ne peut pas utiliser une credential WebAuthn scopee a `localhost`.

Cause secondaire confirmee lors du test authenticator reel:

- Confiance elevee: le `POST /private/security/passkeys/register` ne transportait pas le `csrfToken` attendu par le controleur.
- Les logs montrent `register_options.*` puis `register_result.start` puis `register_result.csrf_invalid`, ce qui indique un rejet avant toute validation WebAuthn.
- Le probleme etait dans le flux client `assets/scripts/private/webauthn.js`, qui envoyait bien le token sur `register/options` mais pas sur le `POST` final `register`.

Cause secondaire confirmee sur la connexion Passkey:

- Confiance elevee: `PasskeyController::login()` appelait `Security::login()` avec `new InMemoryUser('private_admin', null, ['ROLE_PRIVATE_ADMIN'])`.
- Le provider memoire configure dans `security.yaml` recharge le meme utilisateur avec `password: '%env(PRIVATE_ADMIN_PASSWORD_HASH)%'`.
- Symfony compare l'utilisateur du token et l'utilisateur recharge par le provider. Comme `InMemoryUser::isEqualTo()` compare aussi le mot de passe, le `null` du token ne correspond pas au hash du provider et la session est deauthentifiee au tour suivant.
- Effet visible cote navigateur: le retour depuis l'authenticator reel semble fonctionner, puis la redirection aboutit a nouveau sur la page de login.

Causes secondaires:

- Confiance moyenne: l'environnement Playwright utilise `HeadlessChrome/148.0.0.0` sur Linux, sans authenticator reel verifie. Il ne permet pas de conclure qu'une creation ou authentification Passkey reelle fonctionne jusqu'au bout sur `localhost`.
- Confiance moyenne: la CLI Symfony n'est pas le serveur local actif (`symfony server:status` indique `Not Running`), mais un serveur repond quand meme sur le port 8000. Cela n'explique pas l'erreur `invalid domain`, mais doit etre clarifie pour les tests manuels.
- Confiance faible a moyenne: la commande CLI Doctrine echoue depuis l'environnement agent avec `SQLSTATE[HY000] [2002] Unknown error while connecting`, alors que l'application web a affiche la page de gestion Passkeys. Cette divergence semble liee a l'environnement d'execution CLI, pas au flux WebAuthn observe.

## Configuration Reellement Detectee

Bundle et versions:

- Package Composer direct: `web-auth/webauthn-framework` `5.3.5`.
- Le package remplace aussi `web-auth/webauthn-lib`, `web-auth/webauthn-stimulus` et `web-auth/webauthn-symfony-bundle` en `self.version`.
- Bundles declares: `Webauthn\Bundle\WebauthnBundle` et `Webauthn\Stimulus\WebauthnStimulusBundle`.
- Symfony detecte: `symfony/framework-bundle`, `symfony/security-bundle`, `symfony/security-http`, `symfony/security-csrf`, `symfony/http-foundation`, `symfony/http-kernel`, `symfony/routing` en `8.1.0`.
- Doctrine detecte: `doctrine/doctrine-bundle` `3.2.4`, `doctrine/orm` `3.6.7`.

URLs locales testees:

- `http://127.0.0.1:8000/private/login`
- `http://localhost:8000/private/login`
- `http://127.0.0.1:8000/private/security/passkeys`
- `http://localhost:8000/private/security/passkeys`

Navigateur / environnement de test:

- Playwright avec `HeadlessChrome/148.0.0.0`.
- OS rapporte par le navigateur: `Linux x86_64`.
- `window.isSecureContext` vaut `true` sur `http://127.0.0.1:8000` et `http://localhost:8000` dans ce navigateur.
- `window.PublicKeyCredential`, `navigator.credentials.create` et `navigator.credentials.get` existent.
- Aucun authenticator compatible interactif n'a ete confirme dans l'environnement headless.

Parametres WebAuthn:

- `config/services.yaml` definit `app.webauthn.rp_id: 'localhost'` et `app.webauthn.origin: 'http://localhost:8000'`.
- `when@prod` definit `app.webauthn.rp_id: 'benlemin.be'` et `app.webauthn.origin: 'https://benlemin.be'`.
- `config/packages/webauthn.yaml` utilise `allowed_origins: ['%app.webauthn.origin%']`, `allow_subdomains: false`, `creation_profiles.default.rp.id: '%app.webauthn.rp_id%'` et `request_profiles.default.rp_id: '%app.webauthn.rp_id%'`.
- `debug:config webauthn` confirme en dev `allowed_origins: ['http://localhost:8000']`, `rp.id: localhost`, `rp.name: ''` et `request_profiles.default.rp_id: localhost`.
- Les URLs de discovery Passkey sont `http://localhost:8000/private/security/passkeys` en local et `https://benlemin.be/private/security/passkeys` en prod via les parametres.

Relying party name:

- Non configure explicitement dans `config/packages/webauthn.yaml`.
- La configuration effective montre `rp.name: ''`.
- Le package installe compense un nom vide en utilisant l'id comme nom dans `PublicKeyCredentialCreationOptionsFactory::createRpEntity()`, donc les options observees contiennent `rp.name: "localhost"`.

## Cartographie De L'Implementation

Configuration:

- `composer.json`: dependance `web-auth/webauthn-framework`.
- `composer.lock`: version exacte `5.3.5`.
- `config/bundles.php`: activation des bundles WebAuthn.
- `config/packages/webauthn.yaml`: profils WebAuthn, repositories, origins, endpoints passkey.
- `config/packages/security.yaml`: firewall `main`, `form_login`, `login_throttling`, `logout`, `access_control`.
- `config/packages/framework.yaml`: session native, `cookie_secure: auto`, `cookie_httponly: true`, `cookie_samesite: lax`, `gc_maxlifetime: 43200`.
- `config/services.yaml`: parametres `app.webauthn.rp_id` et `app.webauthn.origin`.

Routes Passkey:

- `GET /private/security/passkeys`: gestion des passkeys.
- `POST /private/security/passkeys/register/options`: generation options creation.
- `POST /private/security/passkeys/register`: validation attestation et sauvegarde credential.
- `POST /private/security/passkeys/login/options`: generation options assertion.
- `POST /private/security/passkeys/login`: validation assertion et login Symfony programmatique.
- `POST /private/security/passkeys/{id}/delete`: suppression protegee par CSRF.

Controleurs et services:

- `src/Private/Controller/SecurityController.php`: login classique et logout firewall.
- `src/Private/Security/Controller/PasskeyController.php`: flux WebAuthn manuel, options storage, validation, login programmatique, suppression.
- `src/Private/Security/Repository/PrivateAdminWebauthnUserRepository.php`: utilisateur WebAuthn unique `private_admin`, user handle stable `benlemin-private-admin`.
- `src/Private/Security/Repository/PasskeyCredentialRepository.php`: conversion Doctrine <-> `CredentialRecord`, sauvegarde, recherche, suppression.
- `src/Private/Security/Service/PrivateSessionGuard.php`: expiration idle 30 minutes et absolue 12 heures.
- `src/Private/Security/EventSubscriber/PrivateSecuritySubscriber.php`: marque la session apres login, expire les requetes privees.
- `src/Private/Security/EventSubscriber/PrivateSecurityHeadersSubscriber.php`: headers prives, dont `Permissions-Policy: publickey-credentials-get=(self), publickey-credentials-create=(self)`.
- `src/Private/Security/Handler/WebauthnSuccessHandler.php` et `WebauthnFailureHandler.php`: presents, mais le flux actuel du controleur fait lui-meme la reponse login.

Entite et persistance:

- `src/Entity/Private/PasskeyCredential.php`: table `private_passkey_credentials`, credential ID encode base64url, user handle, type, transports, attestation, trust path, AAGUID, public key, counter, backup flags, dates.
- `migrations/Version20260625183000.php`: creation de la table et index unique sur `public_key_credential_id`.
- Les challenges/options sont stockes en session par `Webauthn\Bundle\Security\Storage\SessionStorage`.
- Les credentials WebAuthn sont persistées en base via Doctrine.

Templates et JavaScript:

- `templates/private/login.html.twig`: bouton Passkey en premier et formulaire mot de passe de secours avec `csrf_token('authenticate')`.
- `templates/private/security/passkeys/index.html.twig`: page d'ajout, liste et suppression des passkeys avec CSRF.
- `assets/scripts/private/private.js`: charge `setupPrivateWebauthn`.
- `assets/scripts/private/webauthn.js`: detection WebAuthn, conversion base64url <-> `ArrayBuffer`, fetch JSON, `navigator.credentials.get()`, `navigator.credentials.create()`, serialisation `toJSON()` ou fallback manuel.

Securite Symfony:

- Provider memoire `private_admin` avec hash lu depuis `PRIVATE_ADMIN_PASSWORD_HASH`.
- Firewall `main` lazy, provider `private_admin`.
- `form_login` sur `app_private_login`, target `app_private_dashboard`, CSRF active.
- `login_throttling`: `max_attempts: 5`, `interval: '15 minutes'`; en test `interval: '1 second'`.
- `logout`: `app_private_logout` vers `app_private_login`.
- `access_control`: `/private/login` public, `/private/security/passkeys/login(?:/options)?` public, puis tout `/private` exige `ROLE_PRIVATE_ADMIN`.
- Aucun voter WebAuthn specifique detecte.

Tests existants:

- `tests/Functional/Private/PrivateSecurityWebTest.php`: acces anonyme, presence action Passkey, options login publiques, payload login malforme rejete, message login generique, throttling, page passkeys authentifiee, suppression CSRF, protection de la derniere passkey, logout.
- `tests/Unit/Private/Security/PrivateSessionGuardTest.php`: expiration idle et absolue.
- Pas de test JS existant pour `assets/scripts/private/webauthn.js`.
- Pas de test automatise de ceremonie WebAuthn complete avec authenticator virtuel.

Incoherences ou points non finalises visibles:

- Le rapport d'audit initial `docs/en-cours/private-authentication-security-audit.md` dit encore "Il n'y a pas encore de support passkey/WebAuthn" dans ses conclusions rapides, puis documente ensuite un plan retenu. La reference stable `docs/private/private-passkeys.md` est plus a jour.
- `docs/private/private-passkeys.md` indique deja que le local doit utiliser `http://localhost:8000`, pas `127.0.0.1`.
- La configuration de la bundle expose `rp.name` deprecie avec valeur vide par defaut. Le package compense en runtime, mais la configuration projet ne documente pas explicitement un nom de RP.
- Le code JS affiche actuellement le message navigateur brut `This is an invalid domain.`, peu actionnable.

## Parcours D'Execution

### Enregistrement

Parcours attendu:

1. L'utilisateur se connecte par mot de passe sur `/private/login`.
2. Il ouvre `/private/security/passkeys`.
3. Le bouton "Ajouter une passkey" poste vers `/private/security/passkeys/register/options` avec CSRF et label.
4. Le backend genere des options via `PublicKeyCredentialCreationOptionsFactory`, les stocke en session et renvoie le JSON.
5. Le JS convertit `challenge`, `user.id`, `excludeCredentials[].id` en `ArrayBuffer`.
6. Le JS appelle `navigator.credentials.create({ publicKey })`.
7. Si le navigateur retourne une credential, le JS poste le resultat vers `/private/security/passkeys/register`.
8. Le backend recupere les options en session, valide l'attestation, sauvegarde le credential et recharge la page.

Observation:

- Sur `127.0.0.1`, l'echec se produit a l'etape 6 avec `This is an invalid domain.`. Aucune requete `/register` n'est envoyee.
- Sur `localhost`, `/register/options` repond `200 OK` avec `rp.id: "localhost"`. En headless, aucun retour `/register` n'a ete observe.

### Connexion

Parcours attendu:

1. L'utilisateur ouvre `/private/login`.
2. Le bouton Passkey poste vers `/private/security/passkeys/login/options`.
3. Le backend genere des options via `PublicKeyCredentialRequestOptionsFactory`, les stocke en session et renvoie le JSON.
4. Le JS convertit `challenge` et `allowCredentials[].id` en `ArrayBuffer`.
5. Le JS appelle `navigator.credentials.get({ publicKey })`.
6. Si le navigateur retourne une assertion, le JS poste le resultat vers `/private/security/passkeys/login`.
7. Le backend recupere les options en session, retrouve le credential, valide l'assertion, met a jour le compteur et connecte `private_admin` via `Security::login()`.

Observation:

- Sur `127.0.0.1`, l'echec se produit a l'etape 5 avec `This is an invalid domain.`. Aucune requete `/login` resultat n'est envoyee.
- Sur `localhost`, `/login/options` repond `200 OK` avec `rpId: "localhost"` et `allowCredentials: []`. En headless, aucun retour `/login` resultat n'a ete observe.

## Elements Observables

Navigateur:

- `http://127.0.0.1:8000/private/login`: `isSecureContext: true`, WebAuthn disponible, erreur UI `This is an invalid domain.` apres clic Passkey.
- `http://localhost:8000/private/login`: `isSecureContext: true`, WebAuthn disponible, pas d'erreur `invalid domain`.
- User-Agent observe: `Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/148.0.0.0 Safari/537.36`.

Requetes reseau:

- `POST http://127.0.0.1:8000/private/security/passkeys/login/options` => `200 OK`.
- `POST http://127.0.0.1:8000/private/security/passkeys/register/options` => `200 OK`.
- `POST http://localhost:8000/private/security/passkeys/login/options` => `200 OK`.
- `POST http://localhost:8000/private/security/passkeys/register/options` => `200 OK`.
- Aucune requete `/private/security/passkeys/login` ou `/register` apres l'erreur `invalid domain` sur `127.0.0.1`.

Corps JSON observes:

- Connexion localhost: `rpId: "localhost"`, `allowCredentials: []`, `userVerification: "required"`.
- Enregistrement localhost: `rp.id: "localhost"`, `rp.name: "localhost"`, user handle encode, `residentKey: "required"`, `userVerification: "required"`, `attestation: "none"`.

Sessions et cookies:

- Les reponses privees locales posent `PHPSESSID` avec `HttpOnly` et `SameSite=lax`.
- `cookie_secure: auto` produit un cookie non `Secure` sur HTTP local, coherent avec Symfony.
- Le login mot de passe sur `localhost` redirige vers `/private` et permet d'ouvrir `/private/security/passkeys`.

Logs / exceptions:

- Aucun `var/log/dev.log` present pendant l'investigation.
- Aucun message console navigateur capture.
- La commande CLI `php bin/console doctrine:migrations:status --no-interaction` echoue avec `SQLSTATE[HY000] [2002] Unknown error while connecting`; cela n'a pas ete observe comme erreur navigateur dans le flux Passkey teste.

## Resultats De La Recherche Externe

Documentation officielle WebAuthn Framework:

- Source: documentation officielle WebAuthn Framework, bundle Symfony, `https://webauthn-doc.spomky-labs.com/symfony-bundle/firewall`.
- Confirme: la bundle Symfony s'integre via configuration firewall et handlers, et la documentation insiste sur un usage HTTPS. Elle presente le stockage des options pendant la ceremonie et les handlers de succes/echec.
- Application au projet: le projet utilise bien la bundle installee, mais implemente un flux manuel avec factories, validators et `OptionsStorage` plutot qu'un authenticator firewall WebAuthn complet. Les services utilises viennent de la bundle. La recommandation HTTPS est pertinente pour production, mais ne suffit pas a expliquer l'erreur locale car Chrome considere `localhost` et `127.0.0.1` comme contextes securises dans ce test.

Reference de configuration installee de la bundle:

- Source: `php bin/console config:dump-reference webauthn` sur `web-auth/webauthn-framework` `5.3.5`.
- Confirme: `allowed_origins` et `allow_subdomains` sont les options actuelles; `secured_rp_ids` est deprecie; `options_storage` par defaut est `Webauthn\Bundle\Security\Storage\SessionStorage`; `passkey_endpoints` attend une URL HTTPS absolue ou une route Symfony.
- Application au projet: les cles utilisees sont compatibles avec la version installee. La config `allowed_origins: http://localhost:8000` est acceptee en local, et `rp_id: localhost` est effectivement envoye au navigateur.

Specification W3C WebAuthn Level 3:

- Source: `https://w3c.github.io/webauthn/`.
- Confirme: le RP ID doit etre egal au domaine effectif de l'origine ou a un suffixe de domaine enregistrable; `http://localhost:8000` est explicitement donne comme exemple valide grace au host `localhost`; la validation asynchrone du RP ID rejette sinon avec une `SecurityError` `DOMException`.
- Application au projet: `rpId=localhost` est valide pour une origine `http://localhost:8000`, mais pas pour une origine `http://127.0.0.1:8000`. L'erreur navigateur observee sur `127.0.0.1` correspond a cette regle.

MDN Secure Contexts:

- Source: `https://developer.mozilla.org/en-US/docs/Web/Security/Defenses/Secure_Contexts`.
- Confirme: les ressources locales comme `http://127.0.0.1`, `http://localhost` et `http://*.localhost` peuvent etre considerees comme livrees de maniere sure pour les tests locaux; `window.isSecureContext` est le mecanisme de detection.
- Application au projet: l'hypothese "HTTP local est forcement non securise" est rejetee pour le navigateur teste. Le probleme observe n'est pas le secure context mais le RP ID.

MDN Web Authentication API:

- Source: `https://developer.mozilla.org/en-US/docs/Web/API/Web_Authentication_API`.
- Confirme: WebAuthn est disponible uniquement dans des contextes securises; `navigator.credentials.create()` cree une credential avec les informations RP/user/challenge; `navigator.credentials.get()` authentifie avec une assertion.
- Application au projet: les options serveur sont bien transmises au navigateur avant l'echec. Le point de rupture observe est conforme a une erreur de validation cote client avant retour backend.

MDN `isUserVerifyingPlatformAuthenticatorAvailable()`:

- Source: `https://developer.mozilla.org/en-US/docs/Web/API/PublicKeyCredential/isUserVerifyingPlatformAuthenticatorAvailable_static`.
- Confirme: la presence d'un authenticator de plateforme avec verification utilisateur peut etre testee; des exemples incluent Touch ID, Face ID, Windows Hello et le deverrouillage Android; la methode peut rejeter avec `SecurityError` si le domaine RP est invalide.
- Application au projet: l'environnement Playwright headless ne prouve pas la disponibilite d'un authenticator reel. Un test manuel sur navigateur de bureau avec Windows Hello, gestionnaire de passkeys ou cle FIDO2 reste requis.

Documentation Symfony 8.1:

- Source: `https://symfony.com/doc/current/security.html` et `https://symfony.com/doc/current/reference/configuration/framework.html`.
- Confirme: `form_login.enable_csrf: true` doit etre couple a un champ `_csrf_token` avec `csrf_token('authenticate')`; `login_throttling` est supporte; `cookie_secure: auto` rend les cookies Secure seulement sur HTTPS; `access_control` applique la premiere regle correspondante.
- Application au projet: le login classique et les regles d'acces sont coherents avec Symfony. Le fallback mot de passe fonctionne et n'est pas la cause du dysfonctionnement Passkey observe.

Issues GitHub:

- Source: recherches ciblees sur `github.com/web-auth/webauthn-framework` avec `This is an invalid domain`, `rpId localhost 127.0.0.1 invalid domain`.
- Resultat: aucun rapport directement pertinent n'a ete identifie pendant cette investigation.
- Application au projet: le probleme correspond aux regles WebAuthn standard plutot qu'a un bug connu de la bundle.

## Diagnostic

Cause confirmee:

- Le dysfonctionnement local reproduit sur `127.0.0.1` est cause par l'incompatibilite entre l'origin reelle `http://127.0.0.1:8000` et le RP ID configure `localhost`.
- Niveau de confiance: eleve.
- Preuves: options serveur `rpId/rp.id = localhost`, erreur navigateur `This is an invalid domain.`, absence d'appel backend resultat, absence de cette erreur sur `localhost`, regles W3C.

Cause confirmee sur le login Passkey apres retour navigateur:

- `PasskeyController::login()` connectait bien l'utilisateur via `Security::login()`, mais l'utilisateur injecte ne portait pas le meme mot de passe que le provider memoire.
- Niveau de confiance: eleve.
- Preuves: code du controller, `security.yaml`, comportement de `Symfony\Component\Security\Http\Firewall\ContextListener::hasUserChanged()`, et test unitaire reproduisant l'inegalite entre un `InMemoryUser` avec `null` et le user recharge du provider.

Hypotheses rejetees:

- "Le login Symfony classique est casse": rejete. Le login mot de passe fonctionne sur `localhost`.
- "La protection CSRF du formulaire login casse le flux": rejete pour le login classique; les endpoints options Passkey repondent avant toute validation finale.
- "HTTP local est automatiquement non securise": rejete dans le navigateur teste, qui rapporte `isSecureContext: true` pour `localhost` et `127.0.0.1`.
- "La bundle utilise une cle de configuration incompatible": non observe. La configuration effective est acceptee et les options sont generees.
- "L'erreur vient d'une validation PHP apres retour assertion": rejete pour le cas reproduit, car aucune requete de resultat n'est envoyee.
- "Le login Passkey echoue avant `Security::login()`": rejete. Les logs montrent `login_result.assertion_validated`, `login_result.credential_saved` et `login_result.security_login_completed`.

Hypotheses non confirmees:

- La creation complete d'une Passkey sur `localhost` avec authenticator reel.
- L'authentification complete avec une Passkey deja enregistree.
- Le comportement exact sur un navigateur non headless avec Windows Hello, gestionnaire de mots de passe, telephone synchronise ou cle FIDO2.
- La disponibilite CLI de la base de donnees depuis tous les contextes de test.

Risques production:

- La production est configuree avec `rpId=benlemin.be` et `origin=https://benlemin.be`. Elle ne doit pas etre modifiee pour accepter `localhost` ou `127.0.0.1`.
- Toute credential creee avec `rpId=localhost` ne sera pas utilisable en production, et inversement. C'est attendu et souhaitable.
- Si la production est accessible via `www.benlemin.be` ou un autre alias sans adaptation, le meme type d'erreur se produira. Il faut tester l'URL canonique exacte.

## Plan De Correction Minimal

Corrections justifiees:

- Ameliorer le message front-end WebAuthn quand le hostname courant ne correspond pas au `rpId` renvoye par le serveur.
- Ajouter des tests JS unitaires pour ce cas afin d'eviter une regression.
- Documenter dans ce rapport et, si necessaire, dans la documentation stable que l'URL locale de test Passkey doit etre `http://localhost:8000` pour la configuration actuelle.
- Faire correspondre l'utilisateur utilise par `Security::login()` avec celui recharge par le provider memoire, pour eviter la deauthentication immediate apres login Passkey.

Corrections non justifiees a ce stade:

- Ne pas remplacer la bundle.
- Ne pas desactiver la verification d'origin ou de RP ID.
- Ne pas ajouter `127.0.0.1` en origin autorisee pour la meme configuration `rpId=localhost`.
- Ne pas basculer automatiquement toute l'application locale en HTTPS sans besoin prouve.
- Ne pas rendre le `rpId` dynamique par requete: cela creerait des credentials locales incompatibles entre hostnames et augmenterait le risque de confusion.

Fichiers a modifier:

- `assets/scripts/private/webauthn.js`: prevalidation locale du `rpId` avant `navigator.credentials.get()` / `create()` et message actionnable.
- `tests/js/private/webauthn.test.js`: tests de normalisation/options et message de mismatch hostname/RP ID.
- `assets/scripts/private/webauthn.js`: ajout du `csrfToken` au `POST` final d'enregistrement.
- `tests/js/private/webauthn.test.js`: verification que le payload final d'enregistrement contient bien `csrfToken`.
- `docs/en-cours/current-work-index.md`: ajouter ce rapport au suivi actif.
- `src/Private/Security/Service/PrivateAdminLoginUserFactory.php`: centraliser la creation de l'utilisateur de login Passkey avec le hash de mot de passe du provider.
- `src/Private/Security/Controller/PasskeyController.php`: utiliser la factory au lieu d'un `InMemoryUser` avec mot de passe `null`.
- `tests/Unit/Private/Security/PrivateAdminLoginUserFactoryTest.php`: couvrir la compatibilite avec le provider memoire.

Impact sur le login classique:

- Aucun changement attendu.
- Le formulaire mot de passe reste le fallback de secours.

Impact securite:

- Positif: l'utilisateur est guide vers l'origin correcte sans affaiblir WebAuthn.
- Aucun secret, challenge complet, cookie ou credential ID ne doit etre journalise.
- Les validations backend origin/RP ID restent intactes.

## Plan De Test

Automatise:

- Ajouter des tests JS pour verifier qu'un hostname `127.0.0.1` avec `rpId=localhost` bloque avant `navigator.credentials.*` et affiche un message explicite.
- Verifier que `localhost` avec `rpId=localhost` continue a appeler `navigator.credentials.*`.
- Lancer `npm run test:js` ou `make check`.
- Lancer `make cc`.

Manuel local:

- Ouvrir strictement `http://localhost:8000/private/login`.
- Verifier dans la console que `window.isSecureContext === true`.
- Se connecter avec `private_admin` / `private-dev-password`.
- Ouvrir `http://localhost:8000/private/security/passkeys`.
- Cliquer "Ajouter une passkey" avec Windows Hello, gestionnaire de passkeys du navigateur, telephone synchronise ou cle FIDO2 disponible.
- Se deconnecter.
- Revenir sur `http://localhost:8000/private/login`.
- Cliquer "Se connecter avec une passkey".
- Tester le fallback mot de passe apres echec controle.
- Tester volontairement `http://127.0.0.1:8000/private/login` pour verifier que le message indique d'utiliser `http://localhost:8000`.

Avant tout essai production:

- Confirmer l'URL canonique exacte: `https://benlemin.be`.
- Verifier qu'il n'y a pas de redirection effective vers `www.benlemin.be` ou un autre hote.
- Verifier HTTPS et certificats.
- Verifier que `debug:config webauthn --env=prod` ou l'equivalent deploiement expose `allowed_origins: ['https://benlemin.be']` et `rpId: benlemin.be`.
- Conserver le mot de passe de secours operationnel.

## Corrections Appliquees

Apres validation du diagnostic, seules les corrections suivantes ont ete appliquees:

- `assets/scripts/private/webauthn.js`: ajout du `csrfToken` au `POST` final d'enregistrement, en plus du payload WebAuthn serialise.
- `assets/scripts/private/webauthn.js`: verification du `rpId` renvoye par les options avant l'appel a `navigator.credentials.get()` ou `navigator.credentials.create()`. En cas de mismatch, le JS affiche l'URL attendue au lieu de laisser le navigateur retourner `This is an invalid domain.`.
- `tests/js/private/webauthn.test.js`: couverture du matching `rpId`/hostname, du message `127.0.0.1` -> `localhost`, de l'arret du flux avant `navigator.credentials.get()`, et de la presence du `csrfToken` dans le payload final d'enregistrement.
- `docs/en-cours/current-work-index.md`: ajout du rapport a l'index des travaux actifs.
- `src/Private/Security/Service/PrivateAdminLoginUserFactory.php`: creation de l'utilisateur de connexion Passkey avec le meme identifiant, les memes roles et le hash de mot de passe que le provider memoire.
- `src/Private/Security/Controller/PasskeyController.php`: utilisation de la factory pour `Security::login()`, afin que la session survive au prochain passage dans le `ContextListener`.
- `tests/Unit/Private/Security/PrivateAdminLoginUserFactoryTest.php`: preuve unitaire que le user cree est compatible avec le provider memoire et que l'ancien user avec mot de passe `null` ne l'etait pas.

Verification manuelle apres correction:

- `http://127.0.0.1:8000/private/login` affiche maintenant: `Cette URL ne correspond pas au domaine Passkey configuré (localhost). Utilise http://localhost:8000 pour tester les passkeys.`
- Le `POST /private/security/passkeys/register` passe maintenant le controle CSRF si la ceremonie WebAuthn fournit un resultat valide.
- Le login Passkey doit maintenant rester authentifie apres le retour navigateur, au lieu de repasser immediatement sur la page de login.

Verifications automatisees apres correction:

- `php bin/phpunit tests/Unit/Private/Security/PrivateAdminLoginUserFactoryTest.php`: passe, 1 test, 5 assertions.
- `npm run test:js -- tests/js/private/webauthn.test.js`: passe, 4 tests.
- `make check`: passe.
- `make cc`: passe.

## Journalisation De Diagnostic Ajoutee

Ajout du 2026-06-26 apres indication que l'echec peut arriver apres usage d'un authenticator reel.

Objectif:

- permettre un test manuel complet avec un authenticator reel;
- relire ensuite chaque etape serveur de la ceremonie;
- conserver des logs utiles sans exposer de secrets WebAuthn ou de session.

Fichier de log:

```text
var/log/private-webauthn.log
```

Format:

- JSON Lines, une entree par etape;
- actif en environnement local/debug;
- desactive en `test` et en `prod` par defaut via `app.webauthn.debug_log_enabled: false`.

Evenements traces:

- `register_options.start`
- `register_options.csrf_invalid`
- `register_options.user_resolved`
- `register_options.created`
- `register_options.stored`
- `register_result.start`
- `register_result.csrf_invalid`
- `register_result.deserialized`
- `register_result.options_loaded`
- `register_result.attestation_validated`
- `register_result.duplicate_credential`
- `register_result.saved`
- `register_result.failed`
- `login_options.start`
- `login_options.created`
- `login_options.stored`
- `login_result.start`
- `login_result.deserialized`
- `login_result.options_loaded`
- `login_result.credential_lookup`
- `login_result.assertion_validated`
- `login_result.credential_saved`
- `login_result.security_login_completed`
- `login_result.failed`

Donnees volontairement non journalisees:

- payload WebAuthn brut;
- challenge complet;
- credential ID complet;
- cookies et identifiant de session;
- cle publique complete;
- donnees biometriques.

Donnees journalisees sous forme reduite:

- `challenge_hash`: 12 premiers caracteres hexadecimaux de SHA-256;
- `credential_id_hash`: 12 premiers caracteres hexadecimaux de SHA-256;
- `user_handle_hash`: 12 premiers caracteres hexadecimaux de SHA-256.

Procedure de reproduction avec logs:

1. Vider le fichier de diagnostic si necessaire:

   ```bash
   : > var/log/private-webauthn.log
   ```

2. Ouvrir strictement:

   ```text
   http://localhost:8000/private/login
   ```

3. Pour tester l'enregistrement:

   - se connecter avec le mot de passe de secours;
   - ouvrir `/private/security/passkeys`;
   - cliquer `Ajouter une passkey`;
   - valider avec l'authenticator reel;
   - noter le message visible si echec.

4. Pour tester la connexion:

   - se deconnecter;
   - ouvrir `/private/login`;
   - cliquer `Se connecter avec une passkey`;
   - valider avec l'authenticator reel;
   - noter le message visible si echec.

5. Relire les logs:

   ```bash
   tail -n 120 var/log/private-webauthn.log
   ```

Interpretation rapide:

- Echec avant `*_result.start`: probleme navigateur/front-end ou authenticator avant retour serveur.
- `*_result.failed` avec `stage: options_storage_get`: challenge absent de la session, cookie/session ou changement d'origin probable.
- `register_result.failed` avec `stage: attestation_validation`: origin/RP ID, authenticator data, attestation ou options de creation a verifier.
- `login_result.failed` avec `stage: credential_lookup`: credential non sauvegardee ou credential ID non retrouve.
- `login_result.failed` avec `stage: assertion_validation`: origin/RP ID, signature, compteur, user handle ou options de requete a verifier.
