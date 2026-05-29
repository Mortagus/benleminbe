# Audit Du Module Network

Date de redaction : 2026-05-29

Ce document consigne un audit structurel du module `Network`.  
L'objectif est de reduire la complexite, supprimer le code mort probable et preparer un refactor cible sans casser le comportement actuel.

Le refactor vise aussi a rester aligne sur le style du projet: services fins orientes cas d'usage, repositories lisibles, conventions deja en place et pas d'abstraction generale inutile.

## Constat General

Le module fonctionne, mais il a atteint une taille qui rend la maintenance fragile.

Note de contexte: le refactor structurel du module a maintenant ete largement applique.  
Les controllers sont repartis par flux, les services ont ete decoupes, et `email` / `phone` sont des listes multi-valeurs.  
La suite du chantier porte surtout sur le reglage fin des heuristiques de fusion automatique et sur la baisse du volume de doublons difficiles.

Deux classes concentrent une part trop importante de la logique:

- [NetworkRepository](../../src/Private/Service/Network/NetworkRepository.php) : 1921 lignes ;
- [ContactMergeReviewService](../../src/Private/Service/Network/ContactMergeReviewService.php) : 1386 lignes.

Le probleme principal n'est pas seulement la longueur. C'est surtout le melange de responsabilites:

- chargement et restitution des donnees ;
- normalisation et fusion ;
- import ;
- auto-merge ;
- revue manuelle des doublons ;
- decoration des donnees pour l'UI ;
- maintenance destructive globale.

## Points De Vigilance

### 1. Normalisation et fusion dupliquees

Plusieurs regles existent dans les deux classes, avec des variantes qui ne sont pas exactement identiques selon le chemin d'execution.

Exemples:

- normalisation d'organisation dans [NetworkRepository](../../src/Private/Service/Network/NetworkRepository.php) et [ContactMergeReviewService](../../src/Private/Service/Network/ContactMergeReviewService.php) ;
- normalisation d'URL ;
- fusion de `source` ;
- traitement de `main_channel` ;
- comparaison de champs pour la detection des doublons.

Effet concret:

- deux chemins proches peuvent produire des resultats differents ;
- les regles de l'auto-merge et celles de la revue manuelle risquent de diverger dans le temps ;
- les corrections de comportement doivent aujourd'hui etre appliquees a plusieurs endroits.
- une extraction partielle laisserait subsister des differences de comportement difficiles a voir dans les tests ;

### 2. `ContactMergeReviewService` fait trop de choses

Cette classe gere a la fois:

- la generation de candidats ;
- le scoring ;
- le rendu des donnees pour la page de revue ;
- la normalisation des choix de fusion ;
- l'application finale de la fusion ;
- l'ignorance d'un doublon ;
- la purge des revues `pending` ;
- le reset global via proxy vers le repository.

Cela empeche de lire la classe comme une seule responsabilite metier.

### 3. `NetworkRepository` joue le role de depot et de service metier

Le nom `Repository` ne reflète plus la réalité.

La classe contient:

- CRUD ;
- decoration de vues ;
- filtrage ;
- normalisation ;
- logique de fusion automatique ;
- logique d'import ;
- gestion des interactions ;
- reset complet des donnees reseau.

Autrement dit, le repository est devenu un service applicatif generaliste.  
Ce n'est pas faux fonctionnellement, mais c'est difficile a maintenir.

### 4. Code mort probable

J'ai relevé des methodes qui semblent n'avoir aucun appel local trouve dans le depot:

- `buildCandidatePairData()` dans [ContactMergeReviewService](../../src/Private/Service/Network/ContactMergeReviewService.php) ;

Il y a aussi des methodes publiques qui ne semblent pas consommees localement et meritent verification:

- `getFieldChoiceLabels()`
- `getFieldDefinitions()`

Si elles ne servent a rien en dehors du service, elles doivent soit etre supprimees, soit devenir des dependances explicites de l'UI ou des tests.

### 5. La detection reste quadratique

La generation des candidats pour la revue manuelle compare toutes les paires de contacts.  
C'est donc du O(n^2) sur ce chemin, ce qui reste acceptable pour l'instant mais ne scale pas proprement si le volume continue de monter.

Le cout principal est dans:

- [ContactMergeReviewService](../../src/Private/Service/Network/ContactMergeReviewService.php) pour la revue manuelle ;
- [NetworkRepository](../../src/Private/Service/Network/NetworkRepository.php) pour l'auto-merge et ses sous-cas de comparaison par paires sur les contacts peu renseignes.

## Recommandation De Refactor

### Lot 1 - Extraire les regles communes

Creer un service dedie pour les regles partagées de normalisation et de fusion.

Responsabilites candidates:

- normalisation comparable ;
- normalisation des organisations ;
- normalisation des URLs ;
- normalisation des telephones ;
- fusion de `source` ;
- regle `main_channel` ;
- fusion de dates, tags, notes et priorites ;
- regles de comparaison reutilisees par l'auto-merge et la revue manuelle.

Objectif:

- une seule definition des regles métier ;
- moins de divergences entre auto-merge et revue manuelle ;
- tests unitaires cibles plus simples.

### Lot 2 - Decomposer la revue des doublons

Couper `ContactMergeReviewService` en plusieurs blocs:

- generation et scoring des candidats ;
- lecture / decoration des donnees de revue ;
- commande de resolution ;
- maintenance de la file (`purge`, `reset`, `refresh`).

Objectif:

- faire baisser la taille de la classe ;
- clarifier la frontiere entre calcul et action ;
- permettre de tester chaque partie independamment.

### Lot 3 - Simplifier `NetworkRepository`

Extraire ce qui n'est pas du stockage pur:

- import de contacts et application des payloads ;
- gestion des interactions ;
- reset global ;
- auto-merge ;
- decoration de listes ;
- utilitaires de comparaison.

Objectif:

- rendre le repository plus proche de son nom ;
- isoler la logique applicative dans des services dedies.

### Lot 4 - Nettoyer le code mort

Supprimer ou relier explicitement:

- `buildCandidatePairData()` ;
- les methodes publiques non consommees ;
- les wrappers redondants si un service parent fait deja le travail.

Objectif:

- reduire la surface inutile ;
- limiter les faux signaux pour la maintenance future.

## Ordre De Travaux Recommande

1. Extraire un service commun de normalisation, fusion et comparaison.
2. Rebrancher auto-merge, import et revue manuelle sur ce service commun.
3. Extraire les flux applicatifs hors de `NetworkRepository` puis decouper `ContactMergeReviewService`.
4. Nettoyer le code mort.
5. Ajouter ou ajuster les tests autour des regles centralisees.

## Risque Si On Ne Fait Rien

- la divergence des regles va continuer ;
- chaque correction demandera plusieurs modifications au lieu d'une seule ;
- les gros services deviendront plus difficiles a relire et a tester ;
- la detection des doublons restera plus lente que necessaire.

## Conclusion

Le module `Network` est fonctionnel, mais sa structure a besoin d'etre resserree.  
La bonne strategie n'est pas de tout casser, mais de centraliser d'abord les regles communes, puis de separer les responsabilités les plus volumineuses sans introduire de couche generique inutile ni de changement de contrat non couvert.
