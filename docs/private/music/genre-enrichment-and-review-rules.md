# Lot 3 - Enrichissement Et Revue Humaine Des Genres

Date de rédaction : 2026-06-29
Date de vérification Spotify : 2026-06-29

Ce document décrit le futur système de genres du module Music.

## Sources Spotify Confirmées

Sources officielles utiles à ce lot :

- https://developer.spotify.com/documentation/web-api/reference/get-an-artist
- https://developer.spotify.com/documentation/web-api/tutorials/february-2026-migration-guide

## Principe Central

Un genre fourni par Spotify ou par une autre source externe n'est jamais un genre métier définitif.

Équivalence interdite :

```text
genre externe
!=
genre validé dans la taxonomie personnelle
```

## Objectifs

- gérer une taxonomie musicale personnelle cohérente ;
- supporter des sous-genres de métal avec hiérarchie claire ;
- permettre plusieurs genres par artiste ;
- intégrer des suggestions externes sans perte de contrôle humain ;
- rendre la revue simple et progressive.

## Hors Périmètre

- classification automatique définitive ;
- taxonomie universelle exhaustive ;
- rattachement aux albums et titres dès le premier lot de revue ;
- implémentation effective de MusicBrainz ou Last.fm dans cette tâche.

## Recommandations Structurantes

### Hiérarchie

Décision recommandée :

- oui, gérer une hiérarchie dès maintenant ;
- limiter cette hiérarchie à un arbre simple ;
- un `Genre` a au plus un parent direct ;
- éviter un graphe multi-parents qui rendrait la revue et les filtres beaucoup plus fragiles.

Exemples visés :

```text
Metal
└── Death metal
    └── Melodic death metal

Metal
└── Doom metal
    └── Death-doom

Metal
└── Black metal
    └── Atmospheric black metal
```

### Multiplicité

Décision recommandée :

- oui, autoriser plusieurs genres validés par artiste ;
- commencer par les artistes seulement ;
- préparer une extension ultérieure vers albums ou titres sans l'implémenter maintenant.

### Contrôle De La Taxonomie

Pour éviter des tags incohérents :

- un `Genre` validé doit avoir un slug canonique ;
- la création d'un nouveau genre doit passer par l'interface de revue, pas par un texte libre omniprésent ;
- les synonymes doivent être portés par des alias ;
- les genres trop vagues doivent pouvoir être marqués comme rejetés en tant que suggestions.

## Modèle Métier Recommandé

### `Genre`

Évolution recommandée du modèle existant :

- `id`
- `name`
- `slug`
- `parent` nullable
- `description` nullable
- `isActive`
- `createdAt`
- `updatedAt`

### `GenreAlias`

- `id`
- `genre`
- `alias`
- `normalizedAlias`
- `source`

Usage :

- `melodeath` peut pointer vers `Melodic death metal`
- `blackened death metal` peut être conservé comme alias si la taxonomie personnelle choisit une autre forme canonique

### `GenreSuggestion`

- `id`
- `artist`
- `suggestedGenreText`
- `normalizedSuggestedGenreText`
- `source`
- `rawValue`
- `confidence` nullable
- `status`
- `createdAt`
- `reviewedAt` nullable
- `reviewedGenre` nullable
- `reviewDecisionNote` nullable

Statuts recommandés :

- `proposed`
- `accepted`
- `rejected`
- `superseded`

Sources extensibles :

- `manual`
- `spotify`
- `musicbrainz`
- `lastfm`
- `other`

### `ArtistGenre`

Le lien métier validé actuel peut rester la source de vérité des genres acceptés, à condition d'évoluer au besoin pour garder la trace minimale de validation.

Recommandation :

- conserver `ArtistGenre` comme lien métier validé ;
- stocker la provenance détaillée dans `GenreSuggestion`, pas dans chaque lien validé ;
- ajouter une date de mise à jour si cela devient utile.

## Workflow De Revue Humaine

Écrans minimaux attendus :

- artistes sans genre validé ;
- suggestions externes en attente ;
- genres validés actuels ;
- suggestions rejetées ;
- alias connus ;
- genres trop vagues à requalifier.

Actions attendues :

- accepter une suggestion ;
- refuser une suggestion ;
- remplacer par un genre existant ;
- créer un nouveau genre canonique ;
- associer un alias à un genre existant ;
- marquer une suggestion comme trop vague ;
- superséder une ancienne suggestion.

## Règles De Décision

### Acceptation

Accepter une suggestion seulement si :

- elle correspond à la taxonomie personnelle voulue ;
- elle n'entre pas en collision avec un genre existant plus précis ;
- elle apporte une valeur réelle de classement.

### Refus

Refuser une suggestion si :

- elle est trop large ;
- elle est trop floue ;
- elle reflète une mode marketing plutôt qu'un classement personnel utile ;
- elle contredit une connaissance personnelle mieux fondée.

### Supersession

Une suggestion peut être `superseded` si :

- une suggestion plus précise a remplacé l'ancienne ;
- un genre canonique a été créé et l'ancienne valeur brute doit rester historique.

## Règles Pour Les Genres Spotify

Contrainte importante :

- le champ `artist.genres` Spotify est documenté comme `Deprecated`.

Conséquence :

- une valeur Spotify de genre ne doit être traitée que comme signal faible ;
- elle peut créer une `GenreSuggestion` ;
- elle ne doit jamais déclencher automatiquement un `ArtistGenre` validé ;
- l'absence de genre Spotify n'indique rien de métier ;
- un changement futur de Spotify ne doit pas casser le workflow de revue.

## Taxonomie Personnelle Recommandée

Principes :

- préférer peu de genres stables à une explosion de tags ;
- séparer genre racine, sous-genre et alias ;
- éviter de garder des doublons orthographiques comme genres canoniques ;
- assumer des décisions personnelles de classement, même si elles divergent d'une source externe.

Exemples :

- canonique : `Melodic death metal`
- alias accepté : `melodeath`
- suggestion rejetée comme trop vague : `metal`

## Extension Future Vers D'Autres Fournisseurs

La source d'une suggestion doit rester un attribut du modèle, pas un branchement spécifique au fournisseur dans le coeur métier.

Architecture recommandée :

- adaptateurs de collecte par fournisseur ;
- DTO internes de suggestion normalisée ;
- service central de création ou mise à jour des `GenreSuggestion`.

Ainsi, l'ajout futur de MusicBrainz ou Last.fm ne devra pas imposer de refonte du workflow de revue.

## Interface Privée Probable

Routes probables :

- `GET /private/music/genres`
- `GET /private/music/genres/review`
- `POST /private/music/genres/suggestions/{id}/accept`
- `POST /private/music/genres/suggestions/{id}/reject`
- `POST /private/music/genres/suggestions/{id}/supersede`
- `POST /private/music/genres/create`

Services probables :

- `GenreTaxonomyService`
- `GenreSuggestionService`
- `GenreReviewService`
- `GenreAliasResolver`

Templates probables :

- `templates/private/music/genres/index.html.twig`
- `templates/private/music/genres/review.html.twig`

## Tests Recommandés

### Unitaires

- création d'un slug canonique ;
- rattachement d'un alias ;
- refus d'une suggestion trop vague ;
- calcul des artistes sans genre validé ;
- supersession d'une suggestion.

### Fonctionnels

- affichage des artistes sans genre ;
- acceptation d'une suggestion ;
- rejet d'une suggestion ;
- création d'un nouveau genre enfant ;
- liaison à un genre existant.

## Vérifications Manuelles Réelles

- revue d'un artiste sans genre ;
- revue de plusieurs suggestions proches ;
- création d'un sous-genre sous `Metal` ;
- refus d'une suggestion `Metal` jugée trop vague ;
- affichage correct des genres validés sur les listes artistes et titres.

## Migrations Probables

- évolution de `music_genres` avec `parent_id`, timestamps et éventuels champs de statut
- création de `music_genre_aliases`
- création de `music_genre_suggestions`
- adaptation éventuelle de `music_artist_genres`

## Critères D'Acceptation Du Lot 3

- les genres validés restent distincts des suggestions ;
- un artiste peut recevoir plusieurs genres validés ;
- la hiérarchie parent/enfant fonctionne ;
- la revue humaine permet accepter, refuser, remplacer et créer ;
- une suggestion Spotify ne devient jamais automatiquement un genre validé ;
- la taxonomie reste cohérente et lisible dans l'interface.

## Décisions À Valider Avant Implémentation

- faut-il autoriser un ordre manuel des genres frères ;
- faut-il stocker une note personnelle sur un genre ;
- faut-il afficher les suggestions rejetées par défaut ou seulement sur demande ;
- faut-il étendre `ArtistGenre` avec un champ `isPrimary`.
