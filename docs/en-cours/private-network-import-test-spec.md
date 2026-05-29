# Strategie De Test Pour L'Import Du Reseau Prive

Date de redaction : 2026-05-29

Ce document propose une strategie de test courte et ciblée pour l'import des contacts du reseau prive.

## Objectif

Valider les deux sources d'import réellement prises en charge :

- le fichier vCard du telephone ;
- le fichier CSV LinkedIn `Connections.csv`.

L'objectif n'est pas de re-tester les gros fichiers de production a chaque execution. Le but est de verifier le contrat fonctionnel minimal avec des fixtures courtes, lisibles et stables.

## Strategie Recommandee

### Tests Fonctionnels

Conserver deux tests fonctionnels distincts :

1. un test pour la source `phone_vcard` ;
2. un test pour la source `linkedin_connections_csv`.

Chaque test doit utiliser un contenu inline minimal, pas les gros fichiers reels.

### Remise A Zero

La remise a zero de la base est deja geree par [NetworkWebTestCase](../../tests/Functional/Private/NetworkWebTestCase.php).

Rien a changer cote strategie :

- les tables du reseau sont truncatees avant chaque test ;
- chaque test part donc d'un etat vierge ;
- il n'est pas utile de vider manuellement les tables dans chaque cas d'import.

## Fixtures Minimales

### 1. Import LinkedIn CSV

Champs minimaux a garder dans la fixture :

- `First Name`
- `Last Name`
- `URL`
- `Email Address`
- `Company`
- `Position`
- `Connected On`

Cas de validation :

- un contact est cree ;
- le `display_name` est compose correctement ;
- `organization` est rempli ;
- `role` est rempli ;
- `profile_url` est rempli ;
- `source_label` du log correspond a `linkedin_connections_csv`.

### 2. Import vCard Telephone

Champs minimaux a garder dans la fixture :

- `FN`
- `N`
- `TEL`
- `EMAIL`
- `ORG`
- `TITLE`
- `URL`

Cas de validation :

- un contact est cree ;
- le nom affiche est extrait correctement ;
- `organization` est rempli ;
- `role` est rempli ;
- `phone` est rempli ;
- `email` est rempli si present ;
- `source_label` du log correspond a `phone_vcard`.

## Ce Qu'On Ne Valide Pas Ici

- le contenu complet des fichiers reels ;
- la qualite exhaustive du mapping de tous les champs possibles ;
- les variations rares de vCard ou de CSV LinkedIn.

Ces cas peuvent etre valides a la main sur les vrais fichiers dans `var/private/network/` si besoin.

## Conclusion

La bonne approche pour l'outil d'import est :

- garder les tests automatiques courts ;
- couvrir les deux sources avec des fixtures minimales ;
- reserver les gros fichiers reels a la validation manuelle ou a un test ponctuel d'observation.
