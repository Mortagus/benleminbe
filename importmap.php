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
    'page_home' => [
        'path' => './assets/pages/home.js',
        'entrypoint' => true,
    ],
    'page_about' => [
        'path' => './assets/pages/about.js',
        'entrypoint' => true,
    ],
    'page_contact' => [
        'path' => './assets/pages/contact.js',
        'entrypoint' => true,
    ],
    'page_card' => [
        'path' => './assets/pages/card.js',
        'entrypoint' => true,
    ],
    'page_skills' => [
        'path' => './assets/pages/skills.js',
        'entrypoint' => true,
    ],
    'page_projects' => [
        'path' => './assets/pages/projects.js',
        'entrypoint' => true,
    ],
    'page_project_detail' => [
        'path' => './assets/pages/project_detail.js',
        'entrypoint' => true,
    ],
    'page_experiences' => [
        'path' => './assets/pages/experiences.js',
        'entrypoint' => true,
    ],
    'page_experience_detail' => [
        'path' => './assets/pages/experience_detail.js',
        'entrypoint' => true,
    ],
    'page_legal' => [
        'path' => './assets/pages/legal.js',
        'entrypoint' => true,
    ],
    'page_lab' => [
        'path' => './assets/pages/lab.js',
        'entrypoint' => true,
    ],
    'page_lab_game_simon' => [
        'path' => './assets/pages/lab_game_simon.js',
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
