# Règles De Matching Des Contacts

Date de mise à jour : 2026-06-01

Ce document décrit les règles utilisées pour décider si un contact en création ou en mise à jour doit être rapproché d'un contact existant.

Le matching décrit ici n'est pas un score de doublon. C'est une règle de décision directe, utilisée avant l'écriture du contact.

## Portée

Ce document concerne:

- la normalisation d'un payload contact;
- la recherche d'un contact existant au moment d'un ajout ou d'une importation;
- la politique de fusion lors d'une écriture avec `merge = true`.

Il s'appuie principalement sur:

- `ContactWritePolicyService`;
- `ContactImportService`;
- `ContactService`.

## Objectif Métier

Le matching à l'écriture doit limiter la création de doublons trivials sans empêcher l'enrichissement progressif d'une fiche existante.

Il sert à répondre à une question simple:

- ce contact correspond-il déjà à une fiche connue?

La règle est volontairement pragmatique:

- si un identifiant fort existe, on réutilise la fiche existante;
- si plusieurs signaux de contact convergent, on fusionne ou on met à jour;
- si rien de fiable ne relie les deux fiches, on crée un nouveau contact.

## Normalisation Du Payload

Avant toute recherche, le payload entrant est normalisé.

### Champs De Base

- `display_name` est trimé;
- `first_name` est trimé;
- `last_name` est trimé;
- `organization` est normalisé comme un nom d'organisation;
- `role` est trimé;
- `email` est converti en liste normalisée;
- `phone` est converti en liste normalisée;
- `profile_url` est trimée;
- `source` est trimée;
- `priority` et `relationship_status` sont validés contre les enums métier;
- `tags` est normalisé en liste;
- `created_at`, `updated_at`, `last_contact_at`, `next_action_at` sont parsés en dates.

### Règle De Nom

Si `display_name` est vide, il est reconstruit dans cet ordre:

1. `first_name + last_name`;
2. `organization + role`;
3. sinon le contact est refusé.

Le matching considère donc qu'un contact doit toujours avoir un nom affichable exploitable.

### Canal Principal

Si `main_channel` est vide, il est déduit:

- `email` si des emails existent;
- `téléphone` si des numéros existent;
- vide sinon.

### Source

Si `source` est vide, la source technique passée au flux est utilisée.

## Recherche D'Un Contact Existant

La méthode `findMatchingContactIndex()` compare le candidat à tous les contacts existants et retourne le premier index correspondant.

L'ordre de priorité est le suivant:

1. email partagé;
2. téléphone partagé;
3. profil normalisé identique;
4. clé de nom compatible.

Le premier match gagne.

### 1. Email Partagé

Si le candidat et le contact existant ont au moins un email normalisé en commun, ils sont considérés comme le même contact.

Règle:

- forte;
- directe;
- prioritaire.

### 2. Téléphone Partagé

Si le candidat et le contact existant partagent au moins un numéro normalisé, ils sont considérés comme le même contact.

Règle:

- très forte;
- directe;
- prioritaire.

### 3. Profil Identique

Si les URLs de profil normalisées sont identiques, le contact est considéré comme déjà connu.

Règle:

- très forte;
- utile pour LinkedIn et les profils externes;
- indépendante du nom affiché.

### 4. Clés De Nom

Si aucune clé forte ne matche, le système compare des clés construites à partir:

- de `display_name + organization`;
- de `first_name + last_name + organization`;
- de l'initiale du prénom + `last_name + organization`.

Ces clés sont construites via `buildContactNameKeys()`.

Elles servent à reconnaître des variantes de saisie ou des doublons très proches.

## Ce Que Le Matching Ne Fait Pas

Le matching à l'écriture ne calcule pas de score pondéré.

Il ne mesure pas:

- la proximité textuelle;
- la qualité globale de la fiche;
- une probabilité de doublon;
- un seuil de confiance.

Il fait un choix direct: oui ou non.

## Politique De Fusion Lors D'Une Écriture

Quand un contact existant est trouvé, l'écriture passe en mode `merge = true`.

Dans ce cas:

- les valeurs utiles sont fusionnées au lieu d'être écrasées aveuglément;
- les listes `email`, `phone` et `tags` sont unionnées;
- les dates sont conservées selon leur logique métier;
- les champs LinkedIn ont une priorité spécifique si la source entrante n'est pas LinkedIn;
- `created_at` est conservé depuis la fiche existante si elle avait déjà été reconnue.

## Signaux Forts, Moyens Et Faibles

### Signaux Forts

- email partagé;
- téléphone partagé;
- profil identique.

Ces signaux suffisent à faire matcher deux fiches.

### Signaux Moyens

- clé de nom avec organisation;
- variantes de nom déjà structurées;
- initiale du prénom + nom + organisation.

Ces signaux peuvent faire matcher deux fiches, mais ils sont moins fiables que les identifiants directs.

### Signaux Faibles

- le simple fait d'appartenir à la même entreprise;
- une similarité vague non structurée;
- une source identique sans autre ancrage.

Ces signaux ne suffisent pas à eux seuls dans le matching à l'écriture.

## Tableau Récapitulatif

| Signal                                  | Condition                                       | Effet         | Niveau | Commentaire                           |
| --------------------------------------- | ----------------------------------------------- | ------------- | ------ | ------------------------------------- |
| Email commun                            | Intersection des emails normalisés non vide     | Match direct  | Fort   | Prioritaire                           |
| Téléphone commun                        | Intersection des téléphones normalisés non vide | Match direct  | Fort   | Prioritaire                           |
| Profil identique                        | URL de profil normalisée identique              | Match direct  | Fort   | Prioritaire                           |
| `display_name + organization`           | Clé de nom générée des deux côtés               | Match direct  | Moyen  | Utile pour les saisies proches        |
| `first_name + last_name + organization` | Clé structurée identique                        | Match direct  | Moyen  | Plus robuste qu'un simple nom affiché |
| Initiale + nom + organisation           | Clé structurée identique                        | Match direct  | Moyen  | Variante pour les saisies partielles  |
| Même entreprise seule                   | Organisation identique sans clé de nom          | Ne suffit pas | Faible | Doit éviter les faux positifs         |
| Même source seule                       | Source identique sans autre clé                 | Ne suffit pas | Faible | Purement contextuel                   |

## Cas Typiques

### Cas Qui Doit Matcher

- une personne importée deux fois avec le même email;
- une fiche créée manuellement puis complétée avec le même téléphone;
- un profil LinkedIn déjà présent avec la même URL normalisée;
- une fiche quasi identique avec nom structuré compatible.

### Cas Qui Ne Doit Pas Matcher

- deux personnes différentes dans la même entreprise;
- deux contacts partageant seulement une source;
- deux fiches sans email, sans téléphone et sans profil commun;
- deux personnes au nom différent mais au contexte identique.

## Point D'Attention

Le matching à l'écriture est plus direct que la revue de doublons.

Il doit rester cohérent avec les imports, mais il ne doit pas être confondu avec:

- le score de revue manuelle;
- l'auto-fusion;
- la fusion champ par champ.
