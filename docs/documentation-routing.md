# Guide De Routage Documentaire

Ce document dit explicitement où lire et où écrire selon le type de question.

Il sert de passerelle pratique entre les index, les documents de référence, les notes de suivi et l'archive.

## Regle Simple

- Si la question porte sur le but global du projet, lire la vision ou l'architecture du site.
- Si la question porte sur le découpage du site en univers ou sur la navigation entre univers, lire `site-universes-and-navigation.md`.
- Si la question porte sur une fonctionnalité ou une règle métier, lire le document de référence du domaine concerné.
- Si la question porte sur un chantier en cours, lire le suivi actif correspondant.
- Si la question porte sur une décision passée, lire l'archive ou l'audit associé.
- Si la question porte sur l'organisation de la documentation elle-même, lire ce document et l'architecture documentaire.
- Si la question porte sur ce qu'il faut fournir a Codex, lire le contexte assistant.

## Règle De Décision

Quand il faut choisir un document, appliquer cet ordre:

1. Le document le plus spécifique au sujet.
2. Le document qui fait autorité sur le sujet.
3. L'index du domaine si le sujet n'est pas encore clair.
4. Le document de suivi si le sujet est encore en cours.
5. L'archive si la réponse est historique.

Si deux documents semblent couvrir le même sujet, privilégier:

- la référence métier avant l'index;
- la spec avant le suivi;
- l'architecture avant la note de reprise;
- l'archive seulement pour comprendre une décision déjà tranchée.

## Carte Par Type De Question

| Type de question                                     | Lire en priorité                                            | Où écrire / mettre à jour                                                      | Document qui fait autorité            |
| ---------------------------------------------------- | ----------------------------------------------------------- | ------------------------------------------------------------------------------ | ------------------------------------- |
| Quel est le but du projet ?                          | `project-architecture.md`                                   | `project-architecture.md` ou une vision dédiée                                 | Vision / architecture du site         |
| Comment le site est-il découpé en univers ?          | `site-universes-and-navigation.md`                          | `site-universes-and-navigation.md`                                             | Architecture de navigation            |
| Comment la documentation est-elle organisée ?        | `documentation-architecture.md`                             | `documentation-routing.md` ou `documentation-index.md` si l'index doit changer | Architecture documentaire             |
| Où trouver rapidement un sujet ?                     | `documentation-index.md`                                    | `documentation-index.md` si un nouveau point d'entrée est nécessaire           | Index global                          |
| Quelle fonctionnalité existe déjà ?                  | `documentation-index.md` puis l'index du domaine            | Le document métier du domaine concerné                                         | Document de référence du domaine      |
| Comment fonctionne l'intégration Spotify privée ?    | `private/music/music-index.md` puis la spec Spotify ciblée  | Le document stable de `docs/private/music/` correspondant au lot concerné      | Référence stable du module Music      |
| Comment fonctionne une règle métier précise ?        | Le document métier dédié                                    | Le document métier dédié                                                       | Référence métier                      |
| Qu'est-ce qui est en cours ?                         | `en-cours/current-work-index.md` puis la note active        | La note active ou le backlog                                                   | Suivi actif                           |
| Quelle est la prochaine évolution prévue ?           | Le backlog du domaine concerné                              | Le backlog du domaine concerné                                                 | Backlog                               |
| Pourquoi une décision technique a-t-elle été prise ? | `project-architecture.md`, puis l'audit ou la note associée | L'audit ou la note associée                                                    | Document d'architecture ou historique |
| Que faut-il fournir à Codex pour reprendre vite ?    | `assistant-context.md`                                      | `assistant-context.md` si le pack minimal doit évoluer                         | Contexte assistant                    |
| Quelle information devient la source de vérité ?     | Le document du sujet concerné                               | Le document du sujet concerné                                                  | Référence stable du sujet             |

## Règle De Nommage

Quand un nouveau document doit être créé:

- utiliser un nom explicite décrivant le contenu;
- éviter `README.md` hors de la racine d'un dossier d'entrée principal;
- éviter les noms vagues comme `notes.md`, `misc.md` ou `todo.md`;
- préférer un nom qui reste compréhensible hors de l'arborescence;
- faire correspondre le titre du document au fichier quand c'est possible.

Exemples de bons noms:

- `project-architecture.md`
- `documentation-routing.md`
- `network-vision.md`
- `contact-write-matching-rules.md`
- `current-work-index.md`

## Carte Par Emplacement

| Emplacement                             | Usage                                                               |
| --------------------------------------- | ------------------------------------------------------------------- |
| `docs/documentation-index.md`           | Porte d'entrée globale de la documentation                          |
| `docs/documentation-architecture.md`    | Règles de structure, rôles et autorité des familles de documents    |
| `docs/documentation-routing.md`         | Aide pratique pour savoir quoi lire ou où écrire selon une question |
| `docs/project-architecture.md`          | Architecture stable du site                                         |
| `docs/site-universes-and-navigation.md` | Découpage des univers et navigation du site                         |
| `docs/content-workflow.md`              | Source de vérité des contenus publics                               |
| `docs/lab/lab-index.md`                 | Index stable du Lab public                                          |
| `docs/games/games-index.md`             | Index stable de l'univers public Games                              |
| `docs/games/simon.md`                   | Référence technique du jeu Simon                                    |
| `docs/assistant-context.md`             | Pack de lecture minimal pour Codex                                  |
| `docs/en-cours/*`                       | Suivi actif, backlog, notes de chantier                             |
| `docs/termines/*`                       | Historique des audits, plans et notes clôturés                      |
| `docs/private/*`                        | Documentation stable de la zone privée                              |
| `docs/private/music/*`                  | Module prive d'analyse de l'historique Spotify                      |
| `docs/private/music/spotify-*.md`       | Architecture, sécurité OAuth, synchronisation et backlog Spotify    |
| `docs/private/network/*`                | Références du module `Contacts et reseau`                           |
| `docs/editorial/*`                      | Corpus éditorial de travail                                         |

## Utilisation Pratique

### Si Tu Ajoutes Une Nouvelle Information

1. Déterminer si l'information est stable ou temporaire.
2. Déterminer si elle concerne le projet global, un domaine métier, un chantier en cours ou l'archive.
3. Écrire dans le document le plus spécifique qui fait autorité.
4. Mettre à jour l'index si un nouveau point d'entrée devient utile.
5. Mettre à jour `documentation-routing.md` si la nouvelle information change la façon de lire ou d'écrire la documentation.
6. Mettre à jour `assistant-context.md` si cette information devient un point d'entrée important pour Codex.

### Si Tu Recherches Une Réponse

1. Ouvrir `documentation-index.md`.
2. Ouvrir le document de routage si tu hésites sur le type de document.
3. Aller ensuite vers le document de référence du domaine.
4. Si le sujet est actif, vérifier `docs/en-cours/`.
5. Si le sujet est ancien, vérifier `docs/termines/`.
6. Si la question porte sur un sujet privé, aller ensuite dans `docs/private/` ou dans le sous-domaine concerné.

### Si Tu Dois Créer Ou Déplacer Un Fichier

1. Choisir la famille documentaire correcte.
2. Choisir un nom explicite.
3. Placer le fichier dans le dossier qui correspond à son statut.
4. Mettre à jour l'index du dossier concerné.
5. Mettre à jour `documentation-routing.md` si le nouveau fichier change le parcours de lecture.
6. Mettre à jour `AGENTS.md` si le nouveau fichier doit être lu au démarrage d'une session.

## Limite Du Document

Ce document ne remplace pas:

- la vision du projet ;
- l'architecture documentaire ;
- les règles métier détaillées ;
- le suivi actif ;
- l'archive.

Il dit seulement où chercher et où écrire.

## Résultat Attendu

Si ce guide est bien suivi, un futur lecteur ou un futur agent doit pouvoir:

- identifier rapidement quel type de document il lui faut;
- savoir où écrire un nouveau document;
- savoir quel document fait autorité sur un sujet donné;
- éviter de créer des doublons de rôle;
- conserver une nomenclature explicite et stable.
