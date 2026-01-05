<?php
/**
 * Copyright © MercadoPago. All rights reserved.
 *
 * @author      Mercado Pago
 * @license     See LICENSE for license details.
 */

namespace MercadoPago\AdbPayment\Tests\Unit\Gateway\Http\Client\Order;

use Exception;
use InvalidArgumentException;
use Magento\Payment\Gateway\Http\TransferInterface;
use Magento\Payment\Model\Method\Logger;
use MercadoPago\AdbPayment\Gateway\Http\Client\Order\FetchOrderClient;
use MercadoPago\AdbPayment\Model\Metrics\MetricsClient;
use MercadoPago\AdbPayment\Model\MPApi\Order\OrderGet;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for FetchOrderClient.
 */
class FetchOrderClientTest extends TestCase
{
    /**
     * @var OrderGet|\PHPUnit\Framework\MockObject\MockObject
     */
    private $mpApiOrderGetMock;

    /**
     * @var Logger|\PHPUnit\Framework\MockObject\MockObject
     */
    private $loggerMock;

    /**
     * @var MetricsClient|\PHPUnit\Framework\MockObject\MockObject
     */
    private $metricsClientMock;

    /**
     * @var FetchOrderClient
     */
    private $fetchOrderClient;

    /**
     * @var TransferInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $transferMock;

    /**
     * Setup test dependencies
     */
    protected function setUp(): void
    {
        $this->mpApiOrderGetMock = $this->createMock(OrderGet::class);
        $this->loggerMock = $this->createMock(Logger::class);
        $this->metricsClientMock = $this->createMock(MetricsClient::class);
        $this->transferMock = $this->createMock(TransferInterface::class);
        
        $this->fetchOrderClient = new FetchOrderClient(
            $this->mpApiOrderGetMock,
            $this->loggerMock,
            $this->metricsClientMock
        );
    }

    /**
     * Test placeRequest with successful response containing transaction ID
     */
    public function testPlaceRequestWithTransactionIdReturnsResultCodeOne()
    {
        $mpOrderId = 'PPORDO32YD7YI5KX2A9C0MZ1QZ33TJW';
        $storeId = '1';
        
        $requestBody = [
            FetchOrderClient::MP_ORDER_ID => $mpOrderId,
            FetchOrderClient::STORE_ID => $storeId,
        ];

        $apiResponse = [
            'id' => 'transaction-456',
            'status' => 'approved',
            'external_reference' => 'order-789',
            'transaction_amount' => 100.50,
        ];

        $this->transferMock->expects($this->once())
            ->method('getBody')
            ->willReturn($requestBody);

        $this->mpApiOrderGetMock->expects($this->once())
            ->method('get')
            ->with($mpOrderId, $storeId)
            ->willReturn($apiResponse);

        $result = $this->fetchOrderClient->placeRequest($this->transferMock);

        $this->assertIsArray($result);
        $this->assertEquals(1, $result[FetchOrderClient::RESULT_CODE]);
        $this->assertEquals('transaction-456', $result['id']);
        $this->assertEquals('approved', $result['status']);
        $this->assertEquals('order-789', $result['external_reference']);
        $this->assertEquals(100.50, $result['transaction_amount']);
    }

    /**
     * Test placeRequest with successful response without transaction ID
     */
    public function testPlaceRequestWithoutTransactionIdReturnsResultCodeZero()
    {
        $mpOrderId = 'PPORDO32YD7YI5KX2A9C0MZ1QZ33ABC';
        $storeId = '2';
        
        $requestBody = [
            FetchOrderClient::MP_ORDER_ID => $mpOrderId,
            FetchOrderClient::STORE_ID => $storeId,
        ];

        $apiResponse = [
            'status' => 'pending',
            'external_reference' => 'order-111',
        ];

        $this->transferMock->expects($this->once())
            ->method('getBody')
            ->willReturn($requestBody);

        $this->mpApiOrderGetMock->expects($this->once())
            ->method('get')
            ->with($mpOrderId, $storeId)
            ->willReturn($apiResponse);

        $result = $this->fetchOrderClient->placeRequest($this->transferMock);

        $this->assertIsArray($result);
        $this->assertEquals(0, $result[FetchOrderClient::RESULT_CODE]);
        $this->assertEquals('pending', $result['status']);
        $this->assertEquals('order-111', $result['external_reference']);
        $this->assertArrayNotHasKey('id', $result);
    }

    /**
     * Test placeRequest with empty response
     */
    public function testPlaceRequestWithEmptyResponseReturnsResultCodeZero()
    {
        $mpOrderId = 'PPORDO32YD7YI5KX2A9C0MZ1QZ33DEF';
        $storeId = '3';
        
        $requestBody = [
            FetchOrderClient::MP_ORDER_ID => $mpOrderId,
            FetchOrderClient::STORE_ID => $storeId,
        ];

        $apiResponse = [];

        $this->transferMock->expects($this->once())
            ->method('getBody')
            ->willReturn($requestBody);

        $this->mpApiOrderGetMock->expects($this->once())
            ->method('get')
            ->with($mpOrderId, $storeId)
            ->willReturn($apiResponse);

        $result = $this->fetchOrderClient->placeRequest($this->transferMock);

        $this->assertIsArray($result);
        $this->assertEquals(0, $result[FetchOrderClient::RESULT_CODE]);
    }

    /**
     * Test placeRequest handles InvalidArgumentException and rethrows as Exception
     */
    public function testPlaceRequestThrowsExceptionOnInvalidArgument()
    {
        $mpOrderId = 'invalid-order-id';
        $storeId = '4';
        
        $requestBody = [
            FetchOrderClient::MP_ORDER_ID => $mpOrderId,
            FetchOrderClient::STORE_ID => $storeId,
        ];

        $this->transferMock->expects($this->once())
            ->method('getBody')
            ->willReturn($requestBody);

        $this->mpApiOrderGetMock->expects($this->once())
            ->method('get')
            ->with($mpOrderId, $storeId)
            ->willThrowException(new InvalidArgumentException('Invalid order ID'));

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Invalid order ID');

        $this->fetchOrderClient->placeRequest($this->transferMock);
    }

    /**
     * Test placeRequest with all response fields
     */
    public function testPlaceRequestMergesAllResponseFields()
    {
        $mpOrderId = 'PPORDO32YD7YI5KX2A9C0MZ1QZ33GHI';
        $storeId = '5';
        
        $requestBody = [
            FetchOrderClient::MP_ORDER_ID => $mpOrderId,
            FetchOrderClient::STORE_ID => $storeId,
        ];

        $apiResponse = [
            'id' => 'txn-full-123',
            'status' => 'processed',
            'external_reference' => 'order-full-456',
            'transaction_amount' => 250.75,
            'payments' => [
                ['id' => 'payment-1', 'status' => 'approved'],
                ['id' => 'payment-2', 'status' => 'approved'],
            ],
            'payments_metadata' => [
                'store_id' => '5',
                'payment_mode' => 'gateway',
            ],
        ];

        $this->transferMock->expects($this->once())
            ->method('getBody')
            ->willReturn($requestBody);

        $this->mpApiOrderGetMock->expects($this->once())
            ->method('get')
            ->with($mpOrderId, $storeId)
            ->willReturn($apiResponse);

        $result = $this->fetchOrderClient->placeRequest($this->transferMock);

        $this->assertIsArray($result);
        $this->assertEquals(1, $result[FetchOrderClient::RESULT_CODE]);
        $this->assertEquals('txn-full-123', $result['id']);
        $this->assertEquals('processed', $result['status']);
        $this->assertEquals('order-full-456', $result['external_reference']);
        $this->assertEquals(250.75, $result['transaction_amount']);
        $this->assertCount(2, $result['payments']);
        $this->assertEquals('5', $result['payments_metadata']['store_id']);
    }

    /**
     * Data provider for various response scenarios
     *
     * @return array
     */
    public function responseDataProvider(): array
    {
        return [
            'with_id' => [
                ['id' => 'txn-001', 'status' => 'approved'],
                1
            ],
            'without_id' => [
                ['status' => 'pending'],
                0
            ],
            'with_null_id' => [
                ['id' => null, 'status' => 'cancelled'],
                0
            ],
            'with_empty_string_id' => [
                ['id' => '', 'status' => 'rejected'],
                1  // isset() returns true for empty string
            ],
            'with_zero_id' => [
                ['id' => '0', 'status' => 'processing'],
                1
            ],
        ];
    }

    /**
     * Test RESULT_CODE determination with various response data
     *
     * @dataProvider responseDataProvider
     */
    public function testPlaceRequestResultCodeDetermination(array $apiResponse, int $expectedResultCode)
    {
        $mpOrderId = 'PPORDO32YD7YI5KX2A9C0MZ1QZ33JKL';
        $storeId = '1';
        
        $requestBody = [
            FetchOrderClient::MP_ORDER_ID => $mpOrderId,
            FetchOrderClient::STORE_ID => $storeId,
        ];

        $this->transferMock->expects($this->once())
            ->method('getBody')
            ->willReturn($requestBody);

        $this->mpApiOrderGetMock->expects($this->once())
            ->method('get')
            ->with($mpOrderId, $storeId)
            ->willReturn($apiResponse);

        $result = $this->fetchOrderClient->placeRequest($this->transferMock);

        $this->assertEquals($expectedResultCode, $result[FetchOrderClient::RESULT_CODE]);
    }

    /**
     * Data provider for success metric scenarios
     */
    public function successMetricDataProvider(): array
    {
        return [
            'approved order with id' => [
                ['id' => 'txn-success-123', 'status' => 'approved'],
            ],
            'processed order with id' => [
                ['id' => 'txn-processed-456', 'status' => 'processed'],
            ],
            'pending order with id' => [
                ['id' => 'txn-pending-789', 'status' => 'pending'],
            ],
        ];
    }

    /**
     * Test that success metric is sent when order is fetched successfully
     *
     * @dataProvider successMetricDataProvider
     */
    public function testPlaceRequestSendsSuccessMetricOnSuccess(array $apiResponse): void
    {
        $mpOrderId = 'PPORDO32YD7YI5KX2A9C0MZ1QZ33MNO';
        $storeId = '1';
        
        $requestBody = [
            FetchOrderClient::MP_ORDER_ID => $mpOrderId,
            FetchOrderClient::STORE_ID => $storeId,
        ];

        $this->transferMock->expects($this->once())
            ->method('getBody')
            ->willReturn($requestBody);

        $this->mpApiOrderGetMock->expects($this->once())
            ->method('get')
            ->with($mpOrderId, $storeId)
            ->willReturn($apiResponse);

        // Verify success metric is called
        $this->metricsClientMock->expects($this->once())
            ->method('sendEvent')
            ->with(
                'magento_order_api_fetch_success',
                'success',
                'origin_magento'
            );

        $this->fetchOrderClient->placeRequest($this->transferMock);
    }

    /**
     * Data provider for exception metric scenarios
     */
    public function exceptionMetricDataProvider(): array
    {
        return [
            'InvalidArgumentException' => [
                new InvalidArgumentException('Invalid order ID'),
                '400',
                'Invalid order ID',
            ],
            'Exception with HTTP 500' => [
                new \Exception('HTTP 500: Internal Server Error'),
                '500',
                'HTTP 500: Internal Server Error',
            ],
            'Exception with HTTP 401' => [
                new \Exception('HTTP 401: Unauthorized'),
                '401',
                'HTTP 401: Unauthorized',
            ],
            'Exception with HTTP 403' => [
                new \Exception('HTTP 403: Forbidden'),
                '403',
                'HTTP 403: Forbidden',
            ],
            'Exception without HTTP code' => [
                new \Exception('Connection timeout'),
                '500',
                'Connection timeout',
            ],
        ];
    }

    /**
     * Test that error metric is sent when exception is thrown
     *
     * @dataProvider exceptionMetricDataProvider
     */
    public function testPlaceRequestSendsErrorMetricOnException(
        \Throwable $exception,
        string $expectedCode,
        string $expectedMessage
    ): void {
        $mpOrderId = 'PPORDO32YD7YI5KX2A9C0MZ1QZ33PQR';
        $storeId = '1';
        
        $requestBody = [
            FetchOrderClient::MP_ORDER_ID => $mpOrderId,
            FetchOrderClient::STORE_ID => $storeId,
        ];

        $this->transferMock->expects($this->once())
            ->method('getBody')
            ->willReturn($requestBody);

        $this->mpApiOrderGetMock->expects($this->once())
            ->method('get')
            ->with($mpOrderId, $storeId)
            ->willThrowException($exception);

        // Verify error metric is called with expected code
        $this->metricsClientMock->expects($this->once())
            ->method('sendEvent')
            ->with(
                'magento_order_api_fetch_error',
                $expectedCode,
                $expectedMessage
            );

        $this->expectException(Exception::class);

        $this->fetchOrderClient->placeRequest($this->transferMock);
    }

    /**
     * Data provider for error response metric scenarios
     */
    public function errorResponseMetricDataProvider(): array
    {
        return [
            'RESULT_CODE 0 with 404 status' => [
                ['RESULT_CODE' => 0, 'status' => 404, 'error' => 'not_found', 'message' => 'Order not found'],
                '404',
                'Order not found',
            ],
            'RESULT_CODE 0 with 400 status' => [
                ['RESULT_CODE' => 0, 'status' => 400, 'error' => 'bad_request', 'message' => 'Bad request'],
                '400',
                'Bad request',
            ],
            'RESULT_CODE 0 with 500 status' => [
                ['RESULT_CODE' => 0, 'status' => 500, 'error' => 'internal_error', 'message' => 'Internal server error'],
                '500',
                'Internal server error',
            ],
            'error field present' => [
                ['error' => 'invalid_token', 'message' => 'Token expired'],
                '0',
                'Token expired',
            ],
            'original_message takes priority' => [
                ['status' => 422, 'message' => 'Generic', 'original_message' => 'Detailed validation error'],
                '422',
                'Detailed validation error',
            ],
        ];
    }

    /**
     * Test that error metric is sent when response indicates error
     *
     * @dataProvider errorResponseMetricDataProvider
     */
    public function testPlaceRequestSendsErrorMetricWhenResponseHasError(
        array $apiResponse,
        string $expectedCode,
        string $expectedMessage
    ): void {
        $mpOrderId = 'PPORDO32YD7YI5KX2A9C0MZ1QZ33STU';
        $storeId = '1';
        
        $requestBody = [
            FetchOrderClient::MP_ORDER_ID => $mpOrderId,
            FetchOrderClient::STORE_ID => $storeId,
        ];

        $this->transferMock->expects($this->once())
            ->method('getBody')
            ->willReturn($requestBody);

        $this->mpApiOrderGetMock->expects($this->once())
            ->method('get')
            ->with($mpOrderId, $storeId)
            ->willReturn($apiResponse);

        // Verify error metric is called
        $this->metricsClientMock->expects($this->once())
            ->method('sendEvent')
            ->with(
                'magento_order_api_fetch_error',
                $expectedCode,
                $expectedMessage
            );

        $this->fetchOrderClient->placeRequest($this->transferMock);
    }

    /**
     * Test that metric error does not break the flow
     */
    public function testPlaceRequestContinuesWhenMetricFails()
    {
        $mpOrderId = 'PPORDO32YD7YI5KX2A9C0MZ1QZ33VWX';
        $storeId = '1';
        
        $requestBody = [
            FetchOrderClient::MP_ORDER_ID => $mpOrderId,
            FetchOrderClient::STORE_ID => $storeId,
        ];

        $apiResponse = [
            'id' => 'txn-metric-fail-123',
            'status' => 'approved',
        ];

        $this->transferMock->expects($this->once())
            ->method('getBody')
            ->willReturn($requestBody);

        $this->mpApiOrderGetMock->expects($this->once())
            ->method('get')
            ->with($mpOrderId, $storeId)
            ->willReturn($apiResponse);

        // MetricsClient throws exception but should not break the flow
        $this->metricsClientMock->expects($this->once())
            ->method('sendEvent')
            ->willThrowException(new \Exception('Metric service unavailable'));

        // Logger should log the metric error
        $this->loggerMock->expects($this->once())
            ->method('debug')
            ->with($this->callback(function ($context) {
                return is_array($context) && isset($context['metric_error']);
            }));

        // Should not throw exception, should return response normally
        $result = $this->fetchOrderClient->placeRequest($this->transferMock);
        $this->assertIsArray($result);
        $this->assertEquals(1, $result[FetchOrderClient::RESULT_CODE]);
    }
}

