<?php
/**
 * Copyright © MercadoPago. All rights reserved.
 *
 * @author      Mercado Pago
 * @license     See LICENSE for license details.
 */

namespace MercadoPago\AdbPayment\Tests\Unit\Helper;

use MercadoPago\AdbPayment\Helper\ApiErrorCategoryMapper;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for ApiErrorCategoryMapper helper.
 *
 * Guards two invariants:
 *  - categorization precedence (status_detail > cause[].code > internal);
 *  - closed allowlist: the return is NEVER a raw API string (SR-1 / CWE-532 / CWE-200).
 */
class ApiErrorCategoryMapperTest extends TestCase
{
    /**
     * @dataProvider fromResponseProvider
     */
    public function testFromResponse(array $response, string $expectedCategory): void
    {
        $this->assertSame($expectedCategory, ApiErrorCategoryMapper::fromResponse($response));
    }

    /**
     * Truth table for fromResponse().
     */
    public function fromResponseProvider(): array
    {
        return [
            // 1) status_detail — exact match (canonical source).
            'status_detail security_code' => [
                ['status_detail' => 'cc_rejected_bad_filled_security_code'],
                'security_code',
            ],
            'status_detail card_number' => [
                ['status_detail' => 'cc_rejected_bad_filled_card_number'],
                'card_number',
            ],
            'status_detail card_expiration' => [
                ['status_detail' => 'cc_rejected_bad_filled_date'],
                'card_expiration',
            ],
            'status_detail card_data (bad_filled_other)' => [
                ['status_detail' => 'cc_rejected_bad_filled_other'],
                'card_data',
            ],
            'status_detail card_data (card_type_not_allowed)' => [
                ['status_detail' => 'cc_rejected_card_type_not_allowed'],
                'card_data',
            ],
            'status_detail insufficient_amount' => [
                ['status_detail' => 'cc_rejected_insufficient_amount'],
                'insufficient_amount',
            ],
            'status_detail card_disabled' => [
                ['status_detail' => 'cc_rejected_card_disabled'],
                'card_disabled',
            ],
            'status_detail high_risk' => [
                ['status_detail' => 'cc_rejected_high_risk'],
                'high_risk',
            ],
            'status_detail high_risk (blacklist)' => [
                ['status_detail' => 'cc_rejected_blacklist'],
                'high_risk',
            ],
            'status_detail call_for_authorize' => [
                ['status_detail' => 'cc_rejected_call_for_authorize'],
                'call_for_authorize',
            ],
            'status_detail duplicated_payment' => [
                ['status_detail' => 'cc_rejected_duplicated_payment'],
                'duplicated_payment',
            ],
            'status_detail invalid_installments' => [
                ['status_detail' => 'cc_rejected_invalid_installments'],
                'invalid_installments',
            ],
            'status_detail max_attempts' => [
                ['status_detail' => 'cc_rejected_max_attempts'],
                'max_attempts',
            ],
            'status_detail challenge_3ds' => [
                ['status_detail' => 'cc_rejected_3ds_challenge'],
                'challenge_3ds',
            ],
            'status_detail challenge_3ds (pending_challenge)' => [
                ['status_detail' => 'pending_challenge'],
                'challenge_3ds',
            ],
            'status_detail invalid_user_identification_number' => [
                ['status_detail' => 'invalid_user_identification_number'],
                'identification',
            ],
            'status_detail card_error maps to internal explicitly' => [
                ['status_detail' => 'cc_rejected_card_error'],
                'internal',
            ],
            'status_detail other_reason maps to internal explicitly' => [
                ['status_detail' => 'cc_rejected_other_reason'],
                'internal',
            ],
            'status_detail unknown falls through to internal' => [
                ['status_detail' => 'algo_desconhecido'],
                'internal',
            ],

            // 2) cause[].code — keyword/substring match.
            'cause security_code' => [
                ['cause' => [['code' => 'security_code_length']]],
                'security_code',
            ],
            'cause card_token' => [
                ['cause' => [['code' => 'card_token_id_invalid']]],
                'card_token',
            ],
            'cause identification' => [
                ['cause' => [['code' => 'identification_number_invalid']]],
                'identification',
            ],
            'cause unknown code falls to internal' => [
                ['cause' => [['code' => 'xyz_unknown']]],
                'internal',
            ],

            // Precedence.
            'status_detail wins over cause' => [
                [
                    'status_detail' => 'cc_rejected_bad_filled_security_code',
                    'cause' => [['code' => 'card_number']],
                ],
                'security_code',
            ],
            'keyword precedence among causes (security_code before card_number)' => [
                [
                    'cause' => [
                        ['code' => 'card_number_invalid'],
                        ['code' => 'security_code_length'],
                    ],
                ],
                'security_code',
            ],

            // Edge cases → internal.
            'empty response' => [
                [],
                'internal',
            ],
            'cause not an array' => [
                ['cause' => 'not-an-array'],
                'internal',
            ],
            'cause item without code' => [
                ['cause' => [['description' => 'x']]],
                'internal',
            ],
            'empty status_detail string' => [
                ['status_detail' => ''],
                'internal',
            ],
        ];
    }

    /**
     * @dataProvider fromOriginalMessageProvider
     */
    public function testFromOriginalMessage(string $originalMessage, string $expectedCategory): void
    {
        $this->assertSame($expectedCategory, ApiErrorCategoryMapper::fromOriginalMessage($originalMessage));
    }

    /**
     * Truth table for fromOriginalMessage().
     */
    public function fromOriginalMessageProvider(): array
    {
        return [
            // STATUS_DETAIL_MAP tokens embedded in the string.
            'status_detail token security_code in original_message' => [
                '400 BAD_REQUEST "cc_rejected_bad_filled_security_code"',
                'security_code',
            ],
            'status_detail token card_number in original_message' => [
                '400 BAD_REQUEST "cc_rejected_bad_filled_card_number"',
                'card_number',
            ],
            'status_detail token card_expiration in original_message' => [
                '400 BAD_REQUEST "cc_rejected_bad_filled_date"',
                'card_expiration',
            ],
            'status_detail token high_risk in original_message' => [
                '400 BAD_REQUEST "cc_rejected_high_risk"',
                'high_risk',
            ],
            'status_detail token challenge_3ds in original_message' => [
                '400 BAD_REQUEST "cc_rejected_3ds_challenge"',
                'challenge_3ds',
            ],
            // cc_rejected_card_error maps to internal even when found as token.
            'status_detail card_error maps to internal even as token' => [
                '400 BAD_REQUEST "cc_rejected_card_error"',
                'internal',
            ],

            // CAUSE_KEYWORD_MAP substring scan — all 8 keywords.
            'cause keyword security_code substring' => [
                'Invalid security_code_length',
                'security_code',
            ],
            'cause keyword cvv substring' => [
                'Invalid cvv format',
                'security_code',
            ],
            'cause keyword card_number substring' => [
                'card_number_invalid detected',
                'card_number',
            ],
            'cause keyword card_token substring' => [
                'card_token_id expired',
                'card_token',
            ],
            // 'token' keyword was removed (too generic — "identification_token" would
            // have matched card_token instead of identification). 'card_token' covers
            // all real cause codes that contain the card_token concept.
            'cause keyword token_only does not match card_token without prefix' => [
                'token_not_found',
                'internal',
            ],
            'cause keyword expiration substring' => [
                'expiration_date_invalid',
                'card_expiration',
            ],
            'cause keyword identification substring' => [
                'identification_number_invalid',
                'identification',
            ],
            'cause keyword doc_number substring' => [
                'doc_number_required',
                'identification',
            ],

            // Precedence: STATUS_DETAIL token beats CAUSE keyword in the same string.
            'status_detail token beats cause keyword in same string' => [
                '400 "cc_rejected_bad_filled_security_code" card_number_invalid',
                'security_code',
            ],

            // Quoted-match guard: bare (unquoted) status_detail token must NOT match.
            'pending_challenge as bare substring does not match' => [
                'This payment is in pending_challenge state and awaits action',
                'internal',
            ],
            'status_detail only matches when surrounded by quotes' => [
                // cc_rejected_duplicated_payment contains no CAUSE_KEYWORD_MAP keywords,
                // so only the quoted STATUS_DETAIL match would find it.
                'cc_rejected_duplicated_payment appears in description only',
                'internal',
            ],

            // Case-insensitive matching — API casing variations must not fall through to internal.
            'uppercase CVV keyword is matched case-insensitively' => [
                'Invalid CVV format',
                'security_code',
            ],
            'mixed-case Security_Code keyword is matched case-insensitively' => [
                'Invalid Security_Code_Length',
                'security_code',
            ],
            'uppercase status_detail token is matched case-insensitively' => [
                '400 BAD_REQUEST "CC_REJECTED_BAD_FILLED_SECURITY_CODE"',
                'security_code',
            ],

            // Fallback.
            'unknown token returns internal' => [
                '400 BAD_REQUEST "some_unknown_code"',
                'internal',
            ],
            'empty string returns internal' => [
                '',
                'internal',
            ],
        ];
    }

    /**
     * All 15 CATEGORY_* constants exist and are non-empty strings.
     */
    public function testAllCategoryConstantsAreDefined(): void
    {
        $constants = [
            ApiErrorCategoryMapper::CATEGORY_INTERNAL,
            ApiErrorCategoryMapper::CATEGORY_SECURITY_CODE,
            ApiErrorCategoryMapper::CATEGORY_CARD_NUMBER,
            ApiErrorCategoryMapper::CATEGORY_CARD_EXPIRATION,
            ApiErrorCategoryMapper::CATEGORY_CARD_DATA,
            ApiErrorCategoryMapper::CATEGORY_CARD_TOKEN,
            ApiErrorCategoryMapper::CATEGORY_IDENTIFICATION,
            ApiErrorCategoryMapper::CATEGORY_INSUFFICIENT_AMOUNT,
            ApiErrorCategoryMapper::CATEGORY_CARD_DISABLED,
            ApiErrorCategoryMapper::CATEGORY_HIGH_RISK,
            ApiErrorCategoryMapper::CATEGORY_CALL_FOR_AUTHORIZE,
            ApiErrorCategoryMapper::CATEGORY_DUPLICATED_PAYMENT,
            ApiErrorCategoryMapper::CATEGORY_INVALID_INSTALLMENTS,
            ApiErrorCategoryMapper::CATEGORY_MAX_ATTEMPTS,
            ApiErrorCategoryMapper::CATEGORY_CHALLENGE_3DS,
        ];

        foreach ($constants as $constant) {
            $this->assertIsString($constant);
            $this->assertNotEmpty($constant);
        }

        $this->assertCount(15, array_unique($constants), 'All 15 categories must be distinct');
    }

    /**
     * SR-1 guard: mapper never returns a raw free-text message.
     */
    public function testNeverReturnsRawMessage(): void
    {
        $response = [
            'status' => 400,
            'original_message' => 'Sensitive free-text 4111111111111111',
            'message' => 'Sensitive free-text',
            'cause' => [['code' => 'security_code_length', 'description' => 'CVV inválido']],
        ];

        $category = ApiErrorCategoryMapper::fromResponse($response);

        $this->assertSame('security_code', $category);
        $this->assertStringNotContainsString('Sensitive', $category);
        $this->assertStringNotContainsString('4111', $category);
    }

    /**
     * Internal fallback constant value.
     */
    public function testInternalConstant(): void
    {
        $this->assertSame('internal', ApiErrorCategoryMapper::CATEGORY_INTERNAL);
    }
}
