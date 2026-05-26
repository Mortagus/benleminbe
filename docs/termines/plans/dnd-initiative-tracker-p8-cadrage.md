# P8 - Cadrage Technique De La Persistance Locale

Date de mise à jour : 2026-05-26

Ce document rassemble les points à définir avant d’implémenter la sauvegarde locale `P8` du `DnD Initiative Tracker`.

## Contexte

Le socle DTO est déjà en place dans `assets/scripts/lab/dnd/dtos.js`. Le snapshot versionné couvre déjà :

- les monstres de rencontre ;
- les joueurs ;
- les règles actives ;
- l’ordre du tour ;
- le round courant ;
- l’acteur actif ;
- `savedAt`.

Le bestiaire n’entre pas dans le snapshot. Les joueurs importés conservent aussi `importData`.

## Points À Définir

### 1. Mode De Persistance

Décider si la persistance locale fonctionne :

- en autosave à chaque mutation ;
- avec une sauvegarde manuelle ;
- ou avec un mix des deux.

### 2. Déclencheurs De Sauvegarde

Décider quelles actions écrivent réellement le snapshot :

- création des emplacements monstres ;
- sélection ou remplacement d’un monstre ;
- modification des PV ;
- jet d’initiative des monstres ;
- ajout, suppression ou modification d’un joueur ;
- import XML joueur ;
- activation ou désactivation d’une règle ;
- génération de l’ordre du tour ;
- toggle d’un tour joué ;
- déplacement d’un tour.

### 3. Source De Vérité Au Moment Du Save

Décider si la sauvegarde lit :

- directement `encounter` ;
- ou `encounter` après resynchronisation des joueurs DOM ;
- ou un mix selon le type de donnée.

Le point sensible est la frontière joueur DOM -> `EncounterState`, car les joueurs existent d’abord comme lignes DOM avant d’être poussés dans l’état.

### 4. Stratégie De Restauration

Décider comment le snapshot est réinjecté au démarrage :

- chargement avant l’instanciation des panneaux ;
- chargement après l’instanciation puis hydratation des panneaux ;
- ou chargement hybride.

À préciser surtout pour les joueurs :

- faut-il reconstruire les lignes DOM depuis le snapshot ;
- ou rendre `PlayersPanel` capable de partir d’un état restauré ;
- ou conserver le DOM comme source initiale puis synchroniser vers l’état.

### 5. Compatibilité Des Snapshots

Décider quoi faire avec :

- un snapshot d’une version inconnue ;
- un snapshot incomplet ;
- un snapshot corrompu ;
- un snapshot partiellement restaurable ;
- une future migration de version.

Questions à trancher :

- ignorer silencieusement ;
- effacer la sauvegarde invalide ;
- migrer automatiquement ;
- prévenir l’utilisateur.

### 6. Gestion Des Erreurs `localStorage`

Décider le comportement en cas de :

- quota dépassé ;
- `localStorage` indisponible ;
- navigation privée / stockage désactivé ;
- JSON non sérialisable ou valeur corrompue.

### 7. Politique UX

Décider si `P8` reste invisible ou expose une interface dédiée :

- autosave transparent seulement ;
- bouton de sauvegarde ;
- bouton de restauration ;
- bouton de suppression ;
- indicateur de dernière sauvegarde ;
- message de statut ou non.

### 8. Politique D’Identification

Décider :

- s’il n’existe qu’une seule sauvegarde locale écrasée ;
- ou plusieurs slots ;
- quelle clé `localStorage` utiliser ;
- quel namespace employer pour éviter les collisions avec d’autres outils.

### 9. Contrat D’Amorçage

Décider où s’insère le chargement dans `DndInitiativeTrackerApp` :

- avant la création des panneaux ;
- après la création de `EncounterState` ;
- après l’attache des panneaux DOM ;
- ou dans un module de persistance dédié.

### 10. Couverture De Tests

Prévoir les tests nécessaires pour sécuriser `P8` :

- round-trip snapshot/restauration ;
- persistance des joueurs importés ;
- persistance des monstres sélectionnés ;
- persistance des règles ;
- persistance de l’ordre du tour ;
- restauration d’un snapshot vide ;
- restauration d’un snapshot invalide ou d’une version inconnue ;
- comportement quand `localStorage` échoue.

## Points Déjà Figés

- le snapshot est versionné ;
- le bestiaire n’est pas persisté ;
- les entrées de tour orphelines sont ignorées ;
- un `turnOrder` vide reste vide ;
- les monstres sont restaurés depuis le snapshot ;
- `importData` du joueur importé est conservé dans le DTO ;
- `persistence.js` n’existe pas encore et doit être créé seulement au moment du branchement `localStorage`.

## Référence

- [Backlog DnD Initiative Tracker](dnd-initiative-tracker-backlog.md)
- [`assets/scripts/lab/dnd/dtos.js`](/var/www/projects/benleminbe/assets/scripts/lab/dnd/dtos.js)
- [`assets/scripts/lab/dnd/dnd_initiative.js`](/var/www/projects/benleminbe/assets/scripts/lab/dnd/dnd_initiative.js)

## Décisions de développement

### 1. Mode De Persistance

La persistence locale fonctionne en autosave à chaque mutation.
Faudra voir à l'usage si ce mode est trop gourmand.

### 2. Déclencheurs De Sauvegarde

Voici la liste des déclencheurs de sauvegarde :

- sélection ou remplacement d’un monstre ;
- modification des PV ;
- jet d’initiative des monstres ;
- ajout, suppression ou modification d’un joueur ;
- import XML joueur ;
- génération de l’ordre du tour ;

### 3. Source De Vérité Au Moment Du Save

La source de vérité doit être l'objet qui contient un maximum de données sur l'ensemble du tour.
Si j'ai bien compris, ça doit être `encounter`, mais pour être certain, il faut forcer une resynchro avant de le persister

### 4. Stratégie De Restauration

Le chargement du snapshot ne PEUT PAS se faire automatiquement, j'aimerais laisser la possibilité de l'ignorer si le MJ veut commencer sur une page vierge.
Donc il faut prévoir un bouton pour déclencher le chargement du snapshot.
Le snapshot récupéré depuis le localStorage doit être vérifié avant de le charger pour s'assurer qu'il est valide et qu'il ne contient pas de données corrompues ou non cohérentes.
Ensuite il doit être injecté sur `encounter`.
Une fois la mémoire du tracker chargée depuis le snapshot, il faut procéder au processus suivant : EncounterState -> hydrateModules -> refreshPanels

### 5. Compatibilité Des Snapshots

- un snapshot d’une version inconnue : affichage d'un message d'erreur discret pour prévenir le user.
- un snapshot incomplet ou partiellement restaurable : on applique ce qu'on peut et ce qui n'est pas rempli est juste ignoré.
- un snapshot corrompu : on ignore le snapshot et on prévient le MJ.
- une future migration de version : on ignore le snapshot et on prévient le MJ.

### 6. Gestion Des Erreurs `localStorage`

Chaque erreur doit être affichée au MJ dans une boîte de dialogue modale.
Le message d'erreur doit être explicite et clair pour aider le MJ à comprendre et à prendre une décision.
Le message d'erreur doit être facilement copiable afin de pouvoir être partagé avec le développeur ou le support technique.

### 7. Politique UX

Je pense que le plus simple c'est une toute petite zone de texte pour afficher le statut de la sauvegarde.
Cette zone de texte affichera la date et l'heure de la dernière sauvegarde, ce qui devrait apporter un tout petit peu de transparence.
Il faut voir si c'est tenable point de vue UX, mais je verrais bien un petit bouton de restauration de la dernière sauvegarde juste à côté de cette zone de texte, en gardant un caractère très discret.
Vu que la sauvegarde écrase le slot à chaque fois, pas besoin d'un bouton de suppression.
Vu que la sauvegarde est automatique à certains évènements, pas besoin d'un bouton de sauvegarde explicite.

### 8. Politique D’Identification

Il n'existera qu'un seul slot de sauvegarde par navigateur.
Concernant la clé à utiliser, je ne sais pas trop, mais je pense que `dnd-initiative-tracker-save` serait une bonne option.
Concernant le namespace, je pense que `dnd-initiative-tracker` serait une bonne option pour éviter les collisions avec d’autres outils.

### 9. Contrat D’Amorçage

Là, j'avoue, je ne sais pas ce qu'il convient de faire pour le contrat d'amorçage.
Il faut de toute façon un module spécifique pour gérer le chargement initial des données de la sauvegarde.
Je pense que ce module doit être une dépendance de DndInitiativeTrackerApp.
Au chargement de la page, on détecte s'il existe déjà une sauvegarde dans le localStorage et demande confirmation à l'utilisateur avant de la charger.
Si l'utilisateur refuse, on charge les données par défaut.
Si l'utilisateur accepte, on charge la sauvegarde.
Et là on revient au comportement décrit au point 4.

## Clôture

Le cadrage a été appliqué et le travail de persistance locale a été livré le 2026-05-26.

Vérification passée pendant la livraison :

```bash
make check
```
