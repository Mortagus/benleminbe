# Architecture Documentaire Du Projet

Ce document décrit la structure documentaire du projet `benlemin.be`, les rôles attendus par famille de documents et les règles qui permettent de savoir quoi lire ou où écrire.

L'objectif est de limiter les doublons et d'éviter qu'un document porte plusieurs responsabilités à la fois.

## Principes

### Une Source Par Rôle

Chaque information importante doit avoir un seul document de référence principal.

Exemples:

- la vision du projet ;
- la specification fonctionnelle ;
- l'architecture technique ;
- les regles metier d'un module ;
- le suivi actif ;
- l'historique.

### Une Frontiere Claire Entre Les Statuts

Les documents ne doivent pas mélanger:

- le present stable ;
- le travail en cours ;
- l'historique ;
- le corpus editorial ;
- la documentation destinée a l'IA.

### Le Code Reste La Source Runtime

La documentation explique le comportement du projet, mais la verite executable reste dans:

- les entites ;
- les services ;
- les templates ;
- les routes ;
- les tests ;
- les traductions.

## Categories Documentaires

| Catégorie                   | Rôle                                                          | Autorité          | Exemple                                                   |
| --------------------------- | ------------------------------------------------------------- | ----------------- | --------------------------------------------------------- |
| Index                       | Orienter vers les bons fichiers                               | Faible a moyenne  | `documentation-index.md`                                  |
| Vision projet               | Expliquer le but et la direction                              | Forte             | `project-architecture.md` ou un futur `project-vision.md` |
| Spécification fonctionnelle | Décrire ce que le système doit faire                          | Forte             | `network-mvp-specification.md`                            |
| Architecture                | Expliquer l'organisation du système et les choix structurants | Forte             | `project-architecture.md`                                 |
| Référence métier            | Décrire une règle de fonctionnement stable                    | Très forte        | `merge-review-scoring-rules.md`                           |
| Suivi actif                 | Garder la mémoire d'un chantier en cours                      | Faible a moyenne  | `docs/en-cours/*`                                         |
| Backlog                     | Lister les prochains travaux                                  | Moyenne           | `dnd-initiative-tracker-backlog.md`                       |
| Archive                     | Conserver les audits et plans clos                            | Historique        | `docs/termines/*`                                         |
| Corpus éditorial            | Porter la matière rédactionnelle                              | Source de travail | `docs/editorial/*`                                        |
| Documentation IA            | Donner un point d'entrée court à Codex                        | Utilitaire        | `assistant-context.md`                                    |
| Architecture de navigation  | Expliquer les univers du site et leurs parcours               | Forte             | `site-universes-and-navigation.md`                        |
| Routage documentaire        | Indiquer quoi lire ou où écrire selon une question            | Utilitaire        | `documentation-routing.md`                                |

## Où Mettre Une Nouvelle Information

| Question                                              | Emplacement recommandé                                        |
| ----------------------------------------------------- | ------------------------------------------------------------- |
| Quel est le but du projet ?                           | `project-architecture.md` ou une vision dédiée                |
| Comment le site est-il structuré en univers ?         | `site-universes-and-navigation.md`                            |
| Quelles fonctionnalités existent déjà ?               | `documentation-index.md` puis le document de domaine concerné |
| Quelles fonctionnalités sont en cours ?               | `docs/en-cours/current-work-index.md` puis la note active     |
| Quelles sont les prochaines fonctionnalités prévues ? | Le backlog du domaine concerné                                |
| Comment fonctionne une fonctionnalité métier ?        | Le document métier de référence                               |
| Pourquoi une décision technique a-t-elle été prise ?  | Le document d'architecture ou l'audit associé                 |
| Quelle documentation fournir à Codex ?                | `assistant-context.md`                                        |
| Quel document fait autorité ?                         | Le document de référence du sujet, pas l'index                |
| Où lire ou écrire selon le type de question ?         | `documentation-routing.md`                                    |

## Règles De Lecture

Quand une tâche arrive, l'ordre de lecture conseillé est:

1. `documentation-index.md`
2. `project-architecture.md`
3. `documentation-routing.md`
4. `content-workflow.md`
5. le document de domaine concerné
6. le suivi actif si le sujet est en cours
7. l'archive si la décision a déjà été discutée auparavant

## Règles De Maintenance

- Si un document commence a servir deux rôles, il faut le scinder.
- Si deux documents disent la même chose avec des nuances differentes, il faut elire un document de référence et déplacer l'autre en archive ou le fusionner.
- Si un document est encore utile mais trop provisoire, il doit rester dans `en-cours/`.
- Si un document devient une reference stable, il doit sortir de `en-cours/`.

## Rôle Des Index

Les fichiers d'index servent à:

- signaler les documents de référence ;
- orienter selon le besoin ;
- éviter de forcer une lecture exhaustive de tout le dépôt ;
- donner une carte stable de la documentation.

Ils ne doivent pas:

- porter la règle métier détaillée ;
- devenir le doublon d'un document de référence ;
- accumuler l'historique.
