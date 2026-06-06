import { describe, expect, test } from 'vitest';
import {
    describeHitPointsChange,
    describeInvalidHitPointsChange,
    parseHitPointsChange,
} from '../../../../assets/scripts/lab/dnd/hit-points.js';

describe('hit points quick edit helpers', () => {
    test('parses damage, healing and direct set inputs', () => {
        expect(parseHitPointsChange(' -7 ')).toEqual({
            kind: 'damage',
            amount: 7,
        });
        expect(parseHitPointsChange('+5')).toEqual({
            kind: 'heal',
            amount: 5,
        });
        expect(parseHitPointsChange('12')).toEqual({
            kind: 'set',
            amount: 12,
        });
    });

    test('rejects unsupported hit points inputs', () => {
        expect(parseHitPointsChange('')).toBeNull();
        expect(parseHitPointsChange('abc')).toBeNull();
        expect(parseHitPointsChange('--7')).toBeNull();
        expect(parseHitPointsChange('++5')).toBeNull();
        expect(parseHitPointsChange('+')).toBeNull();
        expect(parseHitPointsChange('-')).toBeNull();
        expect(parseHitPointsChange('7 dégâts')).toBeNull();
        expect(parseHitPointsChange('1d8+3')).toBeNull();
    });

    test('formats hit points change announcements', () => {
        expect(describeHitPointsChange('Lia', { kind: 'damage', amount: 7 }, 6, 20))
            .toBe('Lia subit 7 dégâts. PV 6 / 20.');
        expect(describeHitPointsChange('Lia', { kind: 'heal', amount: 5 }, 18, 20))
            .toBe('Lia récupère 5 PV. PV 18 / 20.');
        expect(describeHitPointsChange('Lia', { kind: 'set', amount: 12 }, 12, 20))
            .toBe('Lia : PV définis à 12 / 20.');
        expect(describeInvalidHitPointsChange('Lia'))
            .toBe('Saisie invalide pour Lia. Utilise -N, +N ou N.');
    });
});
