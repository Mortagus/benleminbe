# P19 - Gestion Des Conditions Et Des Etats De Combat

Date de mise à jour : 2026-06-06

Statut : livré le 2026-06-06. Ce document conserve le cadrage fonctionnel de `P19` à titre historique.

## Objectif

Ajouter une gestion légère, lisible et maintenable des conditions et des états de combat dans l'ordre du tour du `DnD Initiative Tracker`, sans transformer l'outil en moteur automatique de règles D&D.

## Contrat Fonctionnel

- catalogue fixe de conditions D&D courantes ;
- distinction explicite entre conditions temporaires et états de combat / états vitaux ;
- affichage des conditions actives et de l'état vital sur chaque carte de l'ordre du tour ;
- durée optionnelle en rounds pour les conditions temporaires ;
- décrément automatique uniquement au changement de round ;
- retrait manuel possible à tout moment ;
- état de combat manuel séparé des PV et sans automatisme entre PV et statut vital ;
- gestion spéciale de `Épuisement` avec niveau de `1` à `6` ;
- persistance cohérente avec les snapshots locaux existants ;
- compatibilité avec monstres et joueurs présents dans l'ordre du tour.

## Travail Livré

- ajout d'un module dédié aux conditions et aux états de combat ;
- normalisation douce des acteurs anciens lors du chargement d'un snapshot ;
- synchronisation des conditions et des états vitaux entre l'état de rencontre et l'ordre du tour ;
- éditeur compact de conditions sur chaque carte de l'ordre du tour ;
- affichage visuel lisible des badges de conditions et d'états de combat ;
- décrément des conditions à durée limitée au `Nouveau round` uniquement ;
- feedback discret pour les ajouts, les retraits et les expirations ;
- tests Vitest pour le parsing, les mutations métier, la persistance et l'intégration UI.

## Points De Vigilance

- ne pas transformer ce socle en moteur de règles D&D automatique ;
- ne pas mélanger conditions temporaires et états vitaux dans la même structure ;
- ne pas faire dépendre les états de combat des PV courants ;
- ne pas décrémenter les durées sur `Acteur suivant` ou `Réinitialiser les tours de ce round`.

## Vérification

```bash
make check
```
