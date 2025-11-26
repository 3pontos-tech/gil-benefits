<?php

use App\Utils\CpfValidator;

describe('CpfValidator', function () {
    describe('validate', function () {
        it('validates correct CPF numbers', function () {
            // Valid CPF numbers
            expect(CpfValidator::validate('11144477735'))->toBeTrue();
            expect(CpfValidator::validate('111.444.777-35'))->toBeTrue();
            expect(CpfValidator::validate('52998224725'))->toBeTrue();
            expect(CpfValidator::validate('529.982.247-25'))->toBeTrue();
        });

        it('rejects invalid CPF numbers', function () {
            // Invalid CPF numbers
            expect(CpfValidator::validate('11144477736'))->toBeFalse(); // Wrong check digit
            expect(CpfValidator::validate('123.456.789-10'))->toBeFalse(); // Invalid
            expect(CpfValidator::validate('000.000.000-00'))->toBeFalse(); // All zeros
            expect(CpfValidator::validate('111.111.111-11'))->toBeFalse(); // All same digits
        });

        it('rejects CPF with wrong length', function () {
            expect(CpfValidator::validate('123456789'))->toBeFalse(); // Too short
            expect(CpfValidator::validate('123456789012'))->toBeFalse(); // Too long
            expect(CpfValidator::validate(''))->toBeFalse(); // Empty
        });

        it('rejects known invalid patterns', function () {
            // All same digits
            expect(CpfValidator::validate('00000000000'))->toBeFalse();
            expect(CpfValidator::validate('11111111111'))->toBeFalse();
            expect(CpfValidator::validate('22222222222'))->toBeFalse();
            expect(CpfValidator::validate('33333333333'))->toBeFalse();
            expect(CpfValidator::validate('44444444444'))->toBeFalse();
            expect(CpfValidator::validate('55555555555'))->toBeFalse();
            expect(CpfValidator::validate('66666666666'))->toBeFalse();
            expect(CpfValidator::validate('77777777777'))->toBeFalse();
            expect(CpfValidator::validate('88888888888'))->toBeFalse();
            expect(CpfValidator::validate('99999999999'))->toBeFalse();
        });

        it('handles CPF with special characters', function () {
            expect(CpfValidator::validate('111.444.777-35'))->toBeTrue();
            expect(CpfValidator::validate('111 444 777 35'))->toBeTrue();
            expect(CpfValidator::validate('111-444-777-35'))->toBeTrue();
            expect(CpfValidator::validate('111/444/777/35'))->toBeTrue();
        });
    });

    describe('format', function () {
        it('formats CPF with correct mask', function () {
            expect(CpfValidator::format('11144477735'))->toBe('111.444.777-35');
            expect(CpfValidator::format('52998224725'))->toBe('529.982.247-25');
        });

        it('returns original string if not 11 digits', function () {
            expect(CpfValidator::format('123456789'))->toBe('123456789');
            expect(CpfValidator::format('123456789012'))->toBe('123456789012');
        });

        it('removes existing formatting before applying new format', function () {
            expect(CpfValidator::format('111.444.777-35'))->toBe('111.444.777-35');
            expect(CpfValidator::format('111 444 777 35'))->toBe('111.444.777-35');
        });
    });

    describe('clean', function () {
        it('removes all non-numeric characters', function () {
            expect(CpfValidator::clean('111.444.777-35'))->toBe('11144477735');
            expect(CpfValidator::clean('111 444 777 35'))->toBe('11144477735');
            expect(CpfValidator::clean('111-444-777-35'))->toBe('11144477735');
            expect(CpfValidator::clean('111/444/777/35'))->toBe('11144477735');
            expect(CpfValidator::clean('111abc444def777ghi35'))->toBe('11144477735');
        });

        it('returns empty string for non-numeric input', function () {
            expect(CpfValidator::clean('abcdefghijk'))->toBe('');
            expect(CpfValidator::clean('!@#$%^&*()'))->toBe('');
        });

        it('handles already clean CPF', function () {
            expect(CpfValidator::clean('11144477735'))->toBe('11144477735');
        });
    });
});