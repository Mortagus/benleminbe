# Musique

Documentation du module prive d'analyse de l'historique Spotify.

## Documents

- [Specification de cadrage](music-listening-history-specification.md) : but du module, schema observe dans l'archive, regles de normalisation, limites et proposition de modele.

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
