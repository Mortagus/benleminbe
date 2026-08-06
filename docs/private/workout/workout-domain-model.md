# Modele metier Workout

Date de redaction : 2026-08-06

## Frontiere fondamentale

Le programme decrit le **prevu** et peut evoluer. La seance reelle decrit le
**realise** et constitue un historique : elle garde un instantane independant
de ses sources. Une modification du programme, du nom d'une variation ou de
ses objectifs ne modifie donc jamais une seance deja creee.

## Concepts

| Concept | Responsabilite |
| --- | --- |
| Exercice | Famille generale de mouvement (par exemple Deadlift). |
| Variation | Forme precise, mesurable et comparable d'un exercice (par exemple Romanian Deadlift). |
| Equipement | Materiel necessaire ou utilise par une variation. |
| Groupe musculaire | Classification simple, principale ou secondaire, sans ambition anatomique exhaustive. |
| Programme | Organisation durable de plusieurs seances types et de leur calendrier. |
| Seance type | Modele ordonne d'une seance prevue dans un programme. |
| Exercice prevu | Position d'une variation dans une seance type, avec objectifs et consigne. |
| Seance reelle | Occurrence datee d'un entrainement, issue ou non d'une seance type. |
| Exercice realise | Instantane d'un exercice de la seance reelle, avec son ordre, ses objectifs et son statut. |
| Serie | Effort individuel, valide ou non, associe a un exercice realise. |
| Alternative | Relation explicite et justifiable entre deux variations ; hors MVP structurel. |
| Note | Texte libre facultatif au niveau de la seance ou de l'exercice. |
| Ressenti | Observation facultative et descriptive ; jamais un diagnostic ou une recommandation medicale. |

## Mesures et charges

Les valeurs sont stockees dans des unites canoniques afin de garantir calculs
et comparaisons fiables : charge en kilogrammes decimaux, duree en secondes,
distance en metres et repetitions en entier. L'interface peut afficher et
saisir minutes ou kilometres, puis convertit sans ambiguite.

Chaque variation declare son mode de mesure : charge + repetitions,
repetitions seules, duree, distance + duree ou charge + duree. Les champs non
pertinents restent absents plutot que remplis par des valeurs fictives.

Une charge est accompagnee de son mode : `total`, `par_haltere`, `machine` ou
`poids_du_corps`. `par_haltere` conserve la valeur saisie et le nombre
d'halteres ; aucun total implicite ne sera invente pour les statistiques. Le
poids du corps et les exercices sans charge ne sont pas convertis en charge
artificielle.

## Instantane historique

Au demarrage, la seance reelle conserve au minimum les identifiants de source
quand ils existent, les libelles affiches, la variation, le mode de mesure et
de charge, l'ordre, la consigne, les objectifs, les series initialisees et le
contexte du programme/seance type. Les relations vers le catalogue servent a
naviguer ; l'instantane est la source de lecture de l'historique.

Les entites de catalogue et de programme sont archivees, non supprimees, des
qu'elles sont referencees par une seance reelle.

## Planification du MVP

Le MVP utilise un calendrier hebdomadaire a jours fixes, avec jour de repos
explicite. C'est la resolution la plus lisible pour la page « seance du jour »
et pour les seances Musculation A, Cardio et Musculation B. Une selection
manuelle exceptionnelle est une adaptation de la seance, pas une modification
du calendrier. La rotation sequentielle et le mode hybride sont reportes ; le
mode de planification restera une propriete du programme pour ne pas les
interdire plus tard.

## Invariants

- Une variation n'est comparee qu'a elle-meme par defaut.
- Une seance en cours est reprise, jamais dupliquee silencieusement.
- Une adaptation ne modifie que la seance reelle concernee.
- Les notes et ressentis ne conditionnent jamais une validation.
- Les chiffres doivent etre positifs ou nuls selon leur signification ; une
  serie validee doit respecter le mode de mesure de sa variation.
