---
id: TASK-1
title: Aligner le site public sur la fin de l’activité indépendante
status: In Progress
assignee:
  - Codex
created_date: '2026-09-22 07:25'
updated_date: '2026-09-25 09:56'
labels:
  - content
  - public-site
dependencies: []
documentation:
  - README.md
  - docs/content-workflow.md
  - docs/documentation-routing.md
  - docs/development-workflow.md
priority: high
type: task
ordinal: 1000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
L’activité d’indépendant complémentaire de Benjamin Lemin est définitivement arrêtée et le numéro de TVA n’est plus actif. Le site personnel doit rester un portfolio et un CV vivant orienté vers des opportunités professionnelles salariées, sans laisser croire qu’une offre freelance ou une activité commerciale personnelle est encore proposée, tout en conservant les faits historiques du parcours.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [x] #1 Aucune sortie de page publique, métadonnée, vCard ou champ runtime hors CV PDF ne contient l’ancien numéro de TVA ou un champ TVA devenu sans objet.
- [x] #2 Les contenus actuels en français et en anglais ne présentent plus Benjamin Lemin comme indépendant actif et n’annoncent plus de prestations, disponibilité, tarification ou profil commercial freelance.
- [x] #3 Le site présente toujours les compétences, le parcours, les projets, les moyens de contact et la recherche d’opportunités professionnelles salariées.
- [x] #4 Les références historiques factuelles aux missions, expériences et contextes freelance passés sont conservées.
- [x] #5 Les liens publics vers des plateformes commerciales devenus obsolètes sont retirés tandis que LinkedIn, GitHub et les moyens de contact pertinents restent disponibles.
- [x] #6 Les métadonnées SEO, données structurées, vCard, pages légales et sources éditoriales pertinentes sont cohérentes avec le nouveau statut; les CV PDF sont explicitement suivis par TASK-1.1.
- [x] #7 Les tests de contenu concernés sont adaptés et les vérifications globales du dépôt passent.
<!-- AC:END -->

## Implementation Plan

<!-- SECTION:PLAN:BEGIN -->
1. Nettoyer uniquement les reliquats hors index : README, corpus projets, notes d’audit contenu, navigation et test public non suivi.
2. Appliquer les décisions définitives : développeur web expérimenté / experienced web developer, recherche salariée, coaching toujours en cours depuis 10/2021, Superprof principal et Apprentus/Malt historiques.
3. Conserver les dates sitemap du 22 septembre pour les quatre pages effectivement modifiées, conformément aux métadonnées existantes ; ne pas toucher aux fichiers du commit principal.
4. Vérifier les documents, le test public ciblé et make check ; préserver l’index vide, sans staging ni commit.
5. Laisser les CV PDF dans TASK-1.1 ; consigner les limites restantes sans élargir le chantier.
<!-- SECTION:PLAN:END -->

## Implementation Notes

<!-- SECTION:NOTES:BEGIN -->
État de reprise — 25 septembre 2026

- Le commit principal 9aa12ff contient le repositionnement public validé : recherche d’un poste salarié, développeur web expérimenté / experienced web developer, suppression de l’ancien statut freelance actuel, de la TVA, du canal Malt public et des conditions générales commerciales. Les références historiques de consultance restent conservées.
- Décision définitive : le coaching est toujours en cours depuis 10/2021 (présent / Present), principalement via Superprof. Apprentus et Malt sont des plateformes utilisées historiquement. L’ancienne hypothèse d’une fin du coaching en 2026 est abandonnée.
- Les textes publics validés restent inchangés pendant ce nettoyage. Les documents README, corpus projets, audit contenu et navigation sont remis en cohérence ; le test public non suivi est adapté au wording actuel et au coaching en cours. Les quatre dates sitemap du 22 septembre restent hors index : elles concernent les pages À propos, Contact, Confidentialité et Mentions légales effectivement modifiées.
- Les anciennes interventions privées avaient été annulées : ContactMessageSuggestionBuilder, tests privés et snapshot des plateformes restaurés à HEAD ; migration Version20260922080000 retirée. Elle avait auparavant été exécutée en test, sans rollback ultérieur. Aucun changement privé dans le nettoyage actuel.
- Confidentialité : le journal CV enregistre date, locale déduite du référent, référent et User-Agent, sans IP directement enregistrée ni purge automatique. Les textes cookies décrivent thème, D&D, Simon et import XML ; aucune création de compte public. Les paramètres effectifs Infomaniak, fichiers temporaires et rotation extérieure au dépôt restent non vérifiés.
- Point restant : templates/pages/privacy_policy.html.twig référence retention.paragraphs.4, absent des deux YAML. Défaut déjà signalé avant le commit principal ; fichier exclu du nettoyage courant. Prochaine action : retirer cette référence dans une modification séparément autorisée.
- Validation du nettoyage : make check réussi (163 tests JS), ProfessionalProfileWebTest réussi (17 tests, 104 assertions), git diff --check réussi. Prettier conforme pour README, corpus projets et audit contenu ; document navigation avec formatage préexistant non conforme, sans réorganisation globale.
- Les quatre CV PDF restent à traiter dans TASK-1.1, dont le cadrage reprend les décisions définitives. La tâche principale reste ouverte. Aucun staging ni commit effectué dans cette passe ; index vide. Les anciens artefacts var/gpt/review-independent-status restent obsolètes.
<!-- SECTION:NOTES:END -->
