# Backlog unifié — Module de suivi de musculation

Date de création : 2026-07-24
Statut : cadrage initial
Domaine : zone privée
Nom de travail du module : `Workout`

---

## 1. Objectif du document

Ce document centralise les fonctionnalités, décisions, lots d’implémentation et évolutions possibles du futur module privé de suivi sportif de `benlemin.be`.

Il doit servir de :

* backlog fonctionnel ;
* feuille de route d’implémentation ;
* fil de reprise entre les sessions de développement ;
* référence pour découper les demandes adressées à Codex ;
* support de validation après chaque lot.

Ce document n’a pas vocation à remplacer les futures références stables du module.

Les règles métier et décisions stabilisées devront progressivement être déplacées dans des documents dédiés, par exemple :

```text
docs/private/workout/workout-index.md
docs/private/workout/workout-vision.md
docs/private/workout/workout-domain-model.md
docs/private/workout/workout-mvp-specification.md
docs/private/workout/workout-progression-rules.md
docs/private/workout/workout-replacement-rules.md
```

Le présent fichier doit rester le document de suivi actif tant que le chantier n’est pas terminé.

---

## 2. Vision générale

Le module doit permettre de :

1. préparer un programme sportif ;
2. consulter facilement la séance prévue pour le jour courant ;
3. démarrer cette séance depuis un téléphone ;
4. enregistrer rapidement les séries réellement effectuées ;
5. adapter ponctuellement la séance sans modifier le programme permanent ;
6. conserver l’historique réel des entraînements ;
7. visualiser l’évolution des performances ;
8. noter facultativement les sensations, difficultés ou observations ;
9. proposer des alternatives cohérentes lorsqu’un exercice ne convient pas ce jour-là.

L’objectif principal n’est pas de construire un logiciel générique de coaching sportif.

Le module doit d’abord être un outil personnel, simple, fiable et agréable à utiliser pendant une vraie séance.

---

## 3. Principes directeurs

### 3.1 Usage mobile prioritaire

La saisie pendant une séance doit être conçue d’abord pour un téléphone.

Les interactions principales doivent respecter les contraintes suivantes :

* grands contrôles tactiles ;
* peu de texte inutile ;
* peu de navigation entre les pages ;
* utilisation possible avec une seule main ;
* validation rapide d’une série ;
* valeurs précédentes préremplies ;
* sauvegarde automatique ;
* aucune perte de données en cas de rafraîchissement accidentel ;
* visibilité immédiate de l’exercice courant ;
* accès rapide à l’exercice suivant.

### 3.2 Séparer le prévu du réalisé

Le programme représente ce qui est prévu.

La séance représente ce qui a réellement été effectué.

Une séance démarrée depuis un modèle doit en conserver un instantané indépendant.

Une modification ultérieure du programme ne doit jamais modifier rétroactivement l’historique des séances réalisées.

### 3.3 Les adaptations ponctuelles ne modifient pas le programme

Les actions suivantes doivent pouvoir être appliquées uniquement à la séance courante :

* remplacer un exercice ;
* retirer un exercice ;
* ajouter un exercice ;
* réduire le nombre de séries ;
* modifier l’ordre ;
* choisir une version courte ;
* sélectionner une autre séance.

La modification du programme permanent doit être une action distincte et explicite.

### 3.4 Saisie facultative des impressions

Les notes et ressentis doivent être disponibles sans devenir obligatoires.

La validation d’une série, d’un exercice ou d’une séance ne doit jamais être bloquée parce qu’aucune impression n’a été encodée.

### 3.5 Transparence des suggestions

Les suggestions de remplacement ou de progression doivent reposer sur des règles identifiables.

Le système ne doit pas présenter une proposition comme intelligente ou optimale sans pouvoir expliquer son origine.

### 3.6 Pas de recommandations médicales

Le module peut enregistrer et afficher :

* une douleur ;
* une gêne ;
* une fatigue inhabituelle ;
* une difficulté répétée.

Il ne doit pas :

* poser de diagnostic ;
* déterminer la cause d’une douleur ;
* recommander un traitement ;
* décider qu’un exercice est médicalement sûr ;
* remplacer l’avis d’un professionnel de santé.

### 3.7 Progression incrémentale

Le module doit être développé par petits lots utilisables et testables.

Chaque lot doit :

* avoir un périmètre clairement délimité ;
* inclure les tests pertinents ;
* mettre à jour la documentation ;
* passer les vérifications du projet ;
* produire une fonctionnalité cohérente ou préparer explicitement le lot suivant.

---

## 4. Vocabulaire métier initial

### 4.1 Exercice

Un exercice représente une famille générale de mouvements.

Exemples :

* Deadlift ;
* Squat ;
* Bench Press ;
* Biceps Curl ;
* Lateral Raise ;
* Cycling.

### 4.2 Variation d’exercice

Une variation représente une forme précise et mesurable d’un exercice.

Exemples pour `Deadlift` :

* Conventional Deadlift ;
* Romanian Deadlift ;
* Sumo Deadlift ;
* Dumbbell Romanian Deadlift ;
* Trap Bar Deadlift.

Les performances de deux variations différentes ne doivent pas être fusionnées automatiquement dans une même courbe de charge.

### 4.3 Programme

Un programme représente une organisation durable de plusieurs séances types.

Exemples :

* programme habituel ;
* programme de reprise ;
* programme maison ;
* programme allégé.

### 4.4 Séance type

Une séance type décrit ce qui est normalement prévu.

Exemples :

* Musculation A ;
* Cardio ;
* Musculation B ;
* Séance maison.

### 4.5 Séance réelle

Une séance réelle représente une occurrence datée d’un entraînement.

Elle contient les exercices et séries réellement effectués.

### 4.6 Série

Une série représente une tentative ou un effort individuel associé à un exercice.

Une série peut notamment contenir :

* une charge ;
* un nombre de répétitions ;
* une durée ;
* une distance ;
* un type ;
* un état de validation.

### 4.7 Alternative

Une alternative représente une relation explicite entre deux variations d’exercice pouvant se remplacer dans certaines conditions.

---

## 5. Périmètre du MVP

Le MVP doit permettre de réaliser le parcours complet suivant :

1. créer les exercices utilisés dans le programme ;
2. créer les variations utiles ;
3. créer les séances Musculation A, Cardio et Musculation B ;
4. définir un programme hebdomadaire ;
5. consulter la séance du jour ;
6. démarrer une séance ;
7. enregistrer les séries avec charge et répétitions ;
8. afficher les valeurs de la séance précédente ;
9. modifier ponctuellement la séance ;
10. terminer la séance ;
11. consulter l’historique ;
12. afficher une première courbe de progression ;
13. ajouter une note facultative.

Le MVP ne doit pas inclure :

* la génération de programme par intelligence artificielle ;
* les recommandations médicales ;
* l’intégration avec une montre ou un appareil connecté ;
* le suivi nutritionnel ;
* les photos de progression ;
* la gestion sociale ou publique ;
* un catalogue externe massif d’exercices ;
* une gamification avancée ;
* un moteur complexe de récupération.

---

# Lot 0 — Cadrage fonctionnel et architecture métier

## Objectif

Stabiliser le vocabulaire, les règles structurantes et le périmètre avant de créer les entités et interfaces.

## Priorité

Critique.

## Dépendances

Aucune.

## Tâches

### W00.1 — Créer l’index documentaire du module

Créer :

```text
docs/private/workout/workout-index.md
```

Le document doit référencer :

* la vision ;
* le modèle métier ;
* la spécification du MVP ;
* les règles de progression ;
* les règles de remplacement ;
* le backlog actif.

### W00.2 — Rédiger la vision du module

Créer :

```text
docs/private/workout/workout-vision.md
```

Le document doit préciser :

* le problème résolu ;
* les utilisateurs concernés ;
* le contexte d’utilisation ;
* les parcours principaux ;
* les limites du module ;
* les principes d’UX mobile ;
* les critères généraux de réussite.

### W00.3 — Documenter le modèle métier initial

Créer :

```text
docs/private/workout/workout-domain-model.md
```

Décrire les concepts suivants :

* exercice ;
* variation ;
* équipement ;
* groupe musculaire ;
* programme ;
* séance type ;
* exercice prévu ;
* séance réelle ;
* exercice réalisé ;
* série ;
* alternative ;
* note ;
* ressenti.

### W00.4 — Rédiger la spécification du MVP

Créer :

```text
docs/private/workout/workout-mvp-specification.md
```

La spécification doit couvrir :

* les écrans ;
* les actions ;
* les règles métier ;
* les erreurs attendues ;
* les cas limites ;
* les critères d’acceptation.

### W00.5 — Définir les unités et modes de mesure

Décider comment enregistrer :

* les kilogrammes ;
* les répétitions ;
* les secondes ;
* les minutes ;
* les mètres ;
* les kilomètres ;
* la charge par haltère ;
* la charge totale ;
* la charge affichée par une machine ;
* le poids du corps ;
* les exercices sans charge.

### W00.6 — Définir la stratégie d’historisation

Décider quelles informations sont copiées dans la séance réelle lors de son démarrage.

L’historique doit rester compréhensible même si :

* un exercice est renommé ;
* une variation est archivée ;
* le programme est modifié ;
* le nombre de séries prévu change ;
* une consigne est mise à jour.

### W00.7 — Définir le mode de planification initial

Comparer et documenter :

* jours fixes ;
* rotation séquentielle ;
* mode hybride.

Décider quel mode sera implémenté dans le MVP.

### W00.8 — Produire une première cartographie technique

Identifier :

* les entités probables ;
* les services métier ;
* les contrôleurs ;
* les formulaires ;
* les routes ;
* les templates ;
* les scripts JavaScript ;
* les composants CSS ;
* les tests.

## Critères d’acceptation

* le vocabulaire est cohérent ;
* les concepts prévus et réalisés sont clairement séparés ;
* le périmètre MVP est explicite ;
* les décisions non tranchées sont listées ;
* aucune entité n’est créée avant validation du modèle ;
* la documentation est reliée aux index existants.

---

# Lot 1 — Prototype UX du mode séance mobile

## Objectif

Valider l’expérience d’utilisation pendant une séance avant de construire l’ensemble du modèle persistant.

## Priorité

Critique.

## Dépendances

Lot 0.

## Principe

Créer une interface expérimentale utilisant des données temporaires ou limitées.

Le prototype doit permettre d’évaluer le nombre de clics et la facilité de saisie sur téléphone.

## Tâches

### W01.1 — Créer une maquette de séance mobile

Afficher une séance contenant au minimum trois exercices.

Chaque exercice doit montrer :

* son nom ;
* la consigne prévue ;
* les séries ;
* la charge ;
* les répétitions ;
* l’état de validation ;
* la dernière performance connue.

### W01.2 — Tester la validation rapide d’une série

Permettre de :

* valider une série avec les valeurs proposées ;
* modifier rapidement la charge ;
* modifier rapidement les répétitions ;
* annuler une validation ;
* ajouter une série.

### W01.3 — Tester la navigation entre exercices

Prévoir :

* exercice précédent ;
* exercice suivant ;
* accès à la liste complète ;
* mise en évidence de l’exercice courant ;
* état des exercices terminés.

### W01.4 — Tester le préremplissage

Préremplir une série à partir :

* du programme ;
* de la dernière séance ;
* de la série précédente.

La règle exacte doit être définie et documentée.

### W01.5 — Tester l’autosauvegarde locale

Le prototype doit conserver l’état après :

* rafraîchissement de la page ;
* fermeture accidentelle ;
* navigation temporaire.

### W01.6 — Tester les contrôles tactiles

Vérifier sur téléphone :

* taille des boutons ;
* espacement ;
* clavier numérique ;
* lisibilité ;
* contraste ;
* défilement ;
* absence de clics accidentels.

### W01.7 — Recueillir les observations terrain

Utiliser le prototype pendant plusieurs séances réelles.

Documenter :

* les actions trop lentes ;
* les informations inutiles ;
* les informations manquantes ;
* les erreurs de saisie ;
* les contrôles difficiles à atteindre ;
* les interruptions dans le rythme de la séance.

## Critères d’acceptation

* une série peut être encodée en quelques secondes ;
* les actions importantes sont accessibles à une main ;
* aucune saisie non essentielle n’est obligatoire ;
* l’état ne disparaît pas après un rafraîchissement ;
* les retours terrain sont documentés avant le lot suivant.

---

# Lot 2 — Catalogue d’exercices

## Objectif

Créer le référentiel personnel des exercices et variations réellement utilisés.

## Priorité

Haute.

## Dépendances

Lots 0 et 1.

## Tâches

### W02.1 — Créer l’entité Exercise

Champs possibles :

* identifiant ;
* nom ;
* description facultative ;
* catégorie ;
* actif ou archivé ;
* date de création ;
* date de modification.

### W02.2 — Créer l’entité ExerciseVariation

Champs possibles :

* exercice parent ;
* nom ;
* description ;
* type de mesure ;
* mode de charge ;
* unilatéral ou bilatéral ;
* actif ou archivé ;
* ordre d’affichage éventuel.

### W02.3 — Gérer les groupes musculaires

Permettre d’associer :

* un ou plusieurs groupes principaux ;
* un ou plusieurs groupes secondaires.

Éviter une précision anatomique excessive dans le MVP.

### W02.4 — Gérer les équipements

Créer un catalogue limité aux équipements réellement utilisés.

Exemples :

* barbell ;
* dumbbells ;
* bench ;
* machine ;
* cable ;
* bodyweight ;
* treadmill ;
* stationary bike ;
* resistance band.

### W02.5 — Gérer le type d’exercice

Types initiaux possibles :

* force ;
* hypertrophie ;
* cardio ;
* mobilité ;
* échauffement.

Évaluer si ce champ est réellement nécessaire ou s’il doit rester facultatif.

### W02.6 — Gérer les modes de mesure

Modes possibles :

* charge et répétitions ;
* répétitions uniquement ;
* durée ;
* distance et durée ;
* charge et durée ;
* valeur libre spécialisée.

Le modèle doit permettre le cardio sans rendre tous les formulaires inutilement complexes.

### W02.7 — Créer les écrans d’administration

Permettre :

* la liste ;
* la création ;
* la modification ;
* l’archivage ;
* la consultation détaillée ;
* le filtrage ;
* la recherche.

### W02.8 — Interdire les suppressions destructrices

Un exercice utilisé dans l’historique ne doit pas être supprimé physiquement par défaut.

Préférer :

* l’archivage ;
* la désactivation ;
* la conservation des références historiques.

### W02.9 — Ajouter les premiers exercices réels

Créer uniquement les exercices nécessaires au programme actuel.

Ne pas importer un catalogue générique massif.

### W02.10 — Ajouter les tests

Tester notamment :

* les validations ;
* l’archivage ;
* les relations ;
* les exercices déjà utilisés ;
* les modes de mesure.

## Critères d’acceptation

* le catalogue peut représenter les exercices actuels ;
* les variations sont distinguées ;
* le mode de charge est explicite ;
* les exercices archivés restent visibles dans l’historique ;
* les formulaires sont utilisables sur mobile et desktop.

---

# Lot 3 — Programmes et séances types

## Objectif

Permettre de définir les séances prévues et leur organisation.

## Priorité

Haute.

## Dépendances

Lot 2.

## Tâches

### W03.1 — Créer l’entité WorkoutProgram

Champs possibles :

* nom ;
* description ;
* statut ;
* date de début ;
* date de fin facultative ;
* mode de planification ;
* actif ou archivé.

### W03.2 — Créer l’entité WorkoutTemplate

Une séance type doit contenir :

* un programme ;
* un nom ;
* une description ;
* une durée indicative facultative ;
* un ordre dans la rotation ;
* un statut actif ou archivé.

### W03.3 — Créer les exercices prévus

Chaque exercice prévu doit permettre de définir :

* la variation ;
* l’ordre ;
* le nombre de séries ;
* la plage de répétitions ;
* la durée éventuelle ;
* la distance éventuelle ;
* le temps de repos ;
* une charge suggérée facultative ;
* une consigne ;
* le caractère obligatoire ou optionnel.

### W03.4 — Créer l’interface de composition d’une séance

Permettre :

* d’ajouter un exercice ;
* de retirer un exercice ;
* de modifier son ordre ;
* de dupliquer un exercice ;
* de modifier les objectifs ;
* de définir les temps de repos.

### W03.5 — Gérer l’ordre des exercices

Prévoir une solution accessible pour modifier l’ordre.

Le glisser-déposer peut être ajouté, mais une alternative accessible doit rester disponible.

### W03.6 — Implémenter le planning initial

Selon la décision du lot 0 :

* associer une séance à un jour ;
* ou définir une rotation ;
* ou combiner les deux.

### W03.7 — Créer la résolution de la séance du jour

Créer un service dédié, par exemple :

```text
NextWorkoutResolver
```

Le service doit déterminer :

* la séance prévue aujourd’hui ;
* la prochaine séance en cas de rotation ;
* l’absence de séance ;
* le report éventuel ;
* les séances exceptionnellement sélectionnées.

### W03.8 — Créer les programmes initiaux

Prévoir au minimum :

* Musculation A ;
* Cardio ;
* Musculation B.

### W03.9 — Ajouter l’archivage et la duplication

Permettre de :

* dupliquer un programme ;
* dupliquer une séance ;
* archiver un ancien programme ;
* conserver son historique.

### W03.10 — Ajouter les tests

Tester :

* l’ordre ;
* la planification ;
* la prochaine séance ;
* les programmes archivés ;
* les séances sans exercices ;
* les changements de programme.

## Critères d’acceptation

* le programme actuel peut être reproduit ;
* la séance du jour est déterminée correctement ;
* les séances types peuvent être modifiées sans toucher à l’historique ;
* les exercices sont ordonnés ;
* les objectifs de séries et répétitions sont enregistrés.

---

# Lot 4 — Création et cycle de vie d’une séance réelle

## Objectif

Créer une séance datée et indépendante à partir d’une séance type.

## Priorité

Critique.

## Dépendances

Lot 3.

## Tâches

### W04.1 — Créer l’entité WorkoutSession

Champs possibles :

* date prévue ;
* date et heure de début ;
* date et heure de fin ;
* séance type source ;
* programme source ;
* statut ;
* note générale ;
* ressenti ;
* durée réelle.

### W04.2 — Définir les statuts

Statuts initiaux possibles :

* planned ;
* in_progress ;
* completed ;
* abandoned ;
* cancelled.

Éviter les états redondants ou impossibles.

### W04.3 — Créer l’entité WorkoutSessionExercise

Conserver notamment :

* la variation ;
* le nom historique affiché ;
* l’ordre ;
* l’objectif initial ;
* le statut ;
* la source prévue ou ajoutée ;
* l’exercice remplacé éventuel ;
* la note ;
* le ressenti.

### W04.4 — Créer l’entité WorkoutSet

Conserver selon le type d’exercice :

* numéro ;
* charge ;
* répétitions ;
* durée ;
* distance ;
* type de série ;
* état terminé ;
* date et heure de validation.

### W04.5 — Créer WorkoutSessionFactory

Le service doit :

* recevoir une séance type ;
* créer une séance réelle ;
* copier les exercices prévus ;
* copier les objectifs utiles ;
* conserver les informations historiques nécessaires ;
* préparer les séries initiales.

### W04.6 — Empêcher les doublons accidentels

Éviter qu’un double clic ou un rechargement crée plusieurs séances identiques.

### W04.7 — Permettre une séance libre

Prévoir la possibilité de démarrer une séance sans modèle.

Cette fonction peut être repoussée après le MVP si elle complique le premier cycle.

### W04.8 — Gérer la reprise d’une séance inachevée

Lorsqu’une séance est déjà en cours :

* proposer de la reprendre ;
* éviter d’en créer une nouvelle par erreur ;
* afficher son état actuel.

### W04.9 — Ajouter les tests

Tester :

* la création depuis un modèle ;
* l’instantané historique ;
* les statuts ;
* la reprise ;
* les doublons ;
* les séances abandonnées.

## Critères d’acceptation

* une séance réelle peut être créée depuis la séance du jour ;
* son contenu est indépendant du modèle ;
* la séance peut être reprise après interruption ;
* les statuts sont cohérents ;
* aucune modification du programme ne change la séance existante.

---

# Lot 5 — Mode exécution de séance

## Objectif

Fournir l’interface principale utilisée pendant l’entraînement.

## Priorité

Critique.

## Dépendances

Lots 1 et 4.

## Tâches

### W05.1 — Créer la page « Séance du jour »

Afficher :

* la séance prévue ;
* les exercices ;
* la durée estimée ;
* les objectifs ;
* la dernière séance comparable ;
* le bouton de démarrage ;
* les options de modification ponctuelle.

### W05.2 — Créer le mode séance en cours

Afficher :

* l’exercice courant ;
* sa position ;
* ses séries ;
* les valeurs précédentes ;
* les valeurs proposées ;
* les actions principales ;
* l’état global de la séance.

### W05.3 — Préremplir les valeurs

Définir une priorité claire entre :

1. objectif du programme ;
2. dernière performance ;
3. série précédente ;
4. valeur vide.

### W05.4 — Valider une série rapidement

Permettre :

* validation en un clic ;
* modification de charge ;
* modification de répétitions ;
* validation au clavier ;
* retour arrière ;
* correction après validation.

### W05.5 — Ajouter et retirer une série

L’ajout doit réutiliser intelligemment :

* la charge précédente ;
* les répétitions précédentes ;
* le type de série.

La suppression doit demander confirmation si la série contient déjà des données validées.

### W05.6 — Gérer les séries d’échauffement

Types initiaux possibles :

* warmup ;
* working ;
* backoff ;
* drop ;
* other.

Le MVP peut se limiter à :

* échauffement ;
* série de travail.

### W05.7 — Terminer un exercice

Permettre de marquer un exercice :

* terminé ;
* ignoré ;
* abandonné ;
* remplacé.

### W05.8 — Terminer une séance

La fin de séance doit :

* vérifier les données ;
* autoriser des exercices incomplets ;
* calculer la durée ;
* proposer un ressenti facultatif ;
* enregistrer une note facultative ;
* afficher un résumé.

### W05.9 — Autosauvegarder

Sauvegarder après chaque mutation importante :

* série validée ;
* charge modifiée ;
* répétitions modifiées ;
* exercice terminé ;
* note ajoutée ;
* remplacement ;
* réorganisation.

### W05.10 — Gérer les erreurs réseau

Prévoir :

* un état de sauvegarde ;
* un message discret ;
* une tentative de reprise ;
* l’absence de perte silencieuse ;
* une prévention avant fermeture si des données ne sont pas sauvegardées.

### W05.11 — Optimiser le clavier mobile

Utiliser les types de champs adaptés pour afficher :

* clavier numérique ;
* décimales ;
* valeurs positives ;
* contrôles d’incrément.

### W05.12 — Ajouter les tests

Tester :

* validation rapide ;
* autosauvegarde ;
* correction ;
* ajout de série ;
* exercice ignoré ;
* séance terminée ;
* données partielles ;
* erreurs réseau.

## Critères d’acceptation

* la séance est réellement utilisable sur téléphone ;
* une série peut être validée rapidement ;
* les valeurs précédentes sont visibles ;
* les données sont sauvegardées sans action manuelle ;
* une séance incomplète peut être terminée sans contournement ;
* aucune note n’est obligatoire.

---

# Lot 6 — Historique des séances

## Objectif

Permettre de relire précisément les entraînements passés.

## Priorité

Haute.

## Dépendances

Lot 5.

## Tâches

### W06.1 — Créer la liste des séances

Afficher :

* date ;
* nom ;
* statut ;
* durée ;
* nombre d’exercices ;
* ressenti éventuel.

### W06.2 — Ajouter les filtres

Filtres possibles :

* période ;
* programme ;
* séance type ;
* statut ;
* exercice ;
* ressenti.

### W06.3 — Créer la page de détail

Afficher :

* résumé ;
* chronologie ;
* exercices ;
* séries ;
* remplacements ;
* notes ;
* différences avec le prévu.

### W06.4 — Afficher le prévu et le réalisé

Pour chaque exercice, permettre de voir :

* objectif prévu ;
* résultat réel ;
* séries ajoutées ;
* séries supprimées ;
* exercice remplacé ;
* exercice ignoré.

### W06.5 — Permettre la correction historique

Définir quelles données peuvent être corrigées après la séance.

Prévoir une trace minimale des corrections si nécessaire.

### W06.6 — Permettre la duplication

Depuis une ancienne séance, permettre éventuellement de :

* redémarrer une séance similaire ;
* créer une nouvelle séance type ;
* copier certains exercices.

Cette fonction peut être différée.

### W06.7 — Ajouter les tests

Tester :

* la pagination ;
* les filtres ;
* les détails ;
* les exercices archivés ;
* les séances remplacées ;
* les corrections.

## Critères d’acceptation

* toutes les séances sont retrouvables ;
* l’historique reste lisible après modification du catalogue ;
* les écarts entre prévu et réalisé sont visibles ;
* les filtres principaux fonctionnent ;
* les données ne sont pas modifiées silencieusement.

---

# Lot 7 — Métriques et graphiques de progression

## Objectif

Visualiser les progrès sans produire d’indicateurs trompeurs.

## Priorité

Haute.

## Dépendances

Lot 6.

## Principes

Les comparaisons de charge doivent être réalisées par variation exacte.

Les agrégations entre exercices différents doivent être limitées aux statistiques qui restent significatives.

## Tâches

### W07.1 — Créer WorkoutMetricsCalculator

Calculer au minimum :

* charge maximale de la séance ;
* meilleure série ;
* répétitions totales ;
* volume total ;
* nombre de séries ;
* durée totale ;
* distance totale.

### W07.2 — Définir le volume

Pour les exercices avec charge et répétitions :

```text
volume = charge × répétitions
```

Documenter les limites de cette métrique.

### W07.3 — Gérer les séries exclues

Déterminer si les séries suivantes participent aux statistiques :

* échauffement ;
* série abandonnée ;
* série non validée ;
* série partielle ;
* drop set.

### W07.4 — Créer la page de progression d’un exercice

Afficher :

* historique des séances ;
* meilleure série ;
* charge maximale ;
* volume ;
* répétitions ;
* fréquence ;
* notes associées.

### W07.5 — Ajouter une courbe de charge maximale

Permettre de filtrer :

* période ;
* variation ;
* type de série ;
* statut.

### W07.6 — Ajouter une courbe de volume

Afficher uniquement lorsque la métrique est pertinente.

### W07.7 — Ajouter l’évolution des répétitions

Utile notamment pour :

* exercices au poids du corps ;
* charge identique ;
* objectifs de progression en répétitions.

### W07.8 — Ajouter les métriques cardio

Selon les données disponibles :

* durée ;
* distance ;
* vitesse moyenne ;
* fréquence des séances.

### W07.9 — Ajouter l’estimation de 1RM

Fonction avancée et facultative.

Elle doit :

* être clairement présentée comme une estimation ;
* indiquer la formule utilisée ;
* pouvoir être masquée ;
* ne pas être utilisée pour les exercices inadaptés.

### W07.10 — Ajouter les tests

Tester :

* calculs ;
* séries exclues ;
* données manquantes ;
* charges décimales ;
* exercices différents ;
* cardio ;
* historique archivé.

## Critères d’acceptation

* les courbes ne fusionnent pas des variations incompatibles ;
* les métriques sont documentées ;
* les valeurs sont reproductibles ;
* les graphiques restent lisibles sur mobile ;
* les absences de données sont gérées proprement.

---

# Lot 8 — Modifications ponctuelles de séance

## Objectif

Permettre d’adapter la séance du jour sans modifier le programme permanent.

## Priorité

Haute.

## Dépendances

Lot 5.

## Tâches

### W08.1 — Ignorer un exercice aujourd’hui

L’exercice doit rester présent dans la séance avec le statut `skipped`.

Une raison facultative peut être enregistrée.

### W08.2 — Ajouter un exercice ponctuel

L’exercice doit être marqué comme ajouté manuellement.

### W08.3 — Retirer un exercice avant le démarrage

Déterminer s’il doit :

* disparaître de l’affichage actif ;
* rester visible comme retiré ;
* être conservé dans l’historique de la séance.

### W08.4 — Réorganiser les exercices

Permettre de modifier l’ordre uniquement pour la séance courante.

### W08.5 — Réduire le nombre de séries

Proposer une action rapide :

```text
Réduire la séance aujourd’hui
```

La réduction ne doit pas modifier le modèle.

### W08.6 — Choisir une autre séance

Depuis la page du jour, permettre de sélectionner une autre séance type.

Le système doit conserver la différence entre :

* séance prévue ;
* séance choisie ;
* séance réellement exécutée.

### W08.7 — Créer une séance libre

Permettre de partir d’une séance vide et d’ajouter des exercices.

### W08.8 — Proposer une version courte

Première règle possible :

* conserver les exercices prioritaires ;
* réduire les exercices optionnels ;
* réduire le nombre de séries ;
* afficher la durée estimée.

### W08.9 — Ajouter les tests

Tester :

* séance courante uniquement ;
* programme inchangé ;
* historique ;
* exercices retirés ;
* séance alternative ;
* version courte.

## Critères d’acceptation

* toutes les adaptations sont ponctuelles par défaut ;
* le programme original reste intact ;
* la séance garde la trace du prévu ;
* les modifications sont visibles dans l’historique ;
* aucune adaptation ne détruit des données déjà saisies.

---

# Lot 9 — Alternatives et remplacement d’exercice

## Objectif

Proposer des remplacements cohérents et transparents.

## Priorité

Moyenne à haute.

## Dépendances

Lots 2 et 8.

## Tâches

### W09.1 — Créer ExerciseAlternative

Informations possibles :

* variation source ;
* variation alternative ;
* niveau d’équivalence ;
* justification ;
* priorité ;
* active ou inactive.

### W09.2 — Définir les niveaux d’équivalence

Niveaux proposés :

* direct ;
* close ;
* fallback ;
* variety.

### W09.3 — Documenter les règles

Créer :

```text
docs/private/workout/workout-replacement-rules.md
```

### W09.4 — Créer ExerciseReplacementFinder

Le service peut prendre en compte :

* relation explicite ;
* équipement disponible ;
* groupes musculaires ;
* mouvement ;
* exercices déjà présents ;
* préférence ;
* exercice évité ;
* historique récent.

### W09.5 — Créer le bouton « Remplacer aujourd’hui »

Afficher des propositions classées.

Chaque proposition doit expliquer son classement, par exemple :

* même mouvement avec un autre équipement ;
* sollicitation proche ;
* alternative de secours ;
* variation pour changer.

### W09.6 — Conserver la relation de remplacement

La séance doit enregistrer :

* l’exercice initial ;
* l’exercice choisi ;
* le moment du remplacement ;
* la raison facultative.

### W09.7 — Empêcher les propositions incohérentes

Éviter :

* un exercice déjà présent en doublon inutile ;
* un équipement indisponible ;
* un exercice archivé ;
* une alternative explicitement évitée ;
* une boucle de remplacement.

### W09.8 — Ajouter les tests

Tester :

* classement ;
* équipement ;
* doublons ;
* exercices archivés ;
* historique ;
* explication de la proposition.

## Critères d’acceptation

* le système ne choisit pas silencieusement ;
* les alternatives sont explicables ;
* le remplacement n’affecte que la séance ;
* l’historique conserve l’exercice original ;
* les propositions respectent l’équipement disponible.

---

# Lot 10 — Ressenti et notes

## Objectif

Permettre d’enregistrer des observations sans ralentir la séance.

## Priorité

Moyenne.

## Dépendances

Lot 5.

## Tâches

### W10.1 — Ajouter un ressenti de séance

Valeurs initiales possibles :

* very_easy ;
* easy ;
* normal ;
* hard ;
* very_hard ;
* discomfort.

Évaluer un vocabulaire plus naturel dans l’interface.

### W10.2 — Ajouter un ressenti par exercice

Facultatif et distinct du ressenti général.

### W10.3 — Ajouter une note générale

Champ libre facultatif.

### W10.4 — Ajouter une note par exercice

Champ facultatif accessible sans encombrer l’interface principale.

### W10.5 — Ajouter un indicateur de gêne

Permettre de signaler :

* aucune gêne ;
* gêne légère ;
* douleur ou gêne importante.

Le système doit rester descriptif.

### W10.6 — Afficher les notes dans l’historique

Les notes doivent être visibles :

* sur la séance ;
* sur l’exercice ;
* dans la page de progression si pertinent.

### W10.7 — Ajouter les tests

Tester :

* champs facultatifs ;
* caractères longs ;
* affichage historique ;
* absence de blocage ;
* valeurs archivées.

## Critères d’acceptation

* aucune note n’est obligatoire ;
* les ressentis sont rapides à encoder ;
* les notes ne surchargent pas le mode séance ;
* une gêne peut être retrouvée dans l’historique ;
* aucune interprétation médicale n’est produite.

---

# Lot 11 — Suggestions simples de progression

## Objectif

Aider à décider quoi tenter lors de la prochaine séance à partir de règles explicites.

## Priorité

Moyenne.

## Dépendances

Lot 7.

## Tâches

### W11.1 — Documenter les règles de progression

Créer :

```text
docs/private/workout/workout-progression-rules.md
```

### W11.2 — Créer une stratégie de double progression

Exemple :

* objectif de 8 à 10 répétitions ;
* conserver la charge tant que toutes les séries n’atteignent pas 10 ;
* augmenter légèrement la charge lorsque toutes les séries atteignent le haut de la plage.

### W11.3 — Créer LoadSuggestionService

Le service doit produire :

* une suggestion ;
* une explication ;
* les données utilisées ;
* éventuellement aucune suggestion.

### W11.4 — Gérer les incréments

Définir par exercice ou équipement :

* incrément minimal ;
* arrondi ;
* charge disponible ;
* progression par haltère ;
* progression totale.

### W11.5 — Afficher la suggestion sans l’imposer

Exemple :

```text
Dernière séance : 10 / 10 / 10 à 30 kg
Suggestion : essayer 32 kg
```

### W11.6 — Permettre d’ignorer la suggestion

L’utilisateur doit toujours pouvoir saisir une autre valeur.

### W11.7 — Gérer les données insuffisantes

Ne pas suggérer une progression lorsque :

* trop peu de séances existent ;
* les données sont incohérentes ;
* l’exercice est nouveau ;
* la dernière séance est incomplète ;
* une gêne importante a été signalée.

### W11.8 — Ajouter les tests

Tester :

* haut de plage atteint ;
* plage non atteinte ;
* séries incomplètes ;
* incrément ;
* exercice unilatéral ;
* absence de suggestion.

## Critères d’acceptation

* chaque suggestion est expliquée ;
* aucune suggestion n’est présentée comme obligatoire ;
* les règles sont documentées ;
* les données insuffisantes ne produisent pas de conseil arbitraire ;
* les incréments disponibles sont respectés.

---

# Lot 12 — Chronomètre de repos

## Objectif

Aider à respecter les temps de repos sans compliquer la saisie.

## Priorité

Moyenne.

## Dépendances

Lot 5.

## Tâches

### W12.1 — Démarrer automatiquement le chronomètre

Après validation d’une série de travail, proposer ou démarrer le repos associé.

### W12.2 — Afficher le temps restant

L’affichage doit rester visible sans bloquer la navigation.

### W12.3 — Ajouter les actions rapides

Permettre :

* pause ;
* reprise ;
* ajout de temps ;
* réduction ;
* arrêt ;
* redémarrage.

### W12.4 — Ajouter un signal de fin

Options possibles :

* son discret ;
* vibration ;
* notification visuelle.

### W12.5 — Respecter les préférences du navigateur

Le signal doit :

* nécessiter une interaction préalable si le navigateur l’impose ;
* ne jamais bloquer la séance ;
* pouvoir être désactivé.

### W12.6 — Conserver la préférence

Mémoriser :

* chronomètre automatique ou manuel ;
* son activé ;
* vibration activée ;
* volume éventuel.

### W12.7 — Gérer la page en arrière-plan

Évaluer les limitations des navigateurs mobiles.

### W12.8 — Ajouter les tests

Tester :

* démarrage ;
* pause ;
* fin ;
* navigation ;
* préférence ;
* absence d’autorisation audio.

## Critères d’acceptation

* le chronomètre n’interrompt pas la saisie ;
* il peut être désactivé ;
* le signal ne provoque pas d’erreur ;
* le temps de repos vient du programme ;
* l’utilisateur garde le contrôle.

---

# Lot 13 — Dashboard Workout

## Objectif

Fournir une vue synthétique et actionnable.

## Priorité

Moyenne.

## Dépendances

Lots 6 et 7.

## Tâches

### W13.1 — Afficher la séance du jour

Inclure :

* nom ;
* durée indicative ;
* nombre d’exercices ;
* bouton de démarrage ;
* bouton de modification ponctuelle.

### W13.2 — Afficher la dernière séance

Inclure :

* date ;
* séance ;
* durée ;
* exercices terminés ;
* ressenti ;
* lien vers le détail.

### W13.3 — Afficher la semaine courante

Statistiques possibles :

* séances prévues ;
* séances terminées ;
* séances reportées ;
* durée totale ;
* nombre d’exercices.

### W13.4 — Afficher les progressions récentes

Exemples :

* charge augmentée ;
* répétitions augmentées ;
* meilleur volume ;
* durée cardio augmentée.

### W13.5 — Afficher les séances en cours

Permettre de reprendre immédiatement une séance inachevée.

### W13.6 — Limiter les indicateurs culpabilisants

Éviter une présentation agressive des séances manquées.

Préférer :

* prochaine séance ;
* régularité récente ;
* historique factuel ;
* possibilités de report.

### W13.7 — Ajouter les tests

Tester :

* absence de données ;
* programme inactif ;
* séance en cours ;
* semaine sans séance ;
* progression récente.

## Critères d’acceptation

* l’action principale est évidente ;
* le dashboard ne remplace pas le mode séance ;
* les données restent lisibles sur mobile ;
* aucune statistique trompeuse n’est affichée ;
* une séance en cours est immédiatement accessible.

---

# Lot 14 — Profils de lieu et équipements disponibles

## Objectif

Adapter les séances et alternatives au contexte réel.

## Priorité

Basse à moyenne.

## Dépendances

Lot 9.

## Tâches

### W14.1 — Créer WorkoutLocationProfile

Profils initiaux possibles :

* salle ;
* maison ;
* voyage.

### W14.2 — Associer les équipements disponibles

Chaque profil contient la liste des équipements accessibles.

### W14.3 — Associer une séance à un profil

Le profil peut être :

* défini par le programme ;
* sélectionné au démarrage ;
* modifié ponctuellement.

### W14.4 — Filtrer les alternatives

Ne proposer que les exercices compatibles avec l’équipement disponible.

### W14.5 — Signaler les exercices impossibles

Lorsqu’un exercice prévu n’est pas compatible :

* proposer un remplacement ;
* permettre de l’ignorer ;
* permettre de changer de profil.

### W14.6 — Ajouter les tests

Tester :

* profil vide ;
* changement ponctuel ;
* équipement manquant ;
* alternative disponible ;
* programme inchangé.

## Critères d’acceptation

* le lieu influence les propositions ;
* le programme n’est pas modifié automatiquement ;
* les équipements manquants sont visibles ;
* les alternatives restent explicables.

---

# Lot 15 — Favoris, préférences et exercices évités

## Objectif

Personnaliser les suggestions selon l’expérience réelle.

## Priorité

Basse.

## Dépendances

Lot 9.

## Tâches

### W15.1 — Ajouter une préférence par variation

Valeurs possibles :

* favorite ;
* neutral ;
* avoid ;
* temporarily_unavailable.

### W15.2 — Ajouter une note de préférence

Exemple :

```text
Je préfère cet exercice à la machine lorsque la salle est chargée.
```

### W15.3 — Utiliser la préférence dans le classement

Les favoris peuvent être mieux classés.

Les exercices évités doivent être masqués ou signalés.

### W15.4 — Gérer l’indisponibilité temporaire

Ajouter éventuellement :

* date de début ;
* date de fin ;
* raison facultative.

### W15.5 — Ajouter les tests

Tester :

* filtrage ;
* classement ;
* expiration ;
* préférence absente ;
* exercice archivé.

## Critères d’acceptation

* les préférences n’altèrent pas l’historique ;
* un exercice évité n’est pas proposé silencieusement ;
* la préférence peut être retirée ;
* les règles de classement restent compréhensibles.

---

# Lot 16 — Records personnels

## Objectif

Mettre en évidence les progrès significatifs.

## Priorité

Basse à moyenne.

## Dépendances

Lot 7.

## Tâches

### W16.1 — Définir les types de records

Types possibles :

* charge maximale ;
* répétitions maximales à une charge donnée ;
* meilleure série ;
* meilleur volume ;
* meilleure estimation 1RM ;
* durée maximale ;
* distance maximale.

### W16.2 — Calculer les records à la fin d’une séance

Le calcul doit être reproductible depuis l’historique.

### W16.3 — Afficher un feedback discret

Éviter une animation ou une gamification excessive.

### W16.4 — Créer une page de records

Afficher les records par variation.

### W16.5 — Gérer les corrections historiques

Un record doit être recalculé si une ancienne séance est corrigée.

### W16.6 — Ajouter les tests

Tester :

* égalité ;
* correction ;
* exercice archivé ;
* séries d’échauffement ;
* valeurs manquantes.

## Critères d’acceptation

* les records sont calculés par variation compatible ;
* les séries exclues ne créent pas de record ;
* les corrections recalculent les résultats ;
* la présentation reste sobre.

---

# Lot 17 — Détection de stagnation

## Objectif

Identifier factuellement une absence récente de progression mesurable.

## Priorité

Basse.

## Dépendances

Lots 7 et 11.

## Tâches

### W17.1 — Définir une stagnation

La définition doit préciser :

* la métrique observée ;
* le nombre de séances ;
* la période ;
* les données exclues ;
* les seuils.

### W17.2 — Créer PlateauDetector

Le service doit retourner :

* aucune conclusion ;
* stagnation possible ;
* données insuffisantes.

### W17.3 — Afficher les données utilisées

Exemple :

```text
Aucune hausse de charge, de répétitions ou de volume sur les six dernières séances comparables.
```

### W17.4 — Ne pas interpréter la cause

Le système ne doit pas affirmer que la stagnation vient :

* du sommeil ;
* de la nutrition ;
* d’un mauvais programme ;
* d’un problème médical ;
* d’un manque d’effort.

### W17.5 — Ajouter les tests

Tester :

* données insuffisantes ;
* progression en répétitions ;
* progression en charge ;
* séances non comparables ;
* séance abandonnée ;
* stagnation réelle.

## Critères d’acceptation

* la définition est documentée ;
* aucune cause n’est inventée ;
* l’indicateur peut être désactivé ;
* les séances incomparables sont exclues ;
* la formulation reste prudente.

---

# Lot 18 — Séances allégées et alternatives complètes

## Objectif

Proposer une séance différente tout en conservant l’intention générale.

## Priorité

Basse.

## Dépendances

Lots 8, 9 et 14.

## Tâches

### W18.1 — Définir une version courte

Règles possibles :

* conserver les exercices prioritaires ;
* réduire les séries ;
* retirer les exercices optionnels ;
* respecter une durée cible.

### W18.2 — Définir une séance allégée

Règles possibles :

* réduction des séries ;
* réduction suggérée de charge ;
* alternatives moins exigeantes ;
* temps de repos adapté.

Les règles doivent rester configurables et non médicales.

### W18.3 — Définir une séance alternative

Maintenir autant que possible :

* groupes musculaires ;
* patterns de mouvement ;
* durée ;
* équipement ;
* nombre d’exercices.

### W18.4 — Afficher les changements avant validation

L’utilisateur doit voir :

* exercices conservés ;
* exercices remplacés ;
* exercices retirés ;
* raison de chaque changement.

### W18.5 — Permettre un ajustement manuel

La proposition doit rester modifiable avant le démarrage.

### W18.6 — Ajouter les tests

Tester :

* absence d’alternative ;
* durée cible ;
* équipement ;
* exercices prioritaires ;
* programme inchangé.

## Critères d’acceptation

* aucune séance n’est remplacée sans validation ;
* les règles sont visibles ;
* le programme permanent reste intact ;
* les exercices prioritaires sont respectés ;
* le système peut refuser de générer une proposition incohérente.

---

# Lot 19 — Export, sauvegarde et portabilité

## Objectif

Garantir la récupération des données personnelles.

## Priorité

Moyenne.

## Dépendances

Lot 6.

## Tâches

### W19.1 — Exporter l’historique en JSON

Inclure :

* catalogue ;
* programmes ;
* séances ;
* exercices réalisés ;
* séries ;
* notes ;
* relations utiles.

### W19.2 — Exporter les séries en CSV

Prévoir un format exploitable dans un tableur.

### W19.3 — Documenter le format d’export

Préciser :

* version ;
* unités ;
* dates ;
* valeurs nulles ;
* identifiants ;
* compatibilité future.

### W19.4 — Préparer un import contrôlé

L’import complet peut être différé.

Évaluer :

* validation ;
* aperçu ;
* conflits ;
* doublons ;
* rollback.

### W19.5 — Ajouter une sauvegarde manuelle

Permettre de télécharger toutes les données du module.

### W19.6 — Ajouter les tests

Tester :

* gros historique ;
* caractères spéciaux ;
* unités ;
* valeurs absentes ;
* données archivées.

## Critères d’acceptation

* les données peuvent être récupérées ;
* le format est documenté ;
* l’export inclut l’historique archivé ;
* aucune donnée sensible d’un autre module n’est incluse ;
* les dates et unités ne sont pas ambiguës.

---

# Lot 20 — Accessibilité, performance et robustesse

## Objectif

Consolider le module après validation fonctionnelle.

## Priorité

Continue.

## Dépendances

Tous les lots fonctionnels concernés.

## Tâches

### W20.1 — Vérifier l’accessibilité clavier

Toutes les actions doivent être accessibles sans souris.

### W20.2 — Vérifier les lecteurs d’écran

Ajouter :

* labels ;
* annonces de sauvegarde ;
* annonces de validation ;
* statuts compréhensibles ;
* messages d’erreur reliés aux champs.

### W20.3 — Vérifier les contrastes

Respecter les thèmes clair et sombre.

### W20.4 — Vérifier les tailles tactiles

Tester les principaux appareils mobiles.

### W20.5 — Optimiser les requêtes

Surveiller notamment :

* historique ;
* dashboard ;
* courbes ;
* dernière performance ;
* statistiques par exercice.

### W20.6 — Ajouter les index de base de données

Créer les index nécessaires à partir des requêtes réelles.

### W20.7 — Gérer les volumes croissants

Prévoir :

* pagination ;
* chargement différé ;
* agrégation ;
* limitation des périodes par défaut.

### W20.8 — Ajouter des tests fonctionnels

Couvrir les parcours critiques :

* séance du jour ;
* démarrage ;
* saisie ;
* interruption ;
* reprise ;
* fin ;
* historique ;
* remplacement.

### W20.9 — Ajouter un smoke test de production

Vérifier au minimum :

* accès authentifié ;
* page du module ;
* chargement de la séance du jour ;
* création contrôlée d’une donnée de test si possible ;
* absence d’exposition publique.

### W20.10 — Mettre à jour la documentation

Maintenir :

* architecture ;
* règles métier ;
* cartographie ;
* index ;
* backlog ;
* procédure de vérification.

## Critères d’acceptation

* les parcours critiques sont testés ;
* le module est utilisable au clavier ;
* les contrôles mobiles sont adaptés ;
* les principales requêtes sont maîtrisées ;
* les pages privées ne sont pas exposées publiquement ;
* les vérifications du projet passent.

---

# Backlog transversal

## T01 — Internationalisation

Décider si le module privé doit être :

* uniquement en français ;
* bilingue comme le site public ;
* préparé pour une traduction future.

Éviter de dupliquer inutilement les contenus personnels.

## T02 — Gestion des dates et fuseaux horaires

Stocker les dates de manière cohérente.

Afficher selon le fuseau configuré pour le site.

## T03 — Gestion des nombres décimaux

Prévoir correctement :

* `29,5 kg` dans l’interface française ;
* stockage normalisé ;
* arrondis ;
* incréments ;
* machines utilisant des valeurs non entières.

## T04 — Suppression et archivage

Définir pour chaque entité :

* suppression autorisée ;
* archivage ;
* conservation historique ;
* restauration.

## T05 — Journalisation

Journaliser les erreurs importantes sans enregistrer inutilement des données personnelles détaillées.

## T06 — Sécurité

Le module doit :

* rester exclusivement privé ;
* utiliser les protections CSRF ;
* vérifier les droits d’accès ;
* ne pas exposer les données dans le sitemap ;
* rester marqué `noindex,nofollow` ;
* éviter les identifiants prédictibles dans les routes lorsque pertinent.

## T07 — Protection contre les doubles soumissions

Toutes les actions critiques doivent résister :

* au double clic ;
* au rafraîchissement ;
* à une requête répétée ;
* à une connexion lente.

## T08 — Tests de migration

Chaque migration Doctrine doit être :

* relue ;
* réversible lorsque raisonnable ;
* testée avec des données existantes ;
* documentée si elle transforme des données.

## T09 — Fixtures de développement

Créer un jeu de données minimal comprenant :

* exercices ;
* variations ;
* programme ;
* séances types ;
* historique ;
* remplacements ;
* notes ;
* exercice archivé.

## T10 — Données de démonstration

Évaluer si un environnement local doit proposer une commande pour créer des données réalistes sans utiliser l’historique personnel réel.

---

# Évolutions hors périmètre actuel

Les fonctionnalités suivantes ne sont pas rejetées définitivement, mais ne doivent pas être intégrées sans nouveau cadrage.

## F01 — Mesures corporelles

Exemples :

* poids ;
* tour de taille ;
* autres mensurations.

Cette fonctionnalité pourrait appartenir à un module de santé ou de suivi personnel distinct.

## F02 — Photos de progression

Nécessite une réflexion spécifique sur :

* stockage ;
* confidentialité ;
* sauvegarde ;
* espace disque ;
* suppression.

## F03 — Suivi nutritionnel

À traiter comme un domaine séparé.

## F04 — Suivi du sommeil

À traiter comme un domaine séparé ou via une future intégration.

## F05 — Synchronisation avec une montre

Nécessite une étude des API, formats et conditions d’utilisation.

## F06 — Import depuis une application sportive

Nécessite une étude du format source et des règles de correspondance.

## F07 — Partage avec un coach

Nécessite :

* permissions ;
* comptes multiples ;
* visibilité limitée ;
* commentaires ;
* sécurité renforcée.

## F08 — Publication publique

Non souhaitée dans le périmètre actuel.

## F09 — Génération par intelligence artificielle

Toute génération future devrait :

* rester facultative ;
* expliquer les données utilisées ;
* ne pas produire de recommandation médicale ;
* ne pas remplacer les règles métier déterministes ;
* être validée manuellement.

## F10 — Gamification

Exemples :

* badges ;
* séries de jours ;
* niveaux ;
* défis.

À ajouter uniquement si cela améliore réellement la motivation sans créer de pression inutile.

---

# Ordre d’implémentation recommandé

## Phase 1 — Validation du besoin

1. Lot 0 — Cadrage fonctionnel et architecture métier.
2. Lot 1 — Prototype UX du mode séance mobile.

## Phase 2 — MVP structurel

3. Lot 2 — Catalogue d’exercices.
4. Lot 3 — Programmes et séances types.
5. Lot 4 — Cycle de vie d’une séance réelle.
6. Lot 5 — Mode exécution de séance.

À la fin de cette phase, le module doit être utilisable pendant une vraie séance.

## Phase 3 — Exploitation des données

7. Lot 6 — Historique.
8. Lot 7 — Métriques et graphiques.
9. Lot 8 — Modifications ponctuelles.

## Phase 4 — Personnalisation

10. Lot 9 — Alternatives et remplacements.
11. Lot 10 — Ressentis et notes.
12. Lot 11 — Suggestions de progression.
13. Lot 12 — Chronomètre.
14. Lot 13 — Dashboard.

## Phase 5 — Fonctions avancées

15. Lot 14 — Profils de lieu.
16. Lot 15 — Préférences.
17. Lot 16 — Records.
18. Lot 17 — Stagnation.
19. Lot 18 — Séances allégées et alternatives.
20. Lot 19 — Export et portabilité.

## Phase continue

21. Lot 20 — Accessibilité, performance et robustesse.

---

# Définition de terminé d’un lot

Un lot peut être considéré comme terminé lorsque :

* son périmètre fonctionnel est implémenté ;
* les cas limites identifiés sont traités ;
* les tests automatisés pertinents existent ;
* les tests passent ;
* les migrations ont été exécutées ;
* l’interface a été testée sur mobile si elle concerne une séance ;
* la documentation stable est mise à jour ;
* le présent backlog est mis à jour ;
* les décisions importantes sont sorties du backlog vers les références métier ;
* `make check` passe ;
* `make cc` est exécuté lorsque des assets ont changé ;
* le comportement est vérifié manuellement ;
* aucune régression évidente n’est observée dans les autres modules privés.

---

# Risques identifiés

## R01 — Interface trop lente pendant la séance

Probabilité : élevée.
Impact : critique.

Réponse :

* prototype mobile avant le modèle complet ;
* préremplissage ;
* autosauvegarde ;
* limitation du nombre d’actions ;
* tests pendant de vraies séances.

## R02 — Modèle métier trop générique

Probabilité : moyenne.
Impact : élevé.

Réponse :

* partir des exercices réellement pratiqués ;
* éviter un catalogue universel ;
* ajouter les types de mesure progressivement ;
* préférer les besoins réels aux abstractions prématurées.

## R03 — Confusion entre programme et historique

Probabilité : moyenne.
Impact : critique.

Réponse :

* séparer les entités ;
* créer un instantané lors du démarrage ;
* tester les modifications rétroactives ;
* interdire les mises à jour destructrices.

## R04 — Statistiques trompeuses

Probabilité : élevée.
Impact : élevé.

Réponse :

* comparer les variations exactes ;
* documenter les formules ;
* ne pas agréger les charges incompatibles ;
* afficher les données brutes avec les métriques.

## R05 — Périmètre trop ambitieux

Probabilité : élevée.
Impact : élevé.

Réponse :

* livrer le mode séance avant les fonctions avancées ;
* ne pas démarrer les intégrations externes ;
* limiter les lots ;
* reporter les idées non nécessaires.

## R06 — Abandon après quelques semaines

Probabilité : moyenne.
Impact : critique.

Réponse :

* réduire la friction de saisie ;
* utiliser le module dès le prototype ;
* observer les usages réels ;
* supprimer les champs inutiles ;
* privilégier la vitesse plutôt que l’exhaustivité.

## R07 — Perte de données

Probabilité : faible à moyenne.
Impact : critique.

Réponse :

* autosauvegarde ;
* transactions ;
* protection contre les doubles soumissions ;
* export ;
* sauvegarde ;
* tests de reprise après interruption.

## R08 — Suggestions inadaptées

Probabilité : moyenne.
Impact : moyen à élevé.

Réponse :

* relations explicites ;
* explications ;
* validation manuelle ;
* possibilité de ne rien proposer ;
* prise en compte de l’équipement.

---

# Indicateurs de réussite du module

Le module peut être considéré comme utile si :

* il est utilisé pendant les séances réelles ;
* une série est encodée sans interrompre significativement l’entraînement ;
* les valeurs précédentes évitent de devoir consulter une autre source ;
* les séances ponctuellement modifiées restent compréhensibles ;
* l’historique est suffisamment fiable pour observer une progression ;
* les courbes correspondent aux données réelles ;
* le module remplace les notes dispersées ou la mémorisation informelle ;
* son utilisation ne crée pas une charge administrative disproportionnée.

---

# Prochaine action recommandée

Commencer par le lot 0.

La première passe devrait produire :

```text
docs/private/workout/workout-index.md
docs/private/workout/workout-vision.md
docs/private/workout/workout-domain-model.md
docs/private/workout/workout-mvp-specification.md
```

Ensuite, créer un prototype mobile du mode séance avec quelques exercices statiques avant de démarrer les entités Doctrine.

L’objectif de ce prototype sera de répondre à la question suivante :

> Est-il possible d’enregistrer une série en quelques secondes, avec une seule main, sans casser le rythme de l’entraînement ?
