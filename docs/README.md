# Documentation

Ce dossier centralise la documentation de travail du site `benlemin.be`.

La documentation est organisée par statut et par domaine pour éviter de mélanger les références durables, les chantiers en cours et les comptes rendus terminés.

## Points D'Entree

- [Architecture du site](architecture.md) : vue stable des grandes parties du projet.
- [Workflow de contenu](content-workflow.md) : sources de vérité pour les contenus publiés.
- [Zone privee](private/README.md) : documentation stable de la zone privee et de son premier module.

## Organisation

```text
docs/
├── README.md
├── architecture.md
├── content-workflow.md
├── private/
├── en-cours/
├── termines/
│   ├── audits/
│   └── plans/
├── lab/
└── pro_exp/
```

## Statuts

[`en-cours/`](en-cours/) contient les documents qui servent encore de fil de reprise, de backlog ou de plan actif. Un document y reste tant qu'il décrit un travail non terminé ou une prochaine décision à prendre.

[`termines/`](termines/) contient les audits, plans et notes de suivi clôturés. Ils restent utiles comme historique, mais ne doivent pas être traités comme la source de vérité actuelle si un document stable existe à la racine de `docs/`.

## Domaines

`lab/` documente les outils expérimentaux intégrés au site, notamment le DnD Initiative Tracker. Les documents de ce dossier décrivent l'état technique, les contrats et les pipelines encore pertinents.

`pro_exp/` contient le corpus éditorial professionnel. Il sert de matière de travail pour le parcours, les projets, les compétences et les soft skills, mais l'application ne le lit pas au runtime.

`private/` regroupe la documentation stable de la zone privee, y compris le premier outil `Contacts et reseau` et les recommandations de securite associees.

## Regles De Rangement

- Créer une référence stable à la racine de `docs/` quand l'information décrit l'état actuel du site.
- Créer ou déplacer un document dans `en-cours/` quand il reste une action, une décision ou une vérification à mener.
- Déplacer un document dans `termines/audits/` ou `termines/plans/` quand le cycle de travail est terminé.
- Garder les documents métier ou module dans leur domaine si leur contenu est encore une référence technique vivante.
- Mettre à jour les liens au moment du déplacement.
