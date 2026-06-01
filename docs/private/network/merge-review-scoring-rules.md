# Règles De Comparaison Et De Score Des Doublons

Date de mise à jour : 2026-06-01

Ce document décrit les règles métier actuellement utilisées pour détecter les candidats de fusion dans l'outil privé `Contacts et reseau`.

Il sert de référence stable pour comprendre:

- quels signaux alimentent la détection;
- quels signaux sont considérés comme forts, moyens ou faibles;
- comment le score exact et le score de revue sont construits;
- pourquoi certains cas remontent ou non dans la file de revue;
- quelle logique métier guide l'équilibre entre précision et rappel.

## Portée

Ce document concerne uniquement la génération des candidats de fusion manuelle dans la file de revue des doublons.

Il ne décrit pas:

- l'import des contacts;
- le matching à l'ajout ou à la mise à jour d'un contact;
- l'auto-fusion;
- la fusion champ par champ dans l'écran de résolution.

Ces autres flux existent dans le module `Network`, mais ils utilisent des règles différentes ou un sous-ensemble différent de règles.

## Objectif Métier

L'objectif n'est pas de maximiser le nombre de paires détectées.

L'objectif est de faire remonter uniquement des paires plausibles, c'est-à-dire des fiches qui peuvent raisonnablement représenter la même personne.

En pratique:

- mieux vaut laisser passer quelques doublons difficiles;
- il vaut mieux éviter les faux positifs manifestes;
- une ressemblance sur l'entreprise ou le rôle ne doit pas suffire si l'identité nominative est clairement différente.

## Flux De Décision

Lors de la génération de la file de revue:

1. tous les contacts sont chargés;
2. chaque paire de contacts est comparée;
3. les contacts liés à LinkedIn sont exclus dès le départ;
4. un score exact est calculé;
5. un score de revue est calculé à partir du score exact, des proximités textuelles et de pénalités nominatives;
6. si le `review_score` est inférieur au seuil minimal, la paire est rejetée;
7. sinon, la paire devient un candidat de fusion.

Le seuil minimal actuel est:

- `review_score >= 50`

## Les Deux Scores

### Score Exact

Le `score` représente les signaux forts de rapprochement.

Il correspond à une addition de preuves explicites d'identité:

- téléphone identique;
- email identique;
- profil identique;
- nom affiché identique;
- prénom et nom identiques;
- entreprise identique;
- rôle identique;
- source commune.

Ce score est plafonné à `100`.

Il n'est pas utilisé seul pour décider si une paire entre dans la file. Il sert surtout:

- à mesurer la solidité objective du rapprochement;
- à classer les candidats;
- à conserver une trace lisible des signaux exacts.

### Score De Revue

Le `review_score` part du score exact et ajoute ou retire des points selon:

- la proximité du nom affiché;
- la proximité du prénom + nom;
- la présence d'indices de contexte communs;
- la présence de pénalités nominatives fortes;
- la présence d'une fiche partielle.

Ce score est plafonné à `100`.

C'est lui qui décide si une paire entre dans la file de revue.

## Signaux Forts

Les signaux forts doivent faire penser à une même personne presque sans ambiguïté.

### Téléphone Identique

- Score exact: `+100`
- Score de revue: le score exact suffit déjà à rendre la paire très plausible

Interprétation:

- deux fiches partageant un même téléphone ont une forte probabilité de désigner la même personne;
- ce signal est l'un des plus fiables du système.

### Email Identique

- Score exact: `+95`
- Score de revue: très fort

Interprétation:

- un email commun est un signal de doublon très solide;
- il peut parfois exister des exceptions métier, mais elles sont rares.

### Profil Identique

- Score exact: `+90`
- Score de revue: très fort

Interprétation:

- deux fiches avec le même profil normalisé sont très probablement liées à la même personne;
- ce signal est particulièrement utile pour les profils publics et réseaux sociaux.

### Nom Affiché Identique

- Score exact: `+50`
- Score de revue: fort

Interprétation:

- un même nom affiché est un bon signal, mais il peut aussi exister plusieurs personnes avec le même nom;
- il doit donc être renforcé par d'autres éléments quand c'est possible.

### Prénom Et Nom Identiques

- Score exact: `+40`
- Score de revue: fort

Interprétation:

- la combinaison prénom + nom est un signal plus structurant que le nom affiché seul;
- elle reste moins forte qu'un téléphone, un email ou un profil strictement identique.

## Signaux Moyens

Les signaux moyens renforcent un cas déjà plausible, mais ne doivent pas créer à eux seuls un faux doublon.

### Nom Affiché Proche

Similarité calculée sur le texte normalisé:

- `>= 92%` : `+30`
- `>= 82%` : `+20`
- `>= 70%` : `+10`

Interprétation:

- utile pour détecter des variations typographiques légères;
- acceptable pour des inversions mineures, fautes de frappe ou variantes locales;
- insuffisant si le reste de la fiche est incompatible.

### Prénom Et Nom Proches

Similarité calculée sur `prénom + nom` normalisés:

- `>= 90%` : `+20`
- `>= 80%` : `+10`

Interprétation:

- permet de rattraper des variantes orthographiques proches;
- utile pour les doublons légèrement bruités;
- ne doit pas compenser une identité nominative clairement différente.

### Entreprise Identique

- Score exact: `+20`
- Score de revue: `+10`

Interprétation:

- deux personnes peuvent partager la même entreprise sans être la même personne;
- ce signal n'est qu'un contexte;
- il ne doit jamais suffire à lui seul pour faire entrer une paire en revue.

### Rôle Identique

- Score exact: `+10`
- Score de revue: `+5`

Interprétation:

- un rôle identique est très fréquent dans une même organisation;
- ce signal est faible et ne doit être qu'un appui secondaire.

### Source Commune

- Score exact: `+5`
- Score de revue: `+5`

Interprétation:

- le fait qu'une fiche vienne de la même source ne prouve pas un doublon;
- ce signal aide surtout à conforter une cohérence déjà présente.

### Fiche Partielle

- Score de revue: `+10`

Interprétation:

- une fiche peu remplie peut être plus facilement rapprochée d'une autre;
- ce signal sert à compenser le manque d'information, pas à le remplacer;
- il ne doit pas transformer deux fiches pauvres mais différentes en faux doublon.

## Pénalités Nominatives

Les pénalités ont été ajoutées pour éviter qu'un contexte commun masque une identité clairement différente.

### Nom Affiché Très Différent

Si la similarité du nom affiché est inférieure à `40%`:

- pénalité: `-25`
- raison affichée: `Nom affiché très différent`

### Nom Affiché Différent

Si la similarité du nom affiché est comprise entre `40%` et `60%`:

- pénalité: `-15`
- raison affichée: `Nom affiché différent`

### Prénom Et Nom Très Différents

Si la similarité de `prénom + nom` est inférieure à `40%`:

- pénalité: `-20`
- raison affichée: `Prénom et nom très différents`

### Prénom Et Nom Différents

Si la similarité de `prénom + nom` est comprise entre `40%` et `60%`:

- pénalité: `-10`
- raison affichée: `Prénom et nom différents`

### Faiblesse Nominative Globale

Si le nom affiché et le prénom + nom sont tous les deux très faibles:

- pénalité supplémentaire: `-10`
- raison affichée: `Identité nominative très faible`

Interprétation:

- un contexte partagé ne doit pas suffire si les champs nominaux sont incompatibles;
- deux personnes différentes d'une même entreprise doivent cesser d'être “absorbées” par le score.

## Règles De Rejet

Une paire est rejetée si:

- elle implique un contact LinkedIn;
- ou le `review_score` final est inférieur à `50`.

Ce seuil est volontairement conservateur.

Il protège la file de revue contre les faux positifs au prix d'un rappel plus faible.

## Lecture Des Raisons Affichées

Les raisons affichées dans la revue sont destinées à expliquer le score, pas à le remplacer.

Elles peuvent contenir:

- des signaux forts;
- des signaux moyens;
- des pénalités nominatives;
- des conflits bloquants sur certains champs.

Quand aucune raison utile ne reste, le système affiche:

- `Pas de clé forte suffisante`

## Tableau Récapitulatif

| Signal                       | Condition                                | Score exact |  Score de revue | Interprétation métier                            | Commentaire                                 |
| ---------------------------- | ---------------------------------------- | ----------: | --------------: | ------------------------------------------------ | ------------------------------------------- |
| Téléphone identique          | Même téléphone normalisé                 |        +100 |       très fort | Très forte probabilité de même personne          | Signal le plus robuste                      |
| Email identique              | Même email normalisé                     |         +95 |       très fort | Très forte probabilité de doublon                | Peut avoir de rares exceptions              |
| Profil identique             | Même profil normalisé                    |         +90 |       très fort | Très forte probabilité de même personne          | Très utile pour les profils publics         |
| Nom affiché identique        | Même `display_name` normalisé            |         +50 |            fort | Bon indice, mais pas suffisant seul              | Ambigu si homonymes                         |
| Prénom + nom identiques      | Même identité nominative structurée      |         +40 |            fort | Bon indice de doublon plausible                  | Plus solide que le nom affiché seul         |
| Nom affiché proche           | Similarité >= 92% / 82% / 70%            |           0 | +30 / +20 / +10 | Variantes typographiques possibles               | Ne doit pas compenser un reste incompatible |
| Prénom + nom proches         | Similarité >= 90% / 80%                  |           0 |       +20 / +10 | Variantes orthographiques possibles              | Doit rester un appui secondaire             |
| Entreprise identique         | Même organisation normalisée             |         +20 |             +10 | Contexte utile mais non discriminant             | Trop fréquent pour être fort                |
| Rôle identique               | Même rôle normalisé                      |         +10 |              +5 | Contexte secondaire                              | Signal faible                               |
| Source commune               | Même source tokenisée                    |          +5 |              +5 | Cohérence de provenance                          | Signal très faible                          |
| Fiche partielle              | Score de complétude faible               |           0 |             +10 | Aide à ne pas écarter trop vite une fiche pauvre | Signal de confort seulement                 |
| Nom affiché très différent   | Similarité < 40%                         |           0 |             -25 | Deux personnes probablement différentes          | Pénalité forte                              |
| Nom affiché différent        | Similarité 40-60%                        |           0 |             -15 | Différence notable                               | Pénalité intermédiaire                      |
| Prénom + nom très différents | Similarité < 40%                         |           0 |             -20 | Identité nominative incompatible                 | Pénalité forte                              |
| Prénom + nom différents      | Similarité 40-60%                        |           0 |             -10 | Identité nominative peu convaincante             | Pénalité intermédiaire                      |
| Faiblesse nominative globale | Nom affiché et prénom + nom très faibles |           0 |             -10 | Contexte insuffisant pour conclure               | Empêche les faux positifs par contexte seul |

## Cas Typiques

### Cas Qui Doit Passer

Exemple:

- même téléphone;
- ou même email;
- ou même profil;
- ou nom quasi identique avec autres signaux cohérents.

Résultat attendu:

- la paire entre dans la revue;
- le score reste élevé;
- les raisons affichées permettent de comprendre pourquoi.

### Cas Qui Doit Être Rejeté

Exemple:

- même entreprise;
- même rôle;
- même source;
- noms manifestement différents;
- aucun email commun;
- aucun téléphone commun;
- aucun profil commun.

Résultat attendu:

- la paire est rejetée;
- le score tombe sous le seuil;
- le système ne confond pas contexte commun et identité commune.

### Cas Limite Acceptable

Exemple:

- entreprise identique;
- noms proches;
- un seul autre indice modéré;
- fiche incomplète.

Résultat attendu:

- la paire peut entrer en revue;
- le score doit rester prudente;
- la présence d'un doute raisonnable doit être visible.

## Principe De Maintenance

Quand ce score évolue, il faut vérifier en même temps:

- les tests unitaires du scorer;
- les parcours fonctionnels de génération des doublons;
- les cas de faux positifs connus;
- les vrais doublons déjà couverts.

La bonne question n'est pas:

- “peut-on faire monter plus de candidats ?”

La bonne question est:

- “est-ce que cette paire représente réellement une possibilité crédible que ce soit la même personne ?”

Si la réponse n'est pas clairement oui, la paire ne doit pas entrer dans la file de revue.
