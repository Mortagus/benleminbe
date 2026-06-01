# Prochaines Etapes De La Zone Privee

Date de redaction : 2026-05-20

Ce document sert de note de reprise apres le lot 5.

Le lot 5 a pose la fondation de la zone privee : Symfony Security, login/logout, layout prive, dashboard minimal, entrypoint assets prive, protection robots/noindex et gestion recommandee de `PRIVATE_ADMIN_PASSWORD_HASH`.

Le premier module metier retenu pour la suite est l'outil prive "Contacts et reseau", accessible depuis le dashboard via `/private/network`.
La phase 0 de ce projet est documentee dans [Analyse du besoin](network/network-besoin-analysis.md).

## Validation Production - 2026-05-21

La mise en production du socle prive a ete validee le 2026-05-21 sur l'hebergement Infomaniak.

Les details de la procedure de deploiement, des checks prod et des cibles `make` associees sont maintenant documentes dans [Deploiement et verification](../deployment-and-verification.md).

Resultat de ce jalon :

- le socle prive a ete valide en production ;
- le secret admin est gere via Symfony Secrets ;
- les checks prod existent cote `make` ;
- les prochaines evolutions peuvent se concentrer sur le premier vrai besoin metier prive.

## Note De Reprise

La zone privee est stable et le prochain travail doit surtout porter sur l'evolution metier de l'outil `Contacts et reseau`.

Les details d'exploitation, de deploiement et de verification prod doivent désormais vivre dans [Deploiement et verification](../deployment-and-verification.md) et non ici.
