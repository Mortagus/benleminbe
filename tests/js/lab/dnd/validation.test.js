import { describe, expect, test } from 'vitest';
import {
    showValidationErrors,
    validateCurrentHitPointsLimit,
    validateIntegerValue,
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
    test('validates normalized integer field values', () => {
        expect(validateIntegerValue(
            {
                input: null,
                rawValue: ' 4 ',
                badInput: false,
            },
            {
                fieldName: 'initiative joueur 1',
                min: 1,
                max: 20,
                required: true,
            },
        )).toEqual({
            isValid: true,
            errors: [],
        });

        expect(validateIntegerValue(
            {
                input: null,
                rawValue: '4.5',
                badInput: false,
            },
            {
                fieldName: 'initiative joueur 1',
                required: true,
            },
        )).toMatchObject({
            isValid: false,
            errors: [
                {
                    message: 'Le champ initiative joueur 1 doit être un nombre entier.',
                },
            ],
        });
    });

    test('validates current hit points against max hit points without DOM reads', () => {
        expect(validateCurrentHitPointsLimit({
            currentHitPoints: 12,
            maxHitPoints: 20,
            currentHitPointsInput: null,
            actorLabel: 'joueur 1',
        })).toEqual({
            isValid: true,
            errors: [],
        });

        expect(validateCurrentHitPointsLimit({
            currentHitPoints: 21,
            maxHitPoints: 20,
            currentHitPointsInput: null,
            actorLabel: 'joueur 1',
        })).toMatchObject({
            isValid: false,
            errors: [
                {
                    message: 'Les PV actuels du joueur 1 ne peuvent pas dépasser ses PV max.',
                },
            ],
        });
    });

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
