# Timeouts Intermittents Des Tests Vitest

Note de suivi d'un contournement de configuration. Ce n'est pas une correction de fond : à garder en vue.

Statut au 2026-11-07 : contournement en place (`testTimeout: 20000` dans `vitest.config.mjs`), cause profonde non identifiée.

## Symptôme

Des tests JS échouent de façon intermittente avec `Test timed out in 5000ms`, ce qui peut bloquer le hook pre-commit (`make check`) :

- les tests concernés changent d'un run à l'autre : `tests/js/lab/dnd/turn-order.test.js`, `tests/js/games/simon-audio.test.js`, `tests/js/games/simon-controller.test.js`, `tests/js/lab/simulations/flocking.test.js` ;
- plusieurs sont purement synchrones, et Vitest en rapporte parfois une durée de quelques centaines de millisecondes avant de les déclarer expirés : le processus de test est gelé pendant plusieurs secondes, ce ne sont pas les tests qui sont lents.

## Constat Du 2026-11-07

- Sans charge : 12 exécutions de la suite, 12 succès.
- Avec 16 boucles CPU en parallèle sur 12 cœurs : 2 exécutions sur 8 échouent avec le timeout par défaut de 5 s, 0 sur 8 avec `--testTimeout=20000`, 3 sur 8 avec `--maxWorkers=4` (échantillon petit, indice plutôt que preuve).
- L'échec réel est survenu pendant le hook pre-commit, juste après les étapes PHP, stylelint et eslint de `make check` : hypothèse d'une machine chargée à ce moment-là, non vérifiée.

## Contournement Appliqué

`testTimeout: 20000` dans `vitest.config.mjs`. Limite : un vrai blocage sera signalé après 20 s au lieu de 5 s.

## À Faire Plus Tard

- Identifier la cause du gel (WSL2, ordonnancement CPU, mémoire, autre) et, si possible, la traiter.
- Retirer ou réduire `testTimeout` quand les tests ne se bloquent plus sans lui.
- Suivi opérationnel : tâche Backlog.md TASK-4.
