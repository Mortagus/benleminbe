# Specification De Cadrage - Module Musique

Date : 2026-06-24

Ce document cadre le premier lot du module prive d'analyse de l'historique Spotify.

Le but est de rester volontairement simple et robuste:

- importer une archive Spotify ZIP locale;
- analyser les fichiers JSON utiles a l'historique d'ecoute;
- conserver des donnees brutes suffisantes pour corriger la normalisation plus tard;
- proposer des statistiques et listes de consultation utiles;
- ne pas inferer automatiquement des donnees douteuses, notamment les styles.

## But Du Module

Le module doit aider a explorer l'historique musical personnel de facon progressive.

Les questions visees sont les suivantes:

- combien d'ecoutes sont presentes dans l'archive;
- quelle periode historique est couverte;
- quels artistes, titres et albums sont les plus ecoutes;
- combien de temps a ete consacre a chaque artiste;
- quels artistes sont peu ecoutes, anciens ou absents;
- quelle repartition existe par annee, mois, jour ou moment de la journee;
- quels styles sont associes manuellement plus tard.

## Sources De Donnees

### Archive De Reference

Archive locale observee:

- `var/private/music/my_spotify_data.zip`

### Fichiers JSON Observes Dans L'Archive

Fichiers principaux de l'archive:

- `Spotify Account Data/StreamingHistory_music_0.json`
- `Spotify Account Data/StreamingHistory_podcast_0.json`
- `Spotify Account Data/YourLibrary.json`
- `Spotify Account Data/Wrapped2025.json`
- `Spotify Account Data/SearchQueries.json`
- `Spotify Account Data/Marquee.json`
- `Spotify Account Data/AgentGateway.json`
- `Spotify Account Data/Follow.json`
- `Spotify Account Data/Identifiers.json`
- `Spotify Account Data/Inferences.json`
- `Spotify Account Data/MessageData.json`
- `Spotify Account Data/Payments.json`
- `Spotify Account Data/Playlist1.json`
- `Spotify Account Data/Playlist2.json`
- `Spotify Account Data/PodcastInteractivityRatedShow.json`
- `Spotify Account Data/PodcastInteractivityReactions.json`
- `Spotify Account Data/UserAttributes.json`
- `Spotify Account Data/UserPrompts.json`
- `Spotify Account Data/YourSoundCapsule.json`
- `Spotify Account Data/Read_Me_First.pdf`

Les deux seuls fichiers directement utiles au premier lot sont:

- `StreamingHistory_music_0.json` pour les ecoutes musicales;
- `YourLibrary.json` comme metadonnees de reference optionnelles, mais non comme source de verite du temps d'ecoute.

## Schema Reel Observe

### `StreamingHistory_music_0.json`

Structure:

- liste JSON;
- 8841 entrees dans l'archive observee;
- un seul fichier music historique detecte dans cette archive;
- une ligne represente une ecoute.

Champs observes pour chaque ecoute:

- `endTime` : date et heure de fin au format `YYYY-MM-DD HH:mm`;
- `artistName` : nom de l'artiste;
- `trackName` : titre du morceau;
- `msPlayed` : duree ecoutee en millisecondes.

Champs absents:

- identifiant Spotify d'artiste;
- identifiant Spotify de titre;
- identifiant d'album;
- nom d'album;
- contexte de lecture;
- appareil;
- URI Spotify;
- champ de version de schema;
- heure de debut;
- precision a la seconde;
- fuseau horaire explicite.

Limites observees:

- la date est a la minute, pas a la seconde;
- aucun offset de fuseau horaire n'est fourni;
- `msPlayed` mesure le temps ecoute, pas la duree canonique du morceau;
- certaines lignes ont `msPlayed = 0`;
- la structure ne contient pas d'album ni de contexte.

Période observee dans l'archive:

- debut: 2025-06-05 11:07;
- fin: 2026-06-06 19:52.

### `StreamingHistory_podcast_0.json`

Structure:

- liste JSON;
- meme schema general que l'historique musical, mais pour les podcasts.

Champs observes:

- `endTime`;
- `podcastName`;
- `episodeName`;
- `msPlayed`.

Decision:

- ce fichier est ignore pour le premier lot musical;
- il reste utile pour documenter la variete de l'archive et la prudence du parseur.

### `YourLibrary.json`

Structure:

- objet JSON;
- contient notamment `tracks`, `albums`, `artists`, `shows`, `episodes`;
- les sous-listes utiles pour la musique sont `tracks`, `albums` et `artists`.

Champs observes dans `tracks`:

- `artist`;
- `album`;
- `track`;
- `uri`.

Champs observes dans `albums`:

- `artist`;
- `album`;
- `uri`.

Champs observes dans `artists`:

- `name`;
- `uri`.

Limites observees:

- c'est une vue de bibliotheque, pas un historique d'ecoute;
- ce n'est pas une preuve suffisante pour reconstituer tous les albums ecoutes;
- plusieurs couples artiste+titre apparaissent avec plusieurs albums ou plusieurs URIs;
- la relation album/titre est donc partiellement ambigue;
- ce fichier peut servir a enrichir certaines donnees quand l'association est univoque, mais pas a faire de la fusion aveugle;
- l'import musical ne doit pas echouer si ce fichier est absent, invalide ou incomplet;
- son contenu ne sert jamais a identifier un titre de facon prioritaire par rapport a `StreamingHistory_music_*.json`.

### Autres Fichiers

Les autres fichiers JSON observes dans l'archive sont hors perimetre du premier lot:

- metadonnees de compte;
- recherches;
- messages;
- interactions podcasts;
- recapitulatif Wrapped;
- capsules et recommandations.

Ils ne doivent pas devenir des sources de verite pour l'historique musical.

## Donnees Retenues

Le premier lot conserve:

- les ecoutes musicales provenant de `StreamingHistory_music_0.json`;
- les donnees brutes de chaque ligne importee;
- l'horodatage de lecture;
- le nom brut de l'artiste;
- le nom brut du titre;
- la duree ecoutee;
- un fingerprint technique par ligne, stable pour un import donne;
- le hash de l'archive importee;
- les statistiques materialisees derivees des ecoutes;
- les agrégats par artiste et par titre;
- les styles saisis manuellement plus tard.

## Workflow D'Import Retenu

Le traitement de l'archive se fait en pipeline streaming, fichier par fichier:

1. inspection du ZIP pour lister les fichiers musicaux, les podcasts ignores et la presence eventuelle de `YourLibrary.json`;
2. lecture d'un seul `StreamingHistory_music_*.json` a la fois depuis un `ZipArchive::getStream()`;
3. parsing JSON incrementiel, evenement par evenement, sans `json_decode()` du fichier complet;
4. transformation en DTO leger avec fingerprint technique, puis accumulation dans un batch limite;
5. resolution des artistes et titres par indexes scalaires uniquement;
6. ecritures DBAL de la batch courante, mise a jour des indexes scalaires, puis reinitialisation des caches temporaires;
7. resume final materialise avec compteurs, periode, duree et memoire observee.

Le traitement reste lance depuis la requete web d import. Le pipeline etend donc explicitement sa fenetre d execution pour ne pas s arreter au milieu d un gros dataset. Si l environnement d hebergement applique un timeout dur cote serveur web, il faudra alors basculer l import vers un traitement asynchrone plus tard.

Choix importants:

- ne pas relire le fichier historique complet plusieurs fois;
- ne pas construire un plan d'import contenant tous les evenements;
- ne pas conserver d'entites Doctrine detachees dans des caches globaux;
- garder les caches persistants sous forme de maps scalaires `normalized_name => id`;
- ne pas utiliser `YourLibrary.json` comme source de verite pour l'identite des titres;
- garder le traitement idempotent grace au hash de l'archive et au fingerprint des lignes.

## Resume Et Metriques

Le resume d'import expose au moins:

- les fichiers musicaux detectes;
- les fichiers podcast ignores;
- le nombre de lignes lues;
- le nombre de lignes valides;
- le nombre de lignes ignorees;
- le nombre d'artistes crees;
- le nombre de titres crees;
- le nombre d'ecoutes creees;
- le nombre d'ecoutes deja presentes ou ignorees;
- la periode detectee;
- la duree totale importee;
- la duree totale de l'import;
- la memoire maximale observee.

Le suivi memoire est fait par relevés periodiques de `memory_get_usage(true)` et `memory_get_peak_usage(true)` pendant le traitement des lots.

## Gestion Des Echecs

- le `MusicImport` passe a `processing` au demarrage, puis a `completed` ou `failed` en fin de traitement;
- les lots deja commites restent conservees si une erreur survient apres leur commit;
- un echec ne doit pas masquer l'etat partiel deja sauvegarde;
- le meme fichier ZIP reste considere comme deja vu via son checksum, donc un reimport identique est traite comme un doublon;
- les artistes et titres crees avant l'echec restent en base si leur lot avait deja ete commitee.

## Reinitialisation Complete

Le module expose un reset hard volontaire pour repartir de zero quand c'est necessaire:

- le reset supprime les imports, les ecoutes, les titres, les artistes et les liens artistes/styles;
- les genres saisis manuellement restent conserves;
- apres reset, le meme ZIP peut etre importe a nouveau comme un premier import;
- si aucun import n'existe encore, le bouton de reset reste cache et l'UI indique explicitement que le premier import est a venir.

## Donnees Volontairement Ignorees

Le premier lot ignore volontairement:

- les podcasts;
- les fichiers de recommandations ou de profil Spotify;
- les recherches;
- les messages;
- les playlists;
- les genres inferes automatiquement;
- les donnees externes de Spotify API;
- OAuth Spotify;
- la synchronisation automatique;
- les playlists generees automatiquement.

## Regles De Normalisation

### Noms D'Artistes Et De Titres

Normalisation prudente:

- trim;
- collapse des espaces successifs;
- comparaison insensible a la casse;
- conservation de la forme d'affichage la plus utile sans fusionner des artistes distincts sur simple similarite textuelle.

Regles importantes:

- ne pas fusionner deux artistes differents parce que leurs noms se ressemblent;
- ne pas deduire qu'un titre est identique a une version differente sans preuve plus solide;
- ne pas enlever automatiquement les mentions `feat.` ou les variantes editoriales si cela change le sens du morceau;
- ne pas casser les accents ou la ponctuation dans la valeur d'affichage.

### Duree

- `msPlayed` est stocke comme duree ecoutee;
- `0` est conserve comme valeur valide si la ligne le dit explicitement;
- une valeur absente ou invalide est consideree comme manquante.

### Date Et Heure

- `endTime` est parse dans le fuseau de l'application, faute de fuseau fourni par l'archive;
- la precision reste a la minute;
- la date de debut et de fin des periodes est derivee des lignes importees.

### De-duplication

La duplication est traitee avec prudence:

- un import d'archive est identifie par le hash de l'archive ZIP;
- un reimport de la meme archive est traite comme un doublon et ne relance pas le pipeline;
- les lignes d'ecoute conservent un fingerprint technique stable pour l'import courant;
- le garde-fou final cote base est l'unicite de la paire `import_id + fingerprint`;
- la fusion de deux artistes ou titres ne se fait jamais sur simple ressemblance;
- une paire d'ecoutes identiques en apparence ne doit pas etre fusionnee si elle provient d'archives differentes sans regle explicite.

## Modele De Donnees Propose

Le modele doit rester normalise, lisible et exploitable pour les statistiques.

### `MusicImport`

- `id`
- `originalFilename`
- `importedAt`
- `sourceType`
- `archiveChecksum`
- `status`
- `summary`
- `errorMessage`

Rôle:

- tracer une archive importee;
- rendre l'import idempotent;
- garder un resume de traitement lisible.
- marquer explicitement `processing`, `completed` ou `failed`.

### `ListeningEvent`

- `id`
- `import`
- `playedAt`
- `trackName`
- `artistNameRaw`
- `artistNameNormalized`
- `albumName` nullable
- `playedDurationMs` nullable
- `trackUri` nullable
- `sourcePayloadVersion` nullable
- `fingerprint`
- `rawPayload`

Rôle:

- conserver l'historique brut et exploitable;
- servir de source de verite pour les statistiques;
- permettre une re-normalisation ulterieure.
- garantir l'idempotence d'un import via le fingerprint de ligne associe a l'import.

### `Artist`

- `id`
- `normalizedName`
- `displayName`
- `firstPlayedAt` nullable
- `lastPlayedAt` nullable
- `listeningCount`
- `totalPlayedMs`

Rôle:

- servir de vue materialisee pour les listes;
- accelerer les agrégats;
- porter les styles associes plus tard.

### `Track`

- `id`
- `artist`
- `normalizedTitle`
- `displayTitle`
- `albumName` nullable
- `spotifyUri` nullable
- `firstPlayedAt` nullable
- `lastPlayedAt` nullable
- `listeningCount`
- `totalPlayedMs`

Rôle:

- servir de vue materialisee des titres ecoutes;
- rendre la recherche et le tri simples;
- conserver les metadonnees disponibles sans surestimer la fiabilite des albums.

### `ArtistGenre`

- `artist`
- `genre`

Rôle:

- liaison simple pour associer un ou plusieurs styles a un artiste;
- relation manuelle uniquement;
- aucune extraction automatique depuis Spotify.

### `Genre`

- `id`
- `name`
- `slug`

Rôle:

- styles musicaux saisis manuellement;
- aucune inference automatique;
- aucun service externe.

## Ecrans Prevus Pour Le Premier Lot

### 1. Tableau De Bord `/private/music`

Indicateurs prevus:

- nombre total d'ecoutes importees;
- duree totale ecoutee;
- periode couverte;
- nombre d'artistes distincts;
- nombre de titres distincts;
- nombre d'albums distincts si une metadonnee d'album fiable est disponible;
- date du dernier import;
- top 5 artistes;
- top 5 titres;
- acces direct vers l'import;
- acces direct vers les listes.

### 2. Import `/private/music/import`

Fonction:

- uploader un ZIP Spotify;
- verifier le format;
- afficher un resume lisible du traitement;
- signaler les fichiers detectes, les lignes traitees, les lignes ignorees et les erreurs.

### 3. Liste Des Artistes `/private/music/artists`

Fonction:

- recherche textuelle;
- tri serveur;
- pagination;
- affichage des styles associes si disponibles;
- resume des ecoutes et des durees.

### 4. Liste Des Titres `/private/music/tracks`

Fonction:

- recherche textuelle;
- tri serveur;
- pagination;
- affichage de l'artiste, de l'album si disponible et de l'URI Spotify si fiable;
- resume des ecoutes et des durees.

### 5. Liste Des Albums `/private/music/albums`

Decision:

- reporter l'ecran tant que les donnees d'album ne sont pas suffisamment fiables ou couverture n'est pas assez claire.

### 6. Styles `/private/music/genres`

Fonction minimale seulement:

- lister les styles saisis;
- permettre une gestion manuelle simple si cela reste petit et coherent.

Si cette partie grossit, elle doit devenir un vrai sous-sujet documentaire.

## Hors Perimetre Explicite

Ce premier lot n'inclut pas:

- OAuth Spotify;
- appel a l'API Spotify;
- synchronisation externe;
- generation de playlists;
- inference automatique des styles;
- enrichissement de genre par service externe;
- reconstitution exhaustive des albums quand le format ne le permet pas;
- machine de recommandation.

## Limites Connues

- le fichier musical ne contient qu'un debut et une fin de lecture, pas le debut du morceau;
- les titres identiques peuvent representer plusieurs versions;
- les artistes peuvent changer de casse ou de forme selon les exports;
- l'album n'est pas present dans l'historique musical brut;
- les styles ne doivent pas etre devines;
- le fichier `YourLibrary.json` n'est pas suffisant pour garantir une reconstruction exhaustive des albums ecoutes;
- une partie des metadonnees de l'archive est hors sujet pour ce module;
- l'import applicatif est traite par lots avec des ecritures DBAL limitees pour limiter la memoire;
- le parseur lit l'archive sans extraire le ZIP sur disque;
- chaque fichier `StreamingHistory_music_*.json` est lu une seule fois, mais reste traite en memoire le temps de sa passe;
- les caches durables de l'import sont scalaires, pas des entites Doctrine;
- la taille du ZIP reste a surveiller, meme si le fichier n'est plus redecodé completement plusieurs fois.

## Conclusion De Cadrage

Le premier lot doit donc:

- traiter les fichiers `StreamingHistory_music_*.json` un par un;
- conserver le brut utile;
- materialiser les agrégats principaux localement;
- rester prudent sur les albums et les styles;
- garder un workflow d'import simple a raisonner sans relire completement le JSON historique;
- laisser ouverte l'evolution vers de meilleures vues sans rearchitecture.
