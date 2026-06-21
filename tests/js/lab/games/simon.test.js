import { describe, expect, test } from 'vitest';
import { SimonGame, SIMON_PHASE } from '../../../../assets/scripts/lab/games/simon/game.js';
import {
    loadSimonBestScore,
    saveSimonBestScore,
} from '../../../../assets/scripts/lab/games/simon/storage.js';

describe('Simon game logic', () => {
    test('starts a game with one random step and enters preparation mode', () => {
        const game = new SimonGame({
            random: () => 0.24,
        });

        game.startNewGame();

        expect(game.sequence).toEqual([0]);
        expect(game.level).toBe(1);
        expect(game.playerIndex).toBe(0);
        expect(game.phase).toBe(SIMON_PHASE.PREPARATION);
    });

    test('accepts a correct sequence and prepares the next round on round complete', () => {
        const game = new SimonGame({
            random: createRandomSequence([0.1, 0.9]),
        });

        game.startNewGame();
        game.beginPlayerTurn();

        expect(game.submitPlayerStep(0)).toMatchObject({
            status: 'round-complete',
            expected: 0,
            actual: 0,
            bestScore: 1,
        });
        expect(game.bestScore).toBe(1);

        game.prepareNextRound();

        expect(game.sequence).toEqual([0, 3]);
        expect(game.level).toBe(2);
        expect(game.playerIndex).toBe(0);
        expect(game.phase).toBe(SIMON_PHASE.PREPARATION);
    });

    test('validates each player step immediately', () => {
        const game = new SimonGame({
            random: createRandomSequence([0.25, 0.5]),
        });

        game.startNewGame();
        game.beginPlayerTurn();
        game.sequence = [1, 2];

        expect(game.submitPlayerStep(1)).toMatchObject({
            status: 'correct',
            expected: 1,
            actual: 1,
            remaining: 1,
        });
        expect(game.phase).toBe(SIMON_PHASE.PLAYER);

        expect(game.submitPlayerStep(2)).toMatchObject({
            status: 'round-complete',
            expected: 2,
            actual: 2,
        });
    });

    test('ends the game immediately on a wrong input and ignores later inputs', () => {
        const game = new SimonGame({
            random: () => 0.1,
        });

        game.startNewGame();
        game.beginPlayerTurn();
        game.sequence = [2];

        expect(game.submitPlayerStep(1)).toMatchObject({
            status: 'failure',
            expected: 2,
            actual: 1,
        });
        expect(game.phase).toBe(SIMON_PHASE.FAILURE);
        expect(game.submitPlayerStep(2)).toMatchObject({
            status: 'locked',
            expected: null,
            actual: 2,
        });
    });

    test('resets the game without touching the best score', () => {
        const game = new SimonGame({
            bestScore: 4,
            random: () => 0.5,
        });

        game.startNewGame();
        game.beginPlayerTurn();
        game.reset();

        expect(game.sequence).toEqual([]);
        expect(game.level).toBe(0);
        expect(game.playerIndex).toBe(0);
        expect(game.phase).toBe(SIMON_PHASE.IDLE);
        expect(game.bestScore).toBe(4);
    });
});

describe('Simon best score storage', () => {
    test('loads and saves the best local score', () => {
        const storage = createLocalStorageMock();

        expect(loadSimonBestScore(storage)).toBe(0);
        expect(saveSimonBestScore(3, storage)).toBe(true);
        expect(loadSimonBestScore(storage)).toBe(3);
    });

    test('ignores invalid stored values', () => {
        const storage = createLocalStorageMock();
        storage.setItem('benleminbe-lab-simon-best-score', '3.5');

        expect(loadSimonBestScore(storage)).toBe(0);
        expect(storage.getItem('benleminbe-lab-simon-best-score')).toBeNull();
    });

    test('rejects invalid scores when saving', () => {
        const storage = createLocalStorageMock();

        expect(saveSimonBestScore(-1, storage)).toBe(false);
        expect(storage.getItem('benleminbe-lab-simon-best-score')).toBeNull();
    });
});

function createRandomSequence(values) {
    let index = 0;

    return () => values[index++] ?? values[values.length - 1] ?? 0;
}

function createLocalStorageMock() {
    const entries = new Map();

    return {
        getItem: key => entries.get(key) ?? null,
        setItem: (key, value) => {
            entries.set(key, String(value));
        },
        removeItem: key => {
            entries.delete(key);
        },
        clear: () => {
            entries.clear();
        },
    };
}
