<?php
/**
 * Copyright © MercadoPago. All rights reserved.
 *
 * @author      Mercado Pago
 * @license     See LICENSE for license details.
 */

namespace MercadoPago\AdbPayment\Model\Metrics;

use Magento\Framework\HTTP\Client\Curl;
use Magento\Framework\Serialize\Serializer\Json;
use Psr\Log\LoggerInterface;

/**
 * Core Monitor Adapter - Makes direct HTTP calls to Core Monitor API without SDK.
 */
class CoreMonitorAdapter
{
    /**
     * Core Monitor Base URL.
     */
    private const CORE_MONITOR_BASE_URL = 'https://api.mercadopago.com/ppcore/prod/monitor/v1/event/datadog/big';

    /**
     * HTTP Timeout in seconds.
     */
    private const HTTP_TIMEOUT = 2;

    /**
     * @var Curl
     */
    private $httpClient;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var Json
     */
    private $json;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param Curl $httpClient
     * @param Config $config
     * @param Json $json
     * @param LoggerInterface $logger
     */
    public function __construct(
        Curl $httpClient,
        Config $config,
        Json $json,
        LoggerInterface $logger
    ) {
        $this->httpClient = $httpClient;
        $this->config = $config;
        $this->json = $json;
        $this->logger = $logger;
    }

    /**
     * Send event to Core Monitor API.
     *
     * @param string $eventType
     * @param mixed $value
     * @param string|null $message
     * @return void
     */
    public function sendEvent(string $eventType, $value, ?string $message = null): void
    {
        try {
            // Build payload
            $payload = [
                'value' => (string)$value,
                'status' => 'success',
                'plugin_version' => $this->config->getModuleVersion(),
                'platform' => [
                    'name' => 'magento',
                    'uri' => $this->config->getBaseUrl(),
                    'version' => $this->config->getMagentoVersion(),
                ]
            ];

            // Add message if provided
            if ($message !== null) {
                $payload['message'] = $message;
            }

            // Build URL
            $url = self::CORE_MONITOR_BASE_URL . '/' . $eventType;

            // Set headers
            $this->httpClient->setHeaders([
                'Content-Type' => 'application/json'
            ]);

            // Set timeout
            $this->httpClient->setTimeout(self::HTTP_TIMEOUT);

            // Send POST request
            $payloadJson = $this->json->serialize($payload);
            $this->httpClient->post($url, $payloadJson);

            // Check response status
            $statusCode = $this->httpClient->getStatus();
            if ($statusCode >= 400) {
                $this->logger->warning(
                    'Core Monitor API error',
                    [
                        'status' => $statusCode,
                        'event_type' => $eventType,
                        'url' => $url,
                        'response' => $this->httpClient->getBody()
                    ]
                );
            }
        } catch (\Exception $e) {
            // Silent - never break business flow
            $this->logger->error(
                'Core Monitor adapter failed',
                [
                    'error' => $e->getMessage(),
                    'event_type' => $eventType
                ]
            );
        }
    }
}



