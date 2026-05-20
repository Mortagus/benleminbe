# Site Architecture Audit Plan

Ce document sert de fil conducteur pour auditer progressivement la structure, l'architecture et la repartition des responsabilites du site.

Le travail doit rester decoupable en phases courtes. Chaque phase produit une note de suivi permettant de reprendre facilement l'audit plus tard, sans devoir relire tout le projet.

## Regles De Travail

- Auditer une phase a la fois.
- Lire le code avant de proposer des changements.
- Ne pas modifier le code pendant l'audit, sauf demande explicite.
- Distinguer les constats, les risques, les recommandations et les questions ouvertes.
- Terminer chaque phase par une note de reprise.

## Phase 1 - Cartographie Globale

But : obtenir une vue claire de la structure actuelle du site et de ses grands modules.

Perimetre :
- arborescence Symfony ;
- separation entre code PHP, templates, assets, traductions et documentation ;
- routes publiques existantes ;
- pages professionnelles publiques ;
- outil DnD Initiative Tracker ;
- emplacement probable de la future partie privee.

Livrable :
- carte des modules existants ;
- responsabilites principales par dossier ;
- premieres incoherences visibles ;
- points a auditer en detail dans les phases suivantes.

Note de reprise :

```text
Phase 1 terminee.
Structure analysee :
- ...
Modules reperes :
- ...
Risques ou incoherences visibles :
- ...
Points a verifier ensuite :
- ...
Phase suivante : Phase 2 - Architecture Backend Symfony.
```

## Phase 2 - Architecture Backend Symfony

But : verifier si les responsabilites cote PHP sont bien reparties.

Perimetre :
- controllers ;
- services et providers ;
- routing ;
- logique metier dans controllers vs services ;
- extensibilite pour la future partie privee.

Questions principales :
- Les controllers restent-ils minces ?
- Les providers font-ils trop de choses ?
- Les modules publics, lab et futurs modules prives peuvent-ils rester separes ?
- Les services partages sont-ils clairement identifies ?

Livrable :
- analyse par classe ou groupe de classes ;
- recommandations de decoupage ;
- propositions eventuelles de namespaces, dossiers ou services.

Note de reprise :

```text
Phase 2 terminee.
Classes auditees :
- ...
Decisions d'architecture recommandees :
- ...
Refactors utiles :
- ...
Questions ouvertes :
- ...
Phase suivante : Phase 3 - Templates, UX Structurelle Et Reutilisabilite.
```

## Phase 3 - Templates, UX Structurelle Et Reutilisabilite

But : verifier la coherence des templates Twig et des composants de rendu.

Perimetre :
- templates des pages publiques ;
- templates projets et experiences ;
- templates du DnD Initiative Tracker ;
- composants partages ;
- duplication de structures ;
- logique de presentation dans Twig ;
- coherence des noms de classes CSS.

Questions principales :
- Les templates sont-ils trop specialises ?
- Certains blocs devraient-ils devenir des composants ?
- Les pages detail projets et experiences sont-elles structurellement coherentes ?
- Le module DnD est-il suffisamment isole ?

Livrable :
- duplications reperees ;
- composants candidats a extraction ;
- conventions Twig a stabiliser.

Note de reprise :

```text
Phase 3 terminee.
Templates audites :
- ...
Duplications reperees :
- ...
Composants candidats :
- ...
Conventions recommandees :
- ...
Phase suivante : Phase 4 - CSS, Assets Et JavaScript.
```

## Phase 4 - CSS, Assets Et JavaScript

But : verifier la coherence technique du frontend.

Perimetre :
- organisation CSS par base, components, pages, layout et lab ;
- conventions de nommage ;
- styles globaux vs styles page ;
- scripts publics ;
- scripts du module DnD ;
- couplage JS/HTML via attributs data ;
- risques de dette CSS ou JS.

Questions principales :
- Les styles sont-ils bien localises ?
- Les composants globaux sont-ils vraiment generiques ?
- Le DnD tracker a-t-il son espace propre ?
- Les scripts sont-ils suffisamment isoles ?

Livrable :
- diagnostic CSS/JS ;
- conventions a formaliser ;
- refactors possibles a faible risque.

Note de reprise :

```text
Phase 4 terminee.
Fichiers CSS/JS audites :
- ...
Problemes de coherence :
- ...
Actions recommandees :
- ...
Risques residuels :
- ...
Phase suivante : Phase 5 - Donnees, Traductions Et Contenu.
```

## Phase 5 - Donnees, Traductions Et Contenu

But : verifier la coherence entre les sources Markdown, les YAML de traduction et les providers.

Perimetre :
- docs/pro_exp ;
- translations/*.yaml ;
- providers PHP ;
- duplication des contenus ;
- sources de verite ;
- risques d'incoherence FR/EN ;
- structure actuelle projets/experiences.

Questions principales :
- Quelle est la vraie source de verite ?
- Les YAML sont-ils maintenables ?
- Les providers devraient-ils lire des fichiers structures ?
- Les fichiers Markdown sont-ils une archive ou une source editoriale ?

Livrable :
- modele actuel des donnees ;
- incoherences eventuelles ;
- recommandations pour stabiliser le workflow editorial.

Note de reprise :

```text
Phase 5 terminee.
Sources analysees :
- ...
Incoherences reperees :
- ...
Source de verite recommandee :
- ...
Actions possibles :
- ...
Phase suivante : Phase 6 - Audit Specifique DnD Initiative Tracker.
```

## Phase 6 - Audit Specifique DnD Initiative Tracker

But : auditer l'outil DnD comme module applicatif separe.

Perimetre :
- controller et route ;
- templates lab/dnd ;
- scripts assets/scripts/lab/dnd ;
- CSS assets/styles/lab/dnd ;
- separation des responsabilites UI, domaine et etat ;
- maintenabilite du code JavaScript.

Questions principales :
- Le tracker est-il correctement isole du site vitrine ?
- La logique metier DnD est-elle separee de l'affichage ?
- Le code est-il pret pour evolution ?
- Y a-t-il des responsabilites mal placees ?

Livrable :
- diagnostic modulaire du tracker ;
- suggestions de decoupage ;
- risques techniques.

Note de reprise :

```text
Phase 6 terminee.
Module DnD audite :
- ...
Points forts :
- ...
Dette technique :
- ...
Refactors recommandes :
- ...
Phase suivante : Phase 7 - Preparation De La Partie Privee.
```

## Phase 7 - Preparation De La Partie Privee

But : proposer une structure cible pour les futurs modules prives.

Perimetre :
- namespace potentiel pour la partie privee ;
- routes privees ;
- securite et authentification future ;
- separation public, lab et prive ;
- organisation templates/assets ;
- conventions de modules ;
- services partages.

Questions principales :
- Comment eviter que la partie privee pollue la partie publique ?
- Comment structurer des outils personnels varies ?
- Faut-il creer des boundaries explicites Public, Lab et Private ?
- Ou placer les services partages ?

Livrable :
- proposition d'architecture cible ;
- conventions de dossiers ;
- plan de migration progressif si necessaire.

Note de reprise :

```text
Phase 7 terminee.
Architecture cible proposee :
- ...
Decisions a valider :
- ...
Migrations recommandees :
- ...
Risques :
- ...
Phase suivante : Phase 8 - Synthese Et Plan D'Action.
```

## Phase 8 - Synthese Et Plan D'Action

But : transformer l'audit en plan concret et priorise.

Livrable :
- resume des problemes ;
- priorisation par impact et risque ;
- quick wins ;
- refactors moyens ;
- decisions architecturales a prendre ;
- taches decoupees en tickets.

Format de synthese :

```text
Priorite 1 - A corriger bientot
- ...

Priorite 2 - A ameliorer progressivement
- ...

Priorite 3 - A garder en tete
- ...

Decisions ouvertes
- ...
```
