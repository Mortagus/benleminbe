# Premier Outil Prive - Contacts Et Reseau - Specification MVP

Date de redaction : 2026-05-27

Ce document transforme l'analyse du besoin en specification minimale pour un premier MVP techniquement sain, extensible et utile.

## Intention Produit

L'outil doit servir de base privee pour :

- centraliser les plateformes professionnelles ou un profil existe ;
- centraliser les contacts professionnels ;
- reduire la friction d'ajout et d'import ;
- faciliter la recherche, le tri et la relance ;
- garder une base technique propre pour construire des evolutions par-dessus.

Le premier MVP ne cherche pas a etre complet. Il cherche a etre fiable, simple a utiliser et simple a faire grandir.

## Perimetre Fonctionnel

### Inclus

- inventaire des plateformes professionnelles ;
- lien direct vers chaque profil ;
- statut lisible par plateforme ;
- liste centrale des contacts ;
- fiche contact simple ;
- recherche et filtres essentiels ;
- dernier contact ou derniere interaction ;
- signal de priorite ;
- ajout rapide de contact ;
- import initial depuis fichiers ou sources externes ;
- base pour suivre les relances.

### Exclu Pour Le Premier MVP

- automatisation agressive de prospection ;
- moteur de scoring complexe ;
- segmentation commerciale avancee ;
- workflow d'equipe ;
- synchronisation temps reel avec des services tiers ;
- enrichment automatique trop ambitieux ;
- historique conversationnel complet.

## Entites Principales

### Plateforme

Represente un service externe ou un profil pro existe.

Champs minimum :

- nom ;
- slug ;
- type de plateforme ;
- URL du profil ;
- statut ;
- note courte ;
- priorite ou importance ;
- date de derniere verification ;
- actif ou inactif.

### Profil De Plateforme

Represente mon profil sur une plateforme donnee.

Champs minimum :

- plateforme associee ;
- URL du profil ;
- statut lisible ;
- date de mise a jour ;
- note rapide ;
- disponibilite visible ou non ;
- remarque courte.

Remarque :

- dans le premier jet, la notion de plateforme et celle de profil peuvent etre fusionnees si cela simplifie la premiere version ;
- le modele doit rester suffisamment souple pour les separer plus tard si besoin.

### Contact

Represente une personne a suivre.

Champs minimum :

- nom affiche ;
- prenom ;
- nom de famille ;
- entreprise ;
- role ou fonction ;
- canal principal ;
- email ;
- telephone ;
- lien LinkedIn ou autre URL utile ;
- source d'origine ;
- niveau de priorite ;
- date du dernier contact ;
- prochaine action ;
- statut relationnel ;
- notes courtes.

### Organisation

Represente une entreprise, ESN ou structure de recrutement.

Champs minimum :

- nom ;
- type d'organisation ;
- site web ;
- secteur ;
- note courte ;
- importance strategique ;
- liens vers les contacts associes.

### Interaction

Represente une prise de contact, un echange ou une relance.

Champs minimum :

- contact associe ;
- date ;
- canal ;
- resume court ;
- resultat ;
- prochaine action ;
- date de prochaine relance.

### Import Source

Represente la provenance des donnees importees.

Champs minimum :

- type de source ;
- nom du fichier ou de la source ;
- date d'import ;
- nombre d'entrees traitees ;
- nombre d'entrees creees ou mises a jour ;
- erreurs eventuelles.

## Regles De Priorite

La priorite du MVP doit etre simple.

Le premier tri se base sur :

1. la pertinence metier ;
2. la date du dernier contact ;
3. le besoin de relance ;
4. l'urgence eventuelle.

La chaleur du lien peut devenir un indicateur futur, mais elle ne doit pas bloquer la premiere version.

La probabilite de retour est ignoree pour le MVP.

## Recherche Et Filtres

Le MVP doit permettre de retrouver rapidement :

- un contact par nom ;
- une entreprise ;
- une plateforme ;
- une note ou un tag ;
- les contacts sans interaction recente ;
- les contacts a forte priorite ;
- les contacts issus d'un import donne.

Filtres utiles en premiere version :

- priorite ;
- date du dernier contact ;
- source ;
- organisation ;
- statut relationnel ;
- plateforme associee.

## Ajout Et Import

L'ajout doit minimiser la saisie manuelle.

### Cas D'Usage Ajout Rapide

- creation manuelle d'un contact depuis un formulaire court ;
- saisie rapide depuis mobile ;
- pre-remplissage via capture d'image ou de contenu ;
- import depuis CSV, XLSX ou format equivalent ;
- import depuis une liste exportee ou collectee ailleurs.

### Objectif Technique

Le flux d'ajout doit permettre, autant que possible :

- de pre-remplir les champs a partir d'une source ;
- de corriger ensuite manuellement les valeurs si necessaire ;
- de conserver la trace de la source d'origine ;
- d'eviter la double saisie.

## Ecrans MVP

### 1. Dashboard Prive

Role :

- entree principale de l'outil ;
- acces rapide a la liste des contacts ;
- acces rapide aux plateformes ;
- acces rapide aux ajouts et imports.

Contenu minimum :

- synthese des priorites ;
- raccourcis vers la liste des contacts ;
- raccourcis vers les plateformes ;
- bouton d'ajout rapide.

### 2. Liste Des Plateformes

Role :

- visualiser les plateformes ou un profil existe ;
- voir leur statut en un coup d'oeil ;
- ouvrir le lien direct vers chaque profil.

### 3. Liste Des Contacts

Role :

- consulter le carnet centralise ;
- filtrer ;
- trier ;
- identifier rapidement les priorites.

### 4. Fiche Contact

Role :

- voir le contexte utile ;
- voir l'historique minimal ;
- voir la prochaine action ;
- creer ou modifier un contact.

### 5. Ajout Rapide

Role :

- creer un contact avec le minimum de friction ;
- accepter une saisie partielle ;
- permettre une correction ensuite.

### 6. Import

Role :

- importer des listes externes ;
- verifier le resultat ;
- detecter les doublons ;
- garder une trace de la source.

## Flux Principaux

### Flux 1 - Ajouter Un Contact

1. ouvrir le formulaire rapide ;
2. pre-remplir si une source existe ;
3. corriger ou completer ;
4. enregistrer ;
5. creer une interaction initiale si utile.

### Flux 2 - Reprendre Une Relance

1. filtrer les contacts prioritaires ;
2. trier par dernier contact ;
3. ouvrir la fiche contact ;
4. verifier le contexte ;
5. noter la prochaine action ;
6. planifier la relance.

### Flux 3 - Gerer Une Plateforme

1. consulter la liste des plateformes ;
2. ouvrir le profil cible ;
3. verifier le statut ;
4. ajuster le statut si besoin ;
5. conserver la derniere verification.

## Exigences Non Fonctionnelles

- structure de donnees simple et extensible ;
- routes et templates clairs ;
- pas de couplage inutile avec des services externes ;
- base lisible et maintenable ;
- compatibilite avec une evolution progressive ;
- pas de complexite additive avant valeur concrete.

## Etat Technique Initial

Le premier jet implementé suit une approche volontairement legere :

- snapshot JSON versionne dans `data/private/network/platforms.json` pour le seed, l'export et la restauration des plateformes ;
- dashboard prive sur `/private/network` ;
- listes et fiches pour les plateformes et les contacts ;
- edition par formulaires simples ;
- journalisation des interactions par contact ;
- import de contacts depuis vCard du telephone ou CSV LinkedIn ;
- export et import JSON des plateformes depuis la page de listing ;
- modele organisationnel reduit pour l'instant a un champ `organization` sur le contact, avec regroupement derive pour le dashboard.

Ce choix garde un support simple a diff et a transporter entre environnements tout en laissant la base de donnees comme source operationnelle des donnees actives.

## Ordre De Construction Recommande

1. definir le modele de donnees minimal ;
2. poser les routes et ecrans de lecture ;
3. brancher l'ajout manuel ;
4. brancher la recherche et les filtres ;
5. ajouter l'import ;
6. ajouter les indicateurs de priorite et les relances ;
7. affiner les statuts de plateforme et de relation.

## Points Encore A Preciser

- quel format d'import sera prioritaire en premier ;
- comment representer les doublons ;
- quelle granularite exacte adopter pour le statut de plateforme ;
- quels champs doivent etre obligatoires a la creation ;
- quel niveau d'edition rapide doit etre disponible depuis la liste.

## Conclusion

Le MVP doit rester volontairement petit mais deja utile.

La meilleure premiere version est un noyau prive qui :

- centralise les profils et les contacts ;
- permet de retrouver rapidement qui relancer ;
- limite la friction d'ajout ;
- garde une architecture propre pour ajouter ensuite de l'automatisation plus ambitieuse.
