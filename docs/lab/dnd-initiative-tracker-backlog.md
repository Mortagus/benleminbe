# Backlog unifié - DnD Initiative Tracker

Date de mise à jour : 2026-05-15

Ce document remplace l'ancien backlog d'audit et la roadmap avancée. Il conserve les tâches déjà réalisées, aligne les fonctionnalités de la roadmap avec l'état réel du projet et liste les évolutions restantes.

Documents descriptifs associés :

- [dnd-initiative-audit.md](/var/www/projects/benleminbe/docs/lab/dnd-initiative-audit.md:1)
- [dnd-bestiary-pipeline.md](/var/www/projects/benleminbe/docs/lab/dnd-bestiary-pipeline.md:1)
- [dnd-dom-contracts.md](/var/www/projects/benleminbe/docs/lab/dnd-dom-contracts.md:1)

## Note De Reprise - 2026-05-21

Une fois le cycle Lighthouse/cache et la validation de la zone privee termines, le prochain projet hors partie professionnelle du site est de reprendre l'amelioration du `DnD Initiative Tracker`.

Objectif de reprise recommande :

- stabiliser l'outil pour un usage reel pendant une session de jeu ;
- conserver l'approche front-end JavaScript vanilla actuelle ;
- commencer par la sauvegarde locale de rencontre (`P8`) avant les fonctions de combat plus avancees ;
- preparer ensuite l'import/export JSON (`P22`), les PV modifiables pendant le combat (`P10`) et les commandes explicites de round/tour (`P7`).

Premiere action recommandee :

```text
Relire l'etat actuel du modèle de rencontre dans `encounter-state.js`, verifier les contrats DOM existants, puis cadrer le format versionne de sauvegarde locale avant d'ecrire le code.
```

## Légende

- `Fait` : fonctionnalité ou correction présente dans le code.
- `Fait - en observation` : présent dans le code, à ajuster après usage réel.
- `Partiel` : une base existe, mais la fonctionnalité attendue n'est pas complète.
- `À faire` : non implémenté ou à reprendre.
- `Plus tard` : utile, mais non prioritaire pour stabiliser le tracker.

## Principes de priorisation

Le backlog est organisé sous forme de tableau pour pouvoir réordonner les points sans déplacer de longues sections descriptives. La colonne `Ordre conseillé` donne l'enchaînement actuel recommandé.

Les points d'architecture, de modèle de données, de tests et de contrats techniques sont volontairement remontés quand ils conditionnent plusieurs fonctionnalités futures. L'objectif est d'éviter de construire les commandes de combat, la sauvegarde ou les PV en direct sur des fondations fragiles.

## Backlog

| Ordre conseillé | ID  | Priorité actuelle | Statut          | Catégorie                  | Point                                                    | Résumé / résultat attendu                                                                                                        | Dépendances / remarques                                                                               |
|-----------------|-----|-------------------|-----------------|----------------------------|----------------------------------------------------------|----------------------------------------------------------------------------------------------------------------------------------|-------------------------------------------------------------------------------------------------------|
| 1               | P16 | Réalisé           | ✅ **Fait**      | Architecture               | Documentation et allègement du catalogue de monstres     | Pipeline `tools/dnd/` documenté, ancien extracteur supprimé, génération directe de `bestiary.js` et test de contrat ajoutés.     | Import statique conservé tant que le catalogue reste à l'échelle actuelle.                            |
| 2               | P9  | Réalisé           | ✅ **Fait**      | JavaScript / Règles        | Tests sur les règles et la génération d'ordre            | Vitest ajouté avec tests unitaires sur slots, sélection, jets, tri, règles maison, état joué/non joué et mutations de rencontre. | La fixture bestiaire de test est générée depuis le même extracteur que le catalogue complet.          |
| 9               | P18 | Réalisé           | ✅ **Fait**      | Textes                     | Harmonisation des textes et libellés de jeu              | Vocabulaire stabilisé : acteur, joueur, monstre, initiative, ordre du tour, PV actuels, PV max, à jouer et joué.                 | Libellés visibles gardés courts pour ne pas alourdir l'interface.                                     |
| 18              | P6  | Socle livré       | ✅ **Fait**      | Architecture / JavaScript  | Modèle de rencontre explicite et orchestration clarifiée | `encounter-state.js` centralise le modèle et les mutations ; les panneaux deviennent des adaptateurs DOM.                        | Base de la sauvegarde, des commandes de combat, des PV en direct, des tests et de l'import/export.    |
| 19              | P1  | Réalisé           | ✅ **Fait**      | Règles                     | Sélecteur de règles à appliquer                          | Les règles maison sont pilotables via une popup du panneau ordre du tour.                                                        | À ajuster après usage réel si les libellés ou interactions créent une friction.                       |
| 20              | P2  | Réalisé           | ✅ **Fait**      | Sécurité / UX              | Validation des entrées utilisateur                       | Validation JavaScript par panneau avec messages d'erreur et blocage des actions risquées.                                        | Couvre nombre de monstres invalide, absence d'acteur, PV incohérents et joueur incomplet.             |
| 21              | P3  | Réalisé           | ✅ **Fait**      | Sécurité                   | Construction DOM sûre                                    | Les données dynamiques sont rendues via `textContent`, templates DOM, `replaceChildren()` et APIs DOM sûres.                     | Conserver cette règle locale ; éviter `innerHTML` sauf cas strictement contrôlé.                      |
| 22              | P4  | Réalisé           | ✅ **Fait**      | JavaScript                 | Extraction stable des données joueurs                    | Les joueurs sont lus via `data-player-field` plutôt que par position des champs dans le DOM.                                     | Toute évolution du formulaire doit maintenir ce contrat ou adapter `players.js` et `validation.js`.   |
| 23              | P5  | Réalisé           | ✅ **Fait**      | Fonctionnalité             | Noyau MVP de suivi d'initiative                          | Participants, monstres, jets, tri, ordre du tour, drag-and-drop, tours joués, acteur actif et initiales sont en place.           | Le socle reste en mémoire uniquement et ne couvre pas encore rounds, sauvegarde ou PV directs.        |
| 24              | P11 | Réalisé           | ✅ **Fait**      | Twig                       | Réduction de duplication du template joueur              | Le markup joueur est factorisé dans `_player_item.html.twig` pour la ligne initiale et le template dynamique.                    | Limite les doubles modifications futures du formulaire joueur.                                        |
| 25              | P12 | Réalisé           | ✅ **Fait**      | Accessibilité / Twig       | Accessibilité des formulaires dynamiques                 | Labels reliés, identifiants recalculés, noms accessibles des PV, boutons contextualisés et selects monstres labellisés.          | Maintenir ces attributs lors des prochains changements de formulaire.                                 |
| 26              | P13 | Réalisé           | ✅ **Fait**      | Accessibilité / UX         | Drag-and-drop utilisable autrement qu'à la souris        | Drag souris gauche/droite, déplacement au clavier par flèches, focus conservé, aide clavier repliable et annonces `aria-live`.   | Les boutons de déplacement restent cliquables à la souris mais sont retirés de l'enchaînement `Tab`.  |
| 3               | P8  | Haute             | 🔶 **À faire**  | Persistance                | Sauvegarde locale de rencontre                           | Persister et restaurer monstres, joueurs, PV, règles, ordre du tour, round et acteur actif via `localStorage`.                   | Dépend du modèle de rencontre ; définir une version de format dès la première implémentation.         |
| 4               | P22 | Haute technique   | 🔶 **À faire**  | Persistance / Échange      | Import/export JSON d'une rencontre                       | Exporter et importer un état de rencontre lisible : joueurs, monstres, PV, initiatives, règles et ordre courant.                 | Plus simple après P8 ; utile pour archiver, partager et tester des scénarios reproductibles.          |
| 5               | P10 | Haute             | 🔶 **À faire**  | Fonctionnalité / UX        | Modification directe des PV pendant le combat            | Modifier les PV depuis l'ordre du tour et accepter des saisies rapides de dégâts/soins comme `-7` ou `+5`.                       | Préparer sans imposer immédiatement les statuts inconscient/mort.                                     |
| 6               | P7  | Haute             | 🔶 **À faire**  | Fonctionnalité / UX        | Commandes explicites de pilotage du combat               | Ajouter acteur suivant, nouveau round, réinitialisation des tours joués et remise à zéro de la rencontre.                        | S'appuie sur le modèle de rencontre et permettra de fiabiliser conditions, journal et durées.         |
| 7               | P14 | Moyenne haute     | 🔶 **À faire**  | UX                         | Retours d'état utiles au Maître du Jeu                   | Afficher des messages courts : aucun monstre sélectionné, initiatives non lancées, joueur incomplet, acteur marqué joué.         | Peut être livré par petites touches sans attendre les grosses fonctionnalités.                        |
| 8               | P15 | Moyenne haute     | 🔶 **À faire**  | Fonctionnalité / UX        | Aides de sélection pour les monstres                     | Ajouter recherche par nom, puis filtres type/taille/FP si nécessaire, favoris ou presets plus tard.                              | Bénéficie de P16 pour clarifier la source et la structure du catalogue.                               |
| 10              | P19 | Moyenne           | 🔶 **À faire**  | Fonctionnalité / Règles    | Gestion des conditions                                   | Ajouter/retrouver des conditions, les afficher visuellement et suivre leur durée en rounds.                                      | Dépend fortement de P7 pour rounds et acteur actif fiables.                                           |
| 11              | P20 | Moyenne           | 🔶 **À faire**  | Fonctionnalité             | Marqueurs binaires de combat                             | Suivre concentration, réaction utilisée, inspiration, avantage et désavantage via toggles visuels.                               | À commencer sans automatisme de règles ; peut partager l'UI des conditions.                           |
| 12              | P21 | Moyenne           | 🔶 **À faire**  | Fonctionnalité / Suivi     | Journal de combat                                        | Journaliser début de round, changements de PV, acteur joué, conditions ajoutées ou retirées.                                     | Dépend du modèle, des commandes de combat et idéalement des PV en direct.                             |
| 13              | P23 | Moyenne           | 🔶 **À faire**  | Préparation / Productivité | Templates de rencontre                                   | Sauvegarder des setups réutilisables : groupe de monstres, PV initiaux, règles actives et notes de préparation.                  | Dépend de P8 ou P22.                                                                                  |
| 14              | P24 | Moyenne           | 🟡 **Partiel**  | Règles                     | Gestion explicite des égalités d'initiative              | Règle optionnelle désactivée par défaut : départager les égalités par modificateur de DEX, avec joueurs à `0` pour l'instant.    | Gestion de la DEX des joueurs à confirmer ; réordonnancement manuel disponible en fallback.           |
| 15              | P25 | Basse             | 🔶 **À faire**  | UX                         | Différenciation visuelle des types d'acteurs             | Distinguer joueurs, alliés, ennemis, boss ou monstres légendaires avec des classes visuelles sobres.                             | À faire après stabilisation du rendu des cartes.                                                      |
| 16              | P26 | Basse             | 🔶 **À faire**  | UX                         | Portraits optionnels                                     | Remplacer ou compléter les initiales par des portraits optionnels avec initiales en fallback systématique.                       | Attention à ne pas alourdir la préparation d'un combat.                                               |
| 17              | P17 | Basse             | 🔶 **À faire**  | CSS / UX                   | Affichage desktop et tablette sur grands combats         | Optimiser les combats volumineux pour un écran 24 pouces 16:9, puis vérifier les écrans plus petits et tablettes.                | Mobile hors périmètre immédiat ; à valider avec les nouveaux contrôles de combat et les PV en direct. |
| 27              | P27 | Plus tard         | ⏳ **Plus tard** | Fonctionnalité             | Notes par participant                                    | Stocker des notes temporaires pour un joueur, monstre ou effet en cours.                                                         | À reconsidérer après les besoins réels de suivi de combat.                                            |
| 28              | P28 | Plus tard         | ⏳ **Plus tard** | Player-facing              | Vue joueur                                               | Afficher une vue simplifiée partageable avec les joueurs, sans détails réservés au MJ.                                           | À envisager après stabilisation de la vue MJ.                                                         |
| 29              | P29 | Plus tard         | ⏳ **Plus tard** | Player-facing              | Mode plein écran                                         | Afficher le tracker sur un écran secondaire ou une TV.                                                                           | Probablement lié à la future vue joueur.                                                              |
| 30              | P30 | Plus tard         | ⏳ **Plus tard** | Fonctionnalité avancée     | Créatures temporaires et invocations                     | Gérer les créatures invoquées ou temporaires avec un cycle de vie plus court qu'un participant classique.                        | À traiter après clarification des conditions, rounds et templates de rencontre.                       |

## État fonctionnel synthétique

| Domaine       | Fonctionnalité                                                       | Statut                | Notes                                                                                   |
|---------------|----------------------------------------------------------------------|-----------------------|-----------------------------------------------------------------------------------------|
| Participants  | Gestion séparée des joueurs et monstres                              | Fait                  | Trois panneaux distincts : monstres, joueurs, ordre du tour.                            |
| Participants  | Ajout de joueurs                                                     | Fait                  | Ajout et suppression de lignes joueurs.                                                 |
| Participants  | Création de plusieurs monstres                                       | Fait                  | Création de slots puis sélection depuis le catalogue.                                   |
| Participants  | Duplication rapide d'un monstre déjà choisi                          | Partiel               | Possible manuellement en choisissant le même monstre plusieurs fois, sans bouton dédié. |
| Monstres      | Catalogue prédéfini                                                  | Fait                  | Bestiaire généré et embarqué dans `bestiary.js`.                                        |
| Monstres      | Recherche ou filtre dans le catalogue                                | À faire               | Aucun champ de recherche ou filtre FP/type pour le moment.                              |
| Initiative    | Jet automatique pour les monstres                                    | Fait                  | d20 + modificateur issu des données monstre.                                            |
| Initiative    | Initiative joueurs                                                   | Fait                  | Saisie manuelle.                                                                        |
| Initiative    | Tri automatique par initiative                                       | Fait                  | Tri décroissant à la génération de l'ordre.                                             |
| Initiative    | Gestion configurable des égalités                                    | Partiel               | Règle optionnelle par DEX disponible, désactivée par défaut ; DEX joueurs à confirmer.  |
| Ordre du tour | Réordonnancement manuel                                              | Fait                  | Drag-and-drop souris dans les deux sens et déplacement clavier par flèches.             |
| Ordre du tour | Avancer au prochain acteur                                           | À faire               | Le statut joué existe, mais pas de commande "suivant".                                  |
| Ordre du tour | Acteur actif visible                                                 | Fait                  | Première carte non jouée mise en évidence.                                              |
| Ordre du tour | Tours terminés                                                       | Partiel               | Clic, Entrée ou Espace sur une carte pour basculer joué/non joué.                       |
| PV            | Affichage PV actuels et PV max                                       | Fait                  | Présent dans les formulaires et l'ordre du tour.                                        |
| PV            | Modification manuelle des PV                                         | Partiel               | Possible avant génération, pas encore directement dans l'ordre du tour.                 |
| PV            | Application rapide dégâts/soins                                      | À faire               | Pas encore de saisie `-7` ou `+5`.                                                      |
| PV            | Marquer inconscient ou mort                                          | À faire               | Aucun statut dédié.                                                                     |
| CA            | Affichage de la CA                                                   | Fait                  | Présent pour joueurs, monstres et ordre du tour.                                        |
| Règles        | Sélecteur de règles                                                  | Fait - en observation | Popup de règles maison dans le panneau ordre du tour.                                   |
| Règles        | Durées automatiques de conditions                                    | À faire               | Dépend de la gestion des conditions.                                                    |
| États         | Conditions visuelles                                                 | À faire               | Aucun système de conditions.                                                            |
| États         | Marqueurs concentration, réaction, inspiration, avantage/désavantage | À faire               | Aucun marqueur binaire.                                                                 |
| Affichage     | Initiales des participants                                           | Fait                  | Initiale calculée depuis le nom de l'acteur.                                            |
| Affichage     | Portraits                                                            | À faire               | Seules les initiales existent.                                                          |
| Affichage     | Couleurs par type d'acteur                                           | À faire               | Pas de distinction complète joueurs/alliés/ennemis/boss.                                |
| Persistance   | Sauvegarde locale                                                    | À faire               | Aucun `localStorage` pour la rencontre.                                                 |
| Persistance   | Import/export JSON                                                   | À faire               | Aucun format d'échange.                                                                 |
| Préparation   | Templates de rencontre                                               | À faire               | Aucun preset de rencontre sauvegardable.                                                |
| Suivi         | Journal de combat                                                    | À faire               | Aucun historique des rounds ou changements de PV.                                       |
| Suivi         | Notes par participant                                                | Plus tard             | Non nécessaire pour stabiliser le MVP.                                                  |
| Vues          | Vue joueur                                                           | Plus tard             | Aucune vue simplifiée publique.                                                         |
| Vues          | Mode plein écran                                                     | Plus tard             | Aucun mode écran secondaire/TV.                                                         |
| Invocation    | Créatures temporaires et invocations                                 | Plus tard             | Pas de modèle dédié.                                                                    |
