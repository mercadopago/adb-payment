<?php

namespace MercadoPago\AdbPayment\Tests\Unit\Gateway\Http\Client\Order;

use PHPUnit\Framework\TestCase;
use MercadoPago\AdbPayment\Gateway\Http\Client\Order\CreateOrderClient;
use Magento\Payment\Model\Method\Logger;
use MercadoPago\AdbPayment\Gateway\Config\Config;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Payment\Gateway\Http\TransferInterface;
use MercadoPago\AdbPayment\Model\Metrics\MetricsClient;
use InvalidArgumentException;

/**
 * Unit tests for CreateOrderClient.
 */
class CreateOrderClientTest extends TestCase
{
    /**
     * Ensures class_alias is applied before the SDK HttpClient is autoloaded.
     */
    private function aliasHttpClientIfNeeded(): void
    {
        $target = '\\MercadoPago\\PP\\Sdk\\HttpClient\\HttpClient';
        if (!class_exists($target, false)) {
            class_alias(FakeHttpClient::class, $target);
        }
        // Reset captured data and mock response before each test scenario
        FakeHttpClient::reset();
    }

    public function testPlaceRequestSuccessBuildsHeadersAndPayload()
    {
        $this->aliasHttpClientIfNeeded();

        $logger = $this->createMock(Logger::class);
        $config = $this->createMock(Config::class);
        $json = $this->createMock(Json::class);
        $metricsClient = $this->createMock(MetricsClient::class);

        // Config expectations
        $config->expects($this->once())
            ->method('getApiUrl')
            ->willReturn('https://api.mercadopago.com');
        $config->expects($this->once())
            ->method('getClientHeadersMpPluginsPhpSdk')
            ->with(1)
            ->willReturn(['Authorization: Bearer TEST-TOKEN']);
        $config->expects($this->once())
            ->method('isTestMode')
            ->with(1)
            ->willReturn(true);

        // JSON expectations: allow multiple serializations (payload, headers, response)
        $json->expects($this->atLeastOnce())
            ->method('serialize')
            ->willReturnCallback(function ($value) {
                return is_string($value) ? $value : json_encode($value);
            });

        // MetricsClient expectation: success metric should be called
        $metricsClient->expects($this->once())
            ->method('sendEvent')
            ->with(
                'magento_order_api_create_success',
                'success',
                'origin_magento'
            );

        $client = new CreateOrderClient($logger, $config, $json, $metricsClient);

        $transfer = $this->createMock(TransferInterface::class);
        $transfer->expects($this->once())
            ->method('getBody')
            ->willReturn([
                CreateOrderClient::STORE_ID => 1,
                CreateOrderClient::DEVICE_SESSION_ID => 'session-xyz',
                'external_reference' => 'order-123',
                'notification_url' => 'https://example.com/callback',
                'transaction_amount' => 99.90,
                'payments' => [
                    [
                        'payment_method' => ['id' => 'pix'],
                    ],
                ],
            ]);

        $result = $client->placeRequest($transfer);

        // Assert response merged with RESULT_CODE
        $this->assertEquals(1, $result[CreateOrderClient::RESULT_CODE]);
        $this->assertEquals('abc123', $result['id']);
        $this->assertEquals('created', $result['status']);

        // Assert captured request properties from FakeHttpClient
        $this->assertEquals('https://api.mercadopago.com', FakeHttpClient::$captured['baseUrl']);
        $this->assertEquals('/plugins-platforms/v1/orders', FakeHttpClient::$captured['uri']);

        /** @var array $headers */
        $headers = (array) FakeHttpClient::$captured['headers'];
        $this->assertContains('Authorization: Bearer TEST-TOKEN', $headers);
        $this->assertContains('Content-Type: application/json', $headers);
        $this->assertTrue($this->arrayContainsHeaderPrefix($headers, CreateOrderClient::X_IDEMPOTENCY_KEY . ': '));
        $this->assertContains(CreateOrderClient::X_MELI_SESSION_ID . ': session-xyz', $headers);
        $this->assertContains(CreateOrderClient::X_TEST_TOKEN . ': true', $headers);

        // Payload: decode and validate internal keys removed and data preserved
        $encodedPayload = (string) (FakeHttpClient::$captured['payload'] ?? '');
        $decoded = json_decode($encodedPayload, true);
        $this->assertIsArray($decoded);
        $this->assertArrayNotHasKey(CreateOrderClient::STORE_ID, $decoded);
        $this->assertArrayNotHasKey(CreateOrderClient::X_IDEMPOTENCY_KEY, $decoded);
        $this->assertArrayNotHasKey(CreateOrderClient::X_MELI_SESSION_ID, $decoded);
        $this->assertEquals('order-123', $decoded['external_reference']);
    }

    public function testPlaceRequestInvalidJsonThrowsGenericException()
    {
        $this->aliasHttpClientIfNeeded();

        $logger = $this->getMockBuilder(Logger::class)
            ->disableOriginalConstructor()
            ->addMethods(['error'])
            ->getMock();
        $config = $this->createMock(Config::class);
        $json = $this->createMock(Json::class);
        $metricsClient = $this->createMock(MetricsClient::class);

        $config->method('getApiUrl')->willReturn('https://api.mercadopago.com');
        $config->method('getClientHeadersMpPluginsPhpSdk')->willReturn([]);
        $config->method('isTestMode')->willReturn(false);

        // Force JSON serialization to fail to hit the InvalidArgumentException branch
        $json->expects($this->once())
            ->method('serialize')
            ->willThrowException(new InvalidArgumentException('bad json'));

        // Logger should handle any method calls (including error() and debug())
        $logger->method($this->anything())->willReturn(null);

        // MetricsClient expectation: error metric should be called with code 400
        // Note: The actual message passed is the exception message, not the generic one
        $metricsClient->expects($this->once())
            ->method('sendEvent')
            ->with(
                'magento_order_api_create_error',
                '400',
                'bad json'
            );

        $client = new CreateOrderClient($logger, $config, $json, $metricsClient);

        $transfer = $this->createMock(TransferInterface::class);
        $transfer->method('getBody')->willReturn([
            CreateOrderClient::STORE_ID => 1,
            'external_reference' => 'order-xyz',
            'notification_url' => 'https://example.com/callback',
            'transaction_amount' => 10.00,
            'payments' => [
                [
                    'payment_method' => ['id' => 'pix'],
                ],
            ],
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Invalid JSON was returned by the gateway');

        $client->placeRequest($transfer);
    }

    public function testBuildClientHeadersWhenTestModeTrue()
    {
        $logger = $this->createMock(Logger::class);
        $config = $this->createMock(Config::class);
        $json = $this->createMock(Json::class);
        $metricsClient = $this->createMock(MetricsClient::class);

        $config->method('getClientHeadersMpPluginsPhpSdk')
            ->with(1)
            ->willReturn(['Authorization: Bearer TEST-TOKEN']);
        $config->method('isTestMode')
            ->with(1)
            ->willReturn(true);

        $client = new CreateOrderClient($logger, $config, $json, $metricsClient);

        $headers = $client->buildClientHeaders(1, 'idem-123', 'session-xyz');

        $this->assertContains('Authorization: Bearer TEST-TOKEN', $headers);
        $this->assertContains('Content-Type: application/json', $headers);
        $this->assertContains(CreateOrderClient::X_IDEMPOTENCY_KEY . ': idem-123', $headers);
        $this->assertContains(CreateOrderClient::X_MELI_SESSION_ID . ': session-xyz', $headers);
        $this->assertContains(CreateOrderClient::X_TEST_TOKEN . ': true', $headers);
    }

    public function testBuildClientHeadersWhenTestModeFalse()
    {
        $logger = $this->createMock(Logger::class);
        $config = $this->createMock(Config::class);
        $json = $this->createMock(Json::class);
        $metricsClient = $this->createMock(MetricsClient::class);

        $config->method('getClientHeadersMpPluginsPhpSdk')
            ->with(2)
            ->willReturn(['Authorization: Bearer LIVE-TOKEN']);
        $config->method('isTestMode')
            ->with(2)
            ->willReturn(false);

        $client = new CreateOrderClient($logger, $config, $json, $metricsClient);

        $headers = $client->buildClientHeaders(2, 'idem-456', 'session-abc');

        $this->assertContains('Authorization: Bearer LIVE-TOKEN', $headers);
        $this->assertContains('Content-Type: application/json', $headers);
        $this->assertContains(CreateOrderClient::X_IDEMPOTENCY_KEY . ': idem-456', $headers);
        $this->assertContains(CreateOrderClient::X_MELI_SESSION_ID . ': session-abc', $headers);
        $this->assertNotContains(CreateOrderClient::X_TEST_TOKEN . ': true', $headers);
    }

    public function testBuildClientHeadersWithNullValues()
    {
        $logger = $this->createMock(Logger::class);
        $config = $this->createMock(Config::class);
        $json = $this->createMock(Json::class);
        $metricsClient = $this->createMock(MetricsClient::class);

        $config->method('getClientHeadersMpPluginsPhpSdk')
            ->with(1)
            ->willReturn(['Authorization: Bearer TOKEN']);
        $config->method('isTestMode')
            ->with(1)
            ->willReturn(false);

        $client = new CreateOrderClient($logger, $config, $json, $metricsClient);

        // Test with null values - headers should NOT be added
        $headers = $client->buildClientHeaders(1, null, null);

        $this->assertContains('Authorization: Bearer TOKEN', $headers);
        $this->assertContains('Content-Type: application/json', $headers);
        // Should NOT contain idempotency or session headers when null
        $this->assertFalse($this->arrayContainsHeaderPrefix($headers, CreateOrderClient::X_IDEMPOTENCY_KEY . ':'));
        $this->assertFalse($this->arrayContainsHeaderPrefix($headers, CreateOrderClient::X_MELI_SESSION_ID . ':'));
    }

    public function testSanitizeRequestRemovesInternalKeys()
    {
        $logger = $this->createMock(Logger::class);
        $config = $this->createMock(Config::class);
        $json = $this->createMock(Json::class);
        $metricsClient = $this->createMock(MetricsClient::class);

        $client = new CreateOrderClient($logger, $config, $json, $metricsClient);

        $input = [
            CreateOrderClient::STORE_ID => 9,
            CreateOrderClient::DEVICE_SESSION_ID => 'should-be-removed',
            'external_reference' => 'keep-me',
        ];

        $sanitized = $client->sanitizeRequest($input);

        $this->assertArrayNotHasKey(CreateOrderClient::STORE_ID, $sanitized);
        $this->assertArrayNotHasKey(CreateOrderClient::DEVICE_SESSION_ID, $sanitized);
        $this->assertSame('keep-me', $sanitized['external_reference']);
    }

    public function testNormalizeOrderApiResponseCreatedStatus()
    {
        $logger = $this->createMock(Logger::class);
        $config = $this->createMock(Config::class);
        $json = $this->createMock(Json::class);
        $metricsClient = $this->createMock(MetricsClient::class);
        $client = new CreateOrderClient($logger, $config, $json, $metricsClient);

        $data = [
            'id' => 'abc123',
            'status' => 'created',
            'foo' => 'bar',
        ];

        $response = $client->normalizeOrderApiResponse($data);

        $this->assertSame(1, $response[CreateOrderClient::RESULT_CODE]);
        $this->assertSame('abc123', $response[CreateOrderClient::EXT_ORD_ID]);
        $this->assertSame('bar', $response['foo']);
    }


    public function testNormalizeOrderApiResponseFailedStatus()
    {
        $logger = $this->createMock(Logger::class);
        $config = $this->createMock(Config::class);
        $json = $this->createMock(Json::class);
        $metricsClient = $this->createMock(MetricsClient::class);
        $client = new CreateOrderClient($logger, $config, $json, $metricsClient);

        $data = [
            'id' => 'any-id',
            'status' => 'failed',
        ];

        $response = $client->normalizeOrderApiResponse($data);

        $this->assertSame(0, $response[CreateOrderClient::RESULT_CODE]);
        $this->assertNull($response[CreateOrderClient::EXT_ORD_ID]);
    }

    public function testPlaceRequestSendsSuccessMetricOnSuccess()
    {
        $this->aliasHttpClientIfNeeded();

        $logger = $this->createMock(Logger::class);
        $config = $this->createMock(Config::class);
        $json = $this->createMock(Json::class);
        $metricsClient = $this->createMock(MetricsClient::class);

        $config->method('getApiUrl')->willReturn('https://api.mercadopago.com');
        $config->method('getClientHeadersMpPluginsPhpSdk')->willReturn([]);
        $config->method('isTestMode')->willReturn(false);

        $json->method('serialize')->willReturn('{}');

        // Verify success metric is called
        $metricsClient->expects($this->once())
            ->method('sendEvent')
            ->with(
                'magento_order_api_create_success',
                'success',
                'origin_magento'
            );

        $client = new CreateOrderClient($logger, $config, $json, $metricsClient);

        $transfer = $this->createMock(TransferInterface::class);
        $transfer->method('getBody')->willReturn([
            CreateOrderClient::STORE_ID => 1,
            'external_reference' => 'order-123',
        ]);

        $client->placeRequest($transfer);
    }

    public function testPlaceRequestSendsErrorMetricOnInvalidArgumentException()
    {
        $this->aliasHttpClientIfNeeded();

        $logger = $this->getMockBuilder(Logger::class)
            ->disableOriginalConstructor()
            ->addMethods(['error'])
            ->getMock();
        $config = $this->createMock(Config::class);
        $json = $this->createMock(Json::class);
        $metricsClient = $this->createMock(MetricsClient::class);

        $config->method('getApiUrl')->willReturn('https://api.mercadopago.com');
        $config->method('getClientHeadersMpPluginsPhpSdk')->willReturn([]);
        $config->method('isTestMode')->willReturn(false);

        $json->expects($this->once())
            ->method('serialize')
            ->willThrowException(new InvalidArgumentException('bad json'));

        // Logger should handle any method calls (including error() and debug())
        $logger->method($this->anything())->willReturn(null);

        // Verify error metric is called with code 400
        // Note: The actual message passed is the exception message, not the generic one
        $metricsClient->expects($this->once())
            ->method('sendEvent')
            ->with(
                'magento_order_api_create_error',
                '400',
                'bad json'
            );

        $client = new CreateOrderClient($logger, $config, $json, $metricsClient);

        $transfer = $this->createMock(TransferInterface::class);
        $transfer->method('getBody')->willReturn([
            CreateOrderClient::STORE_ID => 1,
        ]);

        $this->expectException(\Exception::class);
        $client->placeRequest($transfer);
    }

    public function testPlaceRequestSendsErrorMetricOnGenericException()
    {
        $this->aliasHttpClientIfNeeded();

        $logger = $this->createMock(Logger::class);
        $config = $this->createMock(Config::class);
        $json = $this->createMock(Json::class);
        $metricsClient = $this->createMock(MetricsClient::class);

        $config->method('getApiUrl')->willReturn('https://api.mercadopago.com');
        $config->method('getClientHeadersMpPluginsPhpSdk')->willReturn([]);
        $config->method('isTestMode')->willReturn(false);

        // Create exception with HTTP code in message (will be extracted by regex)
        $exception = new \Exception('HTTP 401: Unauthorized');

        $json->expects($this->once())
            ->method('serialize')
            ->willThrowException($exception);

        // Verify error metric is called with extracted code from message
        $metricsClient->expects($this->once())
            ->method('sendEvent')
            ->with(
                'magento_order_api_create_error',
                '401',
                'HTTP 401: Unauthorized'
            );

        $client = new CreateOrderClient($logger, $config, $json, $metricsClient);

        $transfer = $this->createMock(TransferInterface::class);
        $transfer->method('getBody')->willReturn([
            CreateOrderClient::STORE_ID => 1,
        ]);

        $this->expectException(\Exception::class);
        $client->placeRequest($transfer);
    }

    public function testPlaceRequestSendsErrorMetricWithDefaultCodeWhenCodeCannotBeExtracted()
    {
        $this->aliasHttpClientIfNeeded();

        $logger = $this->createMock(Logger::class);
        $config = $this->createMock(Config::class);
        $json = $this->createMock(Json::class);
        $metricsClient = $this->createMock(MetricsClient::class);

        $config->method('getApiUrl')->willReturn('https://api.mercadopago.com');
        $config->method('getClientHeadersMpPluginsPhpSdk')->willReturn([]);
        $config->method('isTestMode')->willReturn(false);

        // Create exception without HTTP code
        $exception = new \Exception('Generic error without code');

        $json->expects($this->once())
            ->method('serialize')
            ->willThrowException($exception);

        // Verify error metric is called with default code 500
        $metricsClient->expects($this->once())
            ->method('sendEvent')
            ->with(
                'magento_order_api_create_error',
                '500',
                'Generic error without code'
            );

        $client = new CreateOrderClient($logger, $config, $json, $metricsClient);

        $transfer = $this->createMock(TransferInterface::class);
        $transfer->method('getBody')->willReturn([
            CreateOrderClient::STORE_ID => 1,
        ]);

        $this->expectException(\Exception::class);
        $client->placeRequest($transfer);
    }

    public function testPlaceRequestContinuesOnMetricError()
    {
        $this->aliasHttpClientIfNeeded();

        $logger = $this->createMock(Logger::class);
        $config = $this->createMock(Config::class);
        $json = $this->createMock(Json::class);
        $metricsClient = $this->createMock(MetricsClient::class);

        $config->method('getApiUrl')->willReturn('https://api.mercadopago.com');
        $config->method('getClientHeadersMpPluginsPhpSdk')->willReturn([]);
        $config->method('isTestMode')->willReturn(false);

        $json->method('serialize')->willReturn('{}');

        // MetricsClient throws exception but should not break the flow
        $metricsClient->expects($this->once())
            ->method('sendEvent')
            ->willThrowException(new \Exception('Metric error'));

        // Logger should log the metric error (at least once, could be called multiple times)
        $logger->expects($this->atLeastOnce())
            ->method('debug')
            ->with($this->callback(function ($context) {
                // Check if it's the metric_error log or any other debug log
                return is_array($context) && (
                    (isset($context['metric_error']) && $context['metric_error'] === 'Metric error') ||
                    isset($context['url']) // Other debug logs
                );
            }));

        $client = new CreateOrderClient($logger, $config, $json, $metricsClient);

        $transfer = $this->createMock(TransferInterface::class);
        $transfer->method('getBody')->willReturn([
            CreateOrderClient::STORE_ID => 1,
        ]);

        // Should not throw exception, should return response normally
        $result = $client->placeRequest($transfer);
        $this->assertIsArray($result);
    }

    public function testSendResponseMetricSendsErrorWhenResultCodeIsZero()
    {
        $this->aliasHttpClientIfNeeded();

        // Configure mock response with RESULT_CODE = 0 (error scenario)
        FakeHttpClient::$mockResponse = [
            'id' => null,
            'status' => 'failed',
            'RESULT_CODE' => 0,
            'message' => 'Order creation failed'
        ];

        $logger = $this->createMock(Logger::class);
        $config = $this->createMock(Config::class);
        $json = $this->createMock(Json::class);
        $metricsClient = $this->createMock(MetricsClient::class);

        $config->method('getApiUrl')->willReturn('https://api.mercadopago.com');
        $config->method('getClientHeadersMpPluginsPhpSdk')->willReturn([]);
        $config->method('isTestMode')->willReturn(false);

        $json->method('serialize')->willReturnCallback(function ($value) {
            return is_string($value) ? $value : json_encode($value);
        });

        // Verify error metric is called (not success)
        $metricsClient->expects($this->once())
            ->method('sendEvent')
            ->with(
                'magento_order_api_create_error',
                $this->anything(),
                $this->anything()
            );

        $client = new CreateOrderClient($logger, $config, $json, $metricsClient);

        $transfer = $this->createMock(TransferInterface::class);
        $transfer->method('getBody')->willReturn([
            CreateOrderClient::STORE_ID => 1,
            'external_reference' => 'order-123',
        ]);

        $client->placeRequest($transfer);
    }

    public function testSendResponseMetricSendsErrorWhenStatusIs400OrHigher()
    {
        $this->aliasHttpClientIfNeeded();

        // Configure mock response with HTTP error status
        FakeHttpClient::$mockResponse = [
            'status' => 404,
            'error' => 'not_found',
            'message' => 'Resource not found',
            'original_message' => 'The requested resource was not found'
        ];

        $logger = $this->createMock(Logger::class);
        $config = $this->createMock(Config::class);
        $json = $this->createMock(Json::class);
        $metricsClient = $this->createMock(MetricsClient::class);

        $config->method('getApiUrl')->willReturn('https://api.mercadopago.com');
        $config->method('getClientHeadersMpPluginsPhpSdk')->willReturn([]);
        $config->method('isTestMode')->willReturn(false);

        $json->method('serialize')->willReturnCallback(function ($value) {
            return is_string($value) ? $value : json_encode($value);
        });

        // Verify error metric is called with status code 404
        $metricsClient->expects($this->once())
            ->method('sendEvent')
            ->with(
                'magento_order_api_create_error',
                '404',
                'The requested resource was not found'
            );

        $client = new CreateOrderClient($logger, $config, $json, $metricsClient);

        $transfer = $this->createMock(TransferInterface::class);
        $transfer->method('getBody')->willReturn([
            CreateOrderClient::STORE_ID => 1,
            'external_reference' => 'order-123',
        ]);

        $client->placeRequest($transfer);
    }

    public function testSendResponseMetricSendsErrorWhenErrorFieldIsPresent()
    {
        $this->aliasHttpClientIfNeeded();

        // Configure mock response with error field
        FakeHttpClient::$mockResponse = [
            'error' => 'invalid_request',
            'message' => 'Invalid payment method'
        ];

        $logger = $this->createMock(Logger::class);
        $config = $this->createMock(Config::class);
        $json = $this->createMock(Json::class);
        $metricsClient = $this->createMock(MetricsClient::class);

        $config->method('getApiUrl')->willReturn('https://api.mercadopago.com');
        $config->method('getClientHeadersMpPluginsPhpSdk')->willReturn([]);
        $config->method('isTestMode')->willReturn(false);

        $json->method('serialize')->willReturnCallback(function ($value) {
            return is_string($value) ? $value : json_encode($value);
        });

        // Verify error metric is called
        $metricsClient->expects($this->once())
            ->method('sendEvent')
            ->with(
                'magento_order_api_create_error',
                $this->anything(),
                'Invalid payment method'
            );

        $client = new CreateOrderClient($logger, $config, $json, $metricsClient);

        $transfer = $this->createMock(TransferInterface::class);
        $transfer->method('getBody')->willReturn([
            CreateOrderClient::STORE_ID => 1,
            'external_reference' => 'order-123',
        ]);

        $client->placeRequest($transfer);
    }

    /**
     * @dataProvider errorResponseProvider
     */
    public function testSendResponseMetricWithVariousErrorScenarios(
        array $apiResponse,
        string $expectedErrorCode,
        string $expectedErrorMessage
    ): void {
        $this->aliasHttpClientIfNeeded();

        FakeHttpClient::$mockResponse = $apiResponse;

        $logger = $this->createMock(Logger::class);
        $config = $this->createMock(Config::class);
        $json = $this->createMock(Json::class);
        $metricsClient = $this->createMock(MetricsClient::class);

        $config->method('getApiUrl')->willReturn('https://api.mercadopago.com');
        $config->method('getClientHeadersMpPluginsPhpSdk')->willReturn([]);
        $config->method('isTestMode')->willReturn(false);

        $json->method('serialize')->willReturnCallback(function ($value) {
            return is_string($value) ? $value : json_encode($value);
        });

        $metricsClient->expects($this->once())
            ->method('sendEvent')
            ->with(
                'magento_order_api_create_error',
                $expectedErrorCode,
                $expectedErrorMessage
            );

        $client = new CreateOrderClient($logger, $config, $json, $metricsClient);

        $transfer = $this->createMock(TransferInterface::class);
        $transfer->method('getBody')->willReturn([
            CreateOrderClient::STORE_ID => 1,
        ]);

        $client->placeRequest($transfer);
    }

    public function errorResponseProvider(): array
    {
        return [
            'status 400 bad request' => [
                ['status' => 400, 'error' => 'bad_request', 'message' => 'Bad request'],
                '400',
                'Bad request',
            ],
            'status 401 unauthorized' => [
                ['status' => 401, 'error' => 'unauthorized', 'message' => 'Unauthorized'],
                '401',
                'Unauthorized',
            ],
            'status 500 internal error' => [
                ['status' => 500, 'error' => 'internal_error', 'message' => 'Internal server error'],
                '500',
                'Internal server error',
            ],
            'RESULT_CODE 0 with message' => [
                ['RESULT_CODE' => 0, 'message' => 'Processing failed'],
                '0',
                'Processing failed',
            ],
            'original_message takes priority' => [
                ['status' => 422, 'message' => 'Generic', 'original_message' => 'Detailed error message'],
                '422',
                'Detailed error message',
            ],
        ];
    }

    private function arrayContainsHeaderPrefix(array $headers, string $prefix): bool
    {
        foreach ($headers as $header) {
            if (strpos($header, $prefix) === 0) {
                return true;
            }
        }
        return false;
    }

}


