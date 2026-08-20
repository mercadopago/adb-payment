<?php
/**
 * Copyright © MercadoPago. All rights reserved.
 *
 * @author      Mercado Pago
 * @license     See LICENSE for license details.
 */

namespace MercadoPago\AdbPayment\Helper;

/**
 * Maps a MP Payment API error response to a bounded, closed-set error category.
 *
 * Currently used by CreateOrderPaymentCustomClient (Payment API /v1/payments).
 * Applying this mapper to Order API clients (Gateway/Http/Client/Order/) is
 * tracked as a separate follow-up.
 *
 * Categorization sources, in precedence order:
 *   1. status_detail  (exact match — canonical, mirrors etc/mercadopago_error_mapping.xml)
 *   2. cause[].code   (keyword/substring match — highest-precedence keyword wins)
 *   3. fallback       (CATEGORY_INTERNAL)
 *
 * The returned value is ALWAYS one of the closed allowlist below. Raw API strings
 * (e.g. original_message) are NEVER returned — this protects the metrics payload
 * from leaking sensitive free-text (CWE-532 / CWE-200) and bounds metric cardinality.
 */
class ApiErrorCategoryMapper
{
    /** Closed allowlist of valid error categories. */
    public const CATEGORY_INTERNAL           = 'internal';
    public const CATEGORY_SECURITY_CODE      = 'security_code';
    public const CATEGORY_CARD_NUMBER        = 'card_number';
    public const CATEGORY_CARD_EXPIRATION    = 'card_expiration';
    public const CATEGORY_CARD_DATA          = 'card_data';
    public const CATEGORY_CARD_TOKEN         = 'card_token';
    public const CATEGORY_IDENTIFICATION     = 'identification';
    public const CATEGORY_INSUFFICIENT_AMOUNT = 'insufficient_amount';
    public const CATEGORY_CARD_DISABLED      = 'card_disabled';
    public const CATEGORY_HIGH_RISK          = 'high_risk';
    public const CATEGORY_CALL_FOR_AUTHORIZE = 'call_for_authorize';
    public const CATEGORY_DUPLICATED_PAYMENT = 'duplicated_payment';
    public const CATEGORY_INVALID_INSTALLMENTS = 'invalid_installments';
    public const CATEGORY_MAX_ATTEMPTS       = 'max_attempts';
    public const CATEGORY_CHALLENGE_3DS      = 'challenge_3ds';

    /**
     * status_detail (exact) => category. Source: etc/mercadopago_error_mapping.xml.
     */
    private const STATUS_DETAIL_MAP = [
        'cc_rejected_bad_filled_security_code' => self::CATEGORY_SECURITY_CODE,
        'cc_rejected_bad_filled_card_number'   => self::CATEGORY_CARD_NUMBER,
        'cc_rejected_bad_filled_date'          => self::CATEGORY_CARD_EXPIRATION,
        'cc_rejected_bad_filled_other'         => self::CATEGORY_CARD_DATA,
        'cc_rejected_card_type_not_allowed'    => self::CATEGORY_CARD_DATA,
        'cc_rejected_insufficient_amount'      => self::CATEGORY_INSUFFICIENT_AMOUNT,
        'cc_rejected_card_disabled'            => self::CATEGORY_CARD_DISABLED,
        'cc_rejected_high_risk'                => self::CATEGORY_HIGH_RISK,
        'cc_rejected_blacklist'                => self::CATEGORY_HIGH_RISK,
        'cc_rejected_call_for_authorize'       => self::CATEGORY_CALL_FOR_AUTHORIZE,
        'cc_rejected_duplicated_payment'       => self::CATEGORY_DUPLICATED_PAYMENT,
        'cc_rejected_invalid_installments'     => self::CATEGORY_INVALID_INSTALLMENTS,
        'cc_rejected_max_attempts'             => self::CATEGORY_MAX_ATTEMPTS,
        'cc_rejected_3ds_challenge'            => self::CATEGORY_CHALLENGE_3DS,
        'pending_challenge'                    => self::CATEGORY_CHALLENGE_3DS,
        // Serves fromResponse() (status:rejected/Flow-A) only. For the current HTTP-400 path
        // (Flow B / ApiException), fromOriginalMessage() resolves via CAUSE_KEYWORD_MAP['identification'].
        'invalid_user_identification_number'   => self::CATEGORY_IDENTIFICATION,
        'cc_rejected_card_error'               => self::CATEGORY_INTERNAL,
        'cc_rejected_other_reason'             => self::CATEGORY_INTERNAL,
    ];

    /**
     * cause[].code keyword (substring) => category.
     * Array order defines precedence: the first keyword that matches any cause wins.
     */
    private const CAUSE_KEYWORD_MAP = [
        'security_code'  => self::CATEGORY_SECURITY_CODE,
        'cvv'            => self::CATEGORY_SECURITY_CODE,
        'card_number'    => self::CATEGORY_CARD_NUMBER,
        'card_token'     => self::CATEGORY_CARD_TOKEN,
        'expiration'     => self::CATEGORY_CARD_EXPIRATION,
        'identification' => self::CATEGORY_IDENTIFICATION,
        'doc_number'     => self::CATEGORY_IDENTIFICATION,
    ];

    /**
     * Resolve the error category from an SDK original_message string.
     *
     * The Payment API embeds the status_detail as a double-quoted value inside the
     * original_message field (e.g. '400 BAD_REQUEST "cc_rejected_bad_filled_security_code"').
     * STATUS_DETAIL_MAP tokens are matched as quoted values ("token") to avoid:
     *   - false-positives from shorter/generic keys like 'pending_challenge' appearing
     *     as a substring in unrelated descriptive text;
     *   - implicit first-match wins on compound messages with multiple status codes.
     * CAUSE_KEYWORD_MAP keywords use unanchored substring match intentionally — cause
     * code descriptions embed the keyword (e.g. "security_code_length" → "security_code").
     * Never propagates free-text to the metric payload (CWE-532 / CWE-200).
     *
     * @param string $originalMessage
     * @return string one of the closed allowlist (never a raw API value)
     */
    public static function fromOriginalMessage(string $originalMessage): string
    {
        $originalMessage = strtolower($originalMessage);

        // 1) status_detail tokens — require the token to appear as a quoted value so
        // that generic keys (e.g. 'pending_challenge') cannot match as bare substrings
        // in descriptive text and compound messages have unambiguous precedence.
        foreach (self::STATUS_DETAIL_MAP as $statusDetail => $category) {
            if (strpos($originalMessage, '"' . $statusDetail . '"') !== false) {
                return $category;
            }
        }

        // 2) cause keyword tokens — unanchored substring match is intentional here;
        // cause code descriptions embed the keyword (e.g. "security_code_length" → "security_code").
        foreach (self::CAUSE_KEYWORD_MAP as $keyword => $category) {
            if (strpos($originalMessage, $keyword) !== false) {
                return $category;
            }
        }

        return self::CATEGORY_INTERNAL;
    }

    /**
     * Resolve the error category for a normalized MP API response array.
     *
     * @param array $response
     * @return string one of the closed allowlist (never a raw API value)
     */
    public static function fromResponse(array $response): string
    {
        // 1) status_detail — canonical, exact match.
        $statusDetail = isset($response['status_detail']) ? (string) $response['status_detail'] : '';
        if ($statusDetail !== '' && isset(self::STATUS_DETAIL_MAP[$statusDetail])) {
            return self::STATUS_DETAIL_MAP[$statusDetail];
        }

        // 2) cause[].code — keyword allowlist, highest-precedence keyword wins.
        if (!empty($response['cause']) && is_array($response['cause'])) {
            $category = self::categorizeCauses($response['cause']);
            if ($category !== null) {
                return $category;
            }
        }

        // 3) fallback.
        return self::CATEGORY_INTERNAL;
    }

    /**
     * Find the highest-precedence category among the given causes.
     *
     * @param array $causes
     * @return string|null category, or null if no keyword matched
     */
    private static function categorizeCauses(array $causes): ?string
    {
        foreach (self::CAUSE_KEYWORD_MAP as $keyword => $category) {
            foreach ($causes as $cause) {
                $code = (is_array($cause) && isset($cause['code'])) ? (string) $cause['code'] : '';
                if ($code !== '' && strpos($code, $keyword) !== false) {
                    return $category;
                }
            }
        }

        return null;
    }
}
