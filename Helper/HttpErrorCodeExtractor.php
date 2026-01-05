<?php
/**
 * Copyright © MercadoPago. All rights reserved.
 *
 * @author      Mercado Pago
 * @license     See LICENSE for license details.
 */

namespace MercadoPago\AdbPayment\Helper;

/**
 * Helper to extract HTTP error codes from exceptions.
 */
class HttpErrorCodeExtractor
{
    /**
     * Extract HTTP error code from exception.
     *
     * Tries to extract HTTP status code from:
     * 1. Exception code property (if valid HTTP status code)
     * 2. Exception message (using regex pattern)
     * 3. Defaults to '500' if no valid code found
     *
     * @param \Throwable $exception
     * @return string HTTP error code or default '500'
     */
    public static function extract(\Throwable $exception): string
    {
        // Try to get HTTP status code from exception code (using getCode() method)
        $exceptionCode = $exception->getCode();
        if ($exceptionCode !== null && $exceptionCode !== 0) {
            $code = (string) $exceptionCode;

            if (preg_match('/^[1-5]\d{2}$/', $code)) {
                return $code;
            }
        }

        // Try to extract from exception message
        if (preg_match('/\b([1-5]\d{2})\b/', $exception->getMessage(), $matches)) {
            return $matches[1];
        }

        return '500';
    }
}

