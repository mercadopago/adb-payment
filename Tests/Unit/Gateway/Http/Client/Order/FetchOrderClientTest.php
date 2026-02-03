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
use MercadoPago\AdbPayment\Helper\ApiTypeDetector;
use MercadoPago\AdbPayment\Model\Metrics\MetricsClient;
use MercadoPago\AdbPayment\Model\MPApi\PaymentGet;
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
     * @var PaymentGet|\PHPUnit\Framework\MockObject\MockObject
     */
    private $mpApiPaymentGetMock;

    /**
     * @var Logger|\PHPUnit\Framework\MockObject\MockObject
     */
    private $loggerMock;

    /**
     * @var MetricsClient|\PHPUnit\Framework\MockObject\MockObject
     */
    private $metricsClientMock;

    /**
     * @var ApiTypeDetector|\PHPUnit\Framework\MockObject\MockObject
     */
    private $apiTypeDetectorMock;

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
        $this->mpApiPaymentGetMock = $this->createMock(PaymentGet::class);
        $this->loggerMock = $this->createMock(Logger::class);
        $this->metricsClientMock = $this->createMock(MetricsClient::class);
        $this->apiTypeDetectorMock = $this->createMock(ApiTypeDetector::class);
        $this->transferMock = $this->createMock(TransferInterface::class);

        $this->fetchOrderClient = new FetchOrderClient(
            $this->mpApiOrderGetMock,
            $this->mpApiPaymentGetMock,
            $this->loggerMock,
            $this->metricsClientMock,
            $this->apiTypeDetectorMock
        );
    }

    /**
     * Test placeRequest routes to Order API when isOrderApi returns true
     */
    public function testPlaceRequestRoutesToOrderApiWhenIsOrderApiReturnsTrue()
    {
        $mpOrderId = 'PPORDO32YD7YI5KX2A9C0MZ1QZ33TJW';
        $storeId = '1';

        $requestBody = [
            FetchOrderClient::MP_ORDER_ID => $mpOrderId,
            FetchOrderClient::PAYMENT_ID_KEY => null,
            ApiTypeDetector::ORDER_API_PAYMENT_ID_KEY => 'payment-123',
            FetchOrderClient::STORE_ID => $storeId,
        ];

        $apiResponse = [
            'id' => 'order-456',
            'status' => 'processed',
            'external_reference' => 'order-789',
        ];

        $this->transferMock->expects($this->once())
            ->method('getBody')
            ->willReturn($requestBody);

        // Mock ApiTypeDetector to return true (use Order API)
        $this->apiTypeDetectorMock->expects($this->once())
            ->method('isOrderApiFromRequest')
            ->willReturn(true);

        // Order API should be called
        $this->mpApiOrderGetMock->expects($this->once())
            ->method('get')
            ->with($mpOrderId, $storeId)
            ->willReturn($apiResponse);

        // Payment API should NOT be called
        $this->mpApiPaymentGetMock->expects($this->never())
            ->method('get');

        $result = $this->fetchOrderClient->placeRequest($this->transferMock);

        $this->assertIsArray($result);
        $this->assertEquals(1, $result[FetchOrderClient::RESULT_CODE]);
        $this->assertEquals(FetchOrderClient::API_SOURCE_ORDER, $result[FetchOrderClient::API_SOURCE]);
        $this->assertEquals('order-456', $result['id']);
        $this->assertEquals('processed', $result['status']);
    }

    /**
     * Test placeRequest routes to Payment API when isOrderApi returns false
     */
    public function testPlaceRequestRoutesToPaymentApiWhenIsOrderApiReturnsFalse()
    {
        $mpOrderId = '144005057552';
        $storeId = '1';

        $requestBody = [
            FetchOrderClient::MP_ORDER_ID => $mpOrderId,
            FetchOrderClient::PAYMENT_ID_KEY => $mpOrderId,
            ApiTypeDetector::ORDER_API_PAYMENT_ID_KEY => null,
            FetchOrderClient::STORE_ID => $storeId,
        ];

        $apiResponse = [
            'id' => 144005057552,
            'status' => 'approved',
            'transaction_amount' => 100.50,
        ];

        $this->transferMock->expects($this->once())
            ->method('getBody')
            ->willReturn($requestBody);

        // Mock ApiTypeDetector to return false (use Payment API)
        $this->apiTypeDetectorMock->expects($this->once())
            ->method('isOrderApiFromRequest')
            ->willReturn(false);

        // Payment API should be called
        $this->mpApiPaymentGetMock->expects($this->once())
            ->method('get')
            ->with($mpOrderId, $storeId)
            ->willReturn($apiResponse);

        // Order API should NOT be called
        $this->mpApiOrderGetMock->expects($this->never())
            ->method('get');

        $result = $this->fetchOrderClient->placeRequest($this->transferMock);

        $this->assertIsArray($result);
        $this->assertEquals(1, $result[FetchOrderClient::RESULT_CODE]);
        $this->assertEquals(FetchOrderClient::API_SOURCE_PAYMENT, $result[FetchOrderClient::API_SOURCE]);
        $this->assertEquals(144005057552, $result['id']);
        $this->assertEquals('approved', $result['status']);

        // Verify normalization: payments_details should have normalized data
        $this->assertArrayHasKey('payments_details', $result);
        $this->assertIsArray($result['payments_details']);
        $this->assertCount(1, $result['payments_details']);

        // Verify normalized fields exist in payments_details
        $paymentDetails = $result['payments_details'][0];
        $this->assertArrayHasKey('total_amount', $paymentDetails);
        $this->assertArrayHasKey('paid_amount', $paymentDetails);
        $this->assertEquals(100.50, $paymentDetails['total_amount']);
        $this->assertEquals(100.50, $paymentDetails['paid_amount']);
    }

    /**
     * Test placeRequest with successful Order API response containing transaction ID
     */
    public function testPlaceRequestOrderApiWithTransactionIdReturnsResultCodeOne()
    {
        $mpOrderId = 'PPORDO32YD7YI5KX2A9C0MZ1QZ33ABC';
        $storeId = '1';

        $requestBody = [
            FetchOrderClient::MP_ORDER_ID => $mpOrderId,
            FetchOrderClient::PAYMENT_ID_KEY => null,
            ApiTypeDetector::ORDER_API_PAYMENT_ID_KEY => 'payment-order-123',
            FetchOrderClient::STORE_ID => $storeId,
        ];

        $apiResponse = [
            'id' => 'transaction-456',
            'status' => 'processed',
            'external_reference' => 'order-789',
            'total_paid_amount' => 100.50,
        ];

        $this->transferMock->expects($this->once())
            ->method('getBody')
            ->willReturn($requestBody);

        $this->apiTypeDetectorMock->expects($this->once())
            ->method('isOrderApiFromRequest')
            ->willReturn(true);

        $this->mpApiOrderGetMock->expects($this->once())
            ->method('get')
            ->with($mpOrderId, $storeId)
            ->willReturn($apiResponse);

        $result = $this->fetchOrderClient->placeRequest($this->transferMock);

        $this->assertIsArray($result);
        $this->assertEquals(1, $result[FetchOrderClient::RESULT_CODE]);
        $this->assertEquals(FetchOrderClient::API_SOURCE_ORDER, $result[FetchOrderClient::API_SOURCE]);
        $this->assertEquals('transaction-456', $result['id']);
        $this->assertEquals('processed', $result['status']);
    }

    /**
     * Test placeRequest with successful Payment API response containing transaction ID
     */
    public function testPlaceRequestPaymentApiWithTransactionIdReturnsResultCodeOne()
    {
        $paymentId = '144005057552';
        $storeId = '1';

        $requestBody = [
            FetchOrderClient::MP_ORDER_ID => $paymentId,
            FetchOrderClient::PAYMENT_ID_KEY => $paymentId,
            ApiTypeDetector::ORDER_API_PAYMENT_ID_KEY => null,
            FetchOrderClient::STORE_ID => $storeId,
        ];

        $apiResponse = [
            'id' => 144005057552,
            'status' => 'approved',
            'status_detail' => 'accredited',
            'transaction_amount' => 100.50,
        ];

        $this->transferMock->expects($this->once())
            ->method('getBody')
            ->willReturn($requestBody);

        $this->apiTypeDetectorMock->expects($this->once())
            ->method('isOrderApiFromRequest')
            ->willReturn(false);

        $this->mpApiPaymentGetMock->expects($this->once())
            ->method('get')
            ->with($paymentId, $storeId)
            ->willReturn($apiResponse);

        $result = $this->fetchOrderClient->placeRequest($this->transferMock);

        $this->assertIsArray($result);
        $this->assertEquals(1, $result[FetchOrderClient::RESULT_CODE]);
        $this->assertEquals(FetchOrderClient::API_SOURCE_PAYMENT, $result[FetchOrderClient::API_SOURCE]);
        $this->assertEquals(144005057552, $result['id']);
        $this->assertEquals('approved', $result['status']);
    }

    /**
     * Test placeRequest with response without transaction ID returns result code zero
     */
    public function testPlaceRequestWithoutTransactionIdReturnsResultCodeZero()
    {
        $mpOrderId = 'PPORDO32YD7YI5KX2A9C0MZ1QZ33DEF';
        $storeId = '2';

        $requestBody = [
            FetchOrderClient::MP_ORDER_ID => $mpOrderId,
            FetchOrderClient::PAYMENT_ID_KEY => null,
            ApiTypeDetector::ORDER_API_PAYMENT_ID_KEY => 'payment-456',
            FetchOrderClient::STORE_ID => $storeId,
        ];

        $apiResponse = [
            'status' => 'pending',
            'external_reference' => 'order-111',
        ];

        $this->transferMock->expects($this->once())
            ->method('getBody')
            ->willReturn($requestBody);

        $this->apiTypeDetectorMock->expects($this->once())
            ->method('isOrderApiFromRequest')
            ->willReturn(true);

        $this->mpApiOrderGetMock->expects($this->once())
            ->method('get')
            ->with($mpOrderId, $storeId)
            ->willReturn($apiResponse);

        $result = $this->fetchOrderClient->placeRequest($this->transferMock);

        $this->assertIsArray($result);
        $this->assertEquals(0, $result[FetchOrderClient::RESULT_CODE]);
        $this->assertEquals('pending', $result['status']);
        $this->assertArrayNotHasKey('id', $result);
    }

    /**
     * Test placeRequest with empty response returns result code zero
     */
    public function testPlaceRequestWithEmptyResponseReturnsResultCodeZero()
    {
        $mpOrderId = 'PPORDO32YD7YI5KX2A9C0MZ1QZ33GHI';
        $storeId = '3';

        $requestBody = [
            FetchOrderClient::MP_ORDER_ID => $mpOrderId,
            FetchOrderClient::PAYMENT_ID_KEY => null,
            ApiTypeDetector::ORDER_API_PAYMENT_ID_KEY => 'payment-789',
            FetchOrderClient::STORE_ID => $storeId,
        ];

        $apiResponse = [];

        $this->transferMock->expects($this->once())
            ->method('getBody')
            ->willReturn($requestBody);

        $this->apiTypeDetectorMock->expects($this->once())
            ->method('isOrderApiFromRequest')
            ->willReturn(true);

        $this->mpApiOrderGetMock->expects($this->once())
            ->method('get')
            ->with($mpOrderId, $storeId)
            ->willReturn($apiResponse);

        $result = $this->fetchOrderClient->placeRequest($this->transferMock);

        $this->assertIsArray($result);
        $this->assertEquals(0, $result[FetchOrderClient::RESULT_CODE]);
        $this->assertEquals(FetchOrderClient::API_SOURCE_ORDER, $result[FetchOrderClient::API_SOURCE]);
    }

    /**
     * Test placeRequest handles InvalidArgumentException from Order API
     */
    public function testPlaceRequestThrowsExceptionOnOrderApiInvalidArgument()
    {
        $mpOrderId = 'invalid-order-id';
        $storeId = '4';

        $requestBody = [
            FetchOrderClient::MP_ORDER_ID => $mpOrderId,
            FetchOrderClient::PAYMENT_ID_KEY => null,
            ApiTypeDetector::ORDER_API_PAYMENT_ID_KEY => 'payment-invalid',
            FetchOrderClient::STORE_ID => $storeId,
        ];

        $this->transferMock->expects($this->once())
            ->method('getBody')
            ->willReturn($requestBody);

        $this->apiTypeDetectorMock->expects($this->once())
            ->method('isOrderApiFromRequest')
            ->willReturn(true);

        $this->mpApiOrderGetMock->expects($this->once())
            ->method('get')
            ->with($mpOrderId, $storeId)
            ->willThrowException(new InvalidArgumentException('Invalid order ID'));

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Invalid order ID');

        $this->fetchOrderClient->placeRequest($this->transferMock);
    }

    /**
     * Test placeRequest handles InvalidArgumentException from Payment API
     */
    public function testPlaceRequestThrowsExceptionOnPaymentApiInvalidArgument()
    {
        $paymentId = 'invalid-payment-id';
        $storeId = '4';

        $requestBody = [
            FetchOrderClient::MP_ORDER_ID => $paymentId,
            FetchOrderClient::PAYMENT_ID_KEY => $paymentId,
            ApiTypeDetector::ORDER_API_PAYMENT_ID_KEY => null,
            FetchOrderClient::STORE_ID => $storeId,
        ];

        $this->transferMock->expects($this->once())
            ->method('getBody')
            ->willReturn($requestBody);

        $this->apiTypeDetectorMock->expects($this->once())
            ->method('isOrderApiFromRequest')
            ->willReturn(false);

        $this->mpApiPaymentGetMock->expects($this->once())
            ->method('get')
            ->with($paymentId, $storeId)
            ->willThrowException(new InvalidArgumentException('Invalid payment ID'));

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Invalid payment ID');

        $this->fetchOrderClient->placeRequest($this->transferMock);
    }

    /**
     * Test placeRequest merges all response fields from Order API
     */
    public function testPlaceRequestMergesAllOrderApiResponseFields()
    {
        $mpOrderId = 'PPORDO32YD7YI5KX2A9C0MZ1QZ33JKL';
        $storeId = '5';

        $requestBody = [
            FetchOrderClient::MP_ORDER_ID => $mpOrderId,
            FetchOrderClient::PAYMENT_ID_KEY => null,
            ApiTypeDetector::ORDER_API_PAYMENT_ID_KEY => 'payment-full',
            FetchOrderClient::STORE_ID => $storeId,
        ];

        $apiResponse = [
            'id' => 'txn-full-123',
            'status' => 'processed',
            'external_reference' => 'order-full-456',
            'total_paid_amount' => 250.75,
            'payments' => [
                ['id' => 'payment-1', 'status' => 'approved'],
                ['id' => 'payment-2', 'status' => 'approved'],
            ],
        ];

        $this->transferMock->expects($this->once())
            ->method('getBody')
            ->willReturn($requestBody);

        $this->apiTypeDetectorMock->expects($this->once())
            ->method('isOrderApiFromRequest')
            ->willReturn(true);

        $this->mpApiOrderGetMock->expects($this->once())
            ->method('get')
            ->with($mpOrderId, $storeId)
            ->willReturn($apiResponse);

        $result = $this->fetchOrderClient->placeRequest($this->transferMock);

        $this->assertIsArray($result);
        $this->assertEquals(1, $result[FetchOrderClient::RESULT_CODE]);
        $this->assertEquals(FetchOrderClient::API_SOURCE_ORDER, $result[FetchOrderClient::API_SOURCE]);
        $this->assertEquals('txn-full-123', $result['id']);
        $this->assertEquals('processed', $result['status']);
        $this->assertEquals('order-full-456', $result['external_reference']);
        $this->assertEquals(250.75, $result['total_paid_amount']);
        $this->assertCount(2, $result['payments']);
    }

    /**
     * Test placeRequest merges all response fields from Payment API
     */
    public function testPlaceRequestMergesAllPaymentApiResponseFields()
    {
        $paymentId = '144005057552';
        $storeId = '5';

        $requestBody = [
            FetchOrderClient::MP_ORDER_ID => $paymentId,
            FetchOrderClient::PAYMENT_ID_KEY => $paymentId,
            ApiTypeDetector::ORDER_API_PAYMENT_ID_KEY => null,
            FetchOrderClient::STORE_ID => $storeId,
        ];

        $apiResponse = [
            'id' => 144005057552,
            'status' => 'approved',
            'status_detail' => 'accredited',
            'transaction_amount' => 250.75,
            'payment_method_id' => 'pix',
            'payment_type_id' => 'bank_transfer',
        ];

        $this->transferMock->expects($this->once())
            ->method('getBody')
            ->willReturn($requestBody);

        $this->apiTypeDetectorMock->expects($this->once())
            ->method('isOrderApiFromRequest')
            ->willReturn(false);

        $this->mpApiPaymentGetMock->expects($this->once())
            ->method('get')
            ->with($paymentId, $storeId)
            ->willReturn($apiResponse);

        $result = $this->fetchOrderClient->placeRequest($this->transferMock);

        $this->assertIsArray($result);
        $this->assertEquals(1, $result[FetchOrderClient::RESULT_CODE]);
        $this->assertEquals(FetchOrderClient::API_SOURCE_PAYMENT, $result[FetchOrderClient::API_SOURCE]);
        $this->assertEquals(144005057552, $result['id']);
        $this->assertEquals('approved', $result['status']);
        $this->assertEquals('accredited', $result['status_detail']);
        $this->assertEquals('pix', $result['payment_method_id']);
    }

    /**
     * Data provider for various response scenarios
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
        $mpOrderId = 'PPORDO32YD7YI5KX2A9C0MZ1QZ33MNO';
        $storeId = '1';

        $requestBody = [
            FetchOrderClient::MP_ORDER_ID => $mpOrderId,
            FetchOrderClient::PAYMENT_ID_KEY => null,
            ApiTypeDetector::ORDER_API_PAYMENT_ID_KEY => 'payment-test',
            FetchOrderClient::STORE_ID => $storeId,
        ];

        $this->transferMock->expects($this->once())
            ->method('getBody')
            ->willReturn($requestBody);

        $this->apiTypeDetectorMock->expects($this->once())
            ->method('isOrderApiFromRequest')
            ->willReturn(true);

        $this->mpApiOrderGetMock->expects($this->once())
            ->method('get')
            ->with($mpOrderId, $storeId)
            ->willReturn($apiResponse);

        $result = $this->fetchOrderClient->placeRequest($this->transferMock);

        $this->assertEquals($expectedResultCode, $result[FetchOrderClient::RESULT_CODE]);
    }

    /**
     * Test that success metric is sent when Order API fetch is successful
     */
    public function testPlaceRequestSendsOrderApiSuccessMetric(): void
    {
        $mpOrderId = 'PPORDO32YD7YI5KX2A9C0MZ1QZ33PQR';
        $storeId = '1';

        $requestBody = [
            FetchOrderClient::MP_ORDER_ID => $mpOrderId,
            FetchOrderClient::PAYMENT_ID_KEY => null,
            ApiTypeDetector::ORDER_API_PAYMENT_ID_KEY => 'payment-metric',
            FetchOrderClient::STORE_ID => $storeId,
        ];

        $apiResponse = [
            'id' => 'txn-success-123',
            'status' => 'processed',
        ];

        $this->transferMock->expects($this->once())
            ->method('getBody')
            ->willReturn($requestBody);

        $this->apiTypeDetectorMock->expects($this->once())
            ->method('isOrderApiFromRequest')
            ->willReturn(true);

        $this->mpApiOrderGetMock->expects($this->once())
            ->method('get')
            ->with($mpOrderId, $storeId)
            ->willReturn($apiResponse);

        // Verify Order API success metric is called
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
     * Test that success metric is sent when Payment API fetch is successful
     */
    public function testPlaceRequestSendsPaymentApiSuccessMetric(): void
    {
        $paymentId = '144005057552';
        $storeId = '1';

        $requestBody = [
            FetchOrderClient::MP_ORDER_ID => $paymentId,
            FetchOrderClient::PAYMENT_ID_KEY => $paymentId,
            ApiTypeDetector::ORDER_API_PAYMENT_ID_KEY => null,
            FetchOrderClient::STORE_ID => $storeId,
        ];

        $apiResponse = [
            'id' => 144005057552,
            'status' => 'approved',
        ];

        $this->transferMock->expects($this->once())
            ->method('getBody')
            ->willReturn($requestBody);

        $this->apiTypeDetectorMock->expects($this->once())
            ->method('isOrderApiFromRequest')
            ->willReturn(false);

        $this->mpApiPaymentGetMock->expects($this->once())
            ->method('get')
            ->with($paymentId, $storeId)
            ->willReturn($apiResponse);

        // Verify Payment API success metric is called
        $this->metricsClientMock->expects($this->once())
            ->method('sendEvent')
            ->with(
                'magento_payment_api_fetch_success',
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
     * Test that error metric is sent when Order API throws exception
     *
     * @dataProvider exceptionMetricDataProvider
     */
    public function testPlaceRequestSendsOrderApiErrorMetricOnException(
        \Throwable $exception,
        string $expectedCode,
        string $expectedMessage
    ): void {
        $mpOrderId = 'PPORDO32YD7YI5KX2A9C0MZ1QZ33STU';
        $storeId = '1';

        $requestBody = [
            FetchOrderClient::MP_ORDER_ID => $mpOrderId,
            FetchOrderClient::PAYMENT_ID_KEY => null,
            ApiTypeDetector::ORDER_API_PAYMENT_ID_KEY => 'payment-error',
            FetchOrderClient::STORE_ID => $storeId,
        ];

        $this->transferMock->expects($this->once())
            ->method('getBody')
            ->willReturn($requestBody);

        $this->apiTypeDetectorMock->expects($this->once())
            ->method('isOrderApiFromRequest')
            ->willReturn(true);

        $this->mpApiOrderGetMock->expects($this->once())
            ->method('get')
            ->with($mpOrderId, $storeId)
            ->willThrowException($exception);

        // Verify Order API error metric is called
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
     * Test that error metric is sent when Payment API throws exception
     *
     * @dataProvider exceptionMetricDataProvider
     */
    public function testPlaceRequestSendsPaymentApiErrorMetricOnException(
        \Throwable $exception,
        string $expectedCode,
        string $expectedMessage
    ): void {
        $paymentId = '144005057552';
        $storeId = '1';

        $requestBody = [
            FetchOrderClient::MP_ORDER_ID => $paymentId,
            FetchOrderClient::PAYMENT_ID_KEY => $paymentId,
            ApiTypeDetector::ORDER_API_PAYMENT_ID_KEY => null,
            FetchOrderClient::STORE_ID => $storeId,
        ];

        $this->transferMock->expects($this->once())
            ->method('getBody')
            ->willReturn($requestBody);

        $this->apiTypeDetectorMock->expects($this->once())
            ->method('isOrderApiFromRequest')
            ->willReturn(false);

        $this->mpApiPaymentGetMock->expects($this->once())
            ->method('get')
            ->with($paymentId, $storeId)
            ->willThrowException($exception);

        // Verify Payment API error metric is called
        $this->metricsClientMock->expects($this->once())
            ->method('sendEvent')
            ->with(
                'magento_payment_api_fetch_error',
                $expectedCode,
                $expectedMessage
            );

        $this->expectException(Exception::class);

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
            FetchOrderClient::PAYMENT_ID_KEY => null,
            ApiTypeDetector::ORDER_API_PAYMENT_ID_KEY => 'payment-metric-fail',
            FetchOrderClient::STORE_ID => $storeId,
        ];

        $apiResponse = [
            'id' => 'txn-metric-fail-123',
            'status' => 'processed',
        ];

        $this->transferMock->expects($this->once())
            ->method('getBody')
            ->willReturn($requestBody);

        $this->apiTypeDetectorMock->expects($this->once())
            ->method('isOrderApiFromRequest')
            ->willReturn(true);

        $this->mpApiOrderGetMock->expects($this->once())
            ->method('get')
            ->with($mpOrderId, $storeId)
            ->willReturn($apiResponse);

        // MetricsClient throws exception but should not break the flow
        $this->metricsClientMock->expects($this->once())
            ->method('sendEvent')
            ->willThrowException(new \Exception('Metric service unavailable'));

        // Logger should be called
        $this->loggerMock->expects($this->atLeastOnce())
            ->method('debug');

        // Should not throw exception, should return response normally
        $result = $this->fetchOrderClient->placeRequest($this->transferMock);
        $this->assertIsArray($result);
        $this->assertEquals(1, $result[FetchOrderClient::RESULT_CODE]);
        $this->assertEquals(FetchOrderClient::API_SOURCE_ORDER, $result[FetchOrderClient::API_SOURCE]);
    }

    /**
     * Test Payment API uses PAYMENT_ID_KEY when available
     */
    public function testPaymentApiUsesPaymentIdKeyWhenAvailable()
    {
        $mpOrderId = '999999999';
        $paymentId = '144005057552';
        $storeId = '1';

        $requestBody = [
            FetchOrderClient::MP_ORDER_ID => $mpOrderId,
            FetchOrderClient::PAYMENT_ID_KEY => $paymentId,
            ApiTypeDetector::ORDER_API_PAYMENT_ID_KEY => null,
            FetchOrderClient::STORE_ID => $storeId,
        ];

        $apiResponse = [
            'id' => 144005057552,
            'status' => 'approved',
        ];

        $this->transferMock->expects($this->once())
            ->method('getBody')
            ->willReturn($requestBody);

        $this->apiTypeDetectorMock->expects($this->once())
            ->method('isOrderApiFromRequest')
            ->willReturn(false);

        // Should use PAYMENT_ID_KEY value, not MP_ORDER_ID
        $this->mpApiPaymentGetMock->expects($this->once())
            ->method('get')
            ->with($paymentId, $storeId)
            ->willReturn($apiResponse);

        $result = $this->fetchOrderClient->placeRequest($this->transferMock);

        $this->assertEquals(FetchOrderClient::API_SOURCE_PAYMENT, $result[FetchOrderClient::API_SOURCE]);
    }

    /**
     * Test Payment API falls back to MP_ORDER_ID when PAYMENT_ID_KEY is null
     */
    public function testPaymentApiFallsBackToMpOrderIdWhenPaymentIdKeyIsNull()
    {
        $mpOrderId = '144005057552';
        $storeId = '1';

        $requestBody = [
            FetchOrderClient::MP_ORDER_ID => $mpOrderId,
            FetchOrderClient::PAYMENT_ID_KEY => null,
            ApiTypeDetector::ORDER_API_PAYMENT_ID_KEY => null,
            FetchOrderClient::STORE_ID => $storeId,
        ];

        $apiResponse = [
            'id' => 144005057552,
            'status' => 'approved',
        ];

        $this->transferMock->expects($this->once())
            ->method('getBody')
            ->willReturn($requestBody);

        $this->apiTypeDetectorMock->expects($this->once())
            ->method('isOrderApiFromRequest')
            ->willReturn(false);

        // Should fall back to MP_ORDER_ID
        $this->mpApiPaymentGetMock->expects($this->once())
            ->method('get')
            ->with($mpOrderId, $storeId)
            ->willReturn($apiResponse);

        $result = $this->fetchOrderClient->placeRequest($this->transferMock);

        $this->assertEquals(FetchOrderClient::API_SOURCE_PAYMENT, $result[FetchOrderClient::API_SOURCE]);
    }

    /**
     * Test normalizePaymentApiResponse maps transaction_amount to total_amount
     */
    public function testNormalizePaymentApiResponseMapsTransactionAmountToTotalAmount()
    {
        $mpOrderId = '144005057552';
        $storeId = '1';

        $requestBody = [
            FetchOrderClient::MP_ORDER_ID => $mpOrderId,
            FetchOrderClient::PAYMENT_ID_KEY => $mpOrderId,
            ApiTypeDetector::ORDER_API_PAYMENT_ID_KEY => null,
            FetchOrderClient::STORE_ID => $storeId,
        ];

        // Payment API response with transaction_amount but no total_amount
        $apiResponse = [
            'id' => 144005057552,
            'status' => 'cancelled',
            'status_detail' => 'expired',
            'transaction_amount' => 98.00,
            // Note: no total_amount or paid_amount
        ];

        $this->transferMock->expects($this->once())
            ->method('getBody')
            ->willReturn($requestBody);

        $this->apiTypeDetectorMock->expects($this->once())
            ->method('isOrderApiFromRequest')
            ->willReturn(false);

        $this->mpApiPaymentGetMock->expects($this->once())
            ->method('get')
            ->with($mpOrderId, $storeId)
            ->willReturn($apiResponse);

        $result = $this->fetchOrderClient->placeRequest($this->transferMock);

        // Verify normalization happened
        $this->assertArrayHasKey('payments_details', $result);
        $paymentDetails = $result['payments_details'][0];

        // transaction_amount should be mapped to total_amount
        $this->assertArrayHasKey('total_amount', $paymentDetails);
        $this->assertEquals(98.00, $paymentDetails['total_amount']);

        // paid_amount should use total_amount as fallback
        $this->assertArrayHasKey('paid_amount', $paymentDetails);
        $this->assertEquals(98.00, $paymentDetails['paid_amount']);
    }

    /**
     * Test normalizePaymentApiResponse preserves existing total_amount
     */
    public function testNormalizePaymentApiResponsePreservesExistingTotalAmount()
    {
        $mpOrderId = '144005057552';
        $storeId = '1';

        $requestBody = [
            FetchOrderClient::MP_ORDER_ID => $mpOrderId,
            FetchOrderClient::PAYMENT_ID_KEY => $mpOrderId,
            ApiTypeDetector::ORDER_API_PAYMENT_ID_KEY => null,
            FetchOrderClient::STORE_ID => $storeId,
        ];

        // Payment API response with both transaction_amount and total_amount
        $apiResponse = [
            'id' => 144005057552,
            'status' => 'approved',
            'transaction_amount' => 100.00,
            'total_amount' => 95.00, // Already has total_amount
            'paid_amount' => 95.00,
        ];

        $this->transferMock->expects($this->once())
            ->method('getBody')
            ->willReturn($requestBody);

        $this->apiTypeDetectorMock->expects($this->once())
            ->method('isOrderApiFromRequest')
            ->willReturn(false);

        $this->mpApiPaymentGetMock->expects($this->once())
            ->method('get')
            ->with($mpOrderId, $storeId)
            ->willReturn($apiResponse);

        $result = $this->fetchOrderClient->placeRequest($this->transferMock);

        $paymentDetails = $result['payments_details'][0];

        // Should preserve existing total_amount, not overwrite with transaction_amount
        $this->assertEquals(95.00, $paymentDetails['total_amount']);
        $this->assertEquals(95.00, $paymentDetails['paid_amount']);
    }

    /**
     * Test normalizePaymentApiResponse handles missing transaction_amount
     */
    public function testNormalizePaymentApiResponseHandlesMissingTransactionAmount()
    {
        $mpOrderId = '144005057552';
        $storeId = '1';

        $requestBody = [
            FetchOrderClient::MP_ORDER_ID => $mpOrderId,
            FetchOrderClient::PAYMENT_ID_KEY => $mpOrderId,
            ApiTypeDetector::ORDER_API_PAYMENT_ID_KEY => null,
            FetchOrderClient::STORE_ID => $storeId,
        ];

        // Payment API response without transaction_amount
        $apiResponse = [
            'id' => 144005057552,
            'status' => 'pending',
        ];

        $this->transferMock->expects($this->once())
            ->method('getBody')
            ->willReturn($requestBody);

        $this->apiTypeDetectorMock->expects($this->once())
            ->method('isOrderApiFromRequest')
            ->willReturn(false);

        $this->mpApiPaymentGetMock->expects($this->once())
            ->method('get')
            ->with($mpOrderId, $storeId)
            ->willReturn($apiResponse);

        $result = $this->fetchOrderClient->placeRequest($this->transferMock);

        $paymentDetails = $result['payments_details'][0];

        // paid_amount should default to 0 when no amounts are present
        $this->assertArrayHasKey('paid_amount', $paymentDetails);
        $this->assertEquals(0, $paymentDetails['paid_amount']);
    }

    /**
     * Test API_SOURCE constants are correctly defined
     */
    public function testApiSourceConstantsAreDefined()
    {
        $this->assertEquals('api_source', FetchOrderClient::API_SOURCE);
        $this->assertEquals('payment_api', FetchOrderClient::API_SOURCE_PAYMENT);
        $this->assertEquals('order_api', FetchOrderClient::API_SOURCE_ORDER);
    }
}
