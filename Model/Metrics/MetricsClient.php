<?php
/**
 * Copyright © MercadoPago. All rights reserved.
 *
 * @author      Mercado Pago
 * @license     See LICENSE for license details.
 */

namespace MercadoPago\AdbPayment\Model\Metrics;

use Psr\Log\LoggerInterface;

/**
 * Metrics Client - Main interface for sending metrics (similar to WooCommerce Datadog.php).
 */
class MetricsClient
{
    /**
     * @var CoreMonitorAdapter
     */
    private $adapter;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param CoreMonitorAdapter $adapter
     * @param Config $config
     * @param LoggerInterface $logger
     */
    public function __construct(
        CoreMonitorAdapter $adapter,
        Config $config,
        LoggerInterface $logger
    ) {
        $this->adapter = $adapter;
        $this->config = $config;
        $this->logger = $logger;
    }

    /**
     * Send event to Datadog via Core Monitor.
     *
     * @param string $eventType
     * @param mixed $value
     * @param string|null $message
     * @return void
     */
    public function sendEvent(string $eventType, $value, ?string $message = null): void
    {
        try {
            $this->adapter->sendEvent($eventType, $value, $message);
        } catch (\Exception $e) {
            $this->logger->error(
                'Metrics client failed',
                [
                    'error' => $e->getMessage(),
                    'event_type' => $eventType
                ]
            );
        }
    }
}

