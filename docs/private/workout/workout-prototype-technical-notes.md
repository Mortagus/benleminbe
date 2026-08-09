# Notes techniques — prototype mobile Workout

Date de mise a jour : 2026-08-06

## Role et limites

Cette interface valide le rythme de saisie d'une seance mobile. Elle n'est ni
le modele metier final ni une base de code a recopier telle quelle pour les
entites et ecrans persistants du MVP.

Elle utilise des exercices et objectifs statiques, `localStorage` comme unique
stockage et une route de prototype privee. Elle ne gere ni catalogue,
authentification de donnees, reseau, synchronisation, validation metier
complete, reprise multi-appareils ni historique serveur.

## Cause du triple increment

Un double-clic normal emet deux evenements `click`, puis un `dblclick`. Le
stepper n'utilise volontairement que les deux `click` : le `dblclick`, les
evenements tactiles et les evenements pointeur ne portent aucune action metier.

La fragilite corrigee etait le cycle de vie : une nouvelle initialisation pouvait
attacher un second listener a la racine existante. Chaque listener actif pouvait
alors appliquer la meme mutation. Le prototype annule maintenant le cycle
precedent avec un `AbortController` conserve dans un `WeakMap` par racine avant
d'attacher le listener delegue suivant.

Les essais Playwright confirment qu'un clic donne un pas, deux clics rapides
donnent deux pas, y compris apres deux nouvelles initialisations du composant.

## Responsabilites du code

`assets/scripts/private/workout/prototype.js` contient uniquement :

- le contrat DOM et sa validation explicite ;
- un petit etat local versionne, indexe par identifiants stables ;
- le rendu cible du prototype ;
- les mutations issues du clavier ou du clic ;
- la persistance locale planifiee ;
- le cycle de vie du composant.

`prototype-debug.js` est isole du flux principal. Il ne s'active qu'avec
`?workoutDebug=1` et journalise les evenements utiles ainsi que les ecritures
de stockage impossibles. Il reste temporaire tant que le prototype est teste.

## Snapshot local

Le snapshot porte `version: 2`, `activeExerciseId`, puis les exercices, series
et champs de chaque serie par identifiant stable. Un snapshot absent, invalide ou d'une version inconnue
est ignore au profit de l'etat initial ; aucune migration locale n'est justifiee
pour ce prototype. Si le stockage est indisponible, l'interface reste utilisable
pour la page courante, affiche un message sobre et emet un diagnostic seulement
en mode debug.

## Points a ne pas reutiliser tels quels

- les identifiants statiques, objectifs et valeurs de la seance de test ;
- la forme exacte du snapshot `localStorage` ;
- le statut de sauvegarde local comme garantie de persistance metier ;
- le rendu DOM direct comme implementation de l'historique reel.

Le futur module devra remplacer ces elements par le catalogue, les seances
reelles, les validations et la persistance Doctrine appropries.
