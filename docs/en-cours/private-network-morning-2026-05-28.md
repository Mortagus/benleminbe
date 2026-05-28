# Suivi Du Chantier Prive Du 2026-05-28

Date de redaction : 2026-05-28

Ce document consigne le travail realise ce matin sur la zone privee et les outillages associes.

## Ce Qui A Ete Fait

- refonte du layout prive avec header persistant, navigation et theme switcher ;
- restructuration des pages privees en sections plus lisibles ;
- adaptation mobile et tablette des vues `contacts` et `platforms` ;
- remplacement de la liste des plateformes par des cartes plus lisibles ;
- simplification du rendu mobile des plateformes ;
- ajustement des fiches detail avec un `dl` en 50/50 ;
- ajout d’un bouton de copie discret pour le lien de plateforme ;
- decoupage du CSS prive en plusieurs fichiers plus petits ;
- extraction des scripts Bash du `makefile` vers `tools/private/` ;
- ajout du `login_throttling` sur le formulaire prive ;
- creation de la doc de deploiement et des checks prod ;
- ajout du smoke test prod dans `make deploy`.

## Etat Actuel

- la partie privee est utilisable sur desktop et mobile ;
- le deploiement est maintenant plus encadre ;
- les checks prod couvrent la partie privee et ses assets ;
- le login prive est protege contre le brut force par throttling.

## Prochain Point

Le prochain travail portera sur :

- remplir les donnees des plateformes ;
- analyser l’import des contacts depuis le telephone ;
- analyser l’import des contacts depuis le reseau LinkedIn ;
- definir la meilleure structure de donnee et de flux d’import pour ces deux sources.

## Note

Ce document sert de trace de reprise du chantier de ce matin.  
La documentation d’exploitation du `makefile` vit dans [Makefile Et Deploiement](../makefile.md), et la documentation stable de la zone privee reste dans [`docs/private/`](../private/README.md).
