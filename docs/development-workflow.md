# Workflow De Développement

Ce document définit la surcouche projet légère appliquée à Backlog.md pour faire évoluer le dépôt de façon pilotable et reprenable, y compris avec Codex.

Il complète l'[architecture documentaire](documentation-architecture.md) : il ne décrit ni une fonctionnalité du site ni un système de gestion de projet parallèle.

## Principes

- Backlog.md est le centre de pilotage opérationnel : il rend visibles les tâches, leur priorité, leur état et leurs dépendances.
- Une tâche Backlog.md est l'unité de travail identifiable d'une évolution significative. Elle est aussi la mémoire minimale nécessaire à sa reprise.
- La documentation stable décrit le système tel qu'il fonctionne maintenant ; elle ne vit pas dans la tâche.
- Les archives expliquent un chantier ou une décision passée lorsque cette mémoire reste utile.
- Le code et les tests sont la vérité exécutable ; Git est la trace exacte des changements. La tâche ne les duplique pas.

Le but est de pouvoir reprendre une tâche à partir du dépôt seul, sans dépendre d'une conversation, tout en évitant un journal exhaustif des actions.

## Backlog.md Et Documentation Du Dépôt

Backlog.md est la source opérationnelle des tâches : son ID, son statut configuré, sa priorité, ses dépendances, ses critères d'acceptation, son plan et ses notes ne doivent pas être recopiés dans un backlog Markdown du dépôt. Utiliser ses opérations MCP natives de création, lecture, liste et mise à jour de tâche plutôt que modifier manuellement ses fichiers.

Une fois le projet Backlog.md initialisé, Codex consulte d'abord `backlog://workflow/overview`, puis, selon le besoin, `backlog://workflow/task-creation`, `backlog://workflow/task-execution` ou `backlog://workflow/task-finalization`. Ces ressources définissent le fonctionnement natif courant de Backlog.md et prévalent sur les détails d'outillage de ce document.

Les backlogs Markdown existants dans `docs/en-cours/` restent des documents de cadrage, de roadmap ou de contexte de domaine tant qu'ils sont utiles. Ils ne deviennent pas un second registre de tâches : lors d'une migration future, ils pourront pointer vers les tâches Backlog.md correspondantes au lieu d'en dupliquer les statuts. Aucune migration rétrospective n'est requise.

Créer une tâche Backlog.md pour une évolution, une correction non triviale, une analyse, une dette technique ou une décision qui demande plusieurs étapes, une validation spécifique ou une reprise possible. Ne pas en créer pour une correction évidente, locale et terminée dans la même passe, ni pour une simple consultation. Si un travail supposé mineur révèle un périmètre, un risque ou une décision durable, créer la tâche dès que cela devient utile.

Une idée très brute peut rester un brouillon Backlog.md ; elle devient une tâche structurée avant d'être prise en charge.

## États Et Cycle De Vie

Les statuts et leurs transitions sont configurés et gérés par Backlog.md. Le projet ne définit pas une seconde liste de valeurs de statut. Les repères suivants expriment seulement le niveau de maturité attendu pour une tâche :

| Repère projet          | Attendu dans Backlog.md                                                                                                           |
| ---------------------- | --------------------------------------------------------------------------------------------------------------------------------- |
| Idée à cadrer          | Brouillon ou tâche dont le besoin et le périmètre doivent encore être analysés.                                                   |
| Prête                  | Tâche suffisamment spécifiée pour démarrer.                                                                                       |
| Active                 | Analyse, implémentation ou validation en cours.                                                                                   |
| Interrompue ou bloquée | Tâche dont les notes indiquent la cause, l'état réel et la prochaine action ; le statut configuré de Backlog.md reflète cet état. |
| Terminée               | Tâche complétée après les validations et mises à jour documentaires pertinentes.                                                  |

Une validation en cours reste une tâche active. Une interruption ou un blocage doit être explicité dans les notes Backlog.md, y compris ce qui permettra de le lever.

```text
brouillon → spécification → tâche prête → tâche active → tâche terminée
                                         ↕
                                   interruption / blocage
```

1. Capturer l'idée dans Backlog.md et créer une tâche dès que le travail mérite un suivi.
2. Cadrer la tâche : comprendre l'existant, les dépendances, le périmètre et les critères d'acceptation.
3. La marquer prête selon le statut Backlog.md configuré seulement lorsque l'action de départ est concrète.
4. Pendant le travail, consigner les informations qui rendront une interruption sûre.
5. Valider le résultat, mettre à jour les références stables qui ont réellement changé, puis clôturer.
6. Archiver dans Backlog.md selon son workflow. Déplacer vers `docs/termines/` une note de chantier close uniquement lorsqu'elle garde une valeur historique ; ne pas archiver mécaniquement chaque tâche terminée dans la documentation du dépôt.

## Contenu D'Une Tâche Backlog.md

La structure native de Backlog.md est le format de référence. Le projet demande le contenu suivant dans ses champs natifs, sans créer de fiche Markdown parallèle :

| Champ Backlog.md                | Contenu projet attendu                                                                        |
| ------------------------------- | --------------------------------------------------------------------------------------------- |
| Titre et description            | Contexte, objectif vérifiable et périmètre, y compris les exclusions utiles.                  |
| Statut, priorité et dépendances | Valeurs natives Backlog.md ; priorité et dépendances seulement si elles sont utiles.          |
| Critères d'acceptation          | Résultats vérifiables avant clôture. Requis pour une tâche prête.                             |
| Plan                            | Analyse et approche retenue quand la tâche le nécessite.                                      |
| Notes                           | Progression réelle, décisions utiles, validations, blocages et une prochaine action concrète. |

Les liens vers maquettes, branche, commits, fichiers pressentis ou commandes de vérification restent optionnels. Ils servent seulement s'ils facilitent réellement la reprise ; Git reste la référence des changements.

## Passage À L'État Prêt

Une tâche est prête lorsque :

- son objectif et la raison du travail sont compris ;
- son périmètre et ses limites sont assez clairs pour éviter une exploration non maîtrisée ;
- ses critères d'acceptation sont vérifiables ;
- les dépendances connues sont traitées ou explicitement signalées ;
- les documents de référence à consulter et ceux potentiellement impactés sont identifiés ;
- sa prochaine étape est concrète.

Une solution technique détaillée n'est pas exigée à ce stade. Une analyse peut précisément faire partie de la tâche lorsque cette solution reste à trouver.

## Démarrer Et Reprendre Avec Codex

Avant de démarrer une tâche prête ou de reprendre une tâche interrompue, Codex doit :

1. lire les ressources Backlog.md pertinentes, la tâche et les documents stables indiqués ;
2. vérifier l'état réel du dépôt et les travaux actifs liés ;
3. signaler toute divergence entre la tâche et l'existant ;
4. mettre à jour le statut configuré et la prochaine étape dans Backlog.md lorsque les faits le justifient.

Cette mise à jour opérationnelle ne demande pas d'autorisation humaine systématique. Codex demande une décision seulement si une divergence implique un changement matériel de périmètre, de critères d'acceptation, de priorité ou une dépendance externe. À la reprise, la tâche est la mémoire opérationnelle de départ, puis le code, les tests et Git servent à vérifier ce qui existe réellement.

## Checkpoint Et Interruption

Avant d'interrompre une tâche active, mettre ses notes Backlog.md à jour de façon concise :

- ce qui est réellement terminé ou modifié ;
- les décisions qui expliquent l'état actuel ;
- les validations exécutées et leur résultat ;
- les problèmes, incertitudes ou blocages restants ;
- les références stables déjà mises à jour ou à mettre à jour ;
- la prochaine action unique et concrète.

Utiliser le statut Backlog.md configuré qui correspond à l'interruption ou au blocage si le travail ne reprend pas immédiatement ou dépend d'un élément externe. Un checkpoint n'est pas un compte rendu chronologique : ne retenir que ce qu'une personne nouvelle doit savoir pour continuer sans refaire l'analyse.

## Validation, Documentation Et Definition Of Done

Avant de terminer une tâche, vérifier proportionnellement au risque : tests ciblés, lints et commandes projet pertinentes, puis vérification manuelle lorsque le comportement ou le rendu le demande. `make check` est la vérification générale de référence ; pour une modification documentaire, `npm run lint:md` est la vérification minimale attendue si elle est disponible.

Une tâche peut être terminée dans Backlog.md lorsque :

- ses critères d'acceptation sont satisfaits ou explicitement révisés avec justification ;
- les validations pertinentes ont été exécutées et leur résultat est inscrit dans les notes ;
- les documents de référence stables impactés ont été mis à jour lorsque le comportement, l'architecture ou les règles du système ont changé ;
- ses champs et notes Backlog.md reflètent son état final et n'annoncent plus une prochaine action obsolète ;
- une note historique est créée ou conservée seulement si elle apporte un contexte durable qui ne relève pas d'une référence stable ;
- le dépôt contient les changements réels, dont Git conserve la trace exacte.

Une documentation de suivi ne remplace jamais une référence stable. Inversement, ne pas modifier une référence stable quand le changement n'a pas d'effet durable sur le fonctionnement documenté.

## Compatibilité Avec Les Backlogs Existants

Les backlogs existants restent valides comme documents de cadrage : leurs tableaux, lots, priorités et notes de reprise constituent déjà une base utile. Lors d'une évolution future, créer progressivement une tâche Backlog.md liée au moment où un élément devient actif ou doit pouvoir être interrompu. Il n'est pas nécessaire de convertir les éléments historiques ou les longues listes non engagées.

Les notes de reprise existantes peuvent être absorbées dans les notes de la tâche Backlog.md quand elles sont retravaillées. Cette migration se fait tâche par tâche, sans réécriture globale.
