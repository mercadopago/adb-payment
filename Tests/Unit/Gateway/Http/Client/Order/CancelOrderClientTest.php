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
use MercadoPago\AdbPayment\Gateway\Http\Client\CancelPaymentClient;
use MercadoPago\AdbPayment\Gateway\Http\Client\Order\CancelOrderClient;
use MercadoPago\AdbPayment\Helper\ApiTypeDetector;
use MercadoPago\AdbPayment\Model\Metrics\MetricsClient;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for CancelOrderClient.
 */
class CancelOrderClientTest extends TestCase
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
     * @var CancelPaymentClient|MockObject
     */
    private $cancelPaymentClientMock;

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
        $this->cancelPaymentClientMock = $this->createMock(CancelPaymentClient::class);
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
        $client = new CancelOrderClient(
            $this->loggerMock,
            $this->configMock,
            $this->json,
            $this->metricsClientMock,
            $this->cancelPaymentClientMock,
            $this->apiTypeDetectorMock,
            $this->transferBuilderMock
        );

        $this->assertInstanceOf(CancelOrderClient::class, $client);
    }

    /**
     * @dataProvider constantsProvider
     */
    public function testConstantsHaveExpectedValues(string $constant, string $expectedValue): void
    {
        $this->assertEquals($expectedValue, constant(CancelOrderClient::class . '::' . $constant));
    }

    /**
     * Data provider for constants tests.
     */
    public function constantsProvider(): array
    {
        return [
            'RESULT_CODE' => ['RESULT_CODE', 'RESULT_CODE'],
            'STORE_ID' => ['STORE_ID', 'store_id'],
            'MP_ORDER_ID' => ['MP_ORDER_ID', 'mp_order_id'],
            'RESPONSE_STATUS' => ['RESPONSE_STATUS', 'status'],
            'RESPONSE_STATUS_CANCELED' => ['RESPONSE_STATUS_CANCELED', 'canceled'],
            'RESPONSE_ID' => ['RESPONSE_ID', 'id'],
            'CONTENT_TYPE_JSON' => ['CONTENT_TYPE_JSON', 'Content-Type: application/json'],
            'ORDERS_CANCEL_URI' => ['ORDERS_CANCEL_URI', '/plugins-platforms/v1/orders/%s/cancel'],
            'X_IDEMPOTENCY_KEY' => ['X_IDEMPOTENCY_KEY', 'x-idempotency-key'],
        ];
    }

    /**
     * @dataProvider normalizeCancelResponseProvider
     */
    public function testNormalizeCancelResponseBehavior($apiResponse, int $expectedResultCode): void
    {
        $client = new PublicCancelOrderClient(
            $this->loggerMock,
            $this->configMock,
            $this->json,
            $this->metricsClientMock,
            $this->cancelPaymentClientMock,
            $this->apiTypeDetectorMock,
            $this->transferBuilderMock
        );

        $result = $client->normalizeCancelResponse($apiResponse);

        $this->assertEquals($expectedResultCode, $result['RESULT_CODE']);
        $this->assertIsArray($result);
    }

    /**
     * Data provider for normalize cancel response tests.
     */
    public function normalizeCancelResponseProvider(): array
    {
        return [
            'success with id and canceled status' => [
                ['id' => 'PPORDO32YD7YI5KX2A9C0MZ1QZ33TJW', 'status' => 'canceled'],
                1,
            ],
            'success with short id and canceled status' => [
                ['id' => 'PPORD123', 'status' => 'canceled'],
                1,
            ],
            'missing id returns 0' => [
                ['status' => 'canceled'],
                0,
            ],
            'missing status returns 0' => [
                ['id' => 'PPORD123'],
                0,
            ],
            'wrong status returns 0' => [
                ['id' => 'PPORD123', 'status' => 'processed'],
                0,
            ],
            'pending status returns 0' => [
                ['id' => 'PPORD123', 'status' => 'pending'],
                0,
            ],
            'failed status returns 0' => [
                ['id' => 'PPORD123', 'status' => 'failed'],
                0,
            ],
            'empty id returns 0' => [
                ['id' => '', 'status' => 'canceled'],
                0,
            ],
            'null id returns 0' => [
                ['id' => null, 'status' => 'canceled'],
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
            'string response returns 0' => [
                'invalid',
                0,
            ],
        ];
    }

    /**
     * @dataProvider buildClientHeadersProvider
     */
    public function testBuildClientHeadersBehavior(?string $idempotencyKey, bool $shouldContainIdempotency): void
    {
        $client = new PublicCancelOrderClient(
            $this->loggerMock,
            $this->configMock,
            $this->json,
            $this->metricsClientMock,
            $this->cancelPaymentClientMock,
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
     * Data provider for build client headers tests.
     */
    public function buildClientHeadersProvider(): array
    {
        return [
            'without idempotency key' => [null, false],
            'with empty idempotency key' => ['', false],
            'with idempotency key' => ['test-key-123', true],
            'with hash idempotency key' => ['a1b2c3d4e5f6g7h8i9j0', true],
        ];
    }

    /**
     * Test that normalize preserves original response data.
     */
    public function testNormalizeCancelResponsePreservesOriginalData(): void
    {
        $client = new PublicCancelOrderClient(
            $this->loggerMock,
            $this->configMock,
            $this->json,
            $this->metricsClientMock,
            $this->cancelPaymentClientMock,
            $this->apiTypeDetectorMock,
            $this->transferBuilderMock
        );

        $apiResponse = [
            'id' => 'PPORD123',
            'status' => 'canceled',
            'status_detail' => 'by_collector',
            'external_reference' => '100000001',
            'total_amount' => 100.00,
        ];

        $result = $client->normalizeCancelResponse($apiResponse);

        $this->assertEquals(1, $result['RESULT_CODE']);
        $this->assertEquals('PPORD123', $result['id']);
        $this->assertEquals('canceled', $result['status']);
        $this->assertEquals('by_collector', $result['status_detail']);
        $this->assertEquals('100000001', $result['external_reference']);
        $this->assertEquals(100.00, $result['total_amount']);
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
            'mp_order_id' => 'PPPAY71WFLIEBP0O7H4Q7QM0BQMF6I4',
            'mp_payment_id' => null,
            'mp_payment_id_order' => 'PPPAY71WFLIEBP0O7H4Q7QM0BQMF6I4',
            'store_id' => 1,
        ];

        $transferMock->method('getBody')
            ->willReturn($requestBody);

        // Verify ApiTypeDetector is called with correct array structure
        $this->apiTypeDetectorMock->expects($this->once())
            ->method('isOrderApiFromRequest')
            ->with($this->callback(function ($additionalInfo) {
                return isset($additionalInfo['mp_order_id'])
                    && isset($additionalInfo['mp_payment_id_order'])
                    && $additionalInfo['mp_order_id'] === 'PPPAY71WFLIEBP0O7H4Q7QM0BQMF6I4'
                    && $additionalInfo['mp_payment_id_order'] === 'PPPAY71WFLIEBP0O7H4Q7QM0BQMF6I4';
            }))
            ->willReturn(false);  // Return false to use Payment API (mocked)

        // Mock TransferBuilder to create new transfer object
        $newTransferMock = $this->createMock(TransferInterface::class);

        $this->transferBuilderMock->expects($this->once())
            ->method('setBody')
            ->with($this->callback(function ($body) {
                // Verify mp_order_id was renamed to mp_payment_id
                return isset($body['mp_payment_id'])
                    && $body['mp_payment_id'] === 'PPPAY71WFLIEBP0O7H4Q7QM0BQMF6I4'
                    && !isset($body['mp_order_id']);
            }))
            ->willReturnSelf();

        $this->transferBuilderMock->expects($this->once())
            ->method('setMethod')
            ->with('PUT')
            ->willReturnSelf();

        $this->transferBuilderMock->expects($this->once())
            ->method('build')
            ->willReturn($newTransferMock);

        // Mock CancelPaymentClient to return success
        $this->cancelPaymentClientMock->expects($this->once())
            ->method('placeRequest')
            ->with($newTransferMock)
            ->willReturn([
                'RESULT_CODE' => 1,
                'status' => 'cancelled',
            ]);

        $client = new CancelOrderClient(
            $this->loggerMock,
            $this->configMock,
            $this->json,
            $this->metricsClientMock,
            $this->cancelPaymentClientMock,
            $this->apiTypeDetectorMock,
            $this->transferBuilderMock
        );

        $result = $client->placeRequest($transferMock);

        // Verify result
        $this->assertArrayHasKey('RESULT_CODE', $result);
        $this->assertEquals(1, $result['RESULT_CODE']);
    }

    /**
     * Test URI format.
     */
    public function testOrdersCancelUriFormat(): void
    {
        $orderId = 'PPORDO32YD7YI5KX2A9C0MZ1QZ33TJW';
        $uri = sprintf(CancelOrderClient::ORDERS_CANCEL_URI, $orderId);

        $this->assertEquals('/plugins-platforms/v1/orders/PPORDO32YD7YI5KX2A9C0MZ1QZ33TJW/cancel', $uri);
    }

    /**
     * @dataProvider uriFormatProvider
     */
    public function testOrdersCancelUriWithVariousOrderIds(string $orderId, string $expectedUri): void
    {
        $uri = sprintf(CancelOrderClient::ORDERS_CANCEL_URI, $orderId);

        $this->assertEquals($expectedUri, $uri);
    }

    /**
     * Data provider for URI format tests.
     */
    public function uriFormatProvider(): array
    {
        return [
            'standard order id' => [
                'PPORDO32YD7YI5KX2A9C0MZ1QZ33TJW',
                '/plugins-platforms/v1/orders/PPORDO32YD7YI5KX2A9C0MZ1QZ33TJW/cancel',
            ],
            'short order id' => [
                'PPORD123',
                '/plugins-platforms/v1/orders/PPORD123/cancel',
            ],
            'numeric order id' => [
                '12345678901234567890',
                '/plugins-platforms/v1/orders/12345678901234567890/cancel',
            ],
        ];
    }

    /**
     * Test preparePaymentApiRequest renames mp_order_id to mp_payment_id.
     */
    public function testPreparePaymentApiRequestRenamesMpOrderId(): void
    {
        $client = new CancelOrderClient(
            $this->loggerMock,
            $this->configMock,
            $this->json,
            $this->metricsClientMock,
            $this->cancelPaymentClientMock,
            $this->apiTypeDetectorMock,
            $this->transferBuilderMock
        );

        $request = [
            'mp_order_id' => '144005057552',
            'store_id' => 1,
        ];

        // Use reflection to access protected method
        $reflection = new \ReflectionClass($client);
        $method = $reflection->getMethod('preparePaymentApiRequest');
        $method->setAccessible(true);

        $result = $method->invoke($client, $request);

        // Verify mp_order_id was renamed to mp_payment_id
        $this->assertArrayHasKey('mp_payment_id', $result);
        $this->assertEquals('144005057552', $result['mp_payment_id']);
        $this->assertArrayNotHasKey('mp_order_id', $result);

        // Verify store_id is preserved
        $this->assertArrayHasKey('store_id', $result);
        $this->assertEquals(1, $result['store_id']);
    }

    /**
     * Test preparePaymentApiRequest handles missing mp_order_id.
     */
    public function testPreparePaymentApiRequestHandlesMissingMpOrderId(): void
    {
        $client = new CancelOrderClient(
            $this->loggerMock,
            $this->configMock,
            $this->json,
            $this->metricsClientMock,
            $this->cancelPaymentClientMock,
            $this->apiTypeDetectorMock,
            $this->transferBuilderMock
        );

        $request = [
            'store_id' => 1,
        ];

        // Use reflection to access protected method
        $reflection = new \ReflectionClass($client);
        $method = $reflection->getMethod('preparePaymentApiRequest');
        $method->setAccessible(true);

        $result = $method->invoke($client, $request);

        // Verify mp_payment_id is not added if mp_order_id doesn't exist
        $this->assertArrayNotHasKey('mp_payment_id', $result);
        $this->assertArrayNotHasKey('mp_order_id', $result);

        // Verify store_id is preserved
        $this->assertArrayHasKey('store_id', $result);
        $this->assertEquals(1, $result['store_id']);
    }
}
