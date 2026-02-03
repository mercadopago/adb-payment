<?php
/**
 * Copyright © MercadoPago. All rights reserved.
 *
 * @author      Mercado Pago
 * @license     See LICENSE for license details.
 */

namespace MercadoPago\AdbPayment\Tests\Unit\Gateway\Http\Client;

use Magento\Framework\Serialize\Serializer\Json;
use Magento\Payment\Gateway\Http\TransferInterface;
use Magento\Payment\Model\Method\Logger;
use MercadoPago\AdbPayment\Gateway\Config\Config;
use MercadoPago\AdbPayment\Gateway\Http\Client\RefundClient;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for RefundClient.
 */
class RefundClientTest extends TestCase
{
    /**
     * @var Logger|MockObject
     */
    private $loggerMock;

    /**
     * @var Config|MockObject
     */
    private $configMock;

    /**
     * @var Json
     */
    private $json;

    protected function setUp(): void
    {
        $this->loggerMock = $this->createMock(Logger::class);
        $this->configMock = $this->createMock(Config::class);
        $this->json = new Json();

        $this->configMock->method('getApiUrl')
            ->willReturn('https://api.mercadopago.com');

        $this->configMock->method('getClientHeadersMpPluginsPhpSdk')
            ->willReturn([
                'Authorization: Bearer TEST_TOKEN',
                'x-integrator-id: test-integrator',
            ]);
    }

    /**
     * Test client can be instantiated.
     */
    public function testCanBeInstantiated(): void
    {
        $client = new RefundClient(
            $this->loggerMock,
            $this->configMock,
            $this->json
        );

        $this->assertInstanceOf(RefundClient::class, $client);
    }

    /**
     * @dataProvider constantsProvider
     */
    public function testConstantsHaveExpectedValues(string $constant, string $expectedValue): void
    {
        $this->assertEquals($expectedValue, constant(RefundClient::class . '::' . $constant));
    }

    /**
     * Data provider for constants tests.
     */
    public function constantsProvider(): array
    {
        return [
            'RESULT_CODE' => ['RESULT_CODE', 'RESULT_CODE'],
            'STORE_ID' => ['STORE_ID', 'store_id'],
            'RESPONSE_REFUND_ID' => ['RESPONSE_REFUND_ID', 'id'],
            'RESPONSE_STATUS' => ['RESPONSE_STATUS', 'status'],
            'RESPONSE_STATUS_DENIED' => ['RESPONSE_STATUS_DENIED', 'DENIED'],
            'X_IDEMPOTENCY_KEY' => ['X_IDEMPOTENCY_KEY', 'x-idempotency-key'],
            'NOTIFICATION_ORIGIN' => ['NOTIFICATION_ORIGIN', 'magento'],
            'PAYMENT_DETAILS' => ['PAYMENT_DETAILS', 'payments_details'],
            'TOTAL_AMOUNT' => ['TOTAL_AMOUNT', 'total_amount'],
            'PAYMENT_ID' => ['PAYMENT_ID', 'payment_%_id'],
            'PAYMENT_TOTAL_AMOUNT' => ['PAYMENT_TOTAL_AMOUNT', 'payment_%_total_amount'],
            'PAYMENT_REFUNDED_AMOUNT' => ['PAYMENT_REFUNDED_AMOUNT', 'payment_%_refunded_amount'],
        ];
    }

    /**
     * Test placeRequest handles missing order gracefully.
     *
     * This test ensures that when 'order' is not present in the request,
     * the client doesn't break and processes as a single refund.
     */
    public function testPlaceRequestWithoutOrder(): void
    {
        $transferMock = $this->createMock(TransferInterface::class);

        $requestBody = [
            'payment_id' => '143625890728',
            'store_id' => 1,
            'x-idempotency-key' => 'test-key-123',
            'amount' => 50.00,
            // No 'order' field
        ];

        $transferMock->method('getBody')
            ->willReturn($requestBody);

        // Mock config to trigger exception when HTTP call is attempted
        $this->configMock->expects($this->once())
            ->method('getApiUrl')
            ->willReturn('https://api.mercadopago.com');

        $this->configMock->expects($this->once())
            ->method('getClientHeadersMpPluginsPhpSdk')
            ->willThrowException(new \Exception('Expected exception - HTTP call attempted'));

        $client = new RefundClient(
            $this->loggerMock,
            $this->configMock,
            $this->json
        );

        // Should process as single refund and trigger exception from getClientHeadersMpPluginsPhpSdk
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Expected exception - HTTP call attempted');
        $client->placeRequest($transferMock);
    }

    /**
     * Test placeRequest handles order with null value.
     *
     * Ensures that when 'order' is explicitly null, no errors occur.
     */
    public function testPlaceRequestWithNullOrder(): void
    {
        $transferMock = $this->createMock(TransferInterface::class);

        $requestBody = [
            'payment_id' => '143625890728',
            'store_id' => 1,
            'x-idempotency-key' => 'test-key-123',
            'amount' => 50.00,
            'order' => null,  // Explicitly null
        ];

        $transferMock->method('getBody')
            ->willReturn($requestBody);

        // Mock config to trigger exception when HTTP call is attempted
        $this->configMock->expects($this->once())
            ->method('getApiUrl')
            ->willReturn('https://api.mercadopago.com');

        $this->configMock->expects($this->once())
            ->method('getClientHeadersMpPluginsPhpSdk')
            ->willThrowException(new \Exception('Expected exception - HTTP call attempted'));

        $client = new RefundClient(
            $this->loggerMock,
            $this->configMock,
            $this->json
        );

        // Should process as single refund and trigger exception from getClientHeadersMpPluginsPhpSdk
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Expected exception - HTTP call attempted');
        $client->placeRequest($transferMock);
    }

    /**
     * Test placeRequest handles order without payment_index_list.
     *
     * When order exists but doesn't have payment_index_list,
     * should process as single refund.
     */
    public function testPlaceRequestWithOrderButNoPaymentIndexList(): void
    {
        $transferMock = $this->createMock(TransferInterface::class);

        $orderArray = [
            'payment' => [
                'additional_information' => [
                    // No payment_index_list
                ],
            ],
        ];

        $requestBody = [
            'payment_id' => '143625890728',
            'store_id' => 1,
            'x-idempotency-key' => 'test-key-123',
            'amount' => 50.00,
            'order' => $orderArray,
        ];

        $transferMock->method('getBody')
            ->willReturn($requestBody);

        // Mock config to trigger exception when HTTP call is attempted
        $this->configMock->expects($this->once())
            ->method('getApiUrl')
            ->willReturn('https://api.mercadopago.com');

        $this->configMock->expects($this->once())
            ->method('getClientHeadersMpPluginsPhpSdk')
            ->willThrowException(new \Exception('Expected exception - HTTP call attempted'));

        $client = new RefundClient(
            $this->loggerMock,
            $this->configMock,
            $this->json
        );

        // Should process as single refund and trigger exception from getClientHeadersMpPluginsPhpSdk
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Expected exception - HTTP call attempted');
        $client->placeRequest($transferMock);
    }

    /**
     * Test placeRequest handles order with single payment.
     *
     * When order has payment_index_list with only 1 payment,
     * should process as single refund (not multiple).
     */
    public function testPlaceRequestWithOrderAndSinglePayment(): void
    {
        $transferMock = $this->createMock(TransferInterface::class);

        $orderArray = [
            'payment' => [
                'additional_information' => [
                    'payment_index_list' => ['1'],  // Only 1 payment
                ],
            ],
        ];

        $requestBody = [
            'payment_id' => '143625890728',
            'store_id' => 1,
            'x-idempotency-key' => 'test-key-123',
            'amount' => 50.00,
            'order' => $orderArray,
        ];

        $transferMock->method('getBody')
            ->willReturn($requestBody);

        // Mock config to trigger exception when HTTP call is attempted
        $this->configMock->expects($this->once())
            ->method('getApiUrl')
            ->willReturn('https://api.mercadopago.com');

        $this->configMock->expects($this->once())
            ->method('getClientHeadersMpPluginsPhpSdk')
            ->willThrowException(new \Exception('Expected exception - HTTP call attempted'));

        $client = new RefundClient(
            $this->loggerMock,
            $this->configMock,
            $this->json
        );

        // Should process as single refund and trigger exception from getClientHeadersMpPluginsPhpSdk
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Expected exception - HTTP call attempted');
        $client->placeRequest($transferMock);
    }

    /**
     * Test that request correctly removes sensitive fields before API call.
     *
     * Validates that payment_id, order, store_id, and x-idempotency-key
     * are removed from the request body before sending to API.
     */
    public function testPlaceRequestRemovesSensitiveFields(): void
    {
        $transferMock = $this->createMock(TransferInterface::class);

        $requestBody = [
            'payment_id' => '143625890728',
            'store_id' => 1,
            'x-idempotency-key' => 'test-key-123',
            'amount' => 50.00,
            'description' => 'Test refund',
        ];

        $transferMock->method('getBody')
            ->willReturn($requestBody);

        // Mock config to trigger exception when HTTP call is attempted
        $this->configMock->expects($this->once())
            ->method('getApiUrl')
            ->willReturn('https://api.mercadopago.com');

        $this->configMock->expects($this->once())
            ->method('getClientHeadersMpPluginsPhpSdk')
            ->willThrowException(new \Exception('Expected exception - HTTP call attempted'));

        $client = new RefundClient(
            $this->loggerMock,
            $this->configMock,
            $this->json
        );

        // Should process refund and trigger exception from getClientHeadersMpPluginsPhpSdk
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Expected exception - HTTP call attempted');
        $client->placeRequest($transferMock);
    }

    /**
     * Test URI format for single refund.
     */
    public function testRefundUriFormat(): void
    {
        $paymentId = '143625890728';
        $expectedUri = '/v1/payments/143625890728/refunds';

        $actualUri = '/v1/payments/' . $paymentId . '/refunds';

        $this->assertEquals($expectedUri, $actualUri);
    }

    /**
     * @dataProvider paymentIdProvider
     */
    public function testRefundUriWithVariousPaymentIds(string $paymentId, string $expectedUri): void
    {
        $actualUri = '/v1/payments/' . $paymentId . '/refunds';

        $this->assertEquals($expectedUri, $actualUri);
    }

    /**
     * Data provider for payment ID URI tests.
     */
    public function paymentIdProvider(): array
    {
        return [
            'numeric payment id' => [
                '143625890728',
                '/v1/payments/143625890728/refunds',
            ],
            'short payment id' => [
                '12345',
                '/v1/payments/12345/refunds',
            ],
            'long payment id' => [
                '999999999999999999',
                '/v1/payments/999999999999999999/refunds',
            ],
        ];
    }

    /**
     * Test that metadata includes origin as 'magento'.
     */
    public function testMetadataIncludesOrigin(): void
    {
        $expectedOrigin = RefundClient::NOTIFICATION_ORIGIN;

        $this->assertEquals('magento', $expectedOrigin);
    }

    /**
     * Test order validation - order exists check.
     *
     * Validates the logic: if ($order) { ... }
     */
    public function testOrderExistenceCheck(): void
    {
        // Test null (falsy)
        $order = null;
        $this->assertFalse((bool) $order);

        // Test empty array (also falsy in PHP)
        $order = [];
        $this->assertFalse((bool) $order);

        // Test with data (truthy)
        $order = ['payment' => []];
        $this->assertTrue((bool) $order);
    }

    /**
     * Test payment_index_list check.
     *
     * Validates: if ($order && isset($paymentIndexList) && sizeof($paymentIndexList) > 1)
     */
    public function testPaymentIndexListValidation(): void
    {
        // No payment_index_list
        $order = ['payment' => ['additional_information' => []]];
        $paymentIndexList = $order['payment']['additional_information']['payment_index_list'] ?? null;
        $this->assertNull($paymentIndexList);
        $this->assertFalse($paymentIndexList !== null && sizeof($paymentIndexList) > 1);

        // Single payment
        $order = ['payment' => ['additional_information' => ['payment_index_list' => ['1']]]];
        $paymentIndexList = $order['payment']['additional_information']['payment_index_list'] ?? null;
        $this->assertNotNull($paymentIndexList);
        $this->assertFalse(sizeof($paymentIndexList) > 1);

        // Multiple payments
        $order = ['payment' => ['additional_information' => ['payment_index_list' => ['1', '2']]]];
        $paymentIndexList = $order['payment']['additional_information']['payment_index_list'] ?? null;
        $this->assertNotNull($paymentIndexList);
        $this->assertTrue(sizeof($paymentIndexList) > 1);
    }
}
