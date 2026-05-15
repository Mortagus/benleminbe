# Backlog unifié - DnD Initiative Tracker

Date de mise à jour : 2026-05-15

Ce document remplace l'ancien backlog d'audit et la roadmap avancée. Il conserve les tâches déjà réalisées, aligne les fonctionnalités de la roadmap avec l'état réel du projet et liste les évolutions restantes.

Document descriptif associé : [dnd-initiative-audit.md](/var/www/projects/benleminbe/docs/lab/dnd-initiative-audit.md:1)

## Légende

- `Fait` : fonctionnalité ou correction présente dans le code.
- `Fait - en observation` : présent dans le code, à ajuster après usage réel.
- `Partiel` : une base existe, mais la fonctionnalité attendue n'est pas complète.
- `À faire` : non implémenté ou à reprendre.
- `Plus tard` : utile, mais non prioritaire pour stabiliser le tracker.

## État fonctionnel synthétique

| Domaine       | Fonctionnalité                                                       | Statut                | Notes                                                                                   |
|---------------|----------------------------------------------------------------------|-----------------------|-----------------------------------------------------------------------------------------|
| Participants  | Gestion séparée des joueurs et monstres                              | Fait                  | Trois panneaux distincts : monstres, joueurs, ordre du tour.                            |
| Participants  | Ajout de joueurs                                                     | Fait                  | Ajout et suppression de lignes joueurs.                                                 |
| Participants  | Création de plusieurs monstres                                       | Fait                  | Création de slots puis sélection depuis le catalogue.                                   |
| Participants  | Duplication rapide d'un monstre déjà choisi                          | Partiel               | Possible manuellement en choisissant le même monstre plusieurs fois, sans bouton dédié. |
| Monstres      | Catalogue prédéfini                                                  | Fait                  | Catalogue embarqué dans `monster_classes.js`.                                           |
| Monstres      | Recherche ou filtre dans le catalogue                                | À faire               | Aucun champ de recherche ou filtre FP/type pour le moment.                              |
| Initiative    | Jet automatique pour les monstres                                    | Fait                  | d20 + modificateur issu des données monstre.                                            |
| Initiative    | Initiative joueurs                                                   | Fait                  | Saisie manuelle.                                                                        |
| Initiative    | Tri automatique par initiative                                       | Fait                  | Tri décroissant à la génération de l'ordre.                                             |
| Initiative    | Gestion configurable des égalités                                    | À faire               | Aucun arbitrage dédié au-delà de l'ordre produit par le tri actuel.                     |
| Ordre du tour | Réordonnancement manuel                                              | Partiel               | Drag-and-drop souris sur les cartes uniquement de droite à gauche.                      |
| Ordre du tour | Avancer au prochain acteur                                           | À faire               | Le statut joué existe, mais pas de commande "suivant".                                  |
| Ordre du tour | Acteur actif visible                                                 | Fait                  | Première carte non jouée mise en évidence.                                              |
| Ordre du tour | Tours terminés                                                       | Partiel               | Clic sur une carte pour basculer joué/non joué. Pas encore d'indication de tour.        |
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

## Réalisé

### P1 - Mettre en place un sélecteur de règles à appliquer

Catégorie : Règles

Statut : Fait - en observation

Le sélecteur de règles est en place. Les règles maison `shouldSkipTurn()` et `getTurnCount()` sont pilotables via des cases à cocher dans une popup dédiée, accessible depuis le panneau "Ordre du tour".

Impact : le Maître du Jeu peut activer ou désactiver les règles maison existantes sans changer le code. Le comportement reste simple et suffisamment explicite pour être testé en conditions réelles.

À surveiller : ajuster les libellés, descriptions ou interactions si l'usage réel montre une friction.

### P2 - Renforcer la validation des entrées utilisateur

Catégorie : Sécurité / UX

Statut : Fait

Une couche de validation JavaScript existe avec des helpers dédiés, un affichage d'erreurs par panneau et un blocage des actions risquées.

La validation couvre notamment :

- nombre de monstres invalide ;
- absence d'acteur exploitable ;
- PV incohérents ;
- ligne joueur commencée mais incomplète.

Impact : les actions `Créer la liste` et `Générer le tour de table` ne continuent plus lorsque les validations associées échouent.

### P3 - Garder une construction DOM sûre pour toutes les données affichées

Catégorie : Sécurité

Statut : Fait

Le rendu de l'ordre du tour utilise `textContent` pour les données dynamiques, notamment les noms saisis par l'utilisateur. Les usages risqués de `innerHTML` dans le module DnD ont été supprimés.

Règle locale à conserver : utiliser `textContent`, `replaceChildren()`, `createElement()` et les templates DOM pour les données dynamiques ; éviter `innerHTML` sauf cas strictement contrôlé et documenté.

### P4 - Stabiliser l'extraction des données joueurs

Catégorie : JavaScript

Statut : Fait

`getPlayerActors()` et la validation des joueurs ne dépendent plus de la position des champs dans le DOM. Les inputs joueurs disposent d'attributs `data-player-field` explicites.

Impact : une modification visuelle du formulaire joueur est moins susceptible de casser silencieusement la lecture de la CA, des PV ou de l'initiative.

Contrat à conserver : toute évolution du formulaire joueur doit maintenir les identifiants `data-player-field` ou mettre à jour explicitement `players.js` et `validation.js`.

### P5 - Couvrir le noyau du MVP de suivi d'initiative

Catégorie : Fonctionnalité

Statut : Fait

Le tracker couvre désormais le socle fonctionnel minimal :

- ajout de participants joueurs ;
- création de slots monstres ;
- sélection de monstres depuis un bestiaire embarqué ;
- stockage des statistiques communes utiles au tour : nom, type, CA, PV, initiative ;
- jet automatique d'initiative pour les monstres ;
- tri automatique de l'ordre du tour ;
- réordonnancement manuel par drag-and-drop ;
- suivi des tours joués ;
- mise en évidence du prochain acteur actif ;
- affichage d'initiales pour identifier rapidement les acteurs.

Limite : ce socle reste en mémoire uniquement et ne couvre pas encore les commandes de round, la sauvegarde ou la modification des PV directement depuis l'ordre du tour.

### P6 - Introduire un modèle de rencontre explicite et clarifier l'orchestration JavaScript

Catégorie : Architecture / JavaScript

Statut : Fait - deuxième passe

Constat initial : l'état des monstres était conservé dans une variable de module `monsters`, l'ordre de tour dans `roundOrder`, les règles actives dans `rules.js`, et le DOM restait une source de vérité temporaire pour certains champs. En parallèle, `dnd_initiative.js` récupérait directement les noeuds DOM principaux et branchait les actions de haut niveau, ce qui dispersait les responsabilités entre orchestration, lecture DOM et logique métier.

Impact : cette organisation reste acceptable pour un prototype, mais elle complique déjà la sauvegarde, l'annulation, la reprise de combat, les tests unitaires, la synchronisation entre panneaux et les prochaines fonctionnalités de combat. Découper l'orchestration sans clarifier l'état déplacerait seulement la complexité ; clarifier l'état sans revoir l'orchestration laisserait un modèle difficile à exploiter.

Implémentation actuelle : `encounter-state.js` centralise le modèle de rencontre et les mutations métier. Les modules `monsters.js`, `players.js`, `rules.js` et `turn-order.js` exposent maintenant des initialisations de panneau ou des adaptateurs DOM qui possèdent leurs éléments, valident leurs entrées locales et remontent les interactions vers le modèle.

Le modèle représente actuellement :

- les joueurs ;
- les monstres ;
- les règles actives ;
- l'ordre du tour ;
- le round courant ;
- l'acteur actif ;
- les statuts joué/non joué ;
- les PV actuels et max.

Reste à surveiller : le formulaire joueur sert encore de buffer DOM éditable, même si les joueurs sont synchronisés dans le modèle. Les prochaines fonctionnalités devront continuer à s'appuyer sur les APIs de panneau et sur `encounter-state.js` plutôt que réintroduire des états métier locaux dans les modules de rendu.

Objectif : préparer proprement les commandes de round, la sauvegarde locale, la modification des PV pendant le combat, les tests unitaires et l'import/export.

Complexité estimée : Moyenne à élevée

## Priorité haute

### P7 - Ajouter des commandes explicites de pilotage du combat

Catégorie : Fonctionnalité / UX

Statut : À faire

Constat : un clic sur une carte de l'ordre du tour bascule son statut joué/non joué. Il n'y a pas encore de commandes explicites pour avancer au prochain acteur, démarrer un nouveau round, réinitialiser les tours joués ou remettre le combat à zéro.

Impact : le suivi reste utilisable pour un prototype, mais il demande plus d'attention manuelle au MJ et peut devenir confus pendant un combat long.

Proposition :

- ajouter une action "acteur suivant" ;
- ajouter une action "nouveau round" ;
- ajouter une action "réinitialiser les tours joués" ;
- ajouter une action "réinitialiser la rencontre" ;
- prévoir une confirmation légère ou une annulation pour les actions destructrices.

Complexité estimée : Moyenne

### P8 - Ajouter une sauvegarde locale de rencontre

Catégorie : Fonctionnalité

Statut : À faire

Constat : la rencontre vit uniquement en mémoire dans l'onglet courant. Un rechargement de page perd les monstres, joueurs, PV, règles actives et ordre du tour.

Impact : une fausse manipulation, un refresh ou une fermeture d'onglet peut interrompre le suivi d'un combat.

Proposition : ajouter une persistance locale simple via `localStorage`, avec restauration et effacement de la sauvegarde. Le format devra s'appuyer sur le futur modèle de rencontre explicite.

Complexité estimée : Moyenne

### P9 - Ajouter des tests sur les règles et la génération d'ordre

Catégorie : JavaScript / Règles

Statut : À faire

Constat : aucun test automatisé dédié au JavaScript du tracker n'a été trouvé. Les comportements de règles combinent filtrage, duplication de tours, tri et état joué/non joué.

Impact : les prochaines évolutions peuvent introduire des régressions difficiles à détecter manuellement, surtout si plusieurs variantes de règles sont ajoutées.

Proposition : commencer par des tests unitaires ciblés sur les fonctions de règles et la construction de l'ordre du tour, avec une stack légère compatible avec le JavaScript vanilla du projet.

Complexité estimée : Moyenne

### P10 - Permettre la modification directe des PV pendant le combat

Catégorie : Fonctionnalité / UX

Statut : À faire

Constat : les PV sont saisis dans les panneaux joueurs et monstres avant la génération. L'ordre du tour affiche les PV mais ne permet pas encore d'appliquer directement des dégâts ou soins.

Impact : pendant un combat réel, le MJ doit anticiper ou régénérer l'ordre au lieu de gérer les PV au fil de l'eau.

Proposition :

- rendre les PV modifiables depuis l'ordre du tour ;
- supporter une saisie rapide de dégâts et soins, par exemple `-7` ou `+5` ;
- empêcher les valeurs incohérentes ou les signaler clairement ;
- préparer les statuts automatiques inconscient/mort sans les imposer immédiatement.

Complexité estimée : Moyenne

## Priorité moyenne

### P11 - Réduire la duplication du template joueur

Catégorie : Twig

Statut : Fait

Constat initial : le premier joueur rendu dans `#playerList` et le contenu du template `#playerItemTemplate` dupliquaient quasiment le même HTML.

Implémentation : le markup d'une ligne joueur est maintenant factorisé dans un partial Twig unique, utilisé à la fois pour la première ligne visible et pour le template de clonage dynamique.

Impact : une évolution du formulaire joueur ne doit plus être reportée à deux endroits.

Complexité estimée : Faible

### P12 - Renforcer l'accessibilité des formulaires dynamiques

Catégorie : Accessibilité / Twig

Statut : Fait

Constat initial : les labels des champs joueurs n'avaient pas de relation `for`/`id`, et les champs générés dans les templates dynamiques n'avaient pas d'identifiants uniques. Le select monstre était aussi créé sans label explicite par ligne.

Implémentation : les lignes joueurs reçoivent des identifiants recalculés côté JavaScript, les labels visibles sont reliés aux champs simples, les champs PV disposent d'un nom accessible explicite, les boutons de suppression sont contextualisés par numéro de joueur, et les selects/PV monstres dynamiques ont des `aria-label` par ligne.

Impact : les technologies d'assistance disposent d'un contexte plus précis sans alourdir visuellement l'interface.

Complexité estimée : Faible

### P13 - Rendre le drag-and-drop accessible autrement qu'à la souris

Catégorie : Accessibilité / UX

Statut : Fait

Constat initial : le réordonnancement de l'ordre du tour reposait sur `draggable` et des événements de souris. Les cartes étaient cliquables, mais ne proposaient pas de réordonnancement clavier.

Implémentation actuelle : le drag-and-drop souris permet désormais de déplacer une carte vers la gauche ou vers la droite. Dès qu'une carte cible affiche l'état visuel de drop, relâcher la souris déplace la carte glissée dans cette direction : après la cible pour un déplacement vers la droite, avant la cible pour un déplacement vers la gauche.

Le pilotage clavier est aussi disponible : après génération, la première carte reçoit le focus ; `Entrée` ou `Espace` bascule le statut joué/non joué ; `Flèche gauche` et `Flèche droite` déplacent la carte sélectionnée. Les boutons de déplacement restent disponibles comme raccourcis souris discrets, mais sont retirés de l'enchaînement `Tab` pour garder une navigation de carte en carte. Une aide clavier repliable est disponible depuis le panneau.

Impact : l'ordre du tour peut maintenant être manipulé à la souris ou au clavier, avec un retour exploitable par les technologies d'assistance.

Reste à surveiller : le drag-and-drop HTML natif peut rester moins prévisible sur mobile. Le pilotage clavier offre déjà une alternative fiable pour les environnements où le drag est fragile.

Complexité estimée : Moyenne

### P14 - Ajouter des retours d'état utiles au Maître du Jeu

Catégorie : UX

Statut : À faire

Constat : l'outil permet de créer les monstres, lancer l'initiative et générer l'ordre de tour, mais il donne peu de retours sur les étapes restantes ou les actions impossibles.

Impact : pendant une partie, le MJ doit comprendre rapidement pourquoi un bouton est désactivé, pourquoi un acteur n'apparaît pas, ou quelle action est attendue ensuite.

Proposition : ajouter des messages courts et contextuels : aucun monstre sélectionné, initiatives de monstres non lancées, joueur incomplet, ordre généré sans acteur éligible, acteur marqué comme joué.

Complexité estimée : Faible

### P15 - Ajouter des aides de sélection pour les monstres

Catégorie : Fonctionnalité / UX

Statut : À faire

Constat : chaque ligne monstre propose un select complet. Le catalogue est riche, mais il n'y a pas encore de recherche, filtre par type, filtre par FP, favoris ou presets de rencontre.

Impact : la préparation d'une rencontre devient plus lente dès que le catalogue grandit ou que plusieurs monstres différents doivent être ajoutés.

Proposition :

- ajouter une recherche simple par nom ;
- ajouter ensuite des filtres par type, taille ou facteur de puissance si l'usage le justifie ;
- prévoir des favoris ou presets de groupes de monstres après stabilisation de la recherche.

Complexité estimée : Moyenne

### P16 - Documenter et alléger le catalogue de monstres

Catégorie : Architecture

Statut : À faire

Constat : le catalogue embarqué dans `monster_classes.js` provient d'un pipeline d'extraction dans `tools/dnd/`. Les données générées et les scripts d'extraction coexistent sans documentation courte dans le module DnD.

Impact : la taille reste acceptable pour un lab, mais la mise à jour du catalogue et la compréhension de la source des données demandent de fouiller le dépôt.

Proposition : documenter la commande ou le flux de génération des données monstres. Plus tard, envisager un chargement conditionnel ou une source JSON séparée si le catalogue grossit.

Complexité estimée : Faible

### P17 - Vérifier le responsive sur les grands combats

Catégorie : CSS / UX

Statut : À faire

Constat : la grille principale passe en une colonne sous `960px`, les lignes joueurs/monstres se simplifient sous `768px`, et l'ordre du tour est une liste horizontale scrollable.

Impact : pour quelques acteurs, le rendu est simple. Pour un combat avec beaucoup de monstres ou sur mobile, le scroll horizontal peut rendre le suivi moins efficace et masquer l'acteur actif.

Proposition : tester des cas de combat volumineux et ajuster le layout si besoin : densité des cartes, maintien de l'acteur actif visible, lisibilité des PV/CA et contrôles tactiles.

Complexité estimée : Moyenne

### P18 - Harmoniser les textes affichés et les libellés de jeu

Catégorie : Textes

Statut : À faire

Constat : certains libellés sont abrégés (`Init.`, `rest.`, `max`) et certains termes alternent entre "joueur", "personnage joueur", "tour de table" et "ordre du tour".

Impact : les abréviations sont pratiques pour un usage expert, mais peuvent créer une friction lors d'une reprise de l'outil ou d'un partage avec d'autres MJ.

Proposition : définir un vocabulaire court et cohérent : acteur, joueur, monstre, initiative, ordre du tour, PV actuels, PV max. Ajuster les placeholders et titres sans augmenter la densité textuelle.

Complexité estimée : Faible

## Priorité basse

### P19 - Ajouter la gestion des conditions

Catégorie : Fonctionnalité / Règles

Statut : À faire

Objectif : ajouter et retirer des conditions sur un participant, les afficher visuellement et suivre leur durée en rounds.

Pré-requis conseillé : modèle de rencontre explicite, commandes de round et acteur actif fiable.

Complexité estimée : Moyenne

### P20 - Ajouter des marqueurs binaires de combat

Catégorie : Fonctionnalité

Statut : À faire

Objectif : suivre des états simples comme concentration, réaction utilisée, inspiration, avantage et désavantage.

Proposition : commencer avec des toggles visuels par participant, sans automatisme de règles au départ.

Complexité estimée : Moyenne

### P21 - Ajouter un journal de combat

Catégorie : Fonctionnalité

Statut : À faire

Objectif : enregistrer les événements importants : début de round, changement de PV, acteur joué, conditions ajoutées ou retirées.

Pré-requis conseillé : modèle de rencontre explicite et commandes de combat.

Complexité estimée : Moyenne à élevée

### P22 - Prévoir l'import/export d'une rencontre

Catégorie : Fonctionnalité

Statut : À faire

Constat : l'outil ne propose pas encore d'import/export des joueurs, monstres, PV, initiatives ou ordre courant.

Impact : le MJ ne peut pas préparer une rencontre ailleurs, partager un état de combat, ni archiver une scène terminée.

Proposition : prévoir un export JSON lisible et un import correspondant, après clarification du modèle de rencontre.

Complexité estimée : Moyenne

### P23 - Ajouter des templates de rencontre

Catégorie : Préparation / Productivité

Statut : À faire

Objectif : sauvegarder des setups réutilisables de combat : groupe de monstres, PV initiaux, règles actives et éventuellement notes de préparation.

Pré-requis conseillé : sauvegarde locale ou import/export JSON.

Complexité estimée : Moyenne

### P24 - Gérer les égalités d'initiative

Catégorie : Règles

Statut : À faire

Objectif : rendre le traitement des égalités explicite et configurable.

Pistes possibles :

- conserver l'ordre de saisie ;
- donner priorité aux joueurs ;
- départager par modificateur de dextérité ;
- laisser le MJ réordonner manuellement après génération.

Complexité estimée : Faible à moyenne

### P25 - Ajouter une différenciation visuelle des types d'acteurs

Catégorie : UX

Statut : À faire

Objectif : distinguer plus clairement joueurs, alliés, ennemis, boss ou monstres légendaires.

Proposition : commencer avec des classes visuelles sobres, puis étendre seulement si le besoin apparaît en partie.

Complexité estimée : Faible

### P26 - Ajouter des portraits optionnels

Catégorie : UX

Statut : À faire

Objectif : remplacer ou compléter les initiales par des portraits optionnels.

Proposition : garder les initiales comme fallback systématique.

Complexité estimée : Moyenne

## Plus tard

### P27 - Ajouter des notes par participant

Catégorie : Fonctionnalité

Statut : Plus tard

Objectif : stocker des notes temporaires pour un joueur, monstre ou effet en cours.

### P28 - Ajouter une vue joueur

Catégorie : Player-facing

Statut : Plus tard

Objectif : afficher une vue simplifiée partageable avec les joueurs, sans détails réservés au MJ.

### P29 - Ajouter un mode plein écran

Catégorie : Player-facing

Statut : Plus tard

Objectif : afficher l'initiative tracker sur un écran secondaire ou une TV.

### P30 - Gérer les créatures temporaires et invocations

Catégorie : Fonctionnalité avancée

Statut : Plus tard

Objectif : gérer les créatures invoquées ou temporaires avec un cycle de vie plus court qu'un participant classique.
