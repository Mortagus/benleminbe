# Documentation

Ce dossier regroupe la documentation du site `benlemin.be`.

La règle generale est simple:

- les references stables expliquent l'etat actuel;
- les documents `en-cours/` servent au suivi actif;
- `termines/` conserve l'historique;
- `lab/` documente les outils experimentaux;
- `editorial/` contient la matiere redactionnelle de travail;
- `private/` regroupe la documentation stable de la zone privee.

## Points D'Entree

- [Architecture du site](project-architecture.md) : vue stable des grandes parties du projet.
- [Architecture documentaire](documentation-architecture.md) : typologie, regles de classement et documents de reference.
- [Guide de routage documentaire](documentation-routing.md) : aide pratique pour savoir quoi lire ou où écrire selon une question.
- [Workflow de contenu](content-workflow.md) : sources de verite pour les contenus publics.
- [Deploiement et verification](deployment-and-verification.md) : cibles `make`, deploiement et checks.
- [Zone privee](private/private-area-index.md) : documentation stable de la zone privee et de son premier module.
- [Travaux en cours](en-cours/current-work-index.md) : fil de reprise, backlog et notes actives.
- [Travaux terminés](termines/archive-index.md) : archives des audits, plans et notes clotures.
- [Lab](lab/) : documentation des outils experimentaux.
- [Contexte assistant](assistant-context.md) : lecture minimale a fournir a Codex.

## Organisation

```text
docs/
├── documentation-index.md
├── documentation-architecture.md
├── documentation-routing.md
├── project-architecture.md
├── deployment-and-verification.md
├── content-workflow.md
├── assistant-context.md
├── private/
├── en-cours/
├── termines/
├── lab/
└── editorial/
```

## Regles De Rangement

- Créer une reference stable quand l'information décrit l'état courant du site.
- Mettre un document dans `en-cours/` quand il reste une action, une décision ou une vérification a mener.
- Mettre un document dans `termines/` quand le cycle de travail est clos et que l'information sert surtout d'historique.
- Garder une documentation métier ou module dans son domaine tant qu'elle reste une reference vivante.
- Mettre à jour les liens au moment d'un déplacement ou d'un renommage.
