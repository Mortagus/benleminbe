# Site Architecture Audit - Phase 2

## Objectif

Auditer l'architecture backend Symfony actuelle : controllers, services/providers, commande sitemap, routing et repartition des responsabilites cote PHP.

Cette phase reste un audit. Aucun changement applicatif n'a ete effectue.

## Verification Technique

Commande executee :

```bash
php bin/console lint:container
```

Resultat :

```text
OK - The container was linted successfully.
```

Remarque : une tentative exploratoire avec `debug:container --show-private` a echoue car cette option n'existe pas dans la version Symfony utilisee. L'option disponible est `--show-hidden`. Cela ne revele pas de probleme applicatif.

## Vue Backend Actuelle

Code PHP applicatif principal :

```text
src/
  Command/
    GenerateSitemapCommand.php

  Public/
    Controller/
      BusinessCardController.php
      ExperiencesController.php
      HomeController.php
      LabController.php
      PageController.php
      ProjectsController.php
      TrackingController.php

    Service/
      CvProvider.php
      ExperienceProvider.php
      ProjectProvider.php
```

Configuration :

- `config/routes.yaml` importe les controllers via `routing.controllers`.
- `config/services.yaml` autowire/autoconfigure tout `App\`.
- Les routes sont declarees par attributs PHP.

## Controllers

### HomeController

Responsabilites actuelles :

- rediriger `/` vers la locale par defaut ;
- afficher la home localisee ;
- recuperer les donnees du CV via `CvProvider`.

Constat :

- controller mince ;
- dependance vers `CvProvider` justifiee ;
- aucune logique metier lourde.

Point mineur :

- style PHP pas totalement uniforme avec certains autres fichiers : accolades sur la meme ligne, `TRUE` majuscule.

### PageController

Responsabilites actuelles :

- rendre des pages statiques localisees : about, contact, legal, skills.

Constat :

- controller tres mince ;
- responsabilite claire ;
- bon candidat a rester tel quel tant que les pages restent simples.

Point mineur :

- certains retours ligne semblent melanger styles/encodages historiques. Rien de bloquant, mais un formatage automatique serait utile si le projet adopte une convention stricte.

### ProjectsController

Responsabilites actuelles :

- lister les projets ;
- afficher un projet detaille ;
- recuperer l'experience associee ;
- recuperer projet precedent/suivant.

Constat :

- controller encore raisonnablement mince ;
- orchestration entre `ProjectProvider` et `ExperienceProvider` presente mais limitee ;
- logique de relation projet -> experience reste dans `ProjectProvider`, ce qui est preferable au controller.

Point a surveiller :

- si les pages projets gagnent en complexite, il pourrait devenir utile d'introduire un service applicatif du type `ProjectPageBuilder` ou `ProjectViewModelProvider`.
- pas necessaire pour l'instant.

### ExperiencesController

Responsabilites actuelles :

- lister les experiences ;
- afficher une experience detaillee ;
- recuperer projets associes ;
- recuperer experience precedente/suivante.

Constat :

- controller mince ;
- orchestration similaire a `ProjectsController` ;
- responsabilite claire.

Point a surveiller :

- la symetrie projets/experiences est bonne, mais les deux controllers repeteront probablement certaines structures si les pages detail deviennent plus riches.

### BusinessCardController

Responsabilites actuelles :

- afficher la page carte ;
- generer une vCard ;
- echapper les champs vCard.

Constat :

- la route page est simple ;
- la generation vCard est une logique de formatage concrete directement dans le controller.

Risque :

- faible aujourd'hui, mais si la vCard evolue, le controller deviendra responsable a la fois du HTTP et du format vCard.

Recommandation :

- extraire a terme la generation dans un service dedie, par exemple :

```text
src/Public/Service/VcardProvider.php
```

ou :

```text
src/Public/Service/ContactCardProvider.php
```

Le controller se limiterait alors a :

- demander le contenu vCard au service ;
- retourner une `Response` avec les bons headers.

Priorite : basse.

### TrackingController

Responsabilites actuelles :

- recevoir un POST de tracking ;
- construire une ligne de log ;
- creer le dossier `var/log` si necessaire ;
- ecrire dans un fichier log ;
- retourner une reponse 204.

Constat :

- le controller contient de la logique applicative et de l'I/O filesystem.

Risque :

- couplage direct au filesystem ;
- difficile a tester proprement ;
- risque de croissance si d'autres evenements de tracking sont ajoutes ;
- responsabilite plus technique qu'un controller HTTP classique.

Recommandation :

- extraire l'ecriture dans un service dedie, par exemple :

```text
src/Public/Service/CvDownloadTracker.php
```

ou, si l'usage devient plus large :

```text
src/Shared/Tracking/EventTracker.php
```

Le controller garderait seulement :

- validation minimale de la requete ;
- appel au tracker ;
- reponse HTTP.

Priorite : moyenne si le tracking doit evoluer, basse si cela reste un endpoint minimal.

### LabController

Responsabilites actuelles :

- exposer `/lab/dnd-initiative` ;
- rendre le template du tracker DnD.

Constat :

- controller tres mince ;
- aucun probleme de responsabilite interne.

Point architectural :

- comme documente en phase 1, sa place dans `App\Public` est acceptable si `Public` signifie "accessible publiquement".
- si d'autres outils publics arrivent, un sous-dossier `src/Public/Controller/Lab/` serait plus lisible.

Recommandation :

- pas de refactor urgent ;
- a envisager avant d'ajouter un deuxieme outil lab.

## Services Et Providers

### CvProvider

Responsabilites actuelles :

- construire le chemin relatif du CV selon la locale ;
- calculer une version basee sur `filemtime` et `filesize`.

Constat :

- responsabilite claire ;
- utile pour garder le controller mince.

Risque :

- si le fichier attendu n'existe pas, `filemtime`/`filesize` peuvent emettre des warnings.

Recommandation :

- ajouter a terme une verification explicite avec exception claire ou fallback controle.
- priorite basse tant que les fichiers CV sont garantis.

### ExperienceProvider

Responsabilites actuelles :

- stocker la configuration des experiences ;
- definir l'ordre des experiences ;
- traduire les champs via `TranslatorInterface` ;
- construire les donnees detaillees ;
- construire les resumes ;
- fournir precedent/suivant.

Constat :

- service central pour le domaine experience ;
- logique principalement de lecture/composition ;
- responsabilite acceptable pour la taille actuelle.

Risque :

- melange de configuration structurelle PHP et contenu traduit YAML ;
- `getExperienceData()` depend de conventions de cles de traduction ;
- `getExperienceSummary()` retourne une periode courte alors que `getExperienceData()` retourne une periode longue localisee, ce qui est intentionnel mais doit rester documente ;
- exception utilisee : `InvalidArgumentException`, alors que cote HTTP un slug inconnu meriterait plutot une 404 via `NotFoundHttpException` ou conversion par controller.

Recommandations :

- documenter clairement la difference entre donnees listing/resume et donnees detail ;
- envisager un type plus explicite plus tard : `ExperienceCatalog`, `ExperienceViewProvider`, ou objets DTO ;
- harmoniser la gestion des slugs inconnus avec les projets.

Priorite : moyenne pour la clarification, basse pour un refactor structurel.

### ProjectProvider

Responsabilites actuelles :

- stocker la liste des projets et leur experience associee ;
- charger les traductions YAML projets ;
- retourner les donnees detaillees d'un projet ;
- produire les cards associees a une experience ;
- fournir precedent/suivant ;
- resoudre l'experience associee a un projet.

Constat :

- provider plus oriente "catalogue + loader YAML" que `ExperienceProvider` ;
- utilise `Yaml::parseFile` directement ;
- utilise `KernelInterface` pour trouver le project dir.

Risque :

- `KernelInterface` est une dependance assez large pour obtenir seulement le chemin projet ;
- parse le fichier YAML potentiellement plusieurs fois par requete si plusieurs methodes sont appelees ;
- melange catalogue, chargement fichier et transformation pour cards ;
- fortement couple a la structure exacte de `translations/projects.*.yaml`.

Recommandations :

- remplacer a terme `KernelInterface` par `#[Autowire('%kernel.project_dir%')] string $projectDir`, comme dans `CvProvider` ;
- envisager un cache interne par locale pour eviter plusieurs `Yaml::parseFile` dans une meme requete ;
- si les projets continuent a grossir, separer le catalogue (`PROJECTS`) du chargement/traduction ;
- garder le provider tel quel tant que le volume reste faible.

Priorite : moyenne pour `KernelInterface` et cache local, basse pour decoupage complet.

## Commande Sitemap

### GenerateSitemapCommand

Responsabilites actuelles :

- parcourir les routes avec option `sitemap` ;
- gerer les routes localisees et alternates ;
- gerer les pages detail projets/experiences via providers ;
- generer le XML ;
- ecrire `public/sitemap.xml`.

Constat :

- la commande fonctionne comme un orchestrateur complet ;
- elle est autonome et lisible ;
- l'utilisation des providers evite de dupliquer la liste des projets/experiences.

Risques :

- la commande connait explicitement les noms de routes `app_projects_show` et `app_experiences_show` ;
- logique sitemap, XML et ecriture fichier sont dans une seule classe ;
- si d'autres collections detaillees apparaissent, la methode `buildSitemapUrls()` grossira.

Recommandations :

- pas de refactor urgent ;
- si le sitemap evolue, extraire un `SitemapUrlProvider` ou plusieurs providers par domaine ;
- garder les options `sitemap` sur les routes, c'est une bonne convention locale.

Priorite : basse.

## Routing Et Boundaries

Constats :

- la majorite des pages professionnelles suivent `/{_locale}/...`.
- `BusinessCardController` utilise des chemins localises explicites pour `/card` et `/en/card`.
- `LabController` utilise `/lab/...` sans locale.
- `TrackingController` utilise `/track/...`.

Lecture possible :

- les pages professionnelles sont localisees ;
- les outils techniques ou utilitaires ne sont pas forcement localises ;
- la carte fait exception avec un modele de chemins localises explicites.

Recommandation :

- formaliser cette convention :
  - contenu professionnel : routes localisees ;
  - endpoints techniques : routes non localisees ;
  - labs/outils publics : a decider selon besoin UX ;
  - partie privee future : prefixe dedie, probablement non localise au depart sauf besoin.

## Cohérence De Style PHP

Constats visibles :

- certains fichiers utilisent `declare(strict_types=1)`, d'autres non ;
- melange de `true`/`false`/`null` et `TRUE`/`FALSE`/`NULL` ;
- style d'accolades different selon fichiers ;
- `LabController` n'est pas `final`, contrairement aux autres controllers ;
- `GenerateSitemapCommand` n'est pas `final`.

Impact :

- pas un probleme architectural bloquant ;
- mais cela rend le code moins uniforme ;
- un formatage/lint PHP futur serait utile.

Recommandation :

- choisir une convention PHP minimale :
  - `declare(strict_types=1)` partout dans `src/` ;
  - booleens/null en minuscules ;
  - classes applicatives `final` par defaut ;
  - style d'accolades uniforme.

Priorite : basse a moyenne, selon envie de maintenir une base tres reguliere.

## Recommandations Priorisees

### Priorite 1 - Decisions Structurantes Avant Croissance

- Formaliser les boundaries cible : `Public`, `Public\Controller\Lab`, future `Private`.
- Decider si `Public` signifie accessibilite publique ou domaine professionnel public.
- Documenter la convention de routes : localisees vs non localisees.

### Priorite 2 - Refactors Legers A Bon Rendement

- Extraire la generation vCard dans un service dedie.
- Extraire l'ecriture tracking dans un service dedie.
- Remplacer `KernelInterface` par `%kernel.project_dir%` dans `ProjectProvider`.
- Ajouter un cache par locale dans `ProjectProvider`.

### Priorite 3 - Hygiene Et Lisibilite

- Harmoniser `strict_types`, `final`, booleens/null et style d'accolades.
- Clarifier la difference entre periode courte et periode detaillee dans `ExperienceProvider`.
- Harmoniser la gestion des slugs inconnus projets/experiences.

## Note De Reprise

```text
Phase 2 terminee.

Classes auditees :
- HomeController
- PageController
- ProjectsController
- ExperiencesController
- BusinessCardController
- TrackingController
- LabController
- CvProvider
- ExperienceProvider
- ProjectProvider
- GenerateSitemapCommand

Constats principaux :
- Les controllers de rendu sont globalement minces.
- BusinessCardController contient la generation vCard.
- TrackingController contient l'ecriture filesystem.
- ProjectProvider melange catalogue, lecture YAML et transformations.
- ExperienceProvider melange catalogue PHP et traductions Symfony, mais reste lisible.
- GenerateSitemapCommand est autonome mais connait explicitement les routes detail projets/experiences.
- Les boundaries Public/Lab/Private doivent etre formalisees avant croissance.

Recommandations principales :
- Garder App\Public pour l'instant, avec option future src/Public/Controller/Lab/DndInitiativeController.
- Extraire plus tard VcardProvider et CvDownloadTracker si ces responsabilites evoluent.
- Remplacer KernelInterface dans ProjectProvider par projectDir autowire et ajouter un cache par locale.
- Formaliser les conventions de routes.
- Harmoniser le style PHP progressivement.

Risques residuels :
- Duplication docs/YAML non traitee ici, a auditer en phase 5.
- DnD non audite en profondeur, a traiter en phase 6.
- Templates et CSS/JS non audites ici, a traiter en phases 3 et 4.

Phase suivante :
- Phase 3 - Templates, UX Structurelle Et Reutilisabilite.
```
