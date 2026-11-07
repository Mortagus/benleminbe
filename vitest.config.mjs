import { defineConfig } from 'vitest/config';

export default defineConfig({
    test: {
        environment: 'node',
        include: ['tests/js/**/*.test.js'],
        // Workaround, not a fix: under CPU load the test process sometimes stalls for seconds and unrelated
        // tests (even synchronous ones) hit the default 5s timeout. See docs/en-cours/vitest-timeouts-notes.md.
        testTimeout: 20000,
    },
});
