# Premier Outil Prive - Contacts Et Reseau

Date de redaction : 2026-05-27

Ce document decrit le premier outil prive envisage pour `benlemin.be`.

Le sujet reste volontairement separe du protocole PageSpeed.

L'analyse du besoin et de l'objectif est decrite dans [Premier Outil Prive - Contacts Et Reseau - Analyse Du Besoin](premier-outil-prive-contacts-reseau-analyse-besoin.md).

## Objectif

Construire un outil prive pour mieux suivre mes contacts professionnels, mon reseau et mes demarches pour trouver une mission.

## Intention

L'outil doit aider a :

- centraliser les contacts ;
- garder la trace des echanges ;
- suivre les relances ;
- visualiser les opportunites en cours ;
- eviter que les informations restent dispersees entre notes, mails et memoire.

## Hypothese De MVP

Le premier MVP peut rester simple :

- une page dashboard avec les indicateurs utiles ;
- une liste de contacts ;
- une fiche contact ;
- des notes d'echange ;
- un statut ou un cycle de relation ;
- des rappels ou prochaines actions ;
- une recherche simple.

## Donnees Probables

- personne de contact ;
- societe ;
- role ou contexte ;
- canal d'origine ;
- date du dernier echange ;
- prochaine relance ;
- tags ;
- notes libres ;
- niveau de priorite ;
- etat de la relation.

## Points D'Attention

- la zone privee actuelle est volontairement minimale ;
- ce projet poussera probablement vers une vraie persistance ;
- le modele de donnees doit rester assez simple pour etre utile rapidement ;
- il faut eviter de construire un CRM trop large avant d'avoir confirme le besoin reel.

## Ordre De Construction Recommande

1. cadrer le modele de donnees minimum ;
2. definir le dashboard prive ;
3. choisir le niveau de persistance ;
4. brancher les flux de creation, modification et recherche ;
5. ajouter les aides operationnelles utiles au suivi des demarches.

## Etat Actuel

Ce projet passe maintenant du stade de perspective au stade de premier socle prive concret.

La premiere brique retenue est une route dediee `/private/network`, accessible depuis le dashboard prive. Le but reste de garder l'outil simple au debut, avec une progression par petites etapes plutot qu'un CRM complet d'emblee.
