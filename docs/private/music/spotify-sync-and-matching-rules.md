# Lot 2 - Synchronisation Spotify Et Règles De Rapprochement

Date de rédaction : 2026-06-29
Date de vérification Spotify : 2026-06-29

Ce document décrit le périmètre, les règles et les limites de la future synchronisation Spotify du module Music.

## Faits Confirmés

Points confirmés dans la documentation officielle Spotify :

- `GET /me/tracks` lit les titres sauvegardés avec `user-library-read`, pagination `limit <= 50`, `offset` ;
- `GET /me/following?type=artist` lit les artistes suivis avec `user-follow-read`, pagination par curseur `after`, `limit <= 50` ;
- `GET /me/playlists` lit les playlists du compte avec `playlist-read-private`, pagination `limit <= 50`, `offset`, `offset` maximum `100000` ;
- `GET /playlists/{id}/items` remplace l'ancien endpoint `/tracks`, pagination `limit <= 50`, `offset` ;
- `POST /playlists/{id}/items` accepte au maximum 100 URIs par requête ;
- les réponses Spotify peuvent renvoyer `429` avec `Retry-After` ;
- en `development mode`, les endpoints bulk `GET /tracks`, `GET /albums` et `GET /artists` ont été supprimés ;
- en `development mode`, `playlist.items` n'est disponible que pour les playlists possédées par l'utilisateur ou collaboratives ;
- les champs `genres`, `followers` et `popularity` des artistes ne sont pas des bases fiables pour une vérité métier locale.

Sources officielles :

- https://developer.spotify.com/documentation/web-api/reference/get-users-saved-tracks
- https://developer.spotify.com/documentation/web-api/reference/get-followed
- https://developer.spotify.com/documentation/web-api/reference/get-a-list-of-current-users-playlists
- https://developer.spotify.com/documentation/web-api/reference/get-playlists-items
- https://developer.spotify.com/documentation/web-api/concepts/rate-limits
- https://developer.spotify.com/documentation/web-api/tutorials/february-2026-migration-guide
- https://developer.spotify.com/documentation/web-api/reference/get-an-artist

## Objectifs Du Lot

- synchroniser des données Spotify utiles au module Music ;
- rapprocher prudemment ces données de la base locale existante ;
- conserver les snapshots externes et la provenance ;
- préparer les lots de genres et playlists.

## Hors Périmètre

- remplacement de l'import d'archive locale ;
- synchronisation temps réel ;
- fusion automatique agressive ;
- récupération d'un graphe Spotify exhaustif ;
- enrichissements externes autres que Spotify dans ce lot.

## Périmètre Recommandé De La Première Synchronisation

### À Importer

Premier périmètre recommandé :

- profil minimal du compte connecté via `GET /me` pour cohérence de connexion ;
- titres sauvegardés via `GET /me/tracks` ;
- artistes suivis via `GET /me/following?type=artist` ;
- playlists du compte via `GET /me/playlists` ;
- items des playlists uniquement quand la playlist est possédée par le compte connecté ou collaborative et que l'endpoint renvoie bien `items` ;
- métadonnées unitaires complémentaires nécessaires au rapprochement, via appels individuels :
  - `GET /tracks/{id}`
  - `GET /albums/{id}` si vraiment utile
  - `GET /artists/{id}`

### À Ignorer Dans Le Premier Lot

- recommandations ;
- top tracks d'artiste, supprimé en `development mode` ;
- related artists, endpoint déprécié et peu utile comme source métier ;
- browse categories ;
- new releases ;
- lecture playback en cours ;
- podcasts et épisodes ;
- écriture dans la bibliothèque Spotify ;
- images en masse si elles ne servent pas directement à l'UI.

## Pourquoi Ce Périmètre

- il correspond aux besoins métier demandés ;
- il respecte les limites `development mode` 2026 ;
- il évite de dépendre d'endpoints supprimés ;
- il permet la génération future de playlists sans imposer un miroir complet du compte Spotify.

## Architecture De Synchronisation

Responsabilités probables :

- `SpotifySyncOrchestrator` : orchestration générale d'une synchro ;
- `SpotifySavedTracksSync` ;
- `SpotifyFollowedArtistsSync` ;
- `SpotifyPlaylistsSync` ;
- `SpotifyPlaylistItemsSync` ;
- `SpotifyMatchingService` ;
- `SpotifySyncCheckpointStore` ;
- `SpotifyRateLimitPolicy`.

La synchronisation doit rester manuelle au départ :

- bouton UI `Synchroniser Spotify` ;
- exécution synchrone acceptable si le volume reste raisonnable ;
- conception compatible avec une future commande CLI ou un cron, sans l'exiger tout de suite.

## Modèle De Données Recommandé

### Exécutions

`SpotifySyncRun`

- `id`
- `connection`
- `syncType`
- `status`
- `startedAt`
- `finishedAt`
- `resourceCursor` nullable
- `importedCount`
- `updatedCount`
- `skippedCount`
- `errorCount`
- `lastTechnicalError` nullable
- `rawSummary`

### Snapshots

#### `SpotifySavedTrackSnapshot`

- `connection`
- `spotifyTrackId`
- `spotifyTrackUri`
- `spotifyTrackUrl` nullable
- `spotifyAlbumId` nullable
- `spotifyAlbumUri` nullable
- `addedAt`
- `payload`
- `lastSeenAt`

#### `SpotifyFollowedArtistSnapshot`

- `connection`
- `spotifyArtistId`
- `spotifyArtistUri`
- `spotifyArtistUrl` nullable
- `displayName`
- `imageUrl` nullable
- `payload`
- `lastSeenAt`

#### `SpotifyPlaylistSnapshot`

- `connection`
- `spotifyPlaylistId`
- `spotifyPlaylistUri`
- `spotifyPlaylistUrl` nullable
- `name`
- `ownerSpotifyUserId`
- `isOwnedByCurrentUser`
- `isCollaborative`
- `isPublic` nullable
- `itemsAvailable`
- `snapshotId` nullable
- `payload`
- `lastSeenAt`

#### `SpotifyPlaylistItemSnapshot`

- `playlistSnapshot`
- `spotifyTrackId` nullable
- `spotifyTrackUri` nullable
- `position`
- `addedAt` nullable
- `itemType`
- `payload`
- `lastSeenAt`

### Liaisons Vers Les Entités Locales

`SpotifyTrackMatch`

- entité locale `Track`
- `spotifyTrackId`
- `spotifyTrackUri`
- méthode de rapprochement
- confiance
- statut
- revue humaine éventuelle

`SpotifyArtistMatch`

- entité locale `Artist`
- `spotifyArtistId`
- `spotifyArtistUri`
- méthode de rapprochement
- confiance
- statut

## Règles De Fraîcheur

Recommandations :

- pas de synchronisation automatique à chaque affichage d'écran ;
- afficher la date de dernière synchro réussie ;
- permettre une synchro manuelle explicite ;
- imposer une politique de fraîcheur minimale avant de reproposer un enrichissement massif, par exemple :
  - playlists : pas plus d'une fois toutes les 6 heures hors action explicite ;
  - saved tracks / followed artists : pas plus d'une fois par jour hors action explicite ;
  - métadonnées unitaires d'artiste : rafraîchissement ciblé à la demande ou si snapshot absent.

## Pagination

Règles à documenter et tester :

- saved tracks : `limit=50`, incrément `offset += 50` ;
- followed artists : `limit=50`, pagination par `after` ;
- playlists : `limit=50`, pagination par `offset` ;
- playlist items : `limit=50`, pagination par `offset` ;
- ne jamais supposer qu'une seule page suffit.

## Reprise Après Interruption

Stratégie recommandée :

- une synchro crée un `SpotifySyncRun` persistant ;
- chaque page validée met à jour le checkpoint ;
- un échec garde l'état partiel et le dernier curseur connu ;
- une relance reprend au checkpoint si le type de synchro le permet ;
- si la cohérence n'est plus garantie, relancer proprement la ressource complète concernée, pas forcément toute la plateforme.

## Gestion Des Erreurs Réseau

### `429`

- lire `Retry-After` ;
- attendre le nombre de secondes demandé ;
- réessayer un nombre limité de fois ;
- si le plafond de retries est atteint, arrêter la ressource courante avec état `failed`.

### `5xx` ou timeouts

- retry exponentiel borné ;
- journal technique sans secrets ;
- arrêt propre avec checkpoint conservé.

### `401` / `invalid_grant`

- tenter un refresh token une seule fois si pertinent ;
- si le refresh échoue, marquer la connexion invalide ;
- ne pas boucler.

### Réponses Partielles

- ne jamais supprimer des snapshots existants uniquement parce qu'une page a manqué ;
- distinguer `not_seen_in_this_run` de `confirmed_deleted`.

## Rapprochement Entre Archive Locale Et Spotify

Ordre de priorité recommandé :

1. URI Spotify exacte.
2. ID Spotify exact.
3. correspondance fiable artiste + titre normalisés.
4. correspondance à revoir manuellement.
5. aucune fusion automatique si ambiguïté.

Pseudo-règle :

```text
si uri exacte connue -> liaison automatique fiable
sinon si id exact connu -> liaison automatique fiable
sinon si artiste normalisé + titre normalisé produisent une seule cible plausible -> liaison automatique prudente
sinon -> créer une proposition de rapprochement à revoir
```

## Règles De Non Fusion Destructive

- une ligne d'écoute importée depuis l'archive ne change jamais d'origine ;
- un snapshot Spotify ne remplace jamais le payload brut historique ;
- une métadonnée Spotify absente ne doit pas vider une donnée manuelle locale ;
- plusieurs ressources Spotify ambiguës ne fusionnent jamais un `Artist` ou un `Track` local existant ;
- une URI Spotify ne suffit pas à fusionner deux titres locaux distincts si l'historique local a déjà séparé des variantes légitimes sans preuve plus forte.

## Enrichissements Autorisés Sur Les Entités Locales

Après liaison fiable seulement, enrichissements probables autorisés :

- `Artist` : identifiant Spotify, URI, URL, image principale nullable ;
- `Track` : identifiant Spotify, URI, URL, album Spotify de référence nullable ;
- `Track.albumName` : possible mise à jour seulement si la donnée locale était vide ou explicitement issue d'une source externe moins fiable.

Règle importante :

- une décision manuelle locale doit rester prioritaire sur un enrichissement Spotify contradictoire.

## Interface Privée Minimale

Éléments UI probables :

- bouton `Synchroniser Spotify` ;
- statut de la dernière synchro ;
- détail par ressource ;
- nombre d'éléments importés / mis à jour / ignorés ;
- erreurs techniques non sensibles ;
- liste des rapprochements ambigus à revoir plus tard.

## Tests Recommandés

### Unitaires

- pagination offset ;
- pagination cursor ;
- backoff sur `429` ;
- reprise depuis checkpoint ;
- règles de matching exact, fiable, ambigu ;
- non-écrasement des données manuelles.

### Fonctionnels

- lancement manuel d'une synchro depuis l'UI avec faux client Spotify ;
- affichage des compteurs ;
- conservation d'un état partiel après échec simulé ;
- reprise d'une synchro interrompue.

### Jeux De Fixtures

Prévoir des fixtures JSON locales pour :

- playlists sans `items` ;
- artistes avec `genres` vides ;
- réponses `429` ;
- réponses paginées multi-pages ;
- ressources ambiguës pour le matching.

## Vérifications Manuelles Réelles

- synchro de la bibliothèque sauvegardée ;
- synchro des artistes suivis ;
- synchro d'une playlist personnelle privée ;
- comportement sur une playlist suivie non possédée ;
- second lancement rapproché pour confirmer l'anti-surconsommation ;
- test volontaire après révocation ou expiration de la connexion.

## Migrations Probables

- création de `music_spotify_sync_runs`
- création de `music_spotify_saved_track_snapshots`
- création de `music_spotify_followed_artist_snapshots`
- création de `music_spotify_playlist_snapshots`
- création de `music_spotify_playlist_item_snapshots`
- création de `music_spotify_track_matches`
- création de `music_spotify_artist_matches`
- ajouts éventuels ciblés sur `music_artists` et `music_tracks` pour stocker des identifiants Spotify validés

## Critères D'Acceptation Du Lot 2

- une synchro manuelle importe les saved tracks, artistes suivis et playlists personnelles visées ;
- la pagination est correcte ;
- le système gère `429` et les erreurs temporaires sans casser les données locales ;
- la reprise après interruption est possible ou clairement bornée ;
- les snapshots externes et la provenance sont conservés ;
- les rapprochements ambigus ne sont pas fusionnés automatiquement ;
- la base locale reste utilisable sans Spotify après synchronisation.

## Décisions À Valider Avant Implémentation

- faut-il stocker les payloads bruts complets ou des sous-ensembles normalisés ;
- faut-il synchroniser les images dès le lot 2 ou plus tard ;
- quelle politique exacte de purge appliquer aux snapshots non revus depuis longtemps ;
- faut-il enrichir `Track.albumName` automatiquement quand la liaison Spotify est certaine.
