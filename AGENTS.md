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

- Lire le contexte documentaire avant de modifier le code.
- Préférer les conventions existantes du projet aux nouvelles abstractions.
- Garder les changements ciblés sur la demande.
- Ne pas traiter `docs/editorial/` comme source runtime : les contenus publiés viennent des fichiers YAML de `translations/`.
- Déplacer les documents de suivi vers `docs/en-cours/` ou `docs/termines/` selon leur statut.
- Lancer `make check` après une modification applicative ou documentaire significative.
