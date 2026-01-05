<?php

namespace MercadoPago\AdbPayment\Tests\Unit\Model\Metrics;

use PHPUnit\Framework\TestCase;
use MercadoPago\AdbPayment\Model\Metrics\MetricsClient;
use MercadoPago\AdbPayment\Model\Metrics\CoreMonitorAdapter;
use MercadoPago\AdbPayment\Model\Metrics\Config;
use Psr\Log\LoggerInterface;

class MetricsClientTest extends TestCase
{
    private $adapter;
    private $config;
    private $logger;

    protected function setUp(): void
    {
        $this->adapter = $this->createMock(CoreMonitorAdapter::class);
        $this->config = $this->createMock(Config::class);
        $this->logger = $this->createMock(LoggerInterface::class);
    }

    public function testSendEventCallsAdapter()
    {
        $this->adapter->expects($this->once())
            ->method('sendEvent')
            ->with('mp_order_created', 'success', 'Order created successfully');

        $client = new MetricsClient(
            $this->adapter,
            $this->config,
            $this->logger
        );

        $client->sendEvent('mp_order_created', 'success', 'Order created successfully');
    }

    public function testSendEventWithoutMessage()
    {
        $this->adapter->expects($this->once())
            ->method('sendEvent')
            ->with('mp_test_event', 'value', null);

        $client = new MetricsClient(
            $this->adapter,
            $this->config,
            $this->logger
        );

        $client->sendEvent('mp_test_event', 'value');
    }

    public function testSendEventLogsErrorOnException()
    {
        $this->adapter->expects($this->once())
            ->method('sendEvent')
            ->willThrowException(new \Exception('Adapter error'));

        $this->logger->expects($this->once())
            ->method('error')
            ->with(
                'Metrics client failed',
                $this->callback(function ($context) {
                    return isset($context['error'])
                        && $context['error'] === 'Adapter error'
                        && isset($context['event_type'])
                        && $context['event_type'] === 'mp_test_event';
                })
            );

        $client = new MetricsClient(
            $this->adapter,
            $this->config,
            $this->logger
        );

        // Should not throw exception - silent failure
        $client->sendEvent('mp_test_event', 'value');
    }
}

