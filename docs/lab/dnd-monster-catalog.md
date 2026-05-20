# DnD Monster Catalog

This document describes the current monster catalog pipeline for the DnD Initiative Tracker.

## Runtime File

The browser imports the committed module:

```text
assets/scripts/lab/dnd/monster_classes.js
```

This file exports `monsterClasses` and is used by:

```text
assets/scripts/lab/dnd/monsters.js
```

Do not treat `monster_classes.js` as hand-authored product content. It is a generated catalog copy kept in the frontend assets because the DnD tool is currently a browser-side mini-application.

## Source And Generated Files

Current files:

```text
tools/dnd/monsters-source.html
tools/dnd/monsters.html
tools/dnd/monsters.generated.json
tools/dnd/complete_monster_extractor.php
tools/dnd/extract_monsters.php
assets/scripts/lab/dnd/monster_classes.js
```

Current roles:

- `monsters-source.html` is the full HTML source used by the complete extractor;
- `complete_monster_extractor.php` parses `monsters-source.html`;
- `monsters.generated.json` is the JSON output produced by `complete_monster_extractor.php`;
- `monster_classes.js` is the frontend module consumed by the application;
- `extract_monsters.php` is an older extractor that can write JavaScript from a table-style HTML input, but it does not currently produce the exact ES module format used by `monster_classes.js`.

## Regeneration

Regenerate the JSON catalog with:

```bash
php tools/dnd/complete_monster_extractor.php
```

Then compare:

```bash
git diff -- tools/dnd/monsters.generated.json
```

At the moment, updating `assets/scripts/lab/dnd/monster_classes.js` from `tools/dnd/monsters.generated.json` is not fully automated by a dedicated command. If the catalog must be refreshed, update the frontend module carefully so that it still exports:

```js
export const monsterClasses = [
    // ...
];
```

Recommended future cleanup:

- add a small dedicated script that converts `monsters.generated.json` into `assets/scripts/lab/dnd/monster_classes.js`;
- add a generated-file header to `monster_classes.js`;
- document the upstream source date if the HTML source is refreshed.

## Data Shape

Each monster entry currently exposes fields used by the UI:

```text
id
slug
name
challenge_rating
type
size
armor_class
hit_points
speed
alignment
is_legendary
abilities
initiative_modifier
```

`initiative_modifier` is used by the initiative roll logic. It should stay numeric.

## Checks After Updating

After changing the catalog or extractor:

```bash
php tools/dnd/complete_monster_extractor.php
php bin/console lint:twig templates/lab/dnd
```

Then manually test the DnD page:

- monster selection still opens;
- a selected monster shows expected initiative data;
- initiative rolls still work;
- turn order generation still includes selected monsters.

