# Projet : Simulation Boids En 3D

Document de cadrage du projet de version 3D des boids du Lab, produit avec `~/.ai-context/project-start.md` le 2026-11-07. Il reprend et remplace la section « Piste 3D » de [boids-evolutions-notes.md](boids-evolutions-notes.md). La référence stable de la version 2D est [simulations.md](../lab/simulations.md).

Légende des statuts : **Validé** (approuvé par Benj), **Proposé** (recommandation en attente d'accord), **Ouvert** (non tranché).

Statut global au 2026-11-07 : étapes 1 et 2 faites. Le retour de Benj sur l'étape 2 a ajouté un **décor** (sol, arbres, lignes électriques) absent du cadrage : le périmètre est tranché (voir le tableau) et **le plan révisé a été approuvé par Benj**, qui y a ajouté trois exigences (orientation haut/bas, trajectoires 3D, espacement), ce qui porte le plan à 8 étapes. Suivi : TASK-6 dans Backlog.md.

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
| Comportement         | Validé  | Règles actuelles (voisins topologiques, angle mort, lissage) passées en 3D, jugées « vraiment réussies » par Benj. Deux ajouts le 2026-11-07 : espacement entre voisins et trajectoires qui exploitent les 3 dimensions (voir ci-dessous). |
| Orientation          | Validé  | Les oiseaux ont un haut et un bas : ventre vers le bas, jamais de retournement qui n'aurait pas de sens dans la vraie vie. L'inclinaison dans les virages reste hors périmètre. |
| Espacement           | Validé  | Un oiseau garde un peu d'espace avec ses voisins : à 350 à 500 boids, ils sont aujourd'hui trop rapprochés. Réglage mesuré proposé : D9. |
| Trajectoires 3D      | Validé  | La vision finale de Benj : les boids varient leurs trajectoires en exploitant les 3 dimensions, alors qu'ils tournent en ronds comme un banc de poissons. Mécanisme retenu : une cible mobile en 3D qui attire faiblement la nuée (D10). |
| Mobile               | Validé  | Ordinateur d'abord. Le mobile ne doit pas planter ; son confort relève de TASK-5. Pause au démarrage si `prefers-reduced-motion`, comme en 2D. |
| Calcul               | Validé  | Version générique en N dimensions : le calcul de `flocking.js` est réutilisé tel quel avec `dim = 3`, sans duplication.        |
| Forme du boid        | Validé  | Une forme 3D simple et statique qui évoque un petit oiseau. Aucun battement d'ailes ni animation.                              |
| Ciel                 | Validé  | Coucher de soleil fixe, sans évolution de luminosité et indépendant du thème clair/sombre. Référence : ciel de coucher de soleil de novembre (interprétation à confirmer visuellement à l'étape 2 : soleil bas, orange vif au ras de l'horizon, puis rose et violet, puis bleu profond en altitude). |
| Bords                | Validé  | Confinement dans la scène. Une autre stratégie de peuplement de la scène reste envisageable plus tard si le confinement ne convient pas visuellement. |
| Réglages             | Validé  | Proposition de départ : pause, réinitialisation, nombre de boids (100 à 500, 200 par défaut). Ajustements ou ajouts à décider plus tard. |
| Plan                 | Validé  | Plan d'implémentation en 4 étapes ci-dessous, approuvé le 2026-11-07.                                                          |
| Décor                | Validé  | Benj attendait une scène réaliste avec un sol et des éléments de paysage. Retenu : **silhouettes sombres** sur le ciel (style photo de murmuration à la tombée du jour). Première version : sol ou champ avec horizon, arbres, lignes électriques aériennes. Routes, buissons et bâtiments viendront plus tard. |
| Caméra               | Validé  | Orbite autour de la scène **au-dessus du sol** : elle ne passe jamais sous le sol, le décor est tout autour de la zone de vol. Remplace l'orbite libre de l'étape 2.          |
| Nuée et décor        | Validé  | La nuée reste au-dessus du sol et du décor, sans s'y poser. Pas de nouvelle règle de comportement (le point d'attraction du dortoir reste hors périmètre). |
| Hors périmètre       | Validé  | Inclinaison dans les virages, battement d'ailes ou toute animation du corps, pointeur 3D, simulateur de fluide. Le bruit de direction reste hors périmètre. Le point d'attraction l'était aussi, mais la cible mobile choisie pour les trajectoires 3D (D10) en est une forme : c'est la seule règle de comportement ajoutée, sans dortoir fixe. |

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

### D1. Bibliothèque de rendu — Proposé : p5.js WEBGL reste le choix, malgré le décor

|                              | p5.js WEBGL                                                  | Three.js                                                           |
| ---------------------------- | ------------------------------------------------------------ | ------------------------------------------------------------------ |
| Dépendance                   | Aucune nouvelle                                              | Nouvelle dépendance dans l'importmap (191 Ko gzippés)              |
| 300 à 500 boids              | 3 à 5 ms de CPU par frame : suffisant, peu de marge          | Négligeable                                                        |
| Au-delà de ~800 boids        | Devient le goulot                                            | Sans problème                                                      |
| Battement d'ailes            | Géométrie à modifier par boid : coûteux                      | Possible dans un shader ou par instance : bon marché               |
| Ciel, brouillard, effets     | Limité, mais le ciel peut être un dégradé CSS derrière un canvas transparent | Natif                                                              |
| Caméra à la souris           | `orbitControl()` intégré (à vérifier au tactile)             | `OrbitControls` à ajouter                                          |

Décision : commencer en p5 WEBGL, car le périmètre validé (quelques centaines de boids, silhouettes, pas de battement d'ailes) tient dans ses capacités et n'ajoute aucune dépendance. Le rendu sera isolé dans un fichier dédié, la logique pure ne changeant pas, donc un passage à Three.js resterait un remplacement d'un seul fichier. Critères de bascule : besoin de plus de ~800 boids ou rendu p5 trop coûteux sur des machines modestes (le battement d'ailes, écarté, n'est plus un critère).

Impact du décor, réexaminé le 2026-11-07 : le coût de p5 vient d'un appel de dessin par boid (environ 11 µs). Un décor statique en silhouettes unies peut être fusionné en **une seule géométrie** construite une fois (`buildGeometry`) et dessinée par un seul appel : son coût par frame est négligeable, quel que soit le nombre d'arbres. Le motif de rouvrir D1 ne tient donc pas pour un décor en silhouettes. Il tiendrait pour un décor éclairé ou texturé, ce qui est écarté. Point à noter : p5 n'offre pas de limite d'angle à `orbitControl` (la caméra pourrait passer sous le sol), alors que `OrbitControls` de Three.js en a une ; avec p5 il faut écrire cette limite (voir D6).

Ajouter Three.js est une nouvelle dépendance : elle demandera ton accord explicite si on y vient.

### D2. Emplacement — Validé : seconde section sur `/lab/simulations`

Une deuxième simulation sous le 2D, avec son propre entrypoint Importmap (`lab_simulations_boids3d`). p5 est déjà chargé par la page, donc aucun coût de bibliothèque supplémentaire avec D1. Pas de nouvelle route. Le 2D n'est pas remplacé.

### D3. Bords du terrain — Validé : confinement doux, pas de rebouclage

Le rebouclage en tore du 2D repose sur une marge hors champ. En 3D, une caméra qui tourne montre toutes les faces : un boid qui se téléporte de l'autre côté serait visible. Proposition : une force douce qui ramène vers le centre les boids qui dépassent un rayon (ellipsoïde), de la même famille que les autres règles (progressive, plafonnée par `maxForce`).

Alternatives : garder le tore avec un fondu ou un brouillard aux bords (plus de rendu), ou une boîte à murs rebondissants (comportement peu naturel).

C'est une force de plus que les « règles actuelles » : elle sert uniquement à garder la nuée dans la scène, et ne constitue pas le point d'attraction du dortoir. Confirmée par Benj, qui reste ouvert à une autre stratégie de peuplement de la scène si le résultat visuel ne convient pas.

### D4. Ciel — Validé : coucher de soleil fixe, dégradé CSS derrière un canvas transparent

Le ciel est fixe : pas de cycle jour/nuit, pas de lien avec le thème du site. Le canvas est effacé avec transparence à chaque frame et le dégradé est un fond CSS. Coût nul, identique avec p5 ou Three.js. Risque : le canvas transparent en WEBGL avec p5 est à vérifier dans la première étape du rendu.

### D5. Orientation et rendu des boids — Validé (forme à affiner ; taille : 50 %)

Chaque boid est une forme 3D simple et statique évoquant un petit oiseau (corps effilé et deux ailes fixes, par exemple), orientée selon sa vitesse. Le cap dessiné est lissé comme en 2D, mais en gardant pour chaque boid un vecteur direction lissé plutôt qu'un angle, ce qui évite les sauts d'angle et le blocage de cardan. Silhouettes en couleur unie, sans éclairage. Risque esthétique : sans éclairage, la profondeur ne se lit que par la taille ; une légère variation de teinte selon la distance à la caméra pourrait être nécessaire.

### D6. Caméra — Proposé : orbite personnalisée limitée au-dessus du sol

Benj : sans sol ni éléments de paysage, changer l'orientation de la caméra n'a pas de sens ; il choisit l'orbite autour de la scène, au-dessus du sol. Comme `orbitControl` de p5 ne limite pas l'angle, la caméra est écrite à la main : un état (azimut, hauteur d'angle, distance), une fonction pure qui en déduit la position de la caméra (testable, avec une hauteur d'angle minimale qui interdit de passer sous le sol et une distance bornée), et la souris, le tactile et la molette qui modifient cet état. Une rotation lente automatique, interrompue au toucher, est un raffinement ultérieur.

### D7. Décor en silhouettes — Proposé

Un générateur **pur et déterministe** (graine fixe) produit la liste des éléments : sol, ligne d'horizon, arbres, poteaux avec leurs câbles. Les tests vérifient les règles de placement : aucun élément n'entre dans la zone de vol, le résultat est reproductible, les câbles relient bien les poteaux. Le rendu fusionne tout en une géométrie construite une fois. Le sol est un grand disque sombre dont le bord rejoint l'horizon. Hauteurs stylisées (arbres et poteaux de l'ordre de 100 à 150 unités, un boid fait environ 20 unités de long) : des proportions réelles donneraient des arbres gigantesques par rapport aux oiseaux.

La zone de vol reste l'ellipsoïde du confinement (D3) ; le **sol est placé sous son point le plus bas, plus la hauteur du plus grand élément et une marge**, pour que la nuée ne traverse jamais le décor.

### D8. Réglages — Validé comme point de départ

Pause, réinitialisation et nombre de boids (100 à 500, 200 par défaut). Benj : « suis ton idée pour le moment », les ajouts viendront plus tard. Les valeurs par défaut du calcul sont celles du 2D : elles seront à réajuster à l'œil pour la 3D (par exemple, rayon de perception et poids de cohésion), ce qui fait partie de la première étape.

### D9. Espacement entre voisins — Validé : séparation de 46 px, poids 6 (avec la cible)

Constat mesuré le 2026-11-07, avec les réglages 3D actuels (séparation de 26 px) : le plus proche voisin est à 21 px au 5e centile à 200 boids, 18 à 350 et 17 à 500, et 94 boids sur 350 puis 170 sur 500 sont à moins de 20 px d'un voisin. Un oiseau fait environ 21 px de long et 34 px d'envergure : les silhouettes se chevauchent. La séparation (26 px) est plus petite que l'envergure.

Candidats mesurés (moyenne de 3 graines, 800 frames) :

| Séparation / poids | Effectif | 5e centile du plus proche voisin | Boids à moins de 28 px d'un voisin | Éloignement max du centre |
| ------------------ | -------- | -------------------------------- | ---------------------------------- | ------------------------- |
| 26 / 2,5 (actuel)  | 350      | 20,1 px                          | 350 sur 350                        | 0,87                      |
| 26 / 2,5 (actuel)  | 500      | 16,7 px                          | 500 sur 500                        | 0,89                      |
| 40 / 2,5           | 500      | 24,4 px                          | 136 sur 500                        | 0,99                      |
| **40 / 4**         | 350      | 31,3 px                          | 1 sur 350                          | 0,96                      |
| **40 / 4**         | 500      | 29,4 px                          | 11 sur 500                         | 0,95                      |
| 48 / 4             | 500      | 30,2 px                          | 11 sur 500                         | 1,03 (sort de la zone)    |

Dans tous ces cas, personne n'est isolé (aucun boid sans voisin dans le rayon de perception). **Ces valeurs (40 px, poids 4) ont été mesurées sans la cible mobile de D10.** Avec la cible, la nuée est tirée en bloc et se tasse : à 40 px et poids 4, le 5e centile de distance au plus proche voisin tombe à 17 à 25 px selon la forme de l'attraction. Le réglage final, mesuré avec la cible en forme de ressort (voir D10), est **séparation 46 px et poids 6** : au moins 28,5 px en moyenne dans le temps jusqu'à 500 boids (34,8 px à 200 boids), nuée d'un seul tenant. À confirmer à l'écran par Benj.

### D10. Trajectoires qui exploitent les 3 dimensions — Validé : cible mobile en 3D

Constat mesuré le 2026-11-07 sur 6 parties de 1200 frames (200 et 500 boids) : la part verticale de la vitesse est de 31 à 36 % (un mouvement réellement 3D donnerait environ 50 %), la nuée est moins étalée en hauteur qu'en largeur (écart type vertical de 30 à 75 px contre 30 à 120 en largeur et profondeur), et dans 3 parties sur 6 elle tourne en bloc autour d'un axe (rotation de 0,48 à 0,60 sur une échelle de 0 à 1) : c'est l'effet « banc de poissons » que décrit Benj.

Mécanismes possibles :

| Mécanisme                              | Principe                                                                                                       | Avantages                                              | Inconvénients                                                                    |
| -------------------------------------- | -------------------------------------------------------------------------------------------------------------- | ------------------------------------------------------ | -------------------------------------------------------------------------------- |
| Cible mobile en 3D                     | Un point virtuel suit un chemin lisse en 3D (montées, descentes, virages) et attire faiblement la nuée          | Trajectoires de groupe, cohérentes, qui montent et descendent comme une vraie nuée | Une règle de plus (le « point d'attraction » qui était hors périmètre) à régler |
| Champ de courants 3D                   | Un champ d'accélération qui varie lentement dans l'espace et le temps pousse les boids vers le haut et le bas   | Déformations naturelles de la nuée, sans point visible | Plus difficile à régler et à prévoir ; effet moins lisible                       |
| Bruit de direction par boid (« wander ») | Chaque boid reçoit un petit bruit de direction, avec une composante verticale                                   | Simple                                                 | Agite les individus plus qu'il ne déforme le groupe ; peu de trajectoires collectives |
| Volume plus haut, sans nouvelle règle  | Agrandir la hauteur de la zone de vol et ajuster cohésion et alignement                                         | Aucune règle de plus                                   | N'empêche pas le tournoiement ; effet limité                                     |

Décision de Benj le 2026-11-07 : la cible mobile en 3D, qui produit des trajectoires collectives exploitant la hauteur (une nuée réelle monte, plonge et tourne ensemble) avec une seule règle, déterministe et testable : une attraction vers un point virtuel qui suit un chemin lisse et déterministe en 3D (`boids3d/roost.js`).

**Forme de l'attraction (constat de l'étape 4).** J'avais prévu de réutiliser l'attraction du pointeur du 2D, la plus forte au point. Mesurée, elle tasse la nuée autour du point (espacement de 17 à 25 px) et la fait sortir de sa zone, parce que deux oiseaux de part et d'autre du point sont tirés l'un vers l'autre avec une force presque constante ; renforcer la séparation n'y suffisait pas. Un troisième mode du pointeur, `pull`, en forme de ressort (nulle au point, croissante avec la distance jusqu'à `pointerRadius`, constante au-delà), ramène le groupe entier vers la cible sans le comprimer : à vitesses comparables, l'espacement moyen passe de 17 à 29,5 px. Les modes `repel` et `attract` du 2D ne changent pas.

**Résultats, avec les réglages par défaut 3D** (cible `pull`, rayon 800, poids 2, séparation 46 px et poids 6), sur 12 parties de 1800 frames (6 graines, 200 et 500 boids) :

| Mesure                                         | Sans cible (pire cas) | Avec cible (12 parties)                              | Critère        |
| ---------------------------------------------- | --------------------- | ---------------------------------------------------- | -------------- |
| Part verticale de la vitesse                   | 0,04                  | médiane 0,44 à 0,47, de 0,33 à 0,57 selon la partie  | médiane ≥ 0,45 |
| Rotation en bloc                               | 0,03                  | 0,14 au plus                                         | < 0,30         |
| Déplacement vertical du centre (demi-hauteur)  | 0,07                  | 0,55 au moins                                        | ≥ 0,40         |
| 5e centile du plus proche voisin, moyenne (px) | 28,8                  | 28,5 au moins                                        | ≥ 28           |
| Boids isolés                                   | 0                     | 0                                                    | 0              |
| Éloignement maximal du centre                  | 0,96                  | 1,07                                                 | zone de vol    |

Correction à noter : une première mesure sur 4 parties donnait une part verticale de 0,50 ; sur 12 parties elle varie de 0,33 à 0,57, avec une médiane de 0,45 environ. Le critère est donc tenu en médiane et pas à chaque partie. Le minimum de 0,33 reste très au-dessus de 0,04 (sans cible). Le jugement final reste visuel, par Benj. Critères de réussite mesurables : part verticale de la vitesse d'au moins 45 % en médiane, rotation en bloc inférieure à 0,3 dans toutes les parties testées, déplacement vertical du centre de la nuée d'au moins 40 % de la demi-hauteur de la zone. Le jugement final reste visuel, par Benj.

### D11. Orientation haut/bas des oiseaux — Proposé

Cause du retournement : l'orientation actuelle est la rotation la plus courte qui amène le nez sur la direction. Elle ne dépend que de la direction, donc le roulis (l'orientation des ailes autour de l'axe du nez) change brusquement dès que la direction passe près de l'arrière, où tous les axes sont possibles.

Correctif : une orientation avec un haut fixe. Le haut du boid est le haut du monde projeté perpendiculairement à sa direction ; ventre vers le bas, ailes à l'horizontale en vol de croisière. Pour éviter un saut quand l'oiseau devient presque vertical (où le haut du monde est ambigu), on garde pour chaque boid un vecteur haut lissé, qui se rapproche de la cible sans la suivre instantanément, et qui est conservé tel quel quand la cible n'existe pas. La fonction est pure et testée : le haut ne se retourne jamais en vol horizontal, y compris quand la direction fait un demi-tour complet. Une inclinaison dans les virages pourrait venir plus tard à partir de l'accélération, mais elle est hors périmètre.

## Questions Tranchées

Questions du 2026-11-07 après un second essai de Benj :

- **Q10. Haut et bas des oiseaux :** à ajouter, pas de retournement sans sens physique (D11). *Validé.*
- **Q11. Espacement :** à ajouter, un oiseau garde de l'espace avec ses voisins, surtout entre 350 et 500 boids (D9). *Validé.*
- **Q12. Trajectoires 3D :** la vision finale est que les boids exploitent les 3 dimensions ; mécanisme choisi : cible mobile en 3D (D10). *Validé.*

Questions du 2026-11-07 sur le décor, après le retour de Benj sur l'étape 2 :

- **Q6. Style du décor :** silhouettes sombres. *Validé.*
- **Q7. Caméra :** orbite autour de la scène au-dessus du sol. *Validé.*
- **Q8. Nuée et décor :** reste au-dessus, pas de dortoir. *Validé.*
- **Q9. Éléments de la première version :** sol ou champ avec horizon, arbres, lignes électriques. Routes, buissons, bâtiments plus tard. *Validé.*

Questions du cadrage initial :

- **Q1. Battement d'ailes :** non, une forme 3D simple d'oiseau suffit, aucune animation complexe. *Validé.*
- **Q2. Ciel :** coucher de soleil fixe, sans évolution de luminosité, référence novembre. *Validé.*
- **Q3. Effectif et réglages :** proposition de départ retenue, à ajuster plus tard. *Validé.*
- **Q4. Confinement :** confirmé, ou autre stratégie de peuplement de la scène à réfléchir si besoin. *Validé.*
- **Q5. Plan d'implémentation :** approuvé. *Validé.*

Reste ouvert : les couleurs exactes du dégradé et la forme exacte de l'oiseau, à régler à l'œil aux étapes 2 et 4.

## Plan D'Implémentation

**Ordre modifié le 2026-11-07, à la demande de Benj** : le décor (étape 5) passe avant la fin de l'étape 4. Constat de Benj : la page de test ne montrait ni sol, ni arbres, ni repère haut/bas, ce qui rend l'orientation et les trajectoires difficiles à juger. L'étape 4 est faite côté code et mesures (cible `pull`, espacement), commitée, en attente de sa validation visuelle ; on y reviendra après le décor, avec l'étape 3 qui attend elle aussi son regard. Rien n'est refait : la zone de vol, qui fixe la hauteur du sol, ne change pas.

**Approuvé par Benj le 2026-11-07, puis complété par ses trois ajouts le même jour** : le plan compte 8 étapes. Chaque étape est un commit séparé, vérifiée par `make check`, sans push. Le comportement passe avant le décor, car la hauteur de la zone de vol, qui dépend du comportement, fixe la hauteur du sol.

1. **Logique 3D pure.** *Faite* (TASK-6.1).
2. **Rendu minimal.** *Fait* (TASK-6.2), validé par Benj avec réserves.
3. **Orientation haut/bas des oiseaux** (TASK-6.5, D11). Remplacer l'orientation par rotation la plus courte par une orientation à haut lissé, fonction pure testée. Vérification : tests sur le haut en vol horizontal, sur un demi-tour complet et près de la verticale ; vérification que la rotation appliquée par p5 donne bien le repère attendu ; capture d'écran.
4. **Comportement 3D** (TASK-6.6, D9 et D10). Espacement : séparation 40 et poids 4 sauf mesure contraire. Trajectoires 3D : cible mobile en 3D (D10). Vérification : les mesures de D9 et de D10 refaites avec les critères proposés, tests Vitest, trajectoires 2D inchangées, validation visuelle de Benj.
5. **Décor en silhouettes** (TASK-6.7, D7). Générateur pur et déterministe (sol, horizon, arbres, poteaux et câbles) avec tests de placement, puis géométrie fusionnée dessinée par un seul appel ; sol placé sous la zone de vol. Vérification : tests Vitest, mesure des images par seconde avec et sans décor, capture d'écran, validation de Benj.
6. **Caméra orbitale au-dessus du sol** (TASK-6.8, D6). Remplacer `orbitControl` : état azimut, hauteur d'angle et distance, fonction pure de position de caméra avec limites, souris, tactile et molette. Vérification : tests sur la fonction pure, essai souris et tactile émulé, essai manuel de Benj.
7. **Intégration à la page** (TASK-6.3). Section dans `/lab/simulations`, entrypoint Importmap, pause, réinitialisation et nombre de boids (100 à 500, 200 par défaut), textes FR et EN, pause si `prefers-reduced-motion`, mise à jour de `simulations.md`.
8. **Réglage visuel et finitions** (TASK-6.4). Contraste entre oiseaux et sol, couleurs du ciel (Benj : elles ne reflètent pas exactement celles qu'il avait en tête), volume des oiseaux (ils paraissent plats de face), décision finale sur Three.js d'après les mesures.

### Suite Du Plan Après La Deuxième Passe De Benj

**Proposé le 2026-11-07, en attente de son accord sur l'ordre** (les fonctionnalités, elles, ont été décidées par lui). Les étapes 3 à 5 sont faites (3 en attente du regard de Benj). Les nouvelles étapes viennent avant la caméra, l'intégration à la page et le réglage visuel :

- **TASK-6.9, échelle et proportions** : choix de Benj sur la comparaison ci-dessus, puis application (taille des oiseaux, des arbres et des poteaux, nombre d'oiseaux).
- **TASK-6.10, trajectoire plus ample et plus de hauteur** : zone de vol plus grande, chemin de la cible plus ample, plus de hauteur exploitée.
- **TASK-6.12, propagation par meneurs** : seule une fraction des boids perçoit la cible ; mesure du délai de propagation et réglage de la fraction.
- **TASK-6.11, évitement prédictif des arbres** : zone de vol abaissée jusqu'aux arbres, évitement par regard devant (cylindre le long de la vitesse), sans répulsion ; garde-fou pour le sol.
- **TASK-6.13, repères d'échelle** : brume lointaine cuite dans les couleurs des sommets, maison ou grange, clocher ou silhouette lointaine, route avec voiture.
- puis TASK-6.8 (caméra au-dessus du sol), TASK-6.3 (intégration à la page) et TASK-6.4 (réglage visuel).

Numérotation Backlog.md : les identifiants suivent l'ordre de création, pas l'ordre d'exécution ; l'ordre d'exécution est 6.9, 6.10, 6.12, 6.11, 6.13, 6.8, 6.3, 6.4, porté par les dépendances des tâches.

## Résultats De L'Étape 1

Logique 3D pure faite le 2026-11-07 (TASK-6.1).

- **Confinement doux** : force progressive vers le centre de l'ellipsoïde de la scène, plafonnée à `maxForce * confinementWeight`, désactivée par défaut (`confinementWeight` à 0), donc le 2D ne change pas.
- **Paramètres 3D** : `SCENE_SIZE_3D` (700×420×700) et `DEFAULT_PARAMETERS_3D` (200 boids, perception 100, séparation 26, vitesse max 4, confinement 4). Mesurés sur 12 parties de 800 frames : un seul groupe, jamais au-delà de 0,92 du rayon de confinement, au moins 13 px entre boids. Coût du calcul avec ces réglages : 0,4 à 0,5 ms par frame à 100 boids, 1,1 à 1,6 ms à 200, 4,4 à 5,7 ms à 500. À 500 boids, le calcul et le rendu p5 (environ 5 ms) approchent ensemble le budget d'une frame, ce qui justifie le plafond du curseur à 500. Ces valeurs sont un point de départ à régler à l'œil à l'étape 4.
- **Directions lissées** : `updateDirections`, un vecteur unitaire par boid, sans saut d'angle.
- **2D inchangé** : trajectoires strictement identiques à celles d'avant le projet, protégées par un test de non-régression.

## Résultats De L'Étape 2

Rendu minimal fait le 2026-11-07 (TASK-6.2), en attente de la validation visuelle de Benj.

- **Fichiers** : `assets/scripts/lab/simulations/boids3d/sketch.js` (rendu p5 WEBGL, isolé du calcul), `orientation.js` (rotation du nez de l'oiseau vers sa direction, fonction pure testée) et la classe CSS `.boids-canvas--sunset` (le ciel) dans `assets/styles/lab/simulations.css`.
- **Rendu** : canvas WEBGL transparent effacé à chaque frame, dégradé CSS de coucher de soleil derrière, oiseau construit une fois avec `buildGeometry` (corps effilé et deux ailes en flèche, statique) puis dessiné avec `model()` pour chaque boid, antialiasing activé.
- **Vérifié** : la rotation de p5 (`rotate(angle, axe)`) donne bien la direction attendue sur 5 cas ; aucune erreur JS ni avertissement ; une capture de la scène montre des oiseaux lisibles, orientés dans le même sens, sur le dégradé.
- **Caméra** : `orbitControl` répond à un glisser à la souris et à un glisser au doigt (tactile émulé), nuée en pause. Le zoom à la molette modifie l'image mais ce signal est faible : à confirmer à la main.
- **Images par seconde** (Chrome headless, rendu logiciel donc pessimiste) : 55 images par seconde à 200 boids, 36 à 500 boids.
- **Constat initial, infirmé par Benj** : j'avais jugé la nuée petite et compacte à l'écran (un groupe d'environ 120 px de large dans un canvas de 1000 px). Benj trouve au contraire sa taille et sa densité bonnes.

## Résultats De L'Étape 3

Orientation haut/bas faite le 2026-11-07 (TASK-6.5), en attente de la validation visuelle de Benj.

- **Fichier** : `assets/scripts/lab/simulations/boids3d/orientation.js` (fonctions pures `updateUps` et `orientationMatrix`), qui remplace l'ancienne rotation la plus courte. Le rendu applique la matrice avec `applyMatrix`.
- **Principe** : le haut d'un oiseau est le haut du monde, projeté perpendiculairement à sa direction, et lissé d'une frame à l'autre par une rotation autour de la direction (une fraction constante de l'angle restant, 0,1 par frame). Une direction exactement verticale n'a pas de haut défini : l'ancien haut est conservé. Une première version qui mélangeait linéairement les deux vecteurs restait coincée à l'envers après une boucle ; elle a été remplacée par cette rotation.
- **Mesuré sur 690 000 boids-frames** (3 parties de 1200 frames, 200 boids) : avec l'ancienne orientation, 28 retournements de plus de 90° en une seule frame (jusqu'à 178°) et un oiseau le dos vers le bas 8,6 % du temps ; avec la nouvelle, aucun retournement, un changement maximal de 25° par frame, et 0,7 % du temps le dos vers le bas (passages quasi verticaux).
- **Vérifié dans le navigateur** : `applyMatrix` de p5 applique exactement la matrice attendue (écart nul sur 5 attitudes), aucune erreur JS, environ 52 images par seconde à 200 boids (Chrome headless, rendu logiciel).
- **Tests** : 17 tests dans `orientation.test.js` ; 5 défauts réinjectés volontairement sont tous détectés. L'un d'eux ne l'était pas au départ (un boid vertical aurait emprunté la cible du boid précédent) ; un test a été ajouté.

## Résultats De L'Étape 5

Décor en silhouettes fait le 2026-11-07, avant la fin de l'étape 4 (TASK-6.7), en attente de la validation visuelle de Benj.

- **Fichiers** : `assets/scripts/lab/simulations/boids3d/landscape.js` (générateur pur et déterministe, graine fixe) ; le rendu dans `boids3d/sketch.js`.
- **Contenu** : un sol (disque plat de 6000 unités), une couronne de collines à l'horizon, 78 arbres (conifères et feuillus, par bosquets), deux lignes de poteaux (11 poteaux chacune) avec leurs câbles qui s'affaissent. Environ 7 000 triangles au total, construits une seule fois.
- **Rendu** : deux géométries fusionnées (le sol, le reste du décor), donc deux appels de dessin par frame quel que soit le nombre d'arbres. Le plan lointain de la caméra est repoussé à 15 000 pour voir l'horizon.
- **Sol sous la zone de vol** : il est à 421 unités sous le centre de la scène, soit la limite basse de la nuée (1,1 fois la demi-hauteur) plus la hauteur du plus grand élément (150) plus 40 de marge. Le test vérifie qu'aucun sommet du décor n'entre dans l'ellipsoïde de vol.
- **Caméra de départ abaissée** : à 91 unités du sol, regardant vers la nuée. À l'altitude de la nuée, les arbres sont vus de dessus et se confondent avec le sol ; près du sol, ils se détachent sur le ciel comme sur une photographie de murmuration.
- **Couleurs** : une géométrie p5 garde la couleur active au moment où elle est construite ; un `fill()` appelé ensuite au dessin ne la change pas. Le sol, le décor et les oiseaux ont donc chacun leur couleur fixée avant leur construction (oiseaux les plus sombres, décor un peu plus clair, sol plus clair encore).
- **Mesuré** (Chrome headless, rendu logiciel donc pessimiste) : 42 images par seconde à 200 boids et 31 à 500, contre 52 et 36 sans le décor. Aucune erreur JS.
- **Limite connue** : `orbitControl` n'a pas de limite d'angle, donc la caméra peut passer sous le sol, qui la masque alors entièrement. C'est l'objet de l'étape « caméra » (TASK-6.8).
- **Tests** : 18 tests dans `landscape.test.js` ; 5 défauts réinjectés sur 6 sont détectés. Le sixième (retirer la règle « aucun arbre sous la nuée ») ne l'est pas : avec ce placement par bosquets, la règle ne se déclenche presque jamais, donc les tests ne peuvent pas la faire échouer. C'est une garde défensive non exercée.

## Résultats De TASK-6.10 (Trajectoire Plus Ample)

Fait le 2026-11-07, en attente de la validation visuelle de Benj.

- **Zone de vol** : de 700×420×700 à **1800×700×700** (`SCENE_SIZE_3D`). Large et haute mais peu profonde : la caméra est fixe à environ 1200 unités de la nuée et Benj a choisi la taille apparente des oiseaux à cette distance (50 %) ; une nuée qui bougerait aussi beaucoup en profondeur passerait très près de la caméra (énorme) ou très loin (minuscule). Une caméra qui suit la nuée serait l'autre solution (étape caméra).
- **Chemin de la cible** : amplitudes 0,6, 0,55 et 0,4 de la demi-étendue (la cible reste dans l'ellipsoïde, racine de la somme des carrés 0,91), fréquences basses (0,0035, 0,0085 et 0,0058 rad par frame) pour que la cible n'aille jamais plus vite que la vitesse de croisière des boids (2,6 au plus contre 2,8) ; un balayage latéral complet dure environ 30 secondes à 60 images par seconde.
- **Rayon d'attraction** `pull` de 1400 (800 avant), choisi parmi quatre variantes mesurées.
- **Mesuré sur 6 parties de 3600 frames** (3 graines, 200 et 500 boids), référence = ancienne zone et ancien chemin :

| Mesure                                               | Avant                | Après                |
| ---------------------------------------------------- | -------------------- | -------------------- |
| Parcours du centre en largeur                        | 300 unités           | **980 unités**       |
| Parcours du centre en hauteur                        | 180 unités           | **315 unités**       |
| Part verticale de la vitesse (médiane, minimum)      | 0,47 (0,44)          | 0,46 (0,44)          |
| Rotation en bloc (pire cas)                          | 0,11                 | 0,11                 |
| Espacement, 5e centile du plus proche voisin (px)    | 28,5                 | 33,0                 |
| Boids isolés                                         | 0                    | 0                    |
| Éloignement maximal du centre                        | 1,05                 | 0,97                 |

- **Caméra** : la hauteur de la caméra suit le sol (91 unités au-dessus) et elle vise un point un peu plus bas que le centre de la nuée (200 unités sous le centre) : sans cela, le sol plus bas ne tenait plus que dans 9 % du bas de l'image au lieu de 22 %, ce que les captures ont montré.
- **Limite** : le parcours en profondeur reste modeste (290 unités), par choix. Les arbres sont toujours sous la zone de vol : l'évitement (TASK-6.11) les rapprochera.

## Retour De Benj Sur L'Étape 2

2026-11-07, après avoir regardé la page de test.

- **Ciel** : validé. Les couleurs ne reflètent pas exactement celles que Benj avait en tête, à retravailler plus tard.
- **Forme des oiseaux** : validée pour le moment. Elle ressemble à une forme plate : quand tous les oiseaux font face à la caméra, ils paraissent tout plats, ce qui produit des effets visuels particuliers. À améliorer (volume réel du corps et des ailes).
- **Taille et densité de la nuée** : bonnes. Le constat de l'étape 2 (« nuée compacte et petite ») ne se confirme donc pas côté ressenti ; à retirer des points à régler à l'étape 4.
- **Comportement** : « vraiment réussi ».
- **Décor** : manquant. Benj avait en tête une scène réaliste, avec un sol et des éléments de paysage (arbres, routes, buissons, lignes électriques aériennes). Sans eux, changer l'orientation de la caméra ne veut rien dire.

## Retour De Benj Sur Les Étapes 3 À 5 (Deuxième Passe)

2026-11-07, après avoir regardé la scène avec le décor, le comportement et l'espacement.

- **Scène** : lisible. Le paysage, le ciel, le sol, les éléments se distinguent clairement.
- **Trajectoire** : « vraiment pas mal du tout ».
- **Nouvelles demandes**, toutes à cadrer :
  1. **Proportion entre oiseaux et décor** (par exemple un arbre par rapport à un oiseau) : une idée, sans certitude que ça rende bien ; Benj demande de creuser les astuces des créateurs de scènes 3D.
  2. **Plus de hauteur exploitée** par les boids, et **interaction avec les arbres** : qu'ils puissent passer près d'un arbre sans collision. Pas une répulsion, juste un évitement.
  3. **Propagation d'un changement de trajectoire dans le groupe** : trop rapide, « robotique ». Un délai de propagation est souhaité, sans savoir quel paramètre régler.
  4. **Trajectoire générale plus ample** : vu la taille de la scène, la nuée pourrait parcourir plus de distance.
  5. **Taille de la scène indéfinie** : il faudrait un élément de taille de référence facilement identifiable ; Benj ne sait pas quoi proposer.

### Constats Et Sources

**Échelle.** Un boid fait 21 unités de long, un arbre 60 à 140 et un poteau 110 à 130 : un arbre fait donc 3 à 7 fois la longueur d'un oiseau. Un étourneau mesure environ 20 cm et un arbre 10 à 20 m, soit 50 à 100 fois (ordres de grandeur de mémoire, non vérifiés par la recherche). En prenant 1 unité pour 1 cm, la zone de vol de 700×420×700 unités ferait 7×4×7 m et un poteau 1,3 m : le décor est environ 10 fois trop petit par rapport aux oiseaux, et ce n'est pas le décor qui définit l'échelle. Les réglages internes de la nuée, eux, sont plausibles à cette échelle (7 voisins pris en compte, comme dans les vraies nuées).

**Astuces d'échelle** (sources générales d'art et de jeu vidéo ; peu de matière propre au temps réel). Un objet de taille connue (une personne, une voiture) est le moyen le plus simple de donner l'échelle. La perspective atmosphérique (les objets lointains perdent détails, saturation et contraste, on ne les floute pas) donne la distance. Les éléments modulaires répétés donnent la taille. Un petit objet se lit comme petit plutôt que lointain s'il n'y a ni recouvrement, ni atmosphère, ni sol. La perspective forcée place les grands objets près de la caméra. L'échelle marche mieux quand ces indices se combinent. Une remarque propre à notre cas : le décor est statique, donc la perspective atmosphérique peut être cuite dans les couleurs des sommets, sans brouillard au rendu.

**Propagation du changement de trajectoire.** Dans de vraies nuées d'étourneaux, l'information de virage se propage à 20 à 40 m/s, et traverse une nuée de 400 oiseaux en un peu plus d'une demi-seconde ; quelques oiseaux mènent le virage. Chez nous, la cible attire tous les boids au même instant : il n'y a aucun délai, d'où l'effet robotique. Estimation (non vérifiée, elle suppose une vitesse de vol réelle de 10 à 15 m/s) : l'information va environ 2 à 3 fois plus vite que les oiseaux, soit 6 à 9 unités par frame à notre vitesse de croisière, donc 15 à 35 frames pour traverser une nuée de 100 à 250 unités.

**Évitement d'obstacles.** La méthode classique de Reynolds regarde devant l'agent (un cylindre le long de sa vitesse) : si rien ne gêne, la force est nulle ; sinon on ne traite que l'obstacle le plus proche. C'est un évitement prédictif, pas une répulsion qui croît à l'approche.

Sources : [Creating Vast Landscapes in 3D](https://3dartist.substack.com/p/creating-vast-landscapes-in-3d), [simple stylized environment: scale and mood (80.lv)](https://80.lv/articles/simple-stylized-environment-scale-and-mood/), [atmospheric perspective and scale](https://flipeducation.ai/curriculum/us/arts/grade-6/atmospheric-perspective-and-scale), [Superfluid transport of information in turning flocks of starlings](https://ar5iv.arxiv.org/html/1303.7097), [The seventh starling](https://contrarian.ca/2012/06/20/the-seventh-starling/), [Steering Behaviors (Reynolds 1999)](https://ics-websites.science.uu.nl/docs/vakken/mcrws/papers_new/Reynolds%20-%201999%20-%20Steering%20behaviors%20for%20autonomous%20characters.pdf).

### Décisions Rouvertes

Deux décisions validées plus tôt sont à rouvrir pour un motif nouveau et réel :

- **D3 et D7 (« la nuée reste au-dessus du sol et du décor »)** : Benj veut maintenant que les boids passent près des arbres. La zone de vol et la hauteur des arbres doivent donc se recouvrir, au lieu d'être séparées.
- **Le point de départ du comportement** : une règle de plus (l'évitement des arbres), en plus de la cible déjà ajoutée.

### Décisions De Benj (Q13 À Q16)

- **Q13. Échelle du décor :** comparer des variantes à l'œil avant de trancher. *Validé* ; la comparaison est prête (voir plus bas), le choix reste à faire.
- **Q14. Arbres :** évitement prédictif (chaque boid regarde devant lui et ne dévie que si sa trajectoire va toucher un arbre) ; la zone de vol est abaissée vers la hauteur des arbres. Cela remplace « la nuée reste au-dessus du décor » (D3, D7). *Validé.*
- **Q15. Propagation du changement de trajectoire :** meneurs, c'est-à-dire que seule une petite fraction des boids (environ 10 à 20 %) perçoit la cible, les autres suivent par alignement et cohésion, donc le délai naît tout seul. *Validé.*
- **Q16. Repères de taille :** brume lointaine (perspective atmosphérique), maison ou grange, clocher ou silhouette lointaine, route avec voiture. Les quatre. *Validé.*

### Comparaison Des Proportions Oiseau Et Arbre

Méthode : j'avais proposé d'agrandir arbres et poteaux de ×2, ×4 et ×10. Ce n'est pas une comparaison propre : des arbres plus hauts entrent dans la zone de vol ou obligent à descendre le sol, ce qui change le cadrage, et un décor ×10 sort du cadre avec la même caméra (c'est le vrai dilemme du réalisme : nuée grande à l'écran ou paysage entier à l'écran, pas les deux). La comparaison est donc faite à l'envers, avec le même rapport de taille et le **même cadrage** : les oiseaux sont dessinés plus petits (option `birdScale` du rendu, sans toucher au calcul) à 100 %, 50 %, 25 % et 10 %, soit des arbres de 5, 10, 20 et 50 fois la longueur d'un oiseau.

Constat sur captures : à 25 % la nuée se lit comme un nuage de points, proche d'une photo de murmuration vue de loin ; à 10 % les oiseaux deviennent de la poussière et la nuée disparaît. Plus les oiseaux sont petits, plus il faut d'oiseaux ou un espacement plus serré pour que la nuée garde de la présence ; c'est un choix de goût que Benj tranche sur son écran.

**Choix de Benj le 2026-11-07 : 50 %.** Les oiseaux sont dessinés à la moitié de la taille de leur modèle, soit un arbre d'environ 10 fois la longueur d'un oiseau. En dessous (25 % et 10 %), les boids ne se voient plus du tout. C'est un choix de rendu : la simulation (espacement, rayon de perception, vitesse) ne change pas. Ce rapport reste très en deçà du réel (50 à 100 fois) : le décor est stylisé, et les repères d'échelle à venir (grange, clocher, voiture) devront avoir une taille cohérente avec ce rapport de 10, pas avec des tailles réelles. Les arbres et les poteaux gardent leur taille.

## Évolution De Périmètre (Tranchée Le 2026-11-07)

Le cadrage validé ne prévoyait ni sol ni paysage (« silhouettes sur un ciel de coucher de soleil »). L'attente de Benj est plus large. Ajouter un décor a des conséquences à décider avant toute implémentation, car elles touchent des décisions déjà prises :

- **Rendu (D1)** : un sol et des éléments de paysage ajoutent beaucoup de géométrie, de la perspective, peut-être du brouillard et des textures. Le choix p5 WEBGL ou Three.js, fondé sur « quelques centaines de boids et aucun décor », est à rouvrir pour un motif nouveau et réel.
- **Caméra (D6)** : avec un sol, la caméra ne peut plus orbiter librement autour de la nuée (on passerait sous le sol) ; un point de vue à hauteur d'œil dans le paysage est plus naturel.
- **Comportement (D3)** : la nuée doit rester au-dessus du sol, donc la zone de confinement change de forme et de position.
- **Style** : un décor en silhouettes sombres sur le ciel (arbres, lignes électriques, horizon) est peu coûteux et cohérent avec l'esthétique actuelle ; un décor 3D réaliste éclairé et coloré est un projet bien plus gros.
- **Plan** : l'intégration à la page et le réglage visuel dépendaient de ces décisions ; ils passent après deux étapes nouvelles (décor, caméra).

Benj a tranché les questions : voir les tableaux ci-dessus (décor, caméra, nuée et décor) et Q6 à Q9. Le plan est révisé en conséquence.

## Risques

- **Contraste oiseaux et sol** : oiseaux et sol sont tous deux sombres, donc vus de dessus ou à contre-jour avec le sol derrière eux, les oiseaux risquent de disparaître. À traiter par une teinte distincte du sol (plus chaude, dégradée vers l'horizon) et une hauteur d'angle minimale de la caméra ; à juger à l'écran.
- **Caméra écrite à la main** : plus de code que `orbitControl`, et le comportement au tactile à vérifier ; atténué par une fonction pure testée.
- **Proportions** : décor et oiseaux n'ont pas la même échelle réelle ; les hauteurs stylisées sont à régler à l'œil.

- **Coût de p5 sur les machines modestes** : mesuré en CPU seulement, avec un rendu logiciel ; le GPU réel n'est pas mesuré. Marge faible à 500 boids.
- **Canvas transparent et `orbitControl` au tactile** : non vérifiés, à contrôler en étape 2.
- **Profondeur peu lisible** sans éclairage : risque esthétique, atténué en étape 4.
- **Valeurs par défaut non transposables** : les réglages du 2D peuvent donner une nuée trop dispersée ou trop compacte en 3D.
- **Nouvelle dépendance** si Three.js devient nécessaire : accord requis.
