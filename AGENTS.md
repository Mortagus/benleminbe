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

- Contenu public et sources de vérité : [docs/content-workflow.md](docs/content-workflow.md)
- Travaux actifs : [docs/en-cours/](docs/en-cours/)
- Travaux terminés et historique : [docs/termines/](docs/termines/)
- Lab et DnD Initiative Tracker : [docs/lab/](docs/lab/)
- Corpus éditorial professionnel : [docs/editorial/](docs/editorial/)
- Sécurité de la zone privée : [docs/private/private-security-recommendations.md](docs/private/private-security-recommendations.md)
- Routage documentaire: [docs/documentation-routing.md](docs/documentation-routing.md)

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

- Identifier et consulter la documentation pertinente avant une modification significative.
- Préférer les conventions existantes du projet aux nouvelles abstractions.
- Garder les changements ciblés sur la demande.
- Ne pas traiter `docs/editorial/` comme source runtime : les contenus publiés viennent des fichiers YAML de `translations/`.
- Déplacer les documents de suivi vers `docs/en-cours/` ou `docs/termines/` selon leur statut.
- Ne pas modifier du code ou de la documentation sans lien direct avec la demande.
- Éviter les refactorings opportunistes non demandés.
- Expliquer brièvement les décisions importantes avant leur implémentation.
- Lancer `make check` après une modification applicative ou documentaire significative.

## Avant Toute Modification

Avant d'implémenter une modification :

1. Comprendre l'implémentation existante.
2. Rechercher si une solution similaire existe déjà dans le projet.
3. Vérifier si une documentation pertinente existe déjà.
4. Expliquer les changements architecturaux importants avant de les appliquer.
5. Limiter les modifications au strict nécessaire pour répondre à la demande.

En cas de doute :

- privilégier la cohérence avec l'existant ;
- privilégier les solutions simples ;
- éviter les abstractions prématurées ;
- éviter l'ajout de dépendances sans justification claire.

## Priorités Techniques

Lorsqu'un arbitrage est nécessaire, appliquer l'ordre de priorité suivant :

1. Fonctionnement correct de l'application
2. Maintenabilité du code
3. Lisibilité et compréhension du code
4. Accessibilité
5. Performance

Éviter les optimisations prématurées.

Privilégier les fonctionnalités natives de Symfony et les composants déjà présents dans le projet avant d'introduire une nouvelle dépendance.

## Skills Projet

Les workflows réutilisables du projet sont définis dans `.agents/skills/`.

Utiliser notamment :

- `requirements-analyst` pour clarifier un besoin avant implémentation.
- `symfony-developer` pour les modifications applicatives Symfony.
- `code-reviewer` pour les revues de diff et validations avant commit.
