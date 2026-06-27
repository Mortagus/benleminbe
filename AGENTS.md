# Instructions De Contexte Pour Agents

Ce dépôt contient le code source du site personnel `benlemin.be`.

Au début d'une nouvelle session de travail, lire en priorité :

1. [README.md](README.md)
2. [docs/documentation-index.md](docs/documentation-index.md)
3. [docs/project-architecture.md](docs/project-architecture.md)
4. [docs/documentation-architecture.md](docs/documentation-architecture.md)
5. [docs/documentation-routing.md](docs/documentation-routing.md)

Ces cinq fichiers donnent le contexte global, l'organisation documentaire, l'architecture actuelle du projet et le routage pratique des documents.

## Documentation Par Sujet

* Contenu public et sources de vérité : [docs/content-workflow.md](docs/content-workflow.md)
* Travaux actifs : [docs/en-cours/](docs/en-cours/)
* Travaux terminés et historique : [docs/termines/](docs/termines/)
* Lab et DnD Initiative Tracker : [docs/lab/](docs/lab/)
* Corpus éditorial professionnel : [docs/editorial/](docs/editorial/)
* Sécurité de la zone privée : [docs/private/private-security-recommendations.md](docs/private/private-security-recommendations.md)
* Routage documentaire : [docs/documentation-routing.md](docs/documentation-routing.md)

## Commandes De Verification

Commande principale :

```bash
make check
```

Commandes utiles selon le contexte :

```bash
make reload_assets
make cc
make private-prod-check
make private-prod-auth-check
```

## Regles De Travail

* Identifier et consulter la documentation pertinente avant une modification significative.
* Préférer les conventions existantes du projet aux nouvelles abstractions.
* Garder les changements ciblés sur la demande.
* Ne pas traiter `docs/editorial/` comme source runtime : les contenus publiés viennent des fichiers YAML de `translations/`.
* Déplacer les documents de suivi vers `docs/en-cours/` ou `docs/termines/` selon leur statut.
* Ne pas modifier du code ou de la documentation sans lien direct avec la demande.
* Éviter les refactorings opportunistes non demandés.
* Expliquer brièvement les décisions importantes avant leur implémentation.
* Lancer `make check` après une modification applicative ou documentaire significative.
* Lors des captures Playwright, écrire les fichiers dans `.playwright-mcp/` plutôt qu'à la racine du dépôt.

## Principes De Conception Et Qualite Du Code

Appliquer les principes SOLID autant que cela est pertinent pour les nouvelles fonctionnalités, les corrections et les évolutions de code.

Ces principes doivent améliorer la lisibilité, la testabilité, la maintenabilité et l'évolution future du code, sans créer de complexité artificielle.

En pratique :

* Donner à chaque classe, module ou composant une responsabilité principale identifiable.
* Éviter les classes, services ou modules qui mélangent logique métier, accès aux données, rendu, validation, orchestration et effets de bord sans nécessité.
* Préférer l'extension d'un comportement existant à sa modification risquée lorsque plusieurs variantes métier sont réellement nécessaires.
* Ne pas introduire d'héritage, d'interface ou d'abstraction générique sans besoin concret de plusieurs implémentations, de substitution ou de tests.
* Dépendre d'abstractions stables lorsque cela clarifie une frontière métier, technique ou d'infrastructure.
* Garder les interfaces petites, explicites et orientées vers le besoin réel de leurs consommateurs.
* Isoler autant que possible la logique métier pure des détails de framework, du DOM, de l'I/O, de la persistance, des requêtes HTTP et du rendu.
* Favoriser les fonctions pures pour les règles simples, transformations de données, validations et calculs qui ne nécessitent pas d'état durable.
* Introduire une classe ou un service lorsque celui-ci porte une responsabilité durable, un état cohérent, une orchestration claire ou une dépendance externe à isoler.
* Ajouter ou adapter les tests lorsque la modification isole une règle métier, un comportement sensible ou une frontière de responsabilité importante.

Ne pas appliquer SOLID de manière mécanique.

Éviter notamment :

* une interface pour une seule implémentation sans perspective réaliste d'évolution ;
* des couches de services, factories, handlers ou adapters qui ne font que relayer une ligne de code ;
* des abstractions génériques créées avant qu'un second cas d'usage réel existe ;
* des découpages qui rendent le chemin d'exécution plus difficile à comprendre ;
* un refactor important lors d'une correction localisée, sauf si la structure actuelle empêche raisonnablement une correction fiable.

Lorsqu'un compromis est nécessaire, privilégier une structure simple, explicite, testable et cohérente avec l'architecture existante.

## Avant Toute Modification

Avant d'implémenter une modification :

1. Comprendre l'implémentation existante.
2. Rechercher si une solution similaire existe déjà dans le projet.
3. Vérifier si une documentation pertinente existe déjà.
4. Identifier les responsabilités concernées et éviter d'élargir inutilement leur périmètre.
5. Expliquer les changements architecturaux importants avant de les appliquer.
6. Limiter les modifications au strict nécessaire pour répondre à la demande.
7. Vérifier si les tests existants couvrent le comportement modifié et ajouter des tests ciblés lorsque cela est pertinent.

En cas de doute :

* privilégier la cohérence avec l'existant ;
* privilégier les solutions simples ;
* éviter les abstractions prématurées ;
* éviter l'ajout de dépendances sans justification claire ;
* préférer une amélioration locale lisible à une réorganisation globale non nécessaire.

## Priorités Techniques

Lorsqu'un arbitrage est nécessaire, appliquer l'ordre de priorité suivant :

1. Fonctionnement correct de l'application
2. Maintenabilité du code
3. Lisibilité et compréhension du code
4. Testabilité et prévention des régressions
5. Accessibilité
6. Performance

Éviter les optimisations prématurées.

Privilégier les fonctionnalités natives de Symfony et les composants déjà présents dans le projet avant d'introduire une nouvelle dépendance.

## Skills Projet

Les workflows réutilisables du projet sont définis dans `.agents/skills/`.

Utiliser notamment :

* `requirements-analyst` pour clarifier un besoin avant implémentation.
* `symfony-developer` pour les modifications applicatives Symfony.
* `code-reviewer` pour les revues de diff et validations avant commit.
