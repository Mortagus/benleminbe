# Pipeline bestiaire - DnD Initiative Tracker

Date de mise à jour : 2026-05-15

Ce document décrit la génération du bestiaire utilisé par le DnD Initiative Tracker. Le bestiaire est un catalogue local de monstres destiné à alimenter le sélecteur de monstres, les PV de base, la CA, le calcul d'initiative et les futurs filtres de préparation de rencontre.

## Source canonique

La source de vérité actuelle est [tools/dnd/monsters-source.html](/var/www/projects/benleminbe/tools/dnd/monsters-source.html:1).

Ce fichier HTML est conservé dans le dépôt parce qu'aucune base officielle structurée n'est encore intégrée au projet. Il contient une source plus complète que l'ancien tableau de monstres utilisé au début du lab.

Les anciens fichiers `tools/dnd/monsters.html`, `tools/dnd/extract_monsters.php` et `tools/dnd/monsters.generated.json` ne font plus partie du pipeline. Ils ont été retirés pour éviter deux sources ou deux formats concurrents.

## Fichiers du pipeline

- Source HTML : [tools/dnd/monsters-source.html](/var/www/projects/benleminbe/tools/dnd/monsters-source.html:1)
- Extracteur : [tools/dnd/complete_monster_extractor.php](/var/www/projects/benleminbe/tools/dnd/complete_monster_extractor.php:1)
- Bestiaire généré : [assets/scripts/lab/dnd/bestiary.js](/var/www/projects/benleminbe/assets/scripts/lab/dnd/bestiary.js:1)
- Test de contrat : [tools/dnd/validate_bestiary.php](/var/www/projects/benleminbe/tools/dnd/validate_bestiary.php:1)

Flux de génération :

```txt
tools/dnd/monsters-source.html
        |
        v
tools/dnd/complete_monster_extractor.php
        |
        v
assets/scripts/lab/dnd/bestiary.js
```

## Commandes

Générer le bestiaire :

```bash
composer dnd:bestiary:generate
```

Valider le contrat du bestiaire :

```bash
composer dnd:bestiary:test
```

Les scripts peuvent aussi être lancés directement :

```bash
php tools/dnd/complete_monster_extractor.php
php tools/dnd/validate_bestiary.php
```

## Contrat de données

`bestiary.js` est un fichier généré. Il ne doit pas être modifié à la main.

Il exporte un tableau `bestiary` :

```js
export const bestiary = [
    {
        id,
        slug,
        name,
        challenge_rating,
        type,
        size,
        armor_class,
        hit_points,
        speed,
        alignment,
        is_legendary,
        abilities,
        initiative_modifier
    }
];
```

Champs indispensables au tracker actuel :

- `slug` : identifiant stable utilisé dans le sélecteur ;
- `name` : libellé affiché dans le sélecteur et les instances de combat ;
- `type` : information affichée dans la liste de préparation ;
- `armor_class` : CA de base du monstre ;
- `hit_points` : PV max et PV courants initiaux ;
- `initiative_modifier` : modificateur ajouté au d20 lors du jet d'initiative.

Champs préparés pour les évolutions :

- `challenge_rating` : filtres par FP ;
- `size` : filtres par taille ;
- `speed` : information utile si une fiche de détail est ajoutée ;
- `alignment` : information descriptive ;
- `is_legendary` : distinction future des boss ou créatures légendaires ;
- `abilities` : caractéristiques complètes, notamment pour recalculer ou auditer `initiative_modifier`.

## Test de contrat

Le test [tools/dnd/validate_bestiary.php](/var/www/projects/benleminbe/tools/dnd/validate_bestiary.php:1) vérifie que :

- `bestiary.js` exporte bien `bestiary` ;
- le contenu exporté est un tableau JSON valide ;
- le catalogue contient un nombre attendu de monstres ;
- chaque slug est non vide et unique ;
- les champs nécessaires au tracker sont présents et typés ;
- les six caractéristiques sont présentes avec `score` et `modifier` ;
- `initiative_modifier` correspond au modificateur de DEX.

Le catalogue généré depuis la source actuelle contient 428 monstres.

## Usage front-end

[assets/scripts/lab/dnd/monsters.js](/var/www/projects/benleminbe/assets/scripts/lab/dnd/monsters.js:1) utilise `bestiary` pour remplir les options des sélecteurs de monstres.

[assets/scripts/lab/dnd/encounter-state.js](/var/www/projects/benleminbe/assets/scripts/lab/dnd/encounter-state.js:1) utilise `bestiary` pour créer une instance de monstre dans la rencontre lorsqu'un slug est sélectionné.

Les objets du bestiaire ne doivent pas devenir l'état mutable d'un combat. L'état de rencontre doit copier les informations utiles et y ajouter les données propres au combat : PV courants, jet d'initiative, initiative finale, statut joué/non joué et identifiant d'instance.

## Allègement et chargement futur

Le bestiaire généré pèse environ 418 Ko non compressé et environ 20 Ko gzip. Pour un lab avec 428 monstres, un import statique reste acceptable.

Un chargement plus fin deviendra pertinent si :

- le catalogue dépasse plusieurs milliers d'entrées ;
- plusieurs sources ou extensions sont ajoutées ;
- des fiches complètes avec actions, sorts ou descriptions sont embarquées ;
- la recherche ou les filtres avancés imposent un index dédié ;
- le temps de chargement devient mesurablement gênant.

Options futures possibles :

- garder un index léger pour la recherche et charger le détail à la sélection ;
- séparer bestiaire officiel, monstres maison et templates de rencontre ;
- stocker le catalogue côté backend si le lab devient une application persistée ;
- ajouter une source officielle structurée si une option fiable est retenue.
