# Presentation Du Module Contacts Et Reseau

Date de redaction : 2026-05-30

Ce document sert de reference courte et lisible pour presenter l'etat actuel du module prive `Contacts et reseau`.

Il ne remplace pas la specification MVP ni l'overview. Il resume uniquement les fonctionnalites effectivement mises en place dans le code aujourd'hui.

## Vision

Le module a pour but de centraliser mes contacts professionnels, les plateformes ou je suis present, les doublons a traiter, les interactions recemment echangees et les actions de reprise de contact.

L'objectif n'est pas de construire un CRM complet. L'outil doit surtout:

- reduire la friction d'ajout et de recherche ;
- aider a choisir qui recontacter ;
- garder la trace du contexte utile ;
- permettre une remise en etat rapide apres import ou reset de base.

## Fonctionnalites En Place

### 1. Dashboard Prive

- page d'entree du module sur `/private/network` ;
- cartes de statistiques sur les plateformes et les contacts ;
- synthese des priorites et des relances ;
- apercu des plateformes a suivre ;
- apercu des contacts prioritaires ;
- regroupement des organisations suivies ;
- activité recente sous forme d'interactions ;
- imports recents ;
- raccourcis directs vers l'ajout de contact, l'ajout de plateforme et l'import.

### 2. Gestion Des Plateformes

- liste des plateformes avec lien direct vers chaque fiche ;
- creation d'une plateforme ;
- edition d'une plateforme ;
- fiche detaillee d'une plateforme ;
- statut lisible par plateforme ;
- recherche dans la liste ;
- export JSON des plateformes ;
- import JSON des plateformes ;
- sauvegarde versionnee de reference dans `data/private/network/platforms.json` ;
- bouton de copie du lien de plateforme sur la fiche detaillee ;
- date de derniere verification ;
- note courte et activation ou desactivation.

### 3. Gestion Des Contacts

- liste centrale des contacts ;
- fiche detaillee d'un contact ;
- creation d'un contact ;
- edition d'un contact ;
- suppression d'un contact ;
- pagination complete avec page precedente, page suivante et acces direct par numero ;
- index annuaire alphabétique A-Z pour filtrer par premiere lettre du nom ;
- recherche textuelle ;
- filtrage par priorite ;
- filtrage par etat de relation ;
- filtrage par presence d'entreprise ;
- filtrage par presence de role ;
- filtrage par role exact ;
- filtrage par categorie de role ;
- tri par defaut ;
- tri par entreprise ;
- affichage du role categorise sans perdre le role brut ;
- affichage des badges de priorite et de relation dans la liste ;
- regroupement et normalisation derivée des entreprises pour faciliter la lecture.

### 4. Deduplication Et Fusion

- detection automatique des doublons ;
- file de revue manuelle des doublons ;
- purge des candidats en attente ;
- reinitialisation complete des donnees reseau ;
- fusion automatique des doublons detectes ;
- fusion manuelle champ par champ ;
- choix du contact canonique ;
- conservation des interactions lors d'une fusion ;
- priorisation LinkedIn dans les conflits pertinents ;
- interface de revue avec score, raisons et detail des champs.

### 5. Import Des Contacts

- import depuis vCard du telephone ;
- import depuis CSV LinkedIn ;
- journalisation des imports ;
- affichage des imports recents sur le dashboard ;
- tracabilite minimale de la source d'origine.

### 6. Prise De Contact Rapide

- bouton de preparation de contact sur la liste et la fiche contact ;
- panneau modal local, sans envoi automatique ;
- resume du contact avec nom, role, entreprise et categorie calculee ;
- canal recommande calcule de maniere deterministe ;
- message de prise de contact pre-rempli et editable avant copie ;
- ouverture directe de LinkedIn si disponible ;
- ouverture d'un email pre-rempli si disponible ;
- ouverture d'un appel telephone si disponible ;
- action locale de marquage comme contacté ;
- mise a jour du statut relationnel et de la date de dernier contact.

### 7. Aides A La Lecture Et A La Presentation

- affichage d'une categorie metier derivee a partir du role brut ;
- support de filtres et de tris persistants dans les actions de la page ;
- affichage d'un nombre important d'indicateurs sur les fiches et les badges ;
- support de backup transportable pour les plateformes ;
- test automatises sur les parcours principaux.

## Donnees Et Principes De Fonctionnement

- les contenus publics du site ne viennent pas de ce module ;
- le module `network` fonctionne sur Doctrine et MariaDB ;
- les contacts gardent leur role brut ;
- les categories de role sont calculees, pas stockees comme verite source ;
- les plateformes utilisent un snapshot JSON versionne comme support de backup et de restauration ;
- les interactions sont journalisees comme historique minimal ;
- les imports et les doublons sont conserves comme traces d'activite ;
- la logique privilegie des regles simples, lisibles et testables.

## Ce Qu'Il Faut Retenir Pour La Presentation

- le module est deja utilisable au quotidien ;
- il sert a la fois de base de stockage et d'outil operationnel de reprise de contact ;
- il reduit fortement la friction entre "j'ai un contact" et "je peux agir dessus" ;
- il garde un format simple pour evoluer ensuite sans rearchitecture lourde.
