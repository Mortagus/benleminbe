export const SIMON_PHASE = Object.freeze({
    IDLE: 'idle',
    PREPARATION: 'preparation',
    DEMO: 'demo',
    PLAYER: 'player',
    SUCCESS: 'success',
    FAILURE: 'failure',
});

export class SimonGame {
    constructor({ random = Math.random, bestScore = 0 } = {}) {
        this.random = random;
        this.bestScore = Number.isFinite(bestScore) && bestScore >= 0 ? bestScore : 0;
        this.reset();
    }

    startNewGame() {
        this.sequence = [this.generateStep()];
        this.level = 1;
        this.playerIndex = 0;
        this.phase = SIMON_PHASE.PREPARATION;
        return this.snapshot();
    }

    beginPlayerTurn() {
        if (this.sequence.length === 0) {
            return this.snapshot();
        }

        this.playerIndex = 0;
        this.phase = SIMON_PHASE.PLAYER;

        return this.snapshot();
    }

    submitPlayerStep(stepIndex) {
        if (this.phase !== SIMON_PHASE.PLAYER) {
            return {
                status: 'locked',
                expected: null,
                actual: stepIndex,
            };
        }

        const expected = this.sequence[this.playerIndex];

        if (stepIndex !== expected) {
            this.phase = SIMON_PHASE.FAILURE;

            return {
                status: 'failure',
                expected,
                actual: stepIndex,
                index: this.playerIndex,
            };
        }

        this.playerIndex += 1;

        if (this.playerIndex === this.sequence.length) {
            this.phase = SIMON_PHASE.SUCCESS;
            this.bestScore = Math.max(this.bestScore, this.level);

            return {
                status: 'round-complete',
                expected,
                actual: stepIndex,
                index: this.playerIndex - 1,
                bestScore: this.bestScore,
            };
        }

        return {
            status: 'correct',
            expected,
            actual: stepIndex,
            index: this.playerIndex - 1,
            remaining: this.sequence.length - this.playerIndex,
        };
    }

    prepareNextRound() {
        if (this.sequence.length === 0) {
            return this.startNewGame();
        }

        this.sequence = [...this.sequence, this.generateStep()];
        this.level = this.sequence.length;
        this.playerIndex = 0;
        this.phase = SIMON_PHASE.PREPARATION;

        return this.snapshot();
    }

    reset() {
        this.sequence = [];
        this.level = 0;
        this.playerIndex = 0;
        this.phase = SIMON_PHASE.IDLE;

        return this.snapshot();
    }

    generateStep() {
        return Math.floor(this.random() * 4);
    }

    snapshot() {
        return {
            sequence: [...this.sequence],
            level: this.level,
            playerIndex: this.playerIndex,
            phase: this.phase,
            bestScore: this.bestScore,
        };
    }
}
