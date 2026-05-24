import js from '@eslint/js';

const browserGlobals = {
    document: 'readonly',
    HTMLAnchorElement: 'readonly',
    HTMLElement: 'readonly',
    localStorage: 'readonly',
    navigator: 'readonly',
    Node: 'readonly',
    window: 'readonly',
};

const nodeGlobals = {
    globalThis: 'readonly',
};

export default [
    {
        ignores: [
            'assets/scripts/lab/dnd/bestiary.js',
            'node_modules/**',
            'public/assets/**',
            'var/**',
        ],
    },
    js.configs.recommended,
    {
        files: [
            'assets/**/*.js',
            'tests/js/**/*.js',
            'vitest.config.mjs',
        ],
        languageOptions: {
            ecmaVersion: 'latest',
            sourceType: 'module',
        },
    },
    {
        files: ['assets/**/*.js'],
        languageOptions: {
            globals: browserGlobals,
        },
    },
    {
        files: [
            'tests/js/**/*.js',
            'vitest.config.mjs',
        ],
        languageOptions: {
            globals: nodeGlobals,
        },
    },
];
