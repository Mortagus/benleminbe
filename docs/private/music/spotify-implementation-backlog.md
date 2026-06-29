# Backlog D'Implémentation - Intégration Spotify Du Module Music

Date : 2026-06-29

Ce document liste les futurs travaux d'implémentation. Les règles métier et choix structurants vivent dans les autres documents de référence.

## Ordre Recommandé

1. lot 1 - OAuth sécurisé
2. lot 2 - synchronisation et matching
3. lot 3 - genres et revue humaine
4. lot 4 - génération et publication de playlists

## Lot 1 - OAuth Sécurisé

### Travaux Probables

- créer l'entité `SpotifyConnection`
- créer le stockage des états OAuth en attente
- ajouter les secrets Symfony Spotify
- créer les services OAuth et de chiffrement
- ajouter les routes de connexion, callback et déconnexion
- exposer le statut de connexion dans le module Music

### Migrations Probables

- `music_spotify_connections`
- `music_spotify_oauth_states`

### Fichiers Applicatifs Probables

- `src/Private/Music/Controller/SpotifyConnectionController.php`
- `src/Private/Music/Service/Spotify/SpotifyAuthorizeUrlBuilder.php`
- `src/Private/Music/Service/Spotify/SpotifyAccountsClient.php`
- `src/Private/Music/Service/Spotify/SpotifyConnectionService.php`
- `src/Private/Music/Service/Spotify/SpotifyTokenManager.php`
- `src/Private/Music/Service/Spotify/SpotifyRefreshTokenCipher.php`
- `templates/private/music/spotify/connection.html.twig`

### Critères D'Acceptation

- connexion OAuth réussie depuis la zone privée
- refresh token chiffré au repos
- aucun secret dans les logs
- état de connexion visible
- gestion correcte de `invalid_grant`

## Lot 2 - Synchronisation Et Matching

### Travaux Probables

- créer les snapshots Spotify
- créer `SpotifySyncRun`
- ajouter les services de sync par ressource
- ajouter les règles de pagination et de backoff
- implémenter les liaisons prudentes Spotify vers `Artist` et `Track`
- exposer un déclenchement manuel et un résumé de synchro

### Migrations Probables

- `music_spotify_sync_runs`
- `music_spotify_saved_track_snapshots`
- `music_spotify_followed_artist_snapshots`
- `music_spotify_playlist_snapshots`
- `music_spotify_playlist_item_snapshots`
- `music_spotify_track_matches`
- `music_spotify_artist_matches`

### Fichiers Applicatifs Probables

- `src/Private/Music/Controller/SpotifySyncController.php`
- `src/Private/Music/Service/Spotify/SpotifyWebApiClient.php`
- `src/Private/Music/Service/Spotify/SpotifySyncOrchestrator.php`
- `src/Private/Music/Service/Spotify/SpotifySavedTracksSync.php`
- `src/Private/Music/Service/Spotify/SpotifyFollowedArtistsSync.php`
- `src/Private/Music/Service/Spotify/SpotifyPlaylistsSync.php`
- `src/Private/Music/Service/Spotify/SpotifyMatchingService.php`
- `templates/private/music/spotify/sync.html.twig`

### Critères D'Acceptation

- sync manuelle des ressources visées
- gestion correcte des pages et `429`
- conservation des snapshots et checkpoints
- matching non destructif
- aucune dépendance de l'UI locale à un appel Spotify temps réel

## Lot 3 - Genres Et Revue Humaine

### Travaux Probables

- faire évoluer `Genre`
- ajouter `GenreAlias`
- ajouter `GenreSuggestion`
- créer l'écran de revue
- relier les suggestions Spotify aux artistes concernés

### Migrations Probables

- évolution `music_genres`
- `music_genre_aliases`
- `music_genre_suggestions`
- adaptation `music_artist_genres`

### Fichiers Applicatifs Probables

- `src/Private/Music/Controller/GenreReviewController.php`
- `src/Private/Music/Service/Genres/GenreTaxonomyService.php`
- `src/Private/Music/Service/Genres/GenreSuggestionService.php`
- `src/Private/Music/Service/Genres/GenreReviewService.php`
- `templates/private/music/genres/review.html.twig`

### Critères D'Acceptation

- hiérarchie de genres utilisable
- plusieurs genres par artiste
- revue humaine explicite
- distinction nette entre suggestions et genres validés

## Lot 4 - Génération Et Publication De Playlists

### Travaux Probables

- créer le moteur local de sélection
- créer le modèle `GeneratedPlaylist`
- créer l'aperçu avant publication
- ajouter le publisher Spotify
- gérer les réessais sans doublons

### Migrations Probables

- `music_generated_playlists`
- `music_generated_playlist_items`

### Fichiers Applicatifs Probables

- `src/Private/Music/Controller/PlaylistGenerationController.php`
- `src/Private/Music/Service/Playlists/PlaylistGenerator.php`
- `src/Private/Music/Service/Playlists/PlaylistCandidateSelector.php`
- `src/Private/Music/Service/Playlists/PlaylistPreviewBuilder.php`
- `src/Private/Music/Service/Spotify/SpotifyPlaylistPublisher.php`
- `templates/private/music/playlists/generate.html.twig`
- `templates/private/music/playlists/show.html.twig`

### Critères D'Acceptation

- génération locale testable sans réseau
- aperçu validé explicitement
- création d'une playlist privée Spotify
- reprise possible après échec partiel
- snapshot local complet

## Décisions À Trancher Avant Les Prompts D'Implémentation

- hôte local exact pour les tests OAuth réels
- structure exacte des snapshots Spotify
- portée du premier écran de revue des genres
- création seule versus mise à jour de playlist dès le lot 4

## Prompts Futurs Recommandés

1. implémenter le lot 1 OAuth Spotify sécurisé uniquement
2. implémenter le lot 2 synchronisation Spotify et matching prudent
3. implémenter le lot 3 taxonomie de genres et revue humaine
4. implémenter le lot 4 générateur local puis publication Spotify
