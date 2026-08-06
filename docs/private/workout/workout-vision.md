# Vision du module Workout

Date de redaction : 2026-08-06

## Probleme resolu

Une seance doit pouvoir etre executee et consignee sans feuille, notes
dispersees ni memoire approximative. `Workout` centralise le programme, les
valeurs realisees et l'historique, tout en distinguant explicitement ce qui
etait prevu de ce qui a ete fait.

Ce n'est ni un logiciel de coaching generaliste ni un dispositif medical.

## Utilisateur et contexte

Le premier utilisateur est le proprietaire du site, authentifie dans la zone
privee. L'usage principal se fait debout, entre deux series, sur un telephone
et souvent d'une seule main. L'administration du programme peut se faire sur
telephone ou ordinateur, mais la vitesse de saisie pendant l'effort prime.

## Parcours principaux

1. Configurer un programme hebdomadaire et ses seances types.
2. Ouvrir la seance prevue aujourd'hui et la demarrer.
3. Voir l'exercice courant, les objectifs et la derniere performance
   comparable.
4. Valider ou corriger rapidement chaque serie ; l'etat est sauvegarde apres
   chaque modification utile.
5. Terminer une seance, eventuellement incomplete, et ajouter une note ou un
   ressenti facultatif.
6. Relire l'historique puis consulter une progression par variation exacte.

## Principes UX mobile

- Les actions courantes sont visibles sans navigation profonde ni texte
  superflu.
- Les controles tactiles sont larges, accessibles et compatibles avec un
  clavier numerique adapte.
- Les valeurs proposees ne sont jamais imposees et une validation simple doit
  rester possible en quelques secondes.
- Un rafraichissement accidentel ne doit pas perdre une saisie deja
  sauvegardee ; le prototype validera aussi la persistance locale avant le
  mode connecte.
- Les notes, ressentis et raisons d'adaptation sont facultatifs.

## Limites du MVP

Le MVP exclut notamment les conseils medicaux, la generation de programme par
IA, les objets connectes, le suivi nutritionnel, les fonctions sociales et un
catalogue universel d'exercices. Les suggestions de remplacement et de
progression seront ajoutees seulement lorsque leurs regles explicables seront
stabilisees.

## Criteres generaux de reussite

- Une serie est enregistree sans casser appreciablement le rythme de la
  seance.
- Une seance reelle reste lisible et exacte apres modification ou archivage de
  son programme et de son catalogue source.
- Les performances ne sont comparees qu'entre variations compatibles.
- L'outil remplace une pratique de notes dispersees sans creer une charge
  administrative excessive.
