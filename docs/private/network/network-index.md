# Reseau Prive

Documentation du premier outil prive `Contacts et reseau`.

## Documents

- [Vision du module](network-vision.md) : contexte, intention, etat actuel et fonctionnalites deja en place.
- [Analyse du besoin](network-besoin-analysis.md) : phase 0 et clarification du besoin.
- [Specification MVP](network-mvp-specification.md) : perimetre fonctionnel minimal et ecrans cibles.
- [Regles de comparaison et de score](merge-review-scoring-rules.md) : logique de detection des doublons et lecture des scores.
- [Regles de matching](contact-write-matching-rules.md) : reutilisation d'un contact existant lors d'un ajout ou d'un import.
- [Regles d'auto-fusion](contact-auto-merge-rules.md) : fusion automatique des doublons les plus certains.
- [Audit structure et CSS](network-ui-structure-audit.md) : audit du rendu Twig et CSS de la partie privee.
- [Audit mobile et tablette](network-responsive-audit.md) : audit du comportement responsive sur les pages `contacts` et `platforms`.
- [Architecture documentaire globale](../../documentation-architecture.md) : carte de lecture du projet entier.
- [Univers du site et navigation](../../site-universes-and-navigation.md) : contexte global de navigation du site et position du réseau dans l'univers privé.

## Routes Et Ecrans Actuels

- tableau de bord du réseau `/private/network`;
- liste des contacts `/private/network/contacts`;
- fiche contact `/private/network/contacts/{id}`;
- formulaire contact `/private/network/contacts/new` et `/private/network/contacts/{id}/edit`;
- import de contacts `/private/network/import` comme action contextuelle des contacts et du tableau de bord réseau;
- revue des doublons `/private/network/contact-merge-reviews` comme action contextuelle du flux Contacts;
- liste des plateformes `/private/network/platforms`;
- fiche plateforme `/private/network/platforms/{slug}`;
- import et export des plateformes `/private/network/platforms/import` et `/private/network/platforms/export`.

## Perimetre

Ces documents decrivent le sous-domaine `network` de la zone privee. Ils servent de reference pour la structure, le cadrage produit et les futures refontes visuelles.
