# Reprise Du Chantier Prive Du 2026-05-29

Date de redaction : 2026-05-29

Ce document sert de point de reprise pour la prochaine session sur le module reseau prive.

## Etat Actuel

- les imports fonctionnent correctement sur les deux sources supportees ;
- `email` et `phone` sont passes en listes multi-valeurs ;
- la fusion facile continue de fonctionner ;
- la detection des candidats doublons est en place ;
- il reste actuellement 774 cas de fusion difficiles a traiter.

## Ce Qui Est Deja En Place

- le module `Private/Network` est decoupe par flux ;
- la politique commune d'ecriture des contacts est centralisee ;
- la revue de doublons affiche les differences intra-chaîne de facon visible ;
- les tests de non regression couvrent le pipeline d'import, la fusion et la revue.

## Prochaine Etape

Le prochain chantier consiste a avancer sur les cas de fusion automatique afin de reduire le volume des candidats difficiles.

Objectif cible si possible :

- passer sous les 500 doublons difficiles ;
- conserver les cas ambigus en revue manuelle ;
- privilegier des regles simples, testees, et alignees avec le style du projet.

## Ordre De Travail Recommande

1. Prendre un lot de candidats auto-traitables et repérer les motifs repetes.
2. Identifier les faux positifs et les regles manquantes.
3. Ajouter une regle ou un ajustement de score a la fois.
4. Ajouter un test cible avant ou pendant chaque changement.
5. Rejouer import + auto-merge + detection des candidats pour mesurer l'impact.

## Note

Le document d'audit structurel reste dans [network-module-audit.md](network-module-audit.md).  
La strategie d'import reste dans [private-network-import-test-spec.md](private-network-import-test-spec.md).
