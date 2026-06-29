# Musique

Documentation du module prive d'analyse de l'historique Spotify et de la future intégration Spotify contrôlée.

## Documents

- [Specification de cadrage](music-listening-history-specification.md) : but du module, schema observe dans l'archive, regles de normalisation, limites et proposition de modele.
- [Architecture cible de l integration Spotify](spotify-integration-architecture.md) : vision, contraintes Spotify vérifiées, frontières de responsabilité et modèle cible.
- [Connexion OAuth securisee](spotify-oauth-security.md) : lot 1, secrets, tokens, états de connexion et tests.
- [Synchronisation et matching](spotify-sync-and-matching-rules.md) : lot 2, périmètre de sync, pagination, erreurs réseau et rapprochement prudent.
- [Genres et revue humaine](genre-enrichment-and-review-rules.md) : lot 3, taxonomie personnelle, suggestions externes et workflow de validation.
- [Generation et publication de playlists](playlist-generation-specification.md) : lot 4, logique locale, aperçu et publication Spotify.
- [Backlog d implementation Spotify](spotify-implementation-backlog.md) : découpage des futurs travaux et critères d'acceptation.

## Routes Et Ecrans Vises

- tableau de bord `/private/music`;
- import ZIP `/private/music/import`;
- liste des artistes `/private/music/artists`;
- liste des titres `/private/music/tracks`;
- liste des albums `/private/music/albums` si les donnees deviennent suffisamment fiables;
- liste des styles `/private/music/genres`.

## Perimetre

Le module conserve une orientation usage personnel plutot qu'une dependance durable a Spotify.
L'import vient de l'archive officielle telechargee depuis le compte Spotify, mais le modele doit rester exploitable si le format evolue ou si d'autres sources d'ecoute sont ajoutees plus tard.
Le pipeline lit les fichiers `StreamingHistory_music_*.json` un par un, en flux, avec des batchs limites, des indexes scalaires pour les artistes et les titres, puis des ecritures DBAL directes pour les lots d'ecoutes afin d'eviter de saturer l'ORM. L'en-tete `MusicImport` reste gere separement pour l'etat de traitement.
`YourLibrary.json` reste une source d'enrichissement facultative, sans fusion agressive avec l'historique d'ecoute.
Les styles musicaux restent manuels et ne sont pas inférés depuis l'archive Spotify.
La future integration Spotify doit rester un fournisseur externe de synchronisation et de metadonnees, pas une nouvelle source de verite de l'historique ou des genres.
