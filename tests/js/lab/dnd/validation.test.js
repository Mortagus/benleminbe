import { describe, expect, test } from 'vitest';
import {
    showValidationErrors,
    validateMonsterCountInput,
    validatePlayerItem,
} from '../../../../assets/scripts/lab/dnd/validation.js';
import {
    createDocumentDouble,
    createInput,
    createPlayerItem,
    TestElement,
} from './dom-test-helpers.js';

describe('validation helpers', () => {
    test('validates monster count boundaries', () => {
        expect(validateMonsterCountInput(createInput('3'))).toEqual({
            isValid: true,
            errors: [],
        });

        expect(validateMonsterCountInput(createInput('31'))).toMatchObject({
            isValid: false,
            errors: [
                {
                    message: 'Le champ nombre de monstres doit être inférieur ou égal à 30.',
                },
            ],
        });
    });

    test('validates started player rows and rejects current hit points above max', () => {
        const playerItem = createPlayerItem({
            name: createInput('Lia'),
            'armor-class': createInput('15'),
            'current-hit-points': createInput('21'),
            'base-hit-points': createInput('20'),
            initiative: createInput('12'),
        });

        expect(validatePlayerItem(playerItem, 0)).toMatchObject({
            isValid: false,
            errors: [
                {
                    message: 'Les PV actuels du joueur 1 ne peuvent pas dépasser ses PV max.',
                },
            ],
        });
    });

    test('renders validation errors and marks invalid fields', () => {
        globalThis.document = createDocumentDouble();

        const summary = new TestElement('div');
        const input = createInput('');

        showValidationErrors(
            {
                isValid: false,
                errors: [
                    {
                        input,
                        message: 'Le champ initiative joueur 1 est obligatoire.',
                    },
                ],
            },
            summary,
            'Un joueur contient une erreur.',
        );

        expect(summary.hidden).toBe(false);
        expect(summary.children).toHaveLength(2);
        expect(summary.children[0].textContent).toBe('Un joueur contient une erreur.');
        expect(summary.children[1].children[0].textContent).toBe('Le champ initiative joueur 1 est obligatoire.');
        expect(input.classList.contains('dnd-field--invalid')).toBe(true);
        expect(input.getAttribute('aria-invalid')).toBe('true');

        delete globalThis.document;
    });
});
