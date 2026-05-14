# Backlog d'audit - DnD Initiative Tracker

Date de l'audit : 2026-05-14

Périmètre audité :

- Route Symfony : `src/Public/Controller/LabController.php`
- Templates Twig : `templates/lab/dnd/*.html.twig`
- JavaScript : `assets/scripts/lab/dnd/*.js`
- CSS : `assets/styles/lab/dnd/*.css`
- Données monstres : `assets/scripts/lab/dnd/monster_classes.js` et `tools/dnd/monsters.generated.json`

Note importante : la règle actuelle `shouldSkipTurn()` qui exclut certains acteurs du tour est une règle maison volontaire. Elle n'est pas considérée comme un bug dans ce backlog. Elle peut en revanche être mentionnée comme un comportement spécifique susceptible d'être rendu configurable plus tard.

## P1 - Mettre en place un sélecteur de règles à appliquer

### Catégorie
Règles

### Constat
Le sélecteur de règles a été mis en place et déployé. Les règles maison `shouldSkipTurn()` et `getTurnCount()` sont désormais pilotables via des cases à cocher dans une popup dédiée, accessible depuis le panneau "Ordre du tour".

### Impact
Le Maître du Jeu peut activer ou désactiver les règles maison existantes sans changer le code. Le comportement reste simple et suffisamment explicite pour être testé en conditions réelles.

### Proposition
Conserver l'implémentation actuelle et la laisser en observation jusqu'aux retours des joueurs. Ajuster ensuite les libellés, descriptions ou interactions si l'usage réel montre une friction.

### Complexité estimée
Moyenne

### Statut
Fait - en observation jusqu'aux retours des joueurs

## P2 - Renforcer la validation des entrées utilisateur

### Catégorie
Sécurité / UX

### Constat
Les champs numériques reposaient surtout sur les attributs HTML (`min`, `type="number"`). Une couche de validation JavaScript a été ajoutée avec des helpers dédiés, un affichage d'erreurs par panneau et un blocage des actions risquées.

### Impact
Les erreurs de saisie sont visibles par le MJ avant correction : nombre de monstres invalide, absence d'acteur exploitable, PV incohérents ou ligne joueur commencée mais incomplète. Les actions `Créer la liste` et `Générer le tour de table` ne continuent plus lorsque les validations associées échouent.

### Proposition
Conserver cette implémentation et surveiller les retours d'usage. Les prochaines améliorations pourront porter sur les libellés, les bornes métier ou la validation en temps réel si le besoin apparaît.

### Complexité estimée
Faible

### Statut
Fait - validations, affichage d'erreurs et blocage des actions validés

## P3 - Garder une construction DOM sûre pour toutes les données affichées

### Catégorie
Sécurité

### Constat
Le rendu actuel de l'ordre de tour utilise `textContent`, ce qui est sain pour afficher les noms saisis par l'utilisateur. Les derniers usages de `innerHTML` dans le module DnD ont été supprimés de `monsters.js`.

### Impact
Le module évite désormais les vidages de DOM via chaînes HTML et s'appuie sur des API DOM explicites. Cela réduit le risque de réintroduire une injection HTML lors des prochaines évolutions.

### Proposition
Conserver cette règle locale : utiliser `textContent`, `replaceChildren()`, `createElement()` et les templates DOM pour les données dynamiques ; éviter `innerHTML` sauf cas strictement contrôlé et documenté.

### Complexité estimée
Faible

### Statut
Fait

## P4 - Stabiliser l'extraction des données joueurs

### Catégorie
JavaScript

### Constat
`getPlayerActors()` et la validation des joueurs ne dépendent plus de la position des champs dans le DOM. Les inputs joueurs disposent désormais d'attributs `data-player-field` explicites.

### Impact
Une modification visuelle du formulaire joueur est moins susceptible de casser silencieusement la lecture de la CA, des PV ou de l'initiative. Les sélecteurs utilisés par le parsing et la validation sont maintenant stables.

### Proposition
Conserver les attributs `data-player-field` comme contrat entre le template joueur, `players.js` et `validation.js`. Toute future évolution du formulaire devra maintenir ces identifiants ou les mettre à jour explicitement.

### Complexité estimée
Faible

### Statut
Fait

## P5 - Découper l'orchestration de `dnd_initiative.js`

### Catégorie
Architecture / JavaScript

### Constat
`dnd_initiative.js` récupère directement tous les noeuds DOM principaux et branche les actions de haut niveau. Les modules spécialisés reçoivent ensuite certains éléments DOM ou en recherchent d'autres eux-mêmes.

### Impact
L'initialisation est lisible aujourd'hui, mais les dépendances DOM sont dispersées. Si l'outil grandit, il deviendra plus difficile de savoir quel module possède quelle responsabilité.

### Proposition
Créer une initialisation plus explicite par panneau : monstres, joueurs, ordre du tour. Chaque panneau pourrait exposer une API simple, sans imposer de framework et en restant en JavaScript vanilla.

### Complexité estimée
Moyenne

### Statut
À faire

## P6 - Réduire la duplication du template joueur

### Catégorie
Twig

### Constat
Le premier joueur rendu dans `#playerList` et le contenu du template `#playerItemTemplate` dupliquent quasiment le même HTML.

### Impact
Chaque évolution du formulaire joueur doit être reportée à deux endroits. C'est une source classique d'oubli et de divergence entre le premier joueur et les joueurs ajoutés dynamiquement.

### Proposition
Factoriser le markup d'un joueur dans un partial Twig dédié, ou rendre la liste initiale à partir du même fragment que le template. Garder le changement local au panneau joueurs.

### Complexité estimée
Faible

### Statut
À faire

## P7 - Renforcer l'accessibilité des formulaires dynamiques

### Catégorie
Accessibilité / Twig

### Constat
Les labels des champs joueurs n'ont pas de relation `for`/`id`, et les champs générés dans les templates dynamiques n'ont pas d'identifiants uniques. Le select monstre est aussi créé sans label explicite par ligne.

### Impact
Les formulaires restent visuellement compréhensibles, mais les technologies d'assistance auront moins de contexte. Cela peut aussi compliquer les tests end-to-end basés sur des noms accessibles.

### Proposition
Ajouter des noms accessibles explicites aux champs dynamiques, par exemple via `aria-label` ou des identifiants générés, sans alourdir visuellement l'interface.

### Complexité estimée
Faible

### Statut
À faire

## P8 - Ajouter des retours d'état utiles au Maître du Jeu

### Catégorie
UX

### Constat
L'outil permet de créer les monstres, lancer l'initiative et générer l'ordre de tour, mais il donne peu de retours sur les étapes restantes ou les actions impossibles. Par exemple, un ordre vide affiche seulement le placeholder général.

### Impact
Pendant une partie, le MJ doit comprendre rapidement pourquoi un bouton est désactivé, pourquoi un acteur n'apparaît pas, ou quelle action est attendue ensuite.

### Proposition
Ajouter des messages courts et contextuels : aucun monstre sélectionné, initiatives de monstres non lancées, joueur incomplet, ordre généré sans acteur éligible, acteur marqué comme joué. Ces messages doivent rester opérationnels et ne pas transformer l'outil en page explicative.

### Complexité estimée
Faible

### Statut
À faire

## P9 - Améliorer le pilotage du tour de combat

### Catégorie
Fonctionnalité / UX

### Constat
Un clic sur une carte de l'ordre du tour bascule son statut joué/non joué. Il n'y a pas encore de commandes explicites pour avancer au prochain acteur, démarrer un nouveau round, réinitialiser les tours joués ou remettre le combat à zéro.

### Impact
Le suivi reste utilisable pour un prototype, mais il demande plus d'attention manuelle au MJ et peut devenir confus pendant un combat long.

### Proposition
Ajouter des actions explicites et petites : acteur suivant, nouveau round, réinitialiser les tours joués, réinitialiser la rencontre. Les actions destructrices devraient demander une confirmation légère ou être facilement annulables.

### Complexité estimée
Moyenne

### Statut
À faire

## P10 - Clarifier l'état applicatif JavaScript

### Catégorie
Architecture / JavaScript

### Constat
L'état des monstres est conservé dans une variable de module `monsters`, et l'ordre de tour dans une variable de module `roundOrder`. Le DOM est parfois la source de vérité temporaire, par exemple pour synchroniser les PV des monstres avant génération.

### Impact
Cette organisation reste acceptable pour un prototype, mais elle complique les évolutions comme la sauvegarde, l'annulation, la reprise de combat, les tests unitaires et la synchronisation entre panneaux.

### Proposition
Introduire progressivement un modèle de rencontre explicite, manipulé par petites fonctions pures quand c'est possible. Ne pas refondre toute l'interface : commencer par isoler les données acteurs, les PV et l'ordre de tour. Ce point est volontairement placé après le découpage de l'orchestration pour limiter le risque de refactor trop large.

### Complexité estimée
Moyenne

### Statut
À faire

## P11 - Rendre le drag-and-drop accessible autrement qu'à la souris

### Catégorie
Accessibilité / UX

### Constat
Le réordonnancement de l'ordre du tour repose sur `draggable` et des événements de souris. Les cartes sont cliquables, mais ne sont pas exposées comme boutons et ne proposent pas de réordonnancement clavier.

### Impact
L'usage clavier, lecteur d'écran ou tactile peut être limité. Sur mobile, le drag-and-drop HTML natif est souvent moins prévisible.

### Proposition
Ajouter des contrôles alternatifs pour monter/descendre un acteur, rendre les cartes focusables si elles restent interactives, et annoncer clairement l'acteur actif avec des attributs ARIA adaptés.

### Complexité estimée
Moyenne

### Statut
À faire

## P12 - Vérifier le responsive sur les grands combats

### Catégorie
CSS / UX

### Constat
La grille principale passe bien en une colonne sous `960px`, et les lignes joueurs/monstres se simplifient sous `768px`. L'ordre du tour est une liste horizontale scrollable avec des cartes de largeur fixe.

### Impact
Pour quelques acteurs, le rendu est simple. Pour un combat avec beaucoup de monstres ou sur mobile, le scroll horizontal peut rendre le suivi moins efficace et masquer l'acteur actif.

### Proposition
Tester des cas de combat volumineux et ajuster le layout si besoin : densité des cartes, maintien de l'acteur actif visible, meilleure lisibilité des PV/CA, et contrôles tactiles plus faciles à atteindre.

### Complexité estimée
Moyenne

### Statut
À faire

## P13 - Harmoniser les textes affichés et les libellés de jeu

### Catégorie
Textes

### Constat
Les textes sont compréhensibles, mais certains libellés sont abrégés (`Init.`, `rest.`, `max`) et certains termes alternent entre "joueur", "personnage joueur", "tour de table" et "ordre du tour".

### Impact
Les abréviations sont pratiques pour un usage expert, mais peuvent créer une petite friction lors d'une reprise de l'outil ou d'un partage avec d'autres MJ.

### Proposition
Définir un vocabulaire court et cohérent pour l'outil : acteur, joueur, monstre, initiative, ordre du tour, PV actuels, PV max. Ajuster les placeholders et titres sans augmenter la densité textuelle.

### Complexité estimée
Faible

### Statut
À faire

## P14 - Ajouter une sauvegarde locale de rencontre

### Catégorie
Fonctionnalité

### Constat
La rencontre vit uniquement en mémoire dans l'onglet courant. Un rechargement de page perd les monstres, joueurs, PV et ordre du tour.

### Impact
Une fausse manipulation, un refresh ou une fermeture d'onglet peut interrompre le suivi d'un combat. Pour un MJ, la reprise rapide est une fonctionnalité très utile.

### Proposition
Ajouter une persistance locale simple pour une rencontre en cours, avec possibilité de restaurer ou d'effacer la sauvegarde. Commencer par `localStorage` avant d'envisager une persistance serveur.

### Complexité estimée
Moyenne

### Statut
À faire

## P15 - Ajouter des tests sur les règles et la génération d'ordre

### Catégorie
JavaScript / Règles

### Constat
Aucun test automatisé dédié au JavaScript du tracker n'a été trouvé. Les fonctions de `initiative.js` sont petites, mais les comportements de `buildRoundOrder()` combinent filtrage, duplication de tours et tri.

### Impact
Les prochaines évolutions de règles peuvent introduire des régressions difficiles à détecter manuellement, surtout si plusieurs variantes de règles sont ajoutées plus tard.

### Proposition
Commencer par des tests unitaires très ciblés sur les fonctions de règles et la construction de l'ordre du tour. Garder une stack de test légère et compatible avec le JavaScript vanilla du projet.

### Complexité estimée
Moyenne

### Statut
À faire

## P16 - Documenter et alléger le catalogue de monstres

### Catégorie
Architecture

### Constat
Le catalogue embarqué dans `monster_classes.js` pèse environ 418 Ko et provient d'un pipeline d'extraction dans `tools/dnd/`. Les données générées et les scripts d'extraction coexistent sans documentation courte dans le module DnD.

### Impact
La taille reste acceptable pour un lab, mais la mise à jour du catalogue et la compréhension de la source des données demandent de fouiller le dépôt.

### Proposition
Documenter la commande ou le flux de génération des données monstres. Plus tard, envisager un chargement conditionnel ou une source JSON séparée si le catalogue grossit.

### Complexité estimée
Faible

### Statut
À faire

## P17 - Ajouter des aides de sélection pour les monstres

### Catégorie
Fonctionnalité / UX

### Constat
Chaque ligne monstre propose un select complet. Le catalogue est riche, mais il n'y a pas encore de recherche, filtre par type, filtre par FP, favoris ou presets de rencontre.

### Impact
La préparation d'une rencontre devient plus lente dès que le catalogue grandit ou que plusieurs monstres différents doivent être ajoutés.

### Proposition
Ajouter progressivement une recherche simple, puis des filtres ou presets si l'usage le justifie. Prioriser les aides qui réduisent la saisie pendant une session de jeu.

### Complexité estimée
Moyenne

### Statut
À faire

## P18 - Prévoir l'import/export d'une rencontre

### Catégorie
Fonctionnalité

### Constat
L'outil ne propose pas encore d'import/export des joueurs, monstres, PV, initiatives ou ordre courant.

### Impact
Le MJ ne peut pas préparer une rencontre ailleurs, partager un état de combat, ni archiver une scène terminée.

### Proposition
Prévoir un export JSON lisible et un import correspondant, après clarification du modèle de rencontre. Cette amélioration doit venir après la stabilisation de l'état applicatif.

### Complexité estimée
Moyenne

### Statut
À faire
