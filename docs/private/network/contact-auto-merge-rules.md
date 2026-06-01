# Règles D'Auto-Fusion Des Contacts

Date de mise à jour : 2026-06-01

Ce document décrit la logique utilisée pour fusionner automatiquement des contacts jugés suffisamment sûrs pour être fusionnés sans revue manuelle.

L'auto-fusion est plus stricte que le matching à l'écriture et différente de la revue manuelle des doublons.

## Portée

Ce document concerne:

- `ContactAutoMergeService`;
- la construction des clusters d'auto-fusion;
- la sélection du contact canonique;
- les conditions qui autorisent une fusion automatique;
- les champs conservés ou fusionnés pendant l'opération.

## Objectif Métier

L'auto-fusion sert à nettoyer automatiquement les doublons les plus évidents.

Elle doit rester très prudente, car elle supprime une fiche et transfère des interactions.

Le principe est simple:

- si le rapprochement est quasi certain, on fusionne;
- si un doute réel existe, on ne touche pas à la donnée et on laisse la revue manuelle décider.

## Construction Des Clusters

L'auto-fusion ne parcourt pas tous les couples avec un score.

Elle commence par construire des clusters par clés déterministes:

- téléphone normalisé;
- email normalisé;
- profil normalisé;
- clé d'identité complète si `display_name`, `first_name` et `last_name` sont présents.

Les clusters sont gérés en union-find:

- si deux contacts partagent une clé, ils sont reliés;
- si une chaîne de clés relie plusieurs contacts, ils se retrouvent dans le même cluster.

## Contacts Sparsifiés

Un contact peut être classé comme "sparse" si sa complétude est faible.

Le score de complétude est calculé à partir:

- du nom affiché;
- du prénom;
- du nom;
- de l'organisation;
- du rôle;
- du canal principal;
- du profil;
- de la source;
- de la prochaine action;
- des notes;
- des tags;
- des dates de suivi;
- des emails;
- des téléphones.

Le seuil actuel de sparse auto-merge est:

- `scoreContactCompleteness() <= 3`

Les contacts sparse peuvent être fusionnés entre eux si leurs noms affichés ne diffèrent que d'une distance de Levenshtein de `1` et si leurs autres champs compatibles ne contredisent pas la fusion.

## Sélection Du Contact Canonique

Quand un cluster contient plusieurs contacts:

1. le contact retenu comme canonique est celui qui a la meilleure complétude;
2. en cas d'égalité, le plus ancien `created_at` gagne.

L'idée est de conserver la fiche la plus utile et la plus ancienne comme base de fusion.

## Conditions D'Auto-Fusion

Même si un cluster existe, la fusion n'est appliquée que si `canAutoMergeContacts()` retourne vrai.

Les cas qui autorisent la fusion sont les suivants:

### 1. Téléphone Partagé

- si deux contacts partagent un téléphone normalisé, la fusion est autorisée.

### 2. Email Partagé

- si deux contacts partagent un email normalisé, la fusion est autorisée.

### 3. Profil Partagé

- si deux contacts partagent la même URL de profil normalisée, la fusion est autorisée.

### 4. Paires Sparse Très Proches

- si deux contacts sparse ont des noms affichés presque identiques;
- si tous les autres champs comparables ne se contredisent pas;
- alors la fusion est autorisée.

### 5. Comparaison Champ Par Champ

Si aucun des cas précédents ne s'applique, tous les champs non vides sont comparés.

La fusion est refusée dès qu'un champ non vide est incompatible, sauf exceptions LinkedIn.

## Cas LinkedIn

Quand au moins un des contacts est reconnu comme contact LinkedIn, certaines contraintes sont assouplies:

- `organization` peut différer;
- `role` peut différer;
- `profile_url` peut différer dans les comparaisons internes;
- `main_channel` peut différer si l'autorité LinkedIn est présente.

Cette tolérance reflète le fait que les données LinkedIn sont souvent plus riches, mais pas toujours parfaitement alignées avec les autres sources.

## Champs Comparés Avant Fusion

Les champs suivants sont pris en compte dans la décision:

- `display_name`;
- `first_name`;
- `last_name`;
- `organization`;
- `role`;
- `profile_url`;
- `next_action`;
- `notes`;
- `priority`;
- `relationship_status`;
- `last_contact_at`;
- `next_action_at`;
- `main_channel`.

Si deux valeurs sont toutes les deux non vides et différentes, la fusion est refusée, sauf exception LinkedIn pour les champs autorisés.

## Champs Fusionnés

Quand la fusion est autorisée, `mergeContactInto()` applique une politique de conservation:

- les interactions sont déplacées vers le contact canonique;
- les emails sont unionnés;
- les téléphones sont unionnés;
- les tags sont unionnés;
- la source est fusionnée;
- les priorités et statuts relationnels gardent la valeur la plus forte;
- `last_contact_at` garde la date la plus récente;
- `next_action_at` garde la date la plus proche;
- les notes sont concaténées;
- `main_channel` est normalisé pour refléter notamment LinkedIn si nécessaire.

## Tableau Récapitulatif

| Mécanisme                   | Règle                                                      | Effet                        | Niveau    | Commentaire                                 |
| --------------------------- | ---------------------------------------------------------- | ---------------------------- | --------- | ------------------------------------------- |
| Clé téléphone               | Téléphone normalisé partagé                                | Clustering + fusion possible | Très fort | Signal direct                               |
| Clé email                   | Email normalisé partagé                                    | Clustering + fusion possible | Très fort | Signal direct                               |
| Clé profil                  | Profil normalisé partagé                                   | Clustering + fusion possible | Très fort | Signal direct                               |
| Clé identité                | `display_name + first_name + last_name` tous présents      | Clustering + fusion possible | Fort      | Réservé aux fiches bien nommées             |
| Sparse pair                 | Levenshtein `1` sur le nom affiché + compatibilité globale | Fusion possible              | Moyen     | Filet de sécurité pour données très pauvres |
| Comparaison champ par champ | Tous les champs non vides doivent être compatibles         | Autorisation finale          | Fort      | Dernier garde-fou                           |
| Autorité LinkedIn           | Certaines différences contextuelles tolérées               | Assouplissement contrôlé     | Fort      | Cas de source plus riche                    |

## Cas Typiques

### Fusion Qui Doit Être Automatique

- même téléphone;
- même email;
- même profil;
- doublon sparse quasi identique;
- deux fiches issues de la même identité structurée.

### Fusion Qui Doit Être Refusée

- même entreprise mais identité différente;
- rôle identique mais noms différents;
- source commune sans autre preuve;
- similitude superficielle sur une seule ligne.

## Différence Avec La Revue Manuelle

L'auto-fusion ne doit pas être confondue avec la revue manuelle.

La revue manuelle:

- accepte un doute;
- utilise un score;
- laisse un humain trancher.

L'auto-fusion:

- ne tolère qu'une quasi-certitude;
- n'utilise pas le score de revue;
- applique la fusion directement.
