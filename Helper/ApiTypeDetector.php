<?php
/**
 * Copyright © MercadoPago. All rights reserved.
 *
 * @author      Mercado Pago
 * @license     See LICENSE for license details.
 */

namespace MercadoPago\AdbPayment\Helper;

/**
 * Helper to detect if payment uses Order API or Payment API.
 */
class ApiTypeDetector
{
    /**
     * Transaction type for Order API.
     */
    public const TRANSACTION_TYPE_ORDER = 'pp_order';

    /**
     * Transaction type for Payment API.
     */
    public const TRANSACTION_TYPE_PAYMENT = 'payment';

    /**
     * Transaction type when pattern is not detected.
     */
    public const TRANSACTION_TYPE_UNKNOWN = 'unknown';

    /**
     * Additional information key for Order ID.
     */
    public const ORDER_API_ID_KEY = 'mp_order_id';

    /**
     * Additional information key for Payment ID Order.
     */
    public const ORDER_API_PAYMENT_ID_KEY = 'mp_payment_id_order';

    /**
     * Regex pattern for Order API IDs (new format: PP...).
     */
    public const ORDER_API_ID_PATTERN = '/^PP[A-Z0-9]+$/';

    /**
     * Regex pattern for Payment API IDs (old format: numeric).
     */
    public const PAYMENT_API_ID_PATTERN = '/^[0-9]+$/';

    /**
     * Detect transaction type based on payment additional information.
     *
     * Validation order:
     * 1. If ORDER_API_PAYMENT_ID_KEY exists and has value → Order API
     * 2. If regex detects a known pattern → use detected type
     * 3. Fallback → Order API (default for unknown patterns)
     *
     * @param array $additionalInformation Payment additional information
     * @return string Transaction type: 'pp_order' or 'payment'
     */
    public function detectTransactionType(array $additionalInformation): string
    {

        if (!empty($additionalInformation[self::ORDER_API_PAYMENT_ID_KEY])) {
            return self::TRANSACTION_TYPE_ORDER;
        }

        $detectedByPattern = $this->detectByIdPattern(
            $additionalInformation[self::ORDER_API_ID_KEY] ?? null
        );

        return $detectedByPattern !== self::TRANSACTION_TYPE_UNKNOWN
            ? $detectedByPattern
            : self::TRANSACTION_TYPE_ORDER;
    }

    /**
     * Detect transaction type by ID pattern.
     *
     * @param string|null $id Transaction ID to analyze
     * @return string Transaction type: 'pp_order', 'payment', or 'unknown'
     */
    public function detectByIdPattern(?string $id): string
    {
        if (!$id) {
            return self::TRANSACTION_TYPE_UNKNOWN;
        }

        if (preg_match(self::ORDER_API_ID_PATTERN, $id)) {
            return self::TRANSACTION_TYPE_ORDER;
        }

        if (preg_match(self::PAYMENT_API_ID_PATTERN, $id)) {
            return self::TRANSACTION_TYPE_PAYMENT;
        }

        return self::TRANSACTION_TYPE_UNKNOWN;
    }

    /**
     * Check if ID matches Order API pattern.
     *
     * @param string $id Transaction ID
     * @return bool
     */
    public function isOrderApiId(string $id): bool
    {
        return (bool) preg_match(self::ORDER_API_ID_PATTERN, $id);
    }

    /**
     * Check if ID matches Payment API pattern.
     *
     * @param string $id Transaction ID
     * @return bool
     */
    public function isPaymentApiId(string $id): bool
    {
        return (bool) preg_match(self::PAYMENT_API_ID_PATTERN, $id);
    }

    /**
     * Check if payment uses Order API based on additional information.
     *
     * @param array $additionalInformation Payment additional information
     * @return bool
     */
    public function isOrderApi(array $additionalInformation): bool
    {
        return $this->detectTransactionType($additionalInformation) === self::TRANSACTION_TYPE_ORDER;
    }

    /**
     * Check if payment uses Payment API based on additional information.
     *
     * @param array $additionalInformation Payment additional information
     * @return bool
     */
    public function isPaymentApi(array $additionalInformation): bool
    {
        return $this->detectTransactionType($additionalInformation) === self::TRANSACTION_TYPE_PAYMENT;
    }

    /**
     * Check if request should use Order API.
     *
     * Extracts the relevant keys from request array and delegates to isOrderApi().
     * Use this method directly from HTTP clients to simplify the detection logic.
     *
     * @param array $request Request array from transfer object
     * @return bool
     */
    public function isOrderApiFromRequest(array $request): bool
    {
        $additionalInfo = [
            self::ORDER_API_ID_KEY => $request[self::ORDER_API_ID_KEY] ?? null,
            self::ORDER_API_PAYMENT_ID_KEY => $request[self::ORDER_API_PAYMENT_ID_KEY] ?? null,
        ];

        return $this->isOrderApi($additionalInfo);
    }
}
