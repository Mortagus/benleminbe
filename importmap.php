<?php

/**
 * Returns the importmap for this application.
 *
 * - "path" is a path inside the asset mapper system. Use the
 *     "debug:asset-map" command to see the full list of paths.
 *
 * - "entrypoint" (JavaScript only) set to true for any module that will
 *     be used as an "entrypoint" (and passed to the importmap() Twig function).
 *
 * The "importmap:require" command can be used to add new entries to this file.
 */
return [
    'app' => [
        'path' => './assets/app.js',
        'entrypoint' => true,
    ],
    'dnd_initiative' => [
        'path' => './assets/scripts/lab/dnd/dnd_initiative.js',
        'entrypoint' => true,
    ],
    'private' => [
        'path' => './assets/scripts/private/private.js',
        'entrypoint' => true,
    ],
];
