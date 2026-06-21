export const SIMON_BEST_SCORE_STORAGE_KEY = 'benleminbe-lab-simon-best-score';

export function loadSimonBestScore(storage = globalThis.localStorage ?? null) {
    if (!storage) {
        return 0;
    }

    try {
        const rawScore = storage.getItem(SIMON_BEST_SCORE_STORAGE_KEY);

        if (rawScore === null) {
            return 0;
        }

        const parsedScore = Number(rawScore);

        if (Number.isInteger(parsedScore) && parsedScore > 0) {
            return parsedScore;
        }

        storage.removeItem?.(SIMON_BEST_SCORE_STORAGE_KEY);

        return 0;
    } catch {
        return 0;
    }
}

export function saveSimonBestScore(score, storage = globalThis.localStorage ?? null) {
    if (!storage) {
        return false;
    }

    try {
        if (!Number.isInteger(score) || score < 0) {
            return false;
        }

        storage.setItem(SIMON_BEST_SCORE_STORAGE_KEY, String(score));

        return true;
    } catch {
        return false;
    }
}
