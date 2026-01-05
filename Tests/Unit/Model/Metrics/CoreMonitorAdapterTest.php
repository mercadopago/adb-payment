<?php

namespace MercadoPago\AdbPayment\Tests\Unit\Model\Metrics;

use PHPUnit\Framework\TestCase;
use MercadoPago\AdbPayment\Model\Metrics\CoreMonitorAdapter;
use MercadoPago\AdbPayment\Model\Metrics\Config;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Framework\Serialize\Serializer\Json;
use Psr\Log\LoggerInterface;

class CoreMonitorAdapterTest extends TestCase
{
    private $httpClient;
    private $config;
    private $json;
    private $logger;

    protected function setUp(): void
    {
        $this->httpClient = $this->createMock(Curl::class);
        $this->config = $this->createMock(Config::class);
        $this->json = $this->createMock(Json::class);
        $this->logger = $this->createMock(LoggerInterface::class);
    }

    public function testSendEventBuildsCorrectPayload()
    {
        $this->config->expects($this->once())
            ->method('getModuleVersion')
            ->willReturn('1.13.0');

        $this->config->expects($this->once())
            ->method('getMagentoVersion')
            ->willReturn('2.4.6');

        $this->config->expects($this->once())
            ->method('getBaseUrl')
            ->willReturn('https://example.com/');

        $expectedPayload = [
            'value' => 'success',
            'status' => 'success',
            'plugin_version' => '1.13.0',
            'platform' => [
                'name' => 'magento',
                'uri' => 'https://example.com/',
                'version' => '2.4.6',
            ],
            'message' => 'Order created successfully',
        ];

        $this->json->expects($this->once())
            ->method('serialize')
            ->with($this->callback(function ($payload) use ($expectedPayload) {
                return $payload['value'] === $expectedPayload['value']
                    && $payload['status'] === $expectedPayload['status']
                    && $payload['plugin_version'] === $expectedPayload['plugin_version']
                    && $payload['platform']['name'] === $expectedPayload['platform']['name']
                    && $payload['platform']['uri'] === $expectedPayload['platform']['uri']
                    && $payload['platform']['version'] === $expectedPayload['platform']['version']
                    && $payload['message'] === $expectedPayload['message'];
            }))
            ->willReturn(json_encode($expectedPayload));

        $this->httpClient->expects($this->once())
            ->method('setHeaders')
            ->with(['Content-Type' => 'application/json']);

        $this->httpClient->expects($this->once())
            ->method('setTimeout')
            ->with(2);

        $this->httpClient->expects($this->once())
            ->method('post')
            ->with(
                'https://api.mercadopago.com/ppcore/prod/monitor/v1/event/datadog/big/mp_order_created',
                json_encode($expectedPayload)
            );

        $this->httpClient->expects($this->once())
            ->method('getStatus')
            ->willReturn(200);

        $adapter = new CoreMonitorAdapter(
            $this->httpClient,
            $this->config,
            $this->json,
            $this->logger
        );

        $adapter->sendEvent('mp_order_created', 'success', 'Order created successfully');
    }

    public function testSendEventWithoutMessage()
    {
        $this->config->expects($this->once())
            ->method('getModuleVersion')
            ->willReturn('1.13.0');

        $this->config->expects($this->once())
            ->method('getMagentoVersion')
            ->willReturn('2.4.6');

        $this->config->expects($this->once())
            ->method('getBaseUrl')
            ->willReturn('https://example.com/');

        $this->json->expects($this->once())
            ->method('serialize')
            ->with($this->callback(function ($payload) {
                return !isset($payload['message'])
                    && $payload['value'] === 'success'
                    && $payload['status'] === 'success';
            }))
            ->willReturn('{}');

        $this->httpClient->expects($this->once())
            ->method('setHeaders')
            ->with(['Content-Type' => 'application/json']);

        $this->httpClient->expects($this->once())
            ->method('setTimeout')
            ->with(2);

        $this->httpClient->expects($this->once())
            ->method('post')
            ->with(
                'https://api.mercadopago.com/ppcore/prod/monitor/v1/event/datadog/big/mp_test_event',
                '{}'
            );

        $this->httpClient->expects($this->once())
            ->method('getStatus')
            ->willReturn(200);

        $adapter = new CoreMonitorAdapter(
            $this->httpClient,
            $this->config,
            $this->json,
            $this->logger
        );

        $adapter->sendEvent('mp_test_event', 'success');
    }

    public function testSendEventLogsWarningOnHttpError()
    {
        $this->config->expects($this->once())
            ->method('getModuleVersion')
            ->willReturn('1.13.0');

        $this->config->expects($this->once())
            ->method('getMagentoVersion')
            ->willReturn('2.4.6');

        $this->config->expects($this->once())
            ->method('getBaseUrl')
            ->willReturn('https://example.com/');

        $this->json->expects($this->once())
            ->method('serialize')
            ->willReturn('{}');

        $this->httpClient->expects($this->once())
            ->method('setHeaders');

        $this->httpClient->expects($this->once())
            ->method('setTimeout')
            ->with(2);

        $this->httpClient->expects($this->once())
            ->method('post');

        $this->httpClient->expects($this->once())
            ->method('getStatus')
            ->willReturn(400);

        $this->httpClient->expects($this->once())
            ->method('getBody')
            ->willReturn('Bad Request');

        $this->logger->expects($this->once())
            ->method('warning')
            ->with(
                'Core Monitor API error',
                $this->callback(function ($context) {
                    return isset($context['status'])
                        && $context['status'] === 400
                        && isset($context['event_type'])
                        && $context['event_type'] === 'mp_test_event';
                })
            );

        $adapter = new CoreMonitorAdapter(
            $this->httpClient,
            $this->config,
            $this->json,
            $this->logger
        );

        $adapter->sendEvent('mp_test_event', 'success');
    }

    public function testSendEventLogsErrorOnException()
    {
        $this->config->expects($this->once())
            ->method('getModuleVersion')
            ->willThrowException(new \Exception('Config error'));

        $this->logger->expects($this->once())
            ->method('error')
            ->with(
                'Core Monitor adapter failed',
                $this->callback(function ($context) {
                    return isset($context['error'])
                        && isset($context['event_type'])
                        && $context['event_type'] === 'mp_test_event';
                })
            );

        $adapter = new CoreMonitorAdapter(
            $this->httpClient,
            $this->config,
            $this->json,
            $this->logger
        );

        // Should not throw exception - silent failure
        $adapter->sendEvent('mp_test_event', 'success');
    }

    public function testSendEventWithNullPluginVersion()
    {
        $this->config->expects($this->once())
            ->method('getModuleVersion')
            ->willReturn(null);

        $this->config->expects($this->once())
            ->method('getMagentoVersion')
            ->willReturn('2.4.6');

        $this->config->expects($this->once())
            ->method('getBaseUrl')
            ->willReturn('https://example.com/');

        $this->json->expects($this->once())
            ->method('serialize')
            ->with($this->callback(function ($payload) {
                return $payload['plugin_version'] === null;
            }))
            ->willReturn('{}');

        $this->httpClient->expects($this->once())
            ->method('setHeaders')
            ->with(['Content-Type' => 'application/json']);

        $this->httpClient->expects($this->once())
            ->method('setTimeout')
            ->with(2);

        $this->httpClient->expects($this->once())
            ->method('post');

        $this->httpClient->expects($this->once())
            ->method('getStatus')
            ->willReturn(200);

        $adapter = new CoreMonitorAdapter(
            $this->httpClient,
            $this->config,
            $this->json,
            $this->logger
        );

        $adapter->sendEvent('mp_test_event', 'success');
    }
}

