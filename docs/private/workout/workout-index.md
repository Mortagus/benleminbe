# Workout

Le module prive `Workout` est l'outil personnel de suivi de musculation et de
cardio de `benlemin.be`. Il est protege par la meme authentification que le
reste de la zone privee.

## Documents de reference

- [Vision du module](workout-vision.md) : intention produit, parcours et limites.
- [Modele metier](workout-domain-model.md) : vocabulaire, responsabilites et
  separation entre programme et historique.
- [Specification MVP](workout-mvp-specification.md) : perimetre, ecrans,
  regles et criteres d'acceptation du premier produit utilisable.
- Les regles de progression et de remplacement seront ajoutees ici lorsqu'elles
  seront stabilisees ; elles ne font pas partie du premier cycle d'implementation.
- [Backlog actif](../../en-cours/muscu-module-backlog.md) : lots, suivi et
  evolutions non stabilisees.

## Etat

Le lot 0 est en cours : le cadrage fonctionnel est documente, sans entite,
migration ni interface applicative. La prochaine etape est le prototype mobile
statique du mode seance (lot 1), avec quelques exercices representatifs.

## Position dans la zone privee

Le module est independant de `Network` et de `Music`. Il reutilisera seulement
le socle Symfony, Doctrine, la securite et les conventions de la zone privee.
