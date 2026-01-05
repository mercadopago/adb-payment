<?php
/**
 * Copyright © MercadoPago. All rights reserved.
 *
 * @author      Mercado Pago
 * @license     See LICENSE for license details.
 */

namespace MercadoPago\AdbPayment\Helper;

/**
 * Helper to validate Order API responses.
 *
 * Detects errors when API returns error status without throwing exception.
 */
class OrderApiResponseValidator
{
    /**
     * Result Code - Block name.
     */
    public const RESULT_CODE = 'RESULT_CODE';

    /**
     * Check if response indicates an error from the API.
     *
     * @param array $response
     * @return bool
     */
    public static function isError(array $response): bool
    {
        if (($response[self::RESULT_CODE] ?? 1) === 0) {
            return true;
        }

        if (!empty($response['error'])) {
            return true;
        }

        $status = $response['status'] ?? null;

        return $status !== null && (int) $status >= 400;
    }

    /**
     * Get error code from response.
     *
     * @param array $response
     * @return string
     */
    public static function getErrorCode(array $response): string
    {
        return (string) ($response['status'] ?? '0');
    }

    /**
     * Get error message from response.
     *
     * @param array $response
     * @return string
     */
    public static function getErrorMessage(array $response): string
    {
        return $response['original_message'] ?? $response['message'] ?? $response['error'] ?? '';
    }
}

