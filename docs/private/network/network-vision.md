# Vision Du Module Contacts Et Reseau

Date de redaction : 2026-05-27

Ce document decrit le premier outil prive envisage pour `benlemin.be`.

Le sujet reste volontairement separe du protocole PageSpeed.

L'analyse du besoin et de l'objectif est decrite dans [Analyse du besoin](network-besoin-analysis.md).

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

Ce projet est maintenant un premier socle prive concret, branche sur Doctrine et MariaDB 10.11.16.

Etat fonctionnel actuel :

- dashboard prive `/private/network` en place ;
- le bloc "Contacts prioritaires" affiche uniquement des contacts avec organisation appartenant aux categories recrutement/RH et developpement/technique, en mettant d'abord les contacts jamais contactes puis les plus anciens, avec la priorite comme critere secondaire ;
- le bloc "Activite recente" du dashboard affiche maintenant le contact concerne, son entreprise et son role quand ces informations existent, avec des fallback propres quand elles manquent ;
- pages de listing et de fiche pour les plateformes et les contacts ;
- creation et edition des plateformes ;
- export et import JSON des plateformes via la page de liste ;
- creation et edition des contacts ;
- ajout d'interactions sur les fiches contact ;
- preparation rapide d'un contact avec prompt de redaction copiable, canal recommande et marquage local du suivi ;
- import vCard du telephone et CSV LinkedIn via l'interface privee ;
- persistance relationnelle active avec tables, migration et seed initial des plateformes depuis un snapshot JSON versionne ;
- tests fonctionnels PHPUnit / WebTestCase en place pour les parcours principaux, dont l'import.

Etat d'organisation :

- `Organization` reste un champ du contact ;
- `Platform` et le profil de plateforme restent fusionnes ;
- `ImportLog` sert de journal minimal ;
- le backup de reference des plateformes vit dans `data/private/network/platforms.json` ;
- `tags` restent en JSON ;
- les statuts sont formalises en enums.

La prochaine priorite est la mise en page de l'outil prive. La fonctionnalite est suffisante pour servir de base, mais l'interface reste trop brute et doit etre habillee avec une meilleure hierarchy visuelle, une grille plus lisible et un traitement plus soigne des formulaires, des tableaux et des cartes.

## Règles Métier Détaillées

Les règles métier stables du module sont documentées séparément pour éviter de mélanger vision, spec et logique de comparaison:

- [Regles de comparaison et de score](merge-review-scoring-rules.md)
- [Regles de matching](contact-write-matching-rules.md)
- [Regles d'auto-fusion](contact-auto-merge-rules.md)

## Ce Qu'il Faut Retenir

- le module n'est pas un CRM généraliste ;
- il sert de base de stockage et d'outil opérationnel pour reprendre contact ;
- il réduit la friction entre "j'ai un contact" et "je peux agir dessus" ;
- il reste volontairement simple pour évoluer sans rearchitecture lourde.
