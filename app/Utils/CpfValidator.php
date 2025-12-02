<?php

namespace App\Utils;

class CpfValidator
{
    /**
     * Validate a Brazilian CPF number
     */
    public static function validate(?string $cpf): bool
    {
        if ($cpf === null) {
            return false;
        }

        // Remove any non-numeric characters
        $cpf = preg_replace('/[^0-9]/', '', $cpf) ?? '';

        // Check if CPF has 11 digits
        if (strlen($cpf) !== 11) {
            return false;
        }

        // Check for known invalid CPFs (all same digits)
        if (preg_match('/(\d)\1{10}/', $cpf)) {
            return false;
        }

        // Calculate first verification digit
        $sum = 0;
        for ($i = 0; $i < 9; ++$i) {
            $sum += intval($cpf[$i]) * (10 - $i);
        }
        $remainder = $sum % 11;
        $firstDigit = $remainder < 2 ? 0 : 11 - $remainder;

        // Check first verification digit
        if (intval($cpf[9]) !== $firstDigit) {
            return false;
        }

        // Calculate second verification digit
        $sum = 0;
        for ($i = 0; $i < 10; ++$i) {
            $sum += intval($cpf[$i]) * (11 - $i);
        }
        $remainder = $sum % 11;
        $secondDigit = $remainder < 2 ? 0 : 11 - $remainder;

        // Check second verification digit
        return intval($cpf[10]) === $secondDigit;
    }

    /**
     * Format CPF with mask (000.000.000-00)
     */
    public static function format(?string $cpf): string
    {
        if ($cpf === null) {
            return '';
        }

        $cpf = preg_replace('/[^0-9]/', '', $cpf) ?? '';

        if (strlen($cpf) !== 11) {
            return $cpf;
        }

        return substr($cpf, 0, 3) . '.' .
               substr($cpf, 3, 3) . '.' .
               substr($cpf, 6, 3) . '-' .
               substr($cpf, 9, 2);
    }

    /**
     * Remove CPF formatting
     */
    public static function clean(?string $cpf): string
    {
        if ($cpf === null) {
            return '';
        }

        return preg_replace('/[^0-9]/', '', $cpf) ?? '';
    }
}
