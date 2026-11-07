# Projet : Simulation Boids En 3D

Document de cadrage du projet de version 3D des boids du Lab, produit avec `~/.ai-context/project-start.md` le 2026-11-07. Il reprend et remplace la section « Piste 3D » de [boids-evolutions-notes.md](boids-evolutions-notes.md). La référence stable de la version 2D est [simulations.md](../lab/simulations.md).

Légende des statuts : **Validé** (approuvé par Benj), **Proposé** (recommandation en attente d'accord), **Ouvert** (non tranché).

Statut global au 2026-11-07 : cadrage terminé, **plan d'implémentation approuvé par Benj**. Suivi : TASK-6 dans Backlog.md. Étape 1 en cours.

## Problème Et Succès

**Validé.**

- **Quoi** : une simulation 3D d'une nuée d'oiseaux (quelques centaines de boids, silhouettes sur un ciel de coucher de soleil), à côté de la simulation 2D du Lab.
- **Pour qui** : les visiteurs du site, et Benj comme terrain d'apprentissage du rendu 3D.
- **Pourquoi** : une pièce visuelle à regarder, plus parlante que la version 2D, qui reste la version pédagogique avec tous ses curseurs.
- **Succès** : la scène est belle et fluide sur un ordinateur, la caméra se manipule à la souris, et le code réutilise le calcul existant sans le dupliquer.

## Périmètre

| Sujet                | Statut  | Décision                                                                                                                       |
| -------------------- | ------- | ------------------------------------------------------------------------------------------------------------------------------ |
| But                  | Validé  | Pièce visuelle, avec peu de réglages ; le 2D reste la version pédagogique complète.                                            |
| Interaction          | Validé  | Caméra seulement (rotation, zoom). Pas de perturbation des boids par un clic.                                                  |
| Comportement         | Validé  | Règles actuelles d'abord (voisins topologiques, angle mort, lissage), passées en 3D. Le point d'attraction et l'inclinaison viendront après, si le résultat le justifie. |
| Mobile               | Validé  | Ordinateur d'abord. Le mobile ne doit pas planter ; son confort relève de TASK-5. Pause au démarrage si `prefers-reduced-motion`, comme en 2D. |
| Calcul               | Validé  | Version générique en N dimensions : le calcul de `flocking.js` est réutilisé tel quel avec `dim = 3`, sans duplication.        |
| Forme du boid        | Validé  | Une forme 3D simple et statique qui évoque un petit oiseau. Aucun battement d'ailes ni animation.                              |
| Ciel                 | Validé  | Coucher de soleil fixe, sans évolution de luminosité et indépendant du thème clair/sombre. Référence : ciel de coucher de soleil de novembre (interprétation à confirmer visuellement à l'étape 2 : soleil bas, orange vif au ras de l'horizon, puis rose et violet, puis bleu profond en altitude). |
| Bords                | Validé  | Confinement dans la scène. Une autre stratégie de peuplement de la scène reste envisageable plus tard si le confinement ne convient pas visuellement. |
| Réglages             | Validé  | Proposition de départ : pause, réinitialisation, nombre de boids (100 à 500, 200 par défaut). Ajustements ou ajouts à décider plus tard. |
| Plan                 | Validé  | Plan d'implémentation en 4 étapes ci-dessous, approuvé le 2026-11-07.                                                          |
| Hors périmètre       | Validé  | Bruit de direction (« wander »), point d'attraction, inclinaison dans les virages, battement d'ailes ou toute animation du corps, pointeur 3D, simulateur de fluide. |

## Faits Établis

Mesurés le 2026-11-07, sous Node 24 et Chrome headless (rendu logiciel).

**Calcul de `stepFlock`**, avec les paramètres par défaut du 2D (non réajustés pour la 3D), après 200 frames de convergence :

| Boids | 2D 924×520  | 3D 924×520×520 | 3D 600×400×400 |
| ----- | ----------- | -------------- | -------------- |
| 100   | 0,12 ms     | 0,10 ms        | 0,15 ms        |
| 300   | 0,48 ms     | 0,49 ms        | 0,97 ms        |
| 500   | 1,00 ms     | 1,41 ms        | 1,61 ms        |
| 1000  | 2,75 ms     | 2,75 ms        | 3,44 ms        |

La 3D n'est pas plus chère que la 2D à ces effectifs : le volume plus grand dilue les voisins, ce qui compense le passage de 9 à 27 cellules. Le calcul n'est pas le sujet de ce projet.

**Soumission du rendu** (prototype jetable : N cônes en mouvement, temps JavaScript médian par frame, GPU non mesuré) :

| Boids | p5.js WEBGL | Three.js (`InstancedMesh`) |
| ----- | ----------- | -------------------------- |
| 100   | 1,3 ms      | 0,1 ms                     |
| 300   | 3,1 ms      | 0,1 ms                     |
| 600   | 6,8 ms      | 0,2 ms                     |
| 1000  | 11,5 ms     | 0,3 ms                     |
| 3000  | 36,7 ms     | 0,9 ms                     |

p5 coûte environ 11 µs par boid (un appel de dessin par boid), contre un seul appel pour tout le groupe avec Three.js.

**Poids des bibliothèques** : p5 1.11.11 (déjà chargé par la page) 1,07 Mo bruts, 265 Ko gzippés. Three.js 0.187.0 (licence MIT), version empaquetée par jsDelivr, auto-suffisante (aucun import externe, donc compatible avec l'importmap du projet) : 754 Ko bruts, 191 Ko gzippés.

## Décisions Techniques

### D1. Bibliothèque de rendu — Validé : p5.js WEBGL d'abord

|                              | p5.js WEBGL                                                  | Three.js                                                           |
| ---------------------------- | ------------------------------------------------------------ | ------------------------------------------------------------------ |
| Dépendance                   | Aucune nouvelle                                              | Nouvelle dépendance dans l'importmap (191 Ko gzippés)              |
| 300 à 500 boids              | 3 à 5 ms de CPU par frame : suffisant, peu de marge          | Négligeable                                                        |
| Au-delà de ~800 boids        | Devient le goulot                                            | Sans problème                                                      |
| Battement d'ailes            | Géométrie à modifier par boid : coûteux                      | Possible dans un shader ou par instance : bon marché               |
| Ciel, brouillard, effets     | Limité, mais le ciel peut être un dégradé CSS derrière un canvas transparent | Natif                                                              |
| Caméra à la souris           | `orbitControl()` intégré (à vérifier au tactile)             | `OrbitControls` à ajouter                                          |

Décision : commencer en p5 WEBGL, car le périmètre validé (quelques centaines de boids, silhouettes, pas de battement d'ailes) tient dans ses capacités et n'ajoute aucune dépendance. Le rendu sera isolé dans un fichier dédié, la logique pure ne changeant pas, donc un passage à Three.js resterait un remplacement d'un seul fichier. Critères de bascule : besoin de plus de ~800 boids ou rendu p5 trop coûteux sur des machines modestes (le battement d'ailes, écarté, n'est plus un critère).

Ajouter Three.js est une nouvelle dépendance : elle demandera ton accord explicite si on y vient.

### D2. Emplacement — Validé : seconde section sur `/lab/simulations`

Une deuxième simulation sous le 2D, avec son propre entrypoint Importmap (`lab_simulations_boids3d`). p5 est déjà chargé par la page, donc aucun coût de bibliothèque supplémentaire avec D1. Pas de nouvelle route. Le 2D n'est pas remplacé.

### D3. Bords du terrain — Validé : confinement doux, pas de rebouclage

Le rebouclage en tore du 2D repose sur une marge hors champ. En 3D, une caméra qui tourne montre toutes les faces : un boid qui se téléporte de l'autre côté serait visible. Proposition : une force douce qui ramène vers le centre les boids qui dépassent un rayon (ellipsoïde), de la même famille que les autres règles (progressive, plafonnée par `maxForce`).

Alternatives : garder le tore avec un fondu ou un brouillard aux bords (plus de rendu), ou une boîte à murs rebondissants (comportement peu naturel).

C'est une force de plus que les « règles actuelles » : elle sert uniquement à garder la nuée dans la scène, et ne constitue pas le point d'attraction du dortoir. Confirmée par Benj, qui reste ouvert à une autre stratégie de peuplement de la scène si le résultat visuel ne convient pas.

### D4. Ciel — Validé : coucher de soleil fixe, dégradé CSS derrière un canvas transparent

Le ciel est fixe : pas de cycle jour/nuit, pas de lien avec le thème du site. Le canvas est effacé avec transparence à chaque frame et le dégradé est un fond CSS. Coût nul, identique avec p5 ou Three.js. Risque : le canvas transparent en WEBGL avec p5 est à vérifier dans la première étape du rendu.

### D5. Orientation et rendu des boids — Validé (forme à affiner)

Chaque boid est une forme 3D simple et statique évoquant un petit oiseau (corps effilé et deux ailes fixes, par exemple), orientée selon sa vitesse. Le cap dessiné est lissé comme en 2D, mais en gardant pour chaque boid un vecteur direction lissé plutôt qu'un angle, ce qui évite les sauts d'angle et le blocage de cardan. Silhouettes en couleur unie, sans éclairage. Risque esthétique : sans éclairage, la profondeur ne se lit que par la taille ; une légère variation de teinte selon la distance à la caméra pourrait être nécessaire.

### D6. Caméra — Validé : `orbitControl` seul

Rotation et zoom manuels. Une rotation lente automatique, interrompue au toucher, est un raffinement ultérieur.

### D7. Réglages — Validé comme point de départ

Pause, réinitialisation et nombre de boids (100 à 500, 200 par défaut). Benj : « suis ton idée pour le moment », les ajouts viendront plus tard. Les valeurs par défaut du calcul sont celles du 2D : elles seront à réajuster à l'œil pour la 3D (par exemple, rayon de perception et poids de cohésion), ce qui fait partie de la première étape.

## Questions Tranchées

- **Q1. Battement d'ailes :** non, une forme 3D simple d'oiseau suffit, aucune animation complexe. *Validé.*
- **Q2. Ciel :** coucher de soleil fixe, sans évolution de luminosité, référence novembre. *Validé.*
- **Q3. Effectif et réglages :** proposition de départ retenue, à ajuster plus tard. *Validé.*
- **Q4. Confinement :** confirmé, ou autre stratégie de peuplement de la scène à réfléchir si besoin. *Validé.*
- **Q5. Plan d'implémentation :** approuvé. *Validé.*

Reste ouvert : les couleurs exactes du dégradé et la forme exacte de l'oiseau, à régler à l'œil aux étapes 2 et 4.

## Plan D'Implémentation

**Validé le 2026-11-07.** Chaque étape est un commit séparé, vérifiée par `make check`, sans push. Suivi : TASK-6 (étapes 1 à 4 : TASK-6.1 à TASK-6.4) dans Backlog.md.

1. **Logique 3D pure.** Paramètres par défaut 3D, force de confinement doux, directions lissées des boids, avec tests Vitest. Livrable : tests verts et benchmark 3D consigné. Aucune interface. Vérification : `npx vitest run tests/js/lab/simulations`, `make check`.
2. **Rendu minimal.** Fichier de rendu p5 WEBGL : canvas transparent, dégradé CSS de coucher de soleil, boids en forme simple d'oiseau orientée, `orbitControl`. Livrable : la scène tourne dans une page de test. Vérification : mesure des images par seconde dans Chrome headless à 200 et 500 boids, et vérification de l'`orbitControl` ; l'aspect visuel est validé par Benj.
3. **Intégration à la page.** Section dans `/lab/simulations`, entrypoint Importmap, pause/réinitialisation/nombre de boids, textes FR et EN, pause si `prefers-reduced-motion`, mise à jour de `simulations.md`. Livrable : fonctionnalité visible. Vérification : `make check`, essai manuel.
4. **Réglage visuel et décision de finition.** Réglage des valeurs par défaut à l'œil, teinte selon la profondeur si nécessaire, décision sur le passage à Three.js d'après les critères de D1. Livrable : mesures et décision consignées.

## Résultats De L'Étape 1

Logique 3D pure faite le 2026-11-07 (TASK-6.1).

- **Confinement doux** : force progressive vers le centre de l'ellipsoïde de la scène, plafonnée à `maxForce * confinementWeight`, désactivée par défaut (`confinementWeight` à 0), donc le 2D ne change pas.
- **Paramètres 3D** : `SCENE_SIZE_3D` (700×420×700) et `DEFAULT_PARAMETERS_3D` (200 boids, perception 100, séparation 26, vitesse max 4, confinement 4). Mesurés sur 12 parties de 800 frames : un seul groupe, jamais au-delà de 0,92 du rayon de confinement, au moins 13 px entre boids. Coût du calcul avec ces réglages : 0,4 à 0,5 ms par frame à 100 boids, 1,1 à 1,6 ms à 200, 4,4 à 5,7 ms à 500. À 500 boids, le calcul et le rendu p5 (environ 5 ms) approchent ensemble le budget d'une frame, ce qui justifie le plafond du curseur à 500. Ces valeurs sont un point de départ à régler à l'œil à l'étape 4.
- **Directions lissées** : `updateDirections`, un vecteur unitaire par boid, sans saut d'angle.
- **2D inchangé** : trajectoires strictement identiques à celles d'avant le projet, protégées par un test de non-régression.

## Risques

- **Coût de p5 sur les machines modestes** : mesuré en CPU seulement, avec un rendu logiciel ; le GPU réel n'est pas mesuré. Marge faible à 500 boids.
- **Canvas transparent et `orbitControl` au tactile** : non vérifiés, à contrôler en étape 2.
- **Profondeur peu lisible** sans éclairage : risque esthétique, atténué en étape 4.
- **Valeurs par défaut non transposables** : les réglages du 2D peuvent donner une nuée trop dispersée ou trop compacte en 3D.
- **Nouvelle dépendance** si Three.js devient nécessaire : accord requis.
