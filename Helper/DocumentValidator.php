<?php
/**
 * Copyright © MercadoPago. All rights reserved.
 *
 * @author      Mercado Pago
 * @license     See LICENSE for license details.
 */

namespace MercadoPago\AdbPayment\Helper;

/**
 * Validates Brazilian fiscal documents (CPF / CNPJ) by check digit (module 11).
 *
 * Backend counterpart of the frontend rule in
 * view/base/web/js/validation/custom-validation-mixin.js — same algorithm so the
 * two layers agree. Used as defense in depth: when the payment form document field
 * is hidden (Capture document identification = disabled + a vatId on the billing
 * address), the frontend rule never runs and an invalid number would otherwise reach
 * the MP Payments API as error 3032 / 2067 (INVALID_USER_IDENTIFICATION_NUMBER).
 *
 * Stateless — exposed as static methods, matching ApiErrorCategoryMapper.
 * Scope is Brazil (MLB) only: other sites use documents the MP API accepts freely,
 * so validating them here would risk rejecting otherwise-valid payments.
 */
class DocumentValidator
{
    /**
     * CPF length in digits.
     */
    public const CPF_LENGTH = 11;

    /**
     * CNPJ length in characters (alphanumeric — RFB Technical Note 49).
     */
    public const CNPJ_LENGTH = 14;

    /**
     * Whether the number is a valid CPF or CNPJ.
     *
     * Expects a number already stripped of punctuation (alphanumeric only); the
     * length selects CPF vs CNPJ. Any other length is invalid.
     *
     * @param string $document
     *
     * @return bool
     */
    public static function isValid(string $document): bool
    {
        $normalized = strtoupper($document);

        if (strlen($normalized) === self::CPF_LENGTH) {
            return self::isValidCpf($normalized);
        }

        if (strlen($normalized) === self::CNPJ_LENGTH) {
            return self::isValidCnpj($normalized);
        }

        return false;
    }

    /**
     * Validate a CPF (11 numeric digits) by its two check digits.
     *
     * @param string $cpf
     *
     * @return bool
     */
    public static function isValidCpf(string $cpf): bool
    {
        if (strlen($cpf) !== self::CPF_LENGTH || !ctype_digit($cpf)) {
            return false;
        }

        if (self::isRepeatedSequence($cpf)) {
            return false;
        }

        $sum = 0;
        for ($i = 0; $i < 9; $i++) {
            $sum += (int) $cpf[$i] * (10 - $i);
        }
        if ((int) $cpf[9] !== self::checkDigit($sum)) {
            return false;
        }

        $sum = 0;
        for ($i = 0; $i < 10; $i++) {
            $sum += (int) $cpf[$i] * (11 - $i);
        }

        return (int) $cpf[10] === self::checkDigit($sum);
    }

    /**
     * Validate a CNPJ (12 alphanumeric chars + 2 numeric check digits) by module 11.
     *
     * Follows the alphanumeric CNPJ algorithm (RFB Technical Note 49): letters
     * contribute their ASCII value minus 48, digits contribute their face value.
     *
     * @param string $cnpj
     *
     * @return bool
     */
    public static function isValidCnpj(string $cnpj): bool
    {
        $normalized = strtoupper($cnpj);

        // Positions 1-12 accept A-Z and 0-9; positions 13-14 (check digits) must be
        // digits. The anchored, fixed-length pattern is ReDoS-safe.
        if (!preg_match('/^[A-Z0-9]{12}[0-9]{2}$/', $normalized)) {
            return false;
        }

        if (self::isRepeatedSequence($normalized)) {
            return false;
        }

        $checkDigits = substr($normalized, self::CNPJ_LENGTH - 2);

        if ((int) $checkDigits[0] !== self::cnpjCheckDigit($normalized, 12)) {
            return false;
        }

        return (int) $checkDigits[1] === self::cnpjCheckDigit($normalized, 13);
    }

    /**
     * Compute one CNPJ check digit over the first $length chars (module 11 with
     * weights cycling 2..9 from right to left).
     *
     * @param string $normalized Full normalized CNPJ.
     * @param int    $length     Number of leading chars to weigh (12 then 13).
     *
     * @return int
     */
    private static function cnpjCheckDigit(string $normalized, int $length): int
    {
        $sum = 0;
        $weight = $length - 7;

        for ($i = $length; $i >= 1; $i--) {
            $sum += (ord($normalized[$length - $i]) - 48) * $weight--;
            if ($weight < 2) {
                $weight = 9;
            }
        }

        return $sum % 11 < 2 ? 0 : 11 - ($sum % 11);
    }

    /**
     * Resolve a module-11 check digit from a weighted sum (10 or 11 collapse to 0).
     *
     * @param int $sum
     *
     * @return int
     */
    private static function checkDigit(int $sum): int
    {
        $rev = 11 - ($sum % 11);

        return $rev >= 10 ? 0 : $rev;
    }

    /**
     * Whether every character is identical (e.g. 00000000000, 11111111111111) —
     * these pass the check-digit math but are never valid documents.
     *
     * @param string $value
     *
     * @return bool
     */
    private static function isRepeatedSequence(string $value): bool
    {
        return $value !== '' && strspn($value, $value[0]) === strlen($value);
    }
}
