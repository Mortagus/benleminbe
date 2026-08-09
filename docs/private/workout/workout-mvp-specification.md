# Specification MVP Workout

Date de redaction : 2026-08-06

## Perimetre

Le MVP permet de gerer un petit catalogue personnel, un programme hebdomadaire
compose de seances types, puis d'executer et conserver une seance reelle. Il
inclut un historique et une premiere visualisation de progression par variation
exacte. Les alternatives et recommandations restent hors de ce premier cycle.

## Ecrans

| Ecran | Role |
| --- | --- |
| Tableau de bord Workout | Ouvrir la seance du jour, reprendre une seance en cours et acceder a l'historique. |
| Catalogue | Creer, modifier, rechercher et archiver exercices et variations utilises. |
| Programme et seance type | Composer Musculation A, Cardio et Musculation B, definir ordre et objectifs. |
| Seance du jour | Afficher la seance planifiee, sa derniere occurrence comparable et l'action de demarrage. |
| Mode seance | Afficher l'exercice courant, ses series et les actions de saisie rapides. |
| Historique | Retrouver les seances, ouvrir leur detail et constater prevu versus realise. |
| Progression | Montrer les donnees et une courbe pertinente pour une variation. |

## Regles de fonctionnement

1. Le demarrage cree une seance reelle et son instantane ; une nouvelle demande
   pour une seance deja en cours renvoie vers celle-ci.
2. Chaque mutation utile (valider ou corriger une serie, changer son statut,
   terminer un exercice ou ajouter une note) est enregistree sans bouton global
   « sauvegarder ».
3. Une serie peut etre corrigee apres validation. Une seance incomplete peut
   etre terminee ; une note et un ressenti restent facultatifs.
4. L'affichage precedent vise la derniere seance terminee de la meme variation,
   sans melanger deux variantes differentes.
5. Ajouter, retirer, ignorer ou reordonner un exercice dans une seance ne
   modifie pas la seance type. Les ecarts restent visibles dans l'historique.
6. Une archive reste consultable dans l'historique ; aucune suppression
   destructive n'est proposee pour une donnee deja utilisee.

## Erreurs et cas limites attendus

- Double soumission ou rechargement lors du demarrage : aucune deuxieme seance
  ne doit etre creee.
- Une seance deja en cours est prioritaire sur une nouvelle seance du jour.
- Des objectifs ou mesures inexistants restent affichables sans valeur
  inventee.
- Une serie invalide (nombre negatif, decimal pour les repetitions, champ non
  compatible avec la variation) est refusee avec un message local explicite.
- Une indisponibilite reseau rend l'etat de sauvegarde visible ; le navigateur
  ne doit pas laisser croire silencieusement que la modification est enregistree.
- Renommer, archiver ou modifier une source n'altere jamais les libelles et
  objectifs de l'historique.

## Criteres d'acceptation du MVP

- Les exercices actuels et leurs modes de mesure sont representables sans
  catalogue generique massif.
- Le programme hebdomadaire fournit une seance du jour explicite.
- Une seance reelle creee depuis un modele est independante de celui-ci.
- Sur telephone, une serie charge + repetitions peut etre validee ou corrigee
  en quelques secondes avec de grands controles et un clavier adapte.
- Un rafraichissement apres sauvegarde ne perd aucune donnee ; le prototype
  mobile valide aussi ce comportement avant la persistance complete.
- L'historique explique les adaptations et les graphiques ne comparent que les
  variations compatibles.

## Decisions a valider par l'usage

- les exercices precis du premier programme et leurs objectifs initiaux ;
- la disposition exacte des controles mobiles apres le prototype terrain ;
- les arrondis d'affichage par type d'equipement ; le prototype utilise 1,25 kg
  (equivalent a 2,5 lb) comme pas minimal, puis le catalogue portera un pas par
  variation ou equipement ;
- le niveau de correction autorise apres une seance terminee ;
- le format initial de la premiere courbe de progression.
