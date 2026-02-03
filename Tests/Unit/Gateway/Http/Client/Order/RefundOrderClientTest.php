<?php
/**
 * Copyright © MercadoPago. All rights reserved.
 *
 * @author      Mercado Pago
 * @license     See LICENSE for license details.
 */

namespace MercadoPago\AdbPayment\Tests\Unit\Gateway\Http\Client\Order;

use Magento\Framework\Serialize\Serializer\Json;
use Magento\Payment\Gateway\Http\TransferBuilder;
use Magento\Payment\Gateway\Http\TransferInterface;
use Magento\Payment\Model\Method\Logger;
use MercadoPago\AdbPayment\Gateway\Config\Config;
use MercadoPago\AdbPayment\Gateway\Http\Client\Order\RefundOrderClient;
use MercadoPago\AdbPayment\Gateway\Http\Client\RefundClient;
use MercadoPago\AdbPayment\Helper\ApiTypeDetector;
use MercadoPago\AdbPayment\Model\Metrics\MetricsClient;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for RefundOrderClient.
 */
class RefundOrderClientTest extends TestCase
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

    /**
     * @var MetricsClient|MockObject
     */
    private $metricsClientMock;

    /**
     * @var RefundClient|MockObject
     */
    private $refundClientMock;

    /**
     * @var ApiTypeDetector|MockObject
     */
    private $apiTypeDetectorMock;

    /**
     * @var TransferBuilder|MockObject
     */
    private $transferBuilderMock;

    protected function setUp(): void
    {
        $this->loggerMock = $this->createMock(Logger::class);
        $this->configMock = $this->createMock(Config::class);
        $this->json = new Json();
        $this->metricsClientMock = $this->createMock(MetricsClient::class);
        $this->refundClientMock = $this->createMock(RefundClient::class);
        $this->apiTypeDetectorMock = $this->createMock(ApiTypeDetector::class);
        $this->transferBuilderMock = $this->createMock(TransferBuilder::class);

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
        $client = new RefundOrderClient(
            $this->loggerMock,
            $this->configMock,
            $this->json,
            $this->metricsClientMock,
            $this->refundClientMock,
            $this->apiTypeDetectorMock,
            $this->transferBuilderMock
        );

        $this->assertInstanceOf(RefundOrderClient::class, $client);
    }

    /**
     * @dataProvider constantsProvider
     */
    public function testConstantsHaveExpectedValues(string $constant, string $expectedValue): void
    {
        $this->assertEquals($expectedValue, constant(RefundOrderClient::class . '::' . $constant));
    }

    /**
     * Data provider for constants tests.
     */
    public function constantsProvider(): array
    {
        return [
            'RESULT_CODE' => ['RESULT_CODE', 'RESULT_CODE'],
            'PAYMENTS' => ['PAYMENTS', 'payments'],
            'STORE_ID' => ['STORE_ID', 'store_id'],
            'MP_ORDER_ID' => ['MP_ORDER_ID', 'mp_order_id'],
            'MP_PAYMENT_ID_ORDER' => ['MP_PAYMENT_ID_ORDER', 'mp_payment_id_order'],
            'PAYMENT_ID_ORDER' => ['PAYMENT_ID_ORDER', 'id'],
            'RESPONSE_REFUND_PAYMENT_ID' => ['RESPONSE_REFUND_PAYMENT_ID', 'refund_payment_id'],
            'RESPONSE_REFUND_ORDER_ID' => ['RESPONSE_REFUND_ORDER_ID', 'refund_order_id'],
            'RESPONSE_REFERENCE' => ['RESPONSE_REFERENCE', 'reference'],
            'RESPONSE_STATUS' => ['RESPONSE_STATUS', 'status'],
            'RESPONSE_STATUS_FAILED' => ['RESPONSE_STATUS_FAILED', 'FAILED'],
            'X_IDEMPOTENCY_KEY' => ['X_IDEMPOTENCY_KEY', 'x-idempotency-key'],
            'CONTENT_TYPE_JSON' => ['CONTENT_TYPE_JSON', 'Content-Type: application/json'],
            'ORDERS_REFUND_URI' => ['ORDERS_REFUND_URI', '/plugins-platforms/v1/orders/%s/refund'],
            'REFUND_AMOUNT' => ['REFUND_AMOUNT', 'amount'],
            'REFUND_KEY' => ['REFUND_KEY', 'refund_key'],
            'IS_PARTIAL_REFUND' => ['IS_PARTIAL_REFUND', 'is_partial_refund'],
        ];
    }

    /**
     * @dataProvider sanitizeRequestProvider
     */
    public function testSanitizeRequestBehavior(array $request, bool $expectEmpty, ?array $expectedStructure): void
    {
        $client = new PublicRefundOrderClient(
            $this->loggerMock,
            $this->configMock,
            $this->json,
            $this->metricsClientMock,
            $this->refundClientMock,
            $this->apiTypeDetectorMock,
            $this->transferBuilderMock
        );

        $result = $client->sanitizeRequest($request);

        if ($expectEmpty) {
            $this->assertEmpty($result);
        } else {
            $this->assertArrayHasKey('payments', $result);
            $this->assertEquals($expectedStructure['id'], $result['payments'][0]['id']);
            $this->assertEquals($expectedStructure['amount'], $result['payments'][0]['amount']);
        }
    }

    /**
     * Data provider for sanitize request tests.
     */
    public function sanitizeRequestProvider(): array
    {
        return [
            'total refund returns empty' => [
                ['mp_order_id' => 'PPORD123', 'amount' => '100.00', 'is_partial_refund' => false],
                true,
                null,
            ],
            'missing partial flag defaults to total' => [
                ['mp_order_id' => 'PPORD123', 'amount' => '100.00'],
                true,
                null,
            ],
            'partial refund returns structured payload' => [
                ['mp_order_id' => 'PPORD123', 'mp_payment_id_order' => 'PPPAY456', 'amount' => '50.00', 'is_partial_refund' => true],
                false,
                ['id' => 'PPPAY456', 'amount' => '50.00'],
            ],
        ];
    }

    /**
     * @dataProvider normalizeRefundResponseProvider
     */
    public function testNormalizeRefundResponseBehavior($apiResponse, int $expectedResultCode): void
    {
        $client = new PublicRefundOrderClient(
            $this->loggerMock,
            $this->configMock,
            $this->json,
            $this->metricsClientMock,
            $this->refundClientMock,
            $this->apiTypeDetectorMock,
            $this->transferBuilderMock
        );

        $result = $client->normalizeRefundResponse($apiResponse);

        $this->assertEquals($expectedResultCode, $result['RESULT_CODE']);
        $this->assertIsArray($result);
    }

    /**
     * Data provider for normalize refund response tests.
     */
    public function normalizeRefundResponseProvider(): array
    {
        return [
            'success with both ids' => [
                ['payments' => [['reference' => ['refund_payment_id' => 'R1', 'refund_order_id' => 'R2'], 'status' => 'processed']]],
                1,
            ],
            'success with refund_payment_id only' => [
                ['payments' => [['reference' => ['refund_payment_id' => 'R1'], 'status' => 'processed']]],
                1,
            ],
            'success with refund_order_id only' => [
                ['payments' => [['reference' => ['refund_order_id' => 'R2'], 'status' => 'processed']]],
                1,
            ],
            'failed status returns 0' => [
                ['payments' => [['reference' => ['refund_payment_id' => 'R1'], 'status' => 'FAILED']]],
                0,
            ],
            'missing refund id returns 0' => [
                ['payments' => [['reference' => [], 'status' => 'processed']]],
                0,
            ],
            'empty payments returns 0' => [
                ['payments' => []],
                0,
            ],
            'empty response returns 0' => [
                [],
                0,
            ],
            'null response returns 0' => [
                null,
                0,
            ],
        ];
    }

    /**
     * @dataProvider buildClientHeadersProvider
     */
    public function testBuildClientHeadersBehavior(?string $idempotencyKey, bool $shouldContainIdempotency): void
    {
        $client = new PublicRefundOrderClient(
            $this->loggerMock,
            $this->configMock,
            $this->json,
            $this->metricsClientMock,
            $this->refundClientMock,
            $this->apiTypeDetectorMock,
            $this->transferBuilderMock
        );

        $result = $client->buildClientHeaders(1, $idempotencyKey);

        $this->assertContains('Authorization: Bearer TEST_TOKEN', $result);
        $this->assertContains('Content-Type: application/json', $result);

        $containsIdempotency = false;
        foreach ($result as $header) {
            if (strpos($header, 'x-idempotency-key') !== false) {
                $containsIdempotency = true;
                if ($idempotencyKey) {
                    $this->assertStringContainsString($idempotencyKey, $header);
                }
                break;
            }
        }

        $this->assertEquals($shouldContainIdempotency, $containsIdempotency);
    }

    /**
     * Test placeRequest calls ApiTypeDetector with correct parameters.
     *
     * Note: Integration tests with actual HTTP clients are avoided here
     * to prevent class_alias conflicts with other test classes.
     */
    public function testPlaceRequestCallsApiTypeDetector(): void
    {
        $transferMock = $this->createMock(TransferInterface::class);

        $requestBody = [
            'mp_order_id' => '143625890728',
            'mp_payment_id' => '143625890728',
            'mp_payment_id_order' => null,
            'store_id' => 1,
            'amount' => 50.00,
            'refund_key' => 'refund-key-123',
        ];

        $transferMock->method('getBody')
            ->willReturn($requestBody);

        // Verify ApiTypeDetector is called with correct array structure
        $this->apiTypeDetectorMock->expects($this->once())
            ->method('isOrderApiFromRequest')
            ->with($this->callback(function ($additionalInfo) {
                return isset($additionalInfo['mp_order_id'])
                    && array_key_exists('mp_payment_id_order', $additionalInfo)
                    && $additionalInfo['mp_order_id'] === '143625890728'
                    && $additionalInfo['mp_payment_id_order'] === null;
            }))
            ->willReturn(false);  // Use Payment API

        // Mock TransferBuilder and RefundClient
        $newTransferMock = $this->createMock(TransferInterface::class);
        $this->transferBuilderMock->method('setBody')->willReturnSelf();
        $this->transferBuilderMock->method('setMethod')->willReturnSelf();
        $this->transferBuilderMock->method('build')->willReturn($newTransferMock);

        $this->refundClientMock->expects($this->once())
            ->method('placeRequest')
            ->willReturn(['RESULT_CODE' => 1]);

        $client = new RefundOrderClient(
            $this->loggerMock,
            $this->configMock,
            $this->json,
            $this->metricsClientMock,
            $this->refundClientMock,
            $this->apiTypeDetectorMock,
            $this->transferBuilderMock
        );

        $result = $client->placeRequest($transferMock);
        $this->assertEquals(1, $result['RESULT_CODE']);
    }

    /**
     * Test preparePaymentApiRequest method.
     */
    public function testPreparePaymentApiRequest(): void
    {
        $client = new PublicRefundOrderClient(
            $this->loggerMock,
            $this->configMock,
            $this->json,
            $this->metricsClientMock,
            $this->refundClientMock,
            $this->apiTypeDetectorMock,
            $this->transferBuilderMock
        );

        $request = [
            'mp_order_id' => '143625890728',
            'mp_payment_id_order' => 'PPPAY123',
            'refund_key' => 'key-123',
            'is_partial_refund' => true,
            'amount' => 50.00,
            'store_id' => 1,
        ];

        $result = $client->preparePaymentApiRequest($request, 'idempotency-key-123');

        // Should rename mp_order_id to payment_id
        $this->assertArrayHasKey('payment_id', $result);
        $this->assertEquals('143625890728', $result['payment_id']);

        // Should add idempotency key
        $this->assertArrayHasKey('x-idempotency-key', $result);
        $this->assertEquals('idempotency-key-123', $result['x-idempotency-key']);

        // Should remove Order API specific fields
        $this->assertArrayNotHasKey('mp_order_id', $result);
        $this->assertArrayNotHasKey('refund_key', $result);
        $this->assertArrayNotHasKey('mp_payment_id_order', $result);
        $this->assertArrayNotHasKey('is_partial_refund', $result);

        // Should keep common fields
        $this->assertArrayHasKey('amount', $result);
        $this->assertArrayHasKey('store_id', $result);
    }

    /**
     * Data provider for build client headers tests.
     */
    public function buildClientHeadersProvider(): array
    {
        return [
            'without idempotency key' => [null, false],
            'with empty idempotency key' => ['', false],
            'with idempotency key' => ['test-key-123', true],
        ];
    }
}
