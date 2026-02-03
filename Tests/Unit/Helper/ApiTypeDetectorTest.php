<?php
/**
 * Copyright © MercadoPago. All rights reserved.
 *
 * @author      Mercado Pago
 * @license     See LICENSE for license details.
 */

namespace MercadoPago\AdbPayment\Tests\Unit\Helper;

use MercadoPago\AdbPayment\Helper\ApiTypeDetector;
use PHPUnit\Framework\TestCase;

/**
 * ApiTypeDetector test case.
 */
class ApiTypeDetectorTest extends TestCase
{
    /**
     * @var ApiTypeDetector
     */
    private $detector;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->detector = new ApiTypeDetector();
    }

    // ========================================
    // Tests for detectByIdPattern method
    // ========================================

    /**
     * Test detectByIdPattern with Order API ID (PP...).
     */
    public function testDetectByIdPatternOrderApi(): void
    {
        $orderId = 'PPPAY71WFLIEBP0O7H4Q7QM0BQMF6I4';

        $result = $this->detector->detectByIdPattern($orderId);

        $this->assertEquals(ApiTypeDetector::TRANSACTION_TYPE_ORDER, $result);
    }

    /**
     * Test detectByIdPattern with Payment API ID (numeric).
     */
    public function testDetectByIdPatternPaymentApi(): void
    {
        $paymentId = '143625890728';

        $result = $this->detector->detectByIdPattern($paymentId);

        $this->assertEquals(ApiTypeDetector::TRANSACTION_TYPE_PAYMENT, $result);
    }

    /**
     * Test detectByIdPattern with null ID returns unknown.
     */
    public function testDetectByIdPatternNull(): void
    {
        $result = $this->detector->detectByIdPattern(null);

        $this->assertEquals(ApiTypeDetector::TRANSACTION_TYPE_UNKNOWN, $result);
    }

    /**
     * Test detectByIdPattern with empty string returns unknown.
     */
    public function testDetectByIdPatternEmptyString(): void
    {
        $result = $this->detector->detectByIdPattern('');

        $this->assertEquals(ApiTypeDetector::TRANSACTION_TYPE_UNKNOWN, $result);
    }

    /**
     * Test detectByIdPattern with invalid ID pattern returns unknown.
     */
    public function testDetectByIdPatternInvalid(): void
    {
        $result = $this->detector->detectByIdPattern('INVALID-ID-123');

        $this->assertEquals(ApiTypeDetector::TRANSACTION_TYPE_UNKNOWN, $result);
    }

    /**
     * Test multiple Order API ID patterns.
     *
     * @dataProvider orderApiIdProvider
     */
    public function testMultipleOrderApiIds(string $orderId): void
    {
        $this->assertTrue($this->detector->isOrderApiId($orderId));
        $this->assertEquals(
            ApiTypeDetector::TRANSACTION_TYPE_ORDER,
            $this->detector->detectByIdPattern($orderId)
        );
    }

    /**
     * Data provider for Order API IDs.
     */
    public function orderApiIdProvider(): array
    {
        return [
            ['PPPAY71WFLIEBP0O7H4Q7QM0BQMF6I4'],
            ['PPPAYABC123DEF456'],
            ['PPPAY000'],
            ['PPPAYZZZ999'],
        ];
    }

    /**
     * Test multiple Payment API ID patterns.
     *
     * @dataProvider paymentApiIdProvider
     */
    public function testMultiplePaymentApiIds(string $paymentId): void
    {
        $this->assertTrue($this->detector->isPaymentApiId($paymentId));
        $this->assertEquals(
            ApiTypeDetector::TRANSACTION_TYPE_PAYMENT,
            $this->detector->detectByIdPattern($paymentId)
        );
    }

    /**
     * Data provider for Payment API IDs.
     */
    public function paymentApiIdProvider(): array
    {
        return [
            ['143625890728'],
            ['12345678'],
            ['999999999999'],
            ['1'],
        ];
    }

    // ========================================
    // Tests for isOrderApiId / isPaymentApiId
    // ========================================

    /**
     * Test isOrderApiId with Order API ID.
     */
    public function testIsOrderApiId(): void
    {
        $this->assertTrue($this->detector->isOrderApiId('PPPAY71WFLIEBP0O7H4Q7QM0BQMF6I4'));
    }

    /**
     * Test isOrderApiId with Payment API ID returns false.
     */
    public function testIsOrderApiIdWithPaymentId(): void
    {
        $this->assertFalse($this->detector->isOrderApiId('143625890728'));
    }

    /**
     * Test isPaymentApiId with Payment API ID.
     */
    public function testIsPaymentApiId(): void
    {
        $this->assertTrue($this->detector->isPaymentApiId('143625890728'));
    }

    /**
     * Test isPaymentApiId with Order API ID returns false.
     */
    public function testIsPaymentApiIdWithOrderId(): void
    {
        $this->assertFalse($this->detector->isPaymentApiId('PPPAY71WFLIEBP0O7H4Q7QM0BQMF6I4'));
    }

    // ========================================
    // Tests for detectTransactionType method
    // Validation order:
    // 1. If ORDER_API_PAYMENT_ID_KEY exists → Order API
    // 2. If regex detects pattern → use detected type
    // 3. Fallback → Order API
    // ========================================

    /**
     * Test: mp_payment_id_order exists → Order API (early return).
     */
    public function testDetectTransactionTypeWithPaymentIdOrderReturnsOrder(): void
    {
        $additionalInfo = [
            'mp_order_id' => 'PPPAY71WFLIEBP0O7H4Q7QM0BQMF6I4',
            'mp_payment_id_order' => '87654321',
        ];

        $result = $this->detector->detectTransactionType($additionalInfo);

        $this->assertEquals(ApiTypeDetector::TRANSACTION_TYPE_ORDER, $result);
    }

    /**
     * Test: mp_payment_id_order exists (even with numeric mp_order_id) → Order API.
     */
    public function testDetectTransactionTypePaymentIdOrderExistsWithNumericOrderId(): void
    {
        $additionalInfo = [
            'mp_order_id' => '12345678',  // Numeric pattern (would be Payment)
            'mp_payment_id_order' => '87654321',  // But field exists → Order
        ];

        $result = $this->detector->detectTransactionType($additionalInfo);

        // Field exists → early return Order API
        $this->assertEquals(ApiTypeDetector::TRANSACTION_TYPE_ORDER, $result);
    }

    /**
     * Test: mp_payment_id_order empty + PP pattern → Order API (via regex).
     */
    public function testDetectTransactionTypeEmptyFieldWithPPPattern(): void
    {
        $additionalInfo = [
            'mp_order_id' => 'PPPAY71WFLIEBP0O7H4Q7QM0BQMF6I4',
            // mp_payment_id_order doesn't exist
        ];

        $result = $this->detector->detectTransactionType($additionalInfo);

        // Field empty → check regex → PP pattern → Order API
        $this->assertEquals(ApiTypeDetector::TRANSACTION_TYPE_ORDER, $result);
    }

    /**
     * Test: mp_payment_id_order empty + numeric pattern → Payment API (via regex).
     */
    public function testDetectTransactionTypeEmptyFieldWithNumericPattern(): void
    {
        $additionalInfo = [
            'mp_order_id' => '143625890728',
            // mp_payment_id_order doesn't exist
        ];

        $result = $this->detector->detectTransactionType($additionalInfo);

        // Field empty → check regex → numeric pattern → Payment API
        $this->assertEquals(ApiTypeDetector::TRANSACTION_TYPE_PAYMENT, $result);
    }

    /**
     * Test: mp_payment_id_order empty + unknown pattern → Order API (fallback).
     */
    public function testDetectTransactionTypeEmptyFieldWithUnknownPattern(): void
    {
        $additionalInfo = [
            'mp_order_id' => 'UNKNOWN-ID-FORMAT',
            // mp_payment_id_order doesn't exist
        ];

        $result = $this->detector->detectTransactionType($additionalInfo);

        // Field empty → check regex → unknown → fallback Order API
        $this->assertEquals(ApiTypeDetector::TRANSACTION_TYPE_ORDER, $result);
    }

    /**
     * Test: mp_payment_id_order empty + no mp_order_id → Order API (fallback).
     */
    public function testDetectTransactionTypeEmptyFieldNoOrderId(): void
    {
        $additionalInfo = [
            'mp_status' => 'approved',
            // No mp_order_id
            // No mp_payment_id_order
        ];

        $result = $this->detector->detectTransactionType($additionalInfo);

        // Field empty → check regex → null → unknown → fallback Order API
        $this->assertEquals(ApiTypeDetector::TRANSACTION_TYPE_ORDER, $result);
    }

    /**
     * Test: Empty additional information → Order API (fallback).
     */
    public function testDetectTransactionTypeEmptyArray(): void
    {
        $additionalInfo = [];

        $result = $this->detector->detectTransactionType($additionalInfo);

        // Field empty → check regex → null → unknown → fallback Order API
        $this->assertEquals(ApiTypeDetector::TRANSACTION_TYPE_ORDER, $result);
    }

    /**
     * Test: mp_payment_id_order is empty string → uses regex.
     */
    public function testDetectTransactionTypeEmptyStringPaymentIdOrder(): void
    {
        $additionalInfo = [
            'mp_order_id' => 'PPPAY71WFLIEBP0O7H4Q7QM0BQMF6I4',
            'mp_payment_id_order' => '',  // Empty string is falsy
        ];

        $result = $this->detector->detectTransactionType($additionalInfo);

        // Empty string → check regex → PP pattern → Order API
        $this->assertEquals(ApiTypeDetector::TRANSACTION_TYPE_ORDER, $result);
    }

    /**
     * Test: mp_payment_id_order is null → uses regex.
     */
    public function testDetectTransactionTypeNullPaymentIdOrder(): void
    {
        $additionalInfo = [
            'mp_order_id' => '143625890728',
            'mp_payment_id_order' => null,
        ];

        $result = $this->detector->detectTransactionType($additionalInfo);

        // Null → check regex → numeric pattern → Payment API
        $this->assertEquals(ApiTypeDetector::TRANSACTION_TYPE_PAYMENT, $result);
    }

    /**
     * Test: mp_payment_id_order is '0' (edge case - empty() returns true).
     */
    public function testDetectTransactionTypeZeroStringPaymentIdOrder(): void
    {
        $additionalInfo = [
            'mp_order_id' => 'PPPAY71WFLIEBP0O7H4Q7QM0BQMF6I4',
            'mp_payment_id_order' => '0',  // String '0' is considered empty
        ];

        $result = $this->detector->detectTransactionType($additionalInfo);

        // '0' is empty → check regex → PP pattern → Order API
        $this->assertEquals(ApiTypeDetector::TRANSACTION_TYPE_ORDER, $result);
    }

    // ========================================
    // Tests for isOrderApi / isPaymentApi
    // ========================================

    /**
     * Test isOrderApi returns true for Order API.
     */
    public function testIsOrderApiReturnsTrue(): void
    {
        $additionalInfo = [
            'mp_payment_id_order' => '87654321',
        ];

        $this->assertTrue($this->detector->isOrderApi($additionalInfo));
    }

    /**
     * Test isOrderApi returns false for Payment API.
     */
    public function testIsOrderApiReturnsFalse(): void
    {
        $additionalInfo = [
            'mp_order_id' => '143625890728',
            // No mp_payment_id_order
        ];

        $this->assertFalse($this->detector->isOrderApi($additionalInfo));
    }

    /**
     * Test isPaymentApi returns true for Payment API.
     */
    public function testIsPaymentApiReturnsTrue(): void
    {
        $additionalInfo = [
            'mp_order_id' => '143625890728',
            // No mp_payment_id_order
        ];

        $this->assertTrue($this->detector->isPaymentApi($additionalInfo));
    }

    /**
     * Test isPaymentApi returns false for Order API.
     */
    public function testIsPaymentApiReturnsFalse(): void
    {
        $additionalInfo = [
            'mp_payment_id_order' => '87654321',
        ];

        $this->assertFalse($this->detector->isPaymentApi($additionalInfo));
    }

    /**
     * Test isOrderApiFromRequest returns true for Order API with PP pattern.
     */
    public function testIsOrderApiFromRequestWithPPPattern(): void
    {
        $request = [
            'mp_order_id' => 'PPPAY71WFLIEBP0O7H4Q7QM0BQMF6I4',
        ];

        $this->assertTrue($this->detector->isOrderApiFromRequest($request));
    }

    /**
     * Test isOrderApiFromRequest returns true when mp_payment_id_order exists.
     */
    public function testIsOrderApiFromRequestWithPaymentIdOrder(): void
    {
        $request = [
            'mp_order_id' => '143625890728',  // Numeric (would be Payment)
            'mp_payment_id_order' => '87654321',  // But field exists → Order
        ];

        $this->assertTrue($this->detector->isOrderApiFromRequest($request));
    }

    /**
     * Test isOrderApiFromRequest returns false for Payment API with numeric pattern.
     */
    public function testIsOrderApiFromRequestWithNumericPattern(): void
    {
        $request = [
            'mp_order_id' => '143625890728',
            // No mp_payment_id_order
        ];

        $this->assertFalse($this->detector->isOrderApiFromRequest($request));
    }

    /**
     * Test isOrderApiFromRequest returns true for empty request (fallback to Order).
     */
    public function testIsOrderApiFromRequestWithEmptyRequest(): void
    {
        $request = [];

        // Empty request → unknown pattern → fallback to Order API
        $this->assertTrue($this->detector->isOrderApiFromRequest($request));
    }

    /**
     * Test isOrderApiFromRequest handles null values gracefully.
     */
    public function testIsOrderApiFromRequestWithNullValues(): void
    {
        $request = [
            'mp_order_id' => null,
            'mp_payment_id_order' => null,
        ];

        // Null values → unknown pattern → fallback to Order API
        $this->assertTrue($this->detector->isOrderApiFromRequest($request));
    }
}
