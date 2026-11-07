---
id: TASK-6.11
title: 'Boids 3D : évitement prédictif des arbres'
status: In Progress
assignee:
  - '@claude'
created_date: '2026-11-07 11:02'
updated_date: '2026-11-07 12:20'
labels:
  - lab
  - 3d
dependencies:
  - TASK-6.12
documentation:
  - docs/en-cours/boids-3d-projet.md
parent_task_id: TASK-6
ordinal: 21000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Retour de Benj le 2026-11-07 : les boids pourraient interagir avec les arbres et passer près sans collision, avec un évitement et pas une répulsion. Décision de Benj : évitement prédictif (chaque boid regarde devant lui le long de sa vitesse et ne dévie que si sa trajectoire va toucher un arbre). Cela suppose de recouvrir la zone de vol et la hauteur des arbres, ce qui remplace la décision « la nuée reste au-dessus du décor ».
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [x] #1 La zone de vol est abaissée jusqu’à la hauteur des arbres, et un sol sûr empêche de le traverser
- [x] #2 Les boids évitent les arbres sans en traverser aucun, vérifié par des tests sur de nombreuses parties, et aucune force n’agit quand la voie est libre
- [x] #3 Le coût du calcul avec l’évitement est mesuré et reste acceptable à 500 boids
- [ ] #4 Benj valide visuellement
<!-- AC:END -->

## Implementation Plan

<!-- SECTION:PLAN:BEGIN -->
1. Obstacles : chaque arbre devient un ensemble de sphères (feuillu : une sphère pour le feuillage ; conifère : trois sphères le long du cône), plutôt qu'un cylindre, qui ferait éviter de l'air au sommet pointu d'un conifère. Le décor les produit (`obstacles` de landscape.js), sans dépendre de p5.
2. Évitement prédictif dans flocking.js (générique en dim, désactivé par défaut donc 2D inchangé) : pour chaque boid, on suit sa vitesse sur `avoidanceLookahead` frames ; si le point de plus courte distance à une sphère passe à moins de rayon + marge, on ajoute une accélération perpendiculaire à la vitesse, dans le sens qui s'éloigne de la sphère, d'autant plus forte que la collision est proche et profonde ; sinon aucune force. Un seul obstacle par boid : le plus urgent (Reynolds). Ajoutée à l'accélération lissée.
3. Garde-fous : le sol est un plan (évitement identique : si la trajectoire prédite passe sous `floorLevel - marge`, accélération vers le haut) ; filet de sécurité dur : si un boid finit une frame dans une sphère ou sous le sol, il est ramené à la surface et sa vitesse entrante est annulée. On mesure combien de fois le filet intervient (devrait être presque jamais).
4. Zone de vol abaissée : le sol remonte à FLIGHT_BOTTOM + 25 (au lieu de + 190) ; les arbres entrent dans la zone ; le chemin de la cible descend plus bas (amplitude verticale plus grande, horizontale un peu réduite pour rester dans l'ellipsoïde) ; des arbres sont ajoutés dans le couloir de vol. Les poteaux, câbles, collines et le sol restent hors de la zone.
5. Mesurer : collisions (zéro), fois où le filet intervient, rapprochements, hauteur minimale atteinte par la nuée, coût du calcul à 500 boids avec évitement, images par seconde ; mettre à jour valeurs et seuils des tests.
6. Tests Vitest avec mutations : pas de force quand la voie est libre, évitement latéral, jamais de pénétration sur de nombreuses parties, le sol, filet de sécurité, 2D inchangé.
<!-- SECTION:PLAN:END -->

## Implementation Notes

<!-- SECTION:NOTES:BEGIN -->
Fait : évitement prédictif (flocking.js : addAvoidance, resolveEnvironment, computeAvoidance, stepFlock reçoit un environnement), arbres en sphères obstacles (landscape.js : obstacles, buildEnvironment qui convertit vers le repère de la nuée), sol remonté à 25 unités sous la limite basse de la nuée, 40 arbres hauts dans le couloir de vol, zone confinée à fond plat (confinementExponent 4), chemin de la cible plus bas et aplati en hauteur, caméra visant le milieu de la plage de vol.
Validé : 168 tests simulation, 8 mutations détectées, make check OK (331 tests), 2D strictement inchangé ; 6 parties de 3600 frames dans la vraie scène : au plus 11 corrections du filet (arbres), 0 pour le sol, rapprochement minimal d'une couronne 1,1 unité, 0,79 rapprochement par image ; coût à 500 boids avec les arbres 5,2 ms contre 5,1 sans ; captures des phases basse et haute, aucune erreur JS.
Incidents : première formule d'urgence trop faible (corrigée) ; mesure faussée par un décalage de repères (corrigée par buildEnvironment) ; seuil de test posé sans mesure (20 sphères, 17 réelles, passé à 10) ; fond d'ellipsoïde trop étroit, d'où la zone à fond plat.
Non vérifié : l'aspect à l'écran par Benj (critère 4), le rendu sur un vrai GPU.
Prochaine action : Benj regarde la nuée raser les arbres et valide ; puis TASK-6.12 (meneurs).
<!-- SECTION:NOTES:END -->
