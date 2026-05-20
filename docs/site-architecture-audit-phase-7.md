# Site Architecture Audit - Phase 7

## Objectif

Proposer une structure cible pour les futurs modules prives, sans polluer la partie publique existante.

Cette phase reste un audit et une proposition d'architecture. Aucun changement applicatif n'a ete effectue.

## Verification Technique

Commandes executees :

```bash
sed -n '220,320p' docs/site-architecture-audit-plan.md
find config src templates assets docs -maxdepth 3 -type f | sort
cat composer.json
cat config/services.yaml
cat config/routes.yaml
cat config/packages/framework.yaml
cat config/packages/twig.yaml
cat config/packages/asset_mapper.yaml
php bin/console debug:router
php bin/console debug:router app_lab_dnd_initiative
php bin/console debug:container security.helper
composer show symfony/security-bundle
composer show symfony/security-http
composer show symfony/security-core
composer show symfony/security-csrf
```

Resultats :

```text
Routes actuelles : toutes publiques.
Route lab DnD : /lab/dnd-initiative.
Security helper : absent.
symfony/security-bundle : absent.
symfony/security-http : absent.
symfony/security-core : absent.
symfony/security-csrf : absent.
```

Le projet n'a donc pas encore de couche d'authentification Symfony installee.

## Etat Actuel

Structure applicative actuelle :

```text
src/
  Command/
  Public/
    Controller/
    Service/

templates/
  base.html.twig
  components/
  home/
  pages/
  projects/
  experiences/
  lab/

assets/
  app.js
  scripts/
  styles/
```

Constat :

- le site public professionnel est deja isole sous `App\Public` cote PHP ;
- le lab DnD est public, mais ses templates et assets sont bien separes ;
- il n'existe pas encore de partie privee ;
- les routes sont chargees par attributs pour tout `src/` ;
- les services sont autowires globalement via `App\:` ;
- la session Symfony est activee, mais aucun firewall n'existe ;
- le sitemap fonctionne par opt-in via l'option de route `sitemap.enabled`.

## Risques A Eviter

Les principaux risques au moment d'ajouter la partie privee :

- ajouter des routes privees sans authentification reelle ;
- cacher une route par convention d'URL au lieu de la proteger par firewall ;
- reutiliser le layout public et y faire apparaitre des liens prives ;
- charger des assets prives dans `app.js` ou `styles/app.css` ;
- placer des services prives dans `App\Public\Service` ;
- exposer des fichiers de donnees privees dans `public/` ;
- ajouter des routes privees au sitemap par erreur ;
- melanger lab public, outils personnels et pages professionnelles.

## Boundaries Proposees

### PHP

Structure cible recommandee :

```text
src/
  Public/
    Controller/
    Service/

  Private/
    Controller/
    Service/
    Module/

  Shared/
    Service/
```

Usage :

- `App\Public` reste reserve au site public, aux pages professionnelles et aux routes publiques ;
- `App\Private` porte les controllers, services et modules personnels proteges ;
- `App\Shared` ne doit exister que pour du code vraiment partage entre public et prive ;
- il ne faut pas deplacer les providers publics actuels tant qu'ils ne sont pas reutilises ailleurs.

Alternative acceptable :

```text
src/
  Public/
  Personal/
  Shared/
```

`App\Private` est plus direct et coherent avec `App\Public`. `App\Personal` evite en revanche de confondre "prive" avec le mot-cle PHP dans la lecture humaine, meme si le namespace fonctionne techniquement.

### Lab

Le lab DnD est public aujourd'hui.

Option recommandee a court terme :

```text
src/Public/Controller/Lab/
templates/lab/
assets/scripts/lab/
assets/styles/lab/
```

Ce deplacement n'est pas urgent. Il deviendra utile si plusieurs outils lab publics apparaissent.

Si un outil lab devient prive, il ne devrait pas rester dans `lab/` simplement parce qu'il a commence la. Il devrait rejoindre la boundary privee :

```text
src/Private/Controller/Tools/
templates/private/tools/
assets/scripts/private/tools/
```

## Routes Privees

Convention proposee :

```text
/private
/private/<module>
/private/<module>/<action>
```

Noms de routes :

```text
app_private_dashboard
app_private_<module>_index
app_private_<module>_<action>
```

Controller racine possible :

```php
namespace App\Private\Controller;

#[Route('/private', name: 'app_private_', methods: ['GET'])]
final class DashboardController
{
}
```

Recommandations :

- utiliser `methods` explicitement sur toutes les routes privees ;
- ne pas localiser les routes privees au depart ;
- ne pas ajouter d'option `sitemap` aux routes privees ;
- ajouter une meta `robots` noindex/nofollow dans le layout prive ;
- ajouter `Disallow: /private/` dans `robots.txt`, mais uniquement comme signal SEO, jamais comme protection.

Pourquoi ne pas localiser tout de suite :

- les outils prives n'ont pas besoin d'etre publics ou SEO ;
- l'authentification et les workflows personnels sont plus simples sans `_locale` ;
- la traduction peut etre ajoutee plus tard si un module prive devient partageable.

## Securite Et Authentification

Etat actuel :

- aucun bundle security installe ;
- aucun firewall ;
- aucun user provider ;
- aucune protection CSRF applicative ;
- session Symfony disponible.

Avant la premiere vraie route privee, il faudra installer et configurer la securite Symfony.

Premiere cible raisonnable :

```text
symfony/security-bundle
symfony/security-csrf
```

Approche d'authentification recommandee au demarrage :

- un seul utilisateur administrateur ;
- identifiants stockes via variables d'environnement ou provider in-memory ;
- mot de passe hashe ;
- firewall protegeant `^/private`;
- formulaire de login simple ;
- logout explicite ;
- pas de remember-me au debut ;
- CSRF sur login et formulaires POST.

Pourquoi cette approche :

- le projet n'a pas encore de base de donnees ;
- ajouter Doctrine uniquement pour un compte personnel serait premature ;
- un provider simple suffit tant qu'il n'y a qu'un seul proprietaire du site.

Evolution possible plus tard :

- stockage utilisateur en base ;
- roles par module ;
- deuxieme facteur ;
- journal d'activite ;
- expiration de session plus stricte ;
- rate limiting du login.

Ces evolutions ne doivent venir que si le contenu prive devient sensible ou multi-utilisateur.

## Templates Prives

Structure recommandee :

```text
templates/private/
  base.html.twig
  dashboard.html.twig
  components/
  <module>/
    index.html.twig
    _partials.html.twig
```

Recommandations :

- creer un layout prive dedie ;
- ne pas reutiliser directement le header public ;
- garder un shell plus operationnel : navigation laterale ou topbar compacte ;
- afficher clairement l'etat connecte et le logout ;
- ajouter `noindex,nofollow` ;
- garder le theme switcher seulement s'il est utile ;
- eviter d'inclure les liens publics dans l'interface de travail.

Le layout public peut rester tel quel.

## Assets Prives

Structure recommandee :

```text
assets/
  scripts/
    private/
      app.js
      <module>.js

  styles/
    private/
      app.css
      layout.css
      components.css
      <module>.css
```

Entrypoint recommande :

```php
'private' => [
    'path' => './assets/scripts/private/app.js',
    'entrypoint' => true,
],
```

Regles :

- ne pas importer les styles prives dans `styles/app.css` ;
- ne pas importer les scripts prives dans `assets/app.js` ;
- creer un entrypoint dedie pour la partie privee ;
- creer des entrypoints par module si un outil devient lourd ;
- reutiliser les tokens CSS seulement si cela ne tire pas tout le design public.

Le modele DnD fournit deja un bon precedent : entrypoint dedie, CSS dedie, module autonome.

## Services Partages

Regle de base :

```text
Public reste public.
Private reste prive.
Shared apparait seulement en cas de partage reel.
```

Exemples :

- `ProjectProvider` et `ExperienceProvider` restent dans `App\Public\Service` ;
- un service de lecture de fichiers personnels va dans `App\Private\Service` ;
- un helper de formatage de dates vraiment commun peut aller dans `App\Shared\Service` ;
- un service d'acces au filesystem prive ne doit jamais etre dans `App\Public`.

Ne pas creer `Shared` trop tot. Un dossier partage cree avant le besoin reel devient vite un fourre-tout.

## Donnees Et Stockage

Etat actuel :

- pas de Doctrine ;
- pas de base de donnees configuree ;
- quelques ecritures existent deja dans `var/log` pour le tracking ;
- les fichiers publics sont sous `public/`.

Recommandations pour la partie privee :

- ne jamais stocker de donnees privees dans `public/` ;
- utiliser `var/private/` ou un stockage externe pour les fichiers personnels ;
- documenter les formats de donnees par module ;
- commencer simple si les donnees sont locales et peu nombreuses ;
- introduire une base de donnees seulement si plusieurs modules ont besoin de requetes, relations ou historique.

Options progressives :

```text
Etape 1 : fichiers JSON/YAML dans var/private pour un module simple.
Etape 2 : SQLite si plusieurs modules locaux ont besoin de persistance.
Etape 3 : Doctrine + base relationnelle si les donnees deviennent structurantes.
```

La decision dependra du premier module prive concret.

## Navigation Et SEO

Constat actuel :

- le header public liste uniquement les pages professionnelles ;
- le footer public liste contact/legal ;
- le sitemap est opt-in ;
- `robots.txt` disallow deja `/track/` et `/_profiler/`.

Recommandations :

- ne pas ajouter de lien prive dans la navigation publique ;
- ajouter un lien prive uniquement si volontaire, par exemple discret en footer ou accessible directement ;
- ne pas indexer les routes privees ;
- ajouter `Disallow: /private/` dans `robots.txt` quand la zone existe ;
- ne pas compter sur `robots.txt` pour la securite.

## Plan De Mise En Place Progressif

### Etape 1 - Fondation Securite

- Installer `symfony/security-bundle`.
- Installer ou activer la protection CSRF necessaire.
- Creer `config/packages/security.yaml`.
- Creer une route de login et de logout.
- Proteger `^/private`.
- Verifier qu'une URL privee redirige vers le login.

### Etape 2 - Shell Prive Minimal

- Creer `src/Private/Controller/DashboardController.php`.
- Creer `templates/private/base.html.twig`.
- Creer `templates/private/dashboard.html.twig`.
- Creer un entrypoint asset `private`.
- Ajouter noindex/nofollow dans le layout prive.

### Etape 3 - Premier Module Prive

- Creer un module vertical complet :
  - controller ;
  - service ;
  - template ;
  - styles/scripts si necessaire ;
  - stockage ;
  - validations.
- Eviter les abstractions tant que le deuxieme module n'existe pas.

### Etape 4 - Stabilisation Apres Deux Modules

- Extraire les composants prives communs ;
- identifier les vrais services partages ;
- formaliser les conventions de routes ;
- ajouter des tests sur les workflows sensibles ;
- decider si un stockage plus robuste est necessaire.

## Decisions A Valider

Avant implementation, il faut valider :

- nom de la boundary PHP : `App\Private` ou `App\Personal` ;
- URL racine : `/private`, `/admin`, `/personal` ou autre ;
- besoin de locale dans la partie privee ;
- methode d'authentification initiale ;
- niveau de sensibilite des donnees stockees ;
- stockage initial : fichiers, SQLite, base relationnelle ;
- place du lab public par rapport aux futurs outils prives ;
- experience UI attendue : shell discret ou outil plein ecran.

## Recommandations Priorisees

### Priorite 1 - Ne Pas Commencer Sans Securite

- Installer la securite Symfony avant toute route privee utile.
- Proteger `^/private` par firewall.
- Garder les routes privees hors sitemap.
- Ne pas stocker de donnees privees dans `public/`.

### Priorite 2 - Isoler Sans Refactorer Le Public

- Creer `src/Private` uniquement au moment du premier module.
- Creer `templates/private` avec un layout dedie.
- Creer un entrypoint assets prive.
- Ne pas deplacer les providers publics actuels.

### Priorite 3 - Extraire Seulement Apres Usage

- Creer `Shared` uniquement pour du code partage reel.
- Ajouter une base de donnees seulement si le premier module le justifie.
- Modulariser davantage le lab seulement si plusieurs outils apparaissent.
- Ajouter roles, 2FA ou journalisation seulement si la sensibilite des donnees l'exige.

## Note De Reprise

```text
Phase 7 terminee.

Sources analysees :
- docs/site-architecture-audit-plan.md
- composer.json
- config/services.yaml
- config/routes.yaml
- config/packages/framework.yaml
- config/packages/twig.yaml
- config/packages/asset_mapper.yaml
- config/packages/routing.yaml
- src/Public/*
- templates/components/*
- src/Command/GenerateSitemapCommand.php
- public/robots.txt
- importmap.php et entrypoints assets existants

Verifications :
- php bin/console debug:router : OK
- php bin/console debug:router app_lab_dnd_initiative : OK
- security.helper absent.
- symfony/security-bundle absent.
- symfony/security-http absent.
- symfony/security-core absent.
- symfony/security-csrf absent.

Architecture cible proposee :
- src/Public pour le site public.
- src/Private ou src/Personal pour les outils proteges.
- src/Shared seulement pour le code vraiment commun.
- templates/private avec layout dedie.
- assets/scripts/private et assets/styles/private avec entrypoint dedie.
- routes /private protegees par firewall.

Decisions a valider :
- App\Private ou App\Personal.
- URL racine privee.
- Authentification initiale.
- Stockage initial.
- Locale ou non dans le prive.
- Statut futur du lab public.

Migrations recommandees :
- Aucune migration public immediate.
- Installer Symfony Security avant le premier module prive.
- Creer un dashboard prive minimal.
- Ajouter le premier module verticalement.
- Extraire Shared seulement apres un besoin concret.

Risques :
- Routes privees sans firewall.
- Donnees privees dans public/.
- Assets prives charges globalement.
- Services prives places dans App\Public.
- Shared cree trop tot et transforme en fourre-tout.

Phase suivante :
- Phase 8 - Synthese Et Plan D'Action.
```
