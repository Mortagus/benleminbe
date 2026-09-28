---
id: TASK-2
title: Supprimer une plateforme et ses données associées depuis la liste privée
status: Done
assignee:
  - Codex
created_date: '2026-09-28 12:43'
updated_date: '2026-09-28 13:02'
labels:
  - private-area
  - network
  - platforms
  - data-lifecycle
dependencies: []
documentation:
  - docs/private/private-area-index.md
  - docs/private/network/network-index.md
  - docs/private/network/network-vision.md
  - docs/private/network/network-mvp-specification.md
  - docs/development-workflow.md
priority: high
type: feature
ordinal: 3000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Benjamin ne participe plus aux plateformes spécialisées en tant qu’indépendant. La liste privée des plateformes doit permettre de retirer définitivement les profils devenus sans objet, sans conserver les informations de leur fiche ni les réintroduire par les données de démarrage. Le snapshot de référence et les données initiales doivent être alignés sur ce changement : Malt, LeHibou et Wiggli ne font plus partie de l’inventaire par défaut. La suppression par l’interface reste disponible pour toute plateforme, sans modifier automatiquement le snapshot versionné.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [x] #1 Chaque plateforme affichée dans la liste privée propose une action de suppression identifiable et utilisable au clavier.
- [x] #2 La suppression requiert une confirmation explicite qui identifie la plateforme avant toute modification définitive.
- [x] #3 Lorsqu’une suppression est confirmée, toutes les informations enregistrées en lien direct avec la plateforme sont supprimées avec elle, de manière cohérente et sans état partiel.
- [x] #4 Après une suppression réussie, la plateforme et ses informations associées ne sont plus accessibles dans la zone privée, un retour de succès est affiché et la plateforme ne réapparaît pas automatiquement, y compris si elle était la dernière de la liste.
- [x] #5 Une tentative de suppression avec une requête invalide, expirée ou visant une plateforme inexistante ne supprime aucune donnée et informe l’utilisateur de façon appropriée.
- [x] #6 Le parcours reste limité à la zone privée autorisée et protège l’action de suppression contre les requêtes forgées.
- [x] #7 Le snapshot versionné et les données de démarrage ne proposent plus Malt, LeHibou ni Wiggli ; les plateformes hors de ce périmètre restent inchangées.
- [x] #8 Les installations existantes et nouvelles ne réintroduisent pas les trois plateformes retirées par les données de démarrage historiques.
- [x] #9 Des tests ciblés couvrent la suppression complète, son absence de réapparition, les données de démarrage nettoyées et les principaux cas d’échec ; les vérifications projet pertinentes passent.
- [x] #10 La documentation stable du module réseau est mise à jour si le comportement durable ou les routes de gestion des plateformes changent.
<!-- AC:END -->

## Implementation Plan

<!-- SECTION:PLAN:BEGIN -->
1. Reprendre le parcours de suppression des contacts pour conserver les conventions Symfony : route POST, CSRF ciblé, messages flash et redirection vers la liste.
2. Ajouter au service des plateformes une suppression atomique, retirer le réensemencement implicite qui ferait revivre une liste intentionnellement vide, puis exposer le parcours depuis la liste.
3. Retirer Malt, LeHibou et Wiggli du snapshot et ajouter une migration de données afin que les bases existantes comme nouvelles restent alignées.
4. Couvrir le flux par les tests fonctionnels privés, puis mettre à jour la documentation réseau concernée et exécuter les vérifications proportionnées.
<!-- SECTION:PLAN:END -->

## Implementation Notes

<!-- SECTION:NOTES:BEGIN -->
Décision de cadrage — 2026-09-28 : le snapshot de référence doit être nettoyé en plus de l’interface de suppression. Périmètre retenu à partir des catégories actuelles : Malt, LeHibou et Wiggli sont des plateformes freelance à retirer ; LinkedIn, Indeed, Superprof et Apprentus restent hors de ce nettoyage.

Livré — route POST de suppression, confirmation navigateur ciblée, CSRF par plateforme et retour à la liste avec conservation de la recherche. PlatformService supprime la fiche dans une transaction et ne réensemence plus une liste vide.

Les données de référence et la migration Version20260928130000 retirent Malt, LeHibou et Wiggli. La migration a été appliquée aux bases locale et de test ; une requête Doctrine confirme l’absence de ces trois slugs dans la base locale.

Validation — php bin/phpunit tests/Functional/Private/NetworkWebTest.php : 38 tests, 543 assertions. make check : succès. Prettier ciblé sur les trois documents réseau : succès. Revue ciblée du diff : aucun problème bloquant.
<!-- SECTION:NOTES:END -->

## Final Summary

<!-- SECTION:FINAL_SUMMARY:BEGIN -->
Ajoute la suppression définitive des plateformes depuis la liste privée, avec confirmation explicite, protection CSRF, retour utilisateur et accès réservé à la zone authentifiée. La suppression est transactionnelle et une liste intentionnellement vide ne se réensemence plus.

Le snapshot de référence et une migration retirent Malt, LeHibou et Wiggli des données de démarrage pour les installations existantes et nouvelles. Les routes et la documentation réseau décrivent le comportement durable.

Validation : `php bin/phpunit tests/Functional/Private/NetworkWebTest.php` (38 tests, 543 assertions), `make check`, contrôle Prettier ciblé et vérification de la migration locale.
<!-- SECTION:FINAL_SUMMARY:END -->
