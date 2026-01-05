<?php
/**
 * Copyright © MercadoPago. All rights reserved.
 *
 * @author      Mercado Pago
 * @license     See LICENSE for license details.
 */

namespace MercadoPago\AdbPayment\Model\MPApi\Order;

use Exception;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Payment\Model\Method\Logger;
use MercadoPago\AdbPayment\Gateway\Config\Config;
use MercadoPago\AdbPayment\Helper\HttpErrorCodeExtractor;
use MercadoPago\AdbPayment\Helper\OrderApiHeadersBuilder;
use MercadoPago\AdbPayment\Helper\OrderApiResponseValidator;
use MercadoPago\AdbPayment\Model\Metrics\MetricsClient;
use MercadoPago\PP\Sdk\HttpClient\HttpClient;
use MercadoPago\PP\Sdk\HttpClient\Requester\CurlRequester;

/**
 * API client to fetch Order notification details.
 * Endpoint: GET /plugins-platforms/v1/notification/order/{orderId}
 */
class OrderNotificationGet
{
    /**
     * Notification Order endpoint path.
     */
    public const NOTIFICATION_ORDER_URI = '/plugins-platforms/v1/notification/order/';

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var Json
     */
    protected $json;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @var OrderApiHeadersBuilder
     */
    protected $headersBuilder;

    /**
     * @var MetricsClient
     */
    protected $metricsClient;

    /**
     * @param Config                 $config
     * @param Json                   $json
     * @param Logger                 $logger
     * @param OrderApiHeadersBuilder $headersBuilder
     * @param MetricsClient          $metricsClient
     */
    public function __construct(
        Config $config,
        Json $json,
        Logger $logger,
        OrderApiHeadersBuilder $headersBuilder,
        MetricsClient $metricsClient
    ) {
        $this->config = $config;
        $this->json = $json;
        $this->logger = $logger;
        $this->headersBuilder = $headersBuilder;
        $this->metricsClient = $metricsClient;
    }

    /**
     * Get Order notification details by order ID.
     *
     * @param string $orderId
     * @param string $storeId
     * @return array
     * @throws Exception
     */
    public function get(string $orderId, string $storeId): array
    {
        $requester = new CurlRequester();
        $baseUrl = $this->config->getApiUrl();
        $client = new HttpClient($baseUrl, $requester);

        $uri = self::NOTIFICATION_ORDER_URI . $orderId;
        $headers = $this->headersBuilder->buildHeaders($storeId);

        try {
            $result = $client->get($uri, $headers);
            $data = $result->getData();

            $this->logger->debug(
                [
                    'url'      => $baseUrl . $uri,
                    'method'   => 'GET',
                    'response' => $this->json->serialize($data),
                ]
            );

            $response = is_array($data) ? $data : [];
            $this->sendResponseMetric($response);

            return $response;
        } catch (\Throwable $e) {
            $errorCode = HttpErrorCodeExtractor::extract($e);
            $this->sendErrorMetric($errorCode, $e->getMessage());

            $this->logger->debug(
                [
                    'url'   => $baseUrl . $uri,
                    'error' => $e->getMessage(),
                ]
            );
            // phpcs:ignore Magento2.Exceptions.DirectThrow
            throw new Exception("Failed to fetch order notification: " . $e->getMessage());
        }
    }

    /**
     * Send appropriate metric based on API response.
     *
     * @param array $response
     * @return void
     */
    private function sendResponseMetric(array $response): void
    {
        if (!OrderApiResponseValidator::isError($response)) {
            $this->sendSuccessMetric();
            return;
        }

        $this->sendErrorMetric(
            OrderApiResponseValidator::getErrorCode($response),
            OrderApiResponseValidator::getErrorMessage($response)
        );
    }

    /**
     * Send metric for successful notification get.
     *
     * @return void
     */
    private function sendSuccessMetric(): void
    {
        try {
            $this->metricsClient->sendEvent(
                'magento_order_api_notification_get_success',
                'success',
                'origin_magento'
            );
        } catch (\Throwable $e) {
            $this->logger->debug(['metric_error' => $e->getMessage()]);
        }
    }

    /**
     * Send metric for notification get error.
     *
     * @param string $errorCode HTTP error code or generic error code
     * @param string $errorMessage Error message description
     * @return void
     */
    private function sendErrorMetric(string $errorCode, string $errorMessage): void
    {
        try {
            $this->metricsClient->sendEvent(
                'magento_order_api_notification_get_error',
                $errorCode,
                $errorMessage
            );
        } catch (\Throwable $e) {
            $this->logger->debug(['metric_error' => $e->getMessage()]);
        }
    }
}
