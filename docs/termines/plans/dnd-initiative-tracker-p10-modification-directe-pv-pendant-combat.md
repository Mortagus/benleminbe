# P10 - Modification Directe Des PV Pendant Le Combat

Date de mise à jour : 2026-06-06

Statut : livré le 2026-06-06. Ce document conserve le cadrage fonctionnel de `P10` à titre historique.

## Objectif

Permettre au Maître du Jeu de modifier rapidement les PV d'un acteur directement depuis l'ordre du tour, sans sortir du rythme du combat.

## Contrat Fonctionnel

- formats acceptés : `-N`, `+N` et `N` ;
- tolérance des espaces autour de la saisie ;
- bornage systématique entre `0` et les PV max ;
- refus des saisies ambiguës ou non supportées ;
- aucun effet sur le round courant, l'acteur actif, l'état joué/non joué ou l'ordre du tour ;
- persistance cohérente avec la sauvegarde locale existante ;
- compatibilité avec monstres et joueurs présents dans l'ordre du tour.

## Travail Livré

- ajout d'un module dédié au parsing des changements de PV ;
- ajout d'un éditeur compact de PV sur chaque carte de l'ordre du tour ;
- centralisation des mutations métier dans `EncounterState` ;
- synchronisation des copies de PV déjà présentes dans l'ordre du tour ;
- feedback discret en cas de saisie invalide ;
- tests Vitest pour le parsing, les bornes, les cas invalides et l'absence d'effet secondaire ;
- vérification complète du module après implémentation.

## Points De Vigilance

- ne pas introduire de parsing de dés ou d'expressions plus complexes ;
- ne pas mélanger la logique métier de calcul des PV avec les handlers DOM ;
- ne pas modifier les commandes de combat déjà livrées dans `P7`.

## Vérification

```bash
make check
```
