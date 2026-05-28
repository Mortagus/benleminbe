# Premier Outil Prive - Contacts Et Reseau - Analyse Du Besoin

Date de redaction : 2026-05-27

Ce document formalise la phase 0 du projet prive : comprendre le besoin avant de dessiner des ecrans ou de choisir une persistence.

## Problematique

Le besoin de depart est plus large que le simple suivi de conversations. Il y a trois problemes lies :

1. je disperse ma presence professionnelle sur plusieurs plateformes et je perds la vision d'ensemble ;
2. je disperse aussi mes contacts professionnels entre plusieurs sources ;
3. je manque d'un moyen simple et peu contraignant pour passer de l'intention a une prise de contact ou a une relance.

Aujourd'hui, une partie de cette information peut se retrouver dans :

- des notes dispersees ;
- des emails ;
- des messages LinkedIn ou autres plateformes ;
- la memoire ;
- des fichiers de suivi non centralises.

Le risque principal est de perdre le contexte entre deux prises de contact, de ne pas savoir ou j'existe reellement en ligne, ou de manquer le bon moment pour relancer.

## Vision Claire Des Plateformes

Je veux une liste exacte des plateformes sur lesquelles mon profil pro existe, avec un lien direct vers chaque profil.

Pour chaque plateforme, je veux aussi un statut lisible et rapide a comprendre, par exemple :

- complete ;
- a jour ;
- disponibilite visible ;
- a verifier ;
- a enrichir.

Je ne veux pas, pour chaque plateforme, une liste detaillee des actions manquantes dans cette phase du projet.

## Vision Claire Des Contacts

Je veux aussi reprendre le controle de ma liste de contacts professionnels.

Pour le moment, elle est dispersee entre :

- ma boite mail ;
- le repertoire de mon telephone sur deux profils differents ;
- mon reseau LinkedIn.

L'objectif est de pouvoir retrouver facilement des personnes selon plusieurs criteres, comme dans certaines fonctions premium de LinkedIn, mais en gardant la maitrise de mes donnees dans mon propre site.

## Friction D'Ajout Et D'Import

La priorite n'est pas seulement la consultation. Il faut aussi que l'ajout d'un nouveau contact soit rapide.

Je veux pouvoir :

- ajouter un contact avec un maximum d'automatisation ;
- utiliser la camera sur mobile pour scanner une carte de visite meme sans QR code ;
- capturer des donnees depuis un morceau de page web ou un contenu visible pour pre-remplir le formulaire ;
- importer des listes depuis des fichiers, car mes donnees sont deja reparties dans plusieurs formats et plusieurs endroits.

Le but est de construire le carnet d'adresse a partir de sources variees sans tout ressaisir manuellement.

## Objectif A Atteindre

L'outil doit m'aider a repondre rapidement a quatre questions :

1. qui dois-je recontacter en priorite ?
2. qu'avons-nous deja echange ?
3. quelle est la prochaine action utile ?
4. quelle opportunite ou relation demande de l'attention maintenant ?

Le but n'est pas de construire un CRM complet, mais un assistant personnel de suivi des relations utiles a la recherche de mission.

## Cible

La cible initiale est moi seul.

Le premier usage doit etre suffisamment simple pour etre utile au quotidien, sans supposer d'equipe, sans workflow complexe et sans administration lourde.

## Priorisation Et Action

Les contacts prioritaires sont d'abord les recruteurs, les ESN et les structures de recrutement en general.

La priorisation repose surtout sur :

- la pertinence metier ;
- la date du dernier contact enregistre ;
- le besoin de relance.

Le canal disponible reste une information utile a afficher, mais pas un critere determinant.

La notion de "chaleur du lien" peut servir plus tard comme indicateur simple de qualite de relation, mais elle ne doit pas compliquer le premier MVP.

La probabilite de retour est trop floue pour servir de base de travail immediate ; je prefere la laisser de cote tant que je n'ai pas un modele plus simple et plus fiable.

## Reseau Et Prospection

Dans un premier temps, l'outil doit :

- rassembler et uniformiser les contacts professionnels existants ;
- faciliter la relance des pistes froides ;
- garder la capacite de suivre les relations deja actives.

La recherche de nouveaux contacts peut venir plus tard.

## Ce Que L'Outil Doit Permettre

- centraliser les profils sur les plateformes professionnelles ;
- centraliser les contacts professionnels utiles ;
- conserver le contexte des echanges ;
- suivre les relances ;
- visualiser les relations a surveiller ;
- garder une trace de la prochaine action ;
- reduire le risque d'oublier un suivi important.

## Ce Que L'Outil Ne Doit Pas Devenir Tout De Suite

- un CRM generaliste complet ;
- un outil de prospection automatisee ;
- une plateforme collaborative ;
- un outil de reporting commercial complexe ;
- un module depend de services externes non indispensables ;
- une usine a gaz avant validation du besoin reel.

## Indicateurs De Reussite

Le besoin sera correctement adresse si, en pratique, je peux :

- voir rapidement les plateformes ou j'existe ;
- savoir si mes profils sont complets et a jour ;
- identifier les priorites du moment en quelques secondes ;
- retrouver le dernier contexte d'un contact sans fouiller dans mes messages ;
- ajouter un contact avec tres peu de friction ;
- savoir facilement qui relancer ;
- garder un historique lisible de la relation.

## Hypotheses De Travail

- la valeur viendra surtout de la lisibilite, de la centralisation et de la reduction de friction ;
- un premier socle tres reduit vaut mieux qu'un pseudo-CRM trop ambitieux ;
- le dashboard prive doit servir d'entree courte vers l'action utile ;
- il vaut mieux commencer par une base techniquement saine et extensible que par une automatisation excessive.

## MVP Recommande

Le premier MVP que je valide comme direction de travail doit couvrir, au minimum :

1. une liste des plateformes avec URL du profil et statut lisible ;
2. une liste de contacts centralisee ;
3. un dernier contact ou une date de dernier echange ;
4. un moyen simple de filtrer et rechercher ;
5. un ajout de contact le plus rapide possible ;
6. un debut d'import depuis fichiers ou sources externes.

## Questions A Trancher Plus Tard

- quelle persistence minimale est la plus adaptee au premier MVP ?
- quels formats d'import seront les plus rentables en premier ?
- quels criteres de statut doivent etre visibles pour les profils de plateforme ?
- comment epaissir progressivement la notion de priorite sans alourdir l'outil ?

## Conclusion De Phase 0

Le besoin a bien ete identifie : il s'agit d'un outil prive personnel pour garder la main sur les profils professionnels, les contacts, les relances et les opportunites.

La prochaine etape consiste a transformer cette analyse en specification fonctionnelle minimale, puis en premier ecran utile.
