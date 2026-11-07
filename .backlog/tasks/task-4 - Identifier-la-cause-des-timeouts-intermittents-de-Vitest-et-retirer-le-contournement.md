---
id: TASK-4
title: >-
  Identifier la cause des timeouts intermittents de Vitest et retirer le
  contournement
status: To Do
assignee: []
created_date: '2026-11-07 08:04'
labels:
  - testing
  - tech-debt
dependencies: []
references:
  - vitest.config.mjs
documentation:
  - docs/en-cours/vitest-timeouts-notes.md
priority: low
ordinal: 8000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Des tests JS sans lien entre eux (DnD, Simon, boids, y compris des tests synchrones) expirent de façon intermittente à 5 s quand la machine est chargée, ce qui peut bloquer le hook pre-commit. Un contournement a été appliqué le 2026-11-07 (`testTimeout: 20000` dans `vitest.config.mjs`) pour débloquer le travail ; il masque le symptôme sans traiter la cause, qui n’est pas identifiée. Cette tâche évite que ce contournement soit oublié.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [ ] #1 La cause du gel du processus de test sous charge est identifiée ou écartée avec des preuves
- [ ] #2 Le contournement `testTimeout` de `vitest.config.mjs` est retiré ou réduit, ou son maintien est justifié dans la note
- [ ] #3 `docs/en-cours/vitest-timeouts-notes.md` est à jour, ou déplacé vers `docs/termines/` une fois la tâche close
<!-- AC:END -->
