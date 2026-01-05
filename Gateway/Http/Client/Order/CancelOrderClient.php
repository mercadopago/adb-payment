<?php
/**
 * Copyright © MercadoPago. All rights reserved.
 *
 * @author      Mercado Pago
 * @license     See LICENSE for license details.
 */

namespace MercadoPago\AdbPayment\Gateway\Http\Client\Order;

use Exception;
use InvalidArgumentException;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Payment\Gateway\Http\ClientInterface;
use Magento\Payment\Gateway\Http\TransferInterface;
use Magento\Payment\Model\Method\Logger;
use MercadoPago\AdbPayment\Gateway\Config\Config;
use MercadoPago\AdbPayment\Helper\HttpErrorCodeExtractor;
use MercadoPago\AdbPayment\Helper\IdempotencyKeyGenerator;
use MercadoPago\AdbPayment\Helper\OrderApiResponseValidator;
use MercadoPago\AdbPayment\Model\Metrics\MetricsClient;
use MercadoPago\PP\Sdk\HttpClient\HttpClient;
use MercadoPago\PP\Sdk\HttpClient\Requester\CurlRequester;

/**
 * HTTP client to cancel Order using Plugins & Platforms Order API.
 * Endpoint: POST /plugins-platforms/v1/orders/{order_id}/cancel
 */
class CancelOrderClient implements ClientInterface
{
    /**
     * Result Code - Block name.
     */
    public const RESULT_CODE = 'RESULT_CODE';

    /**
     * Store Id - Block name.
     */
    public const STORE_ID = 'store_id';

    /**
     * Mercado Pago Order Id - Block Name.
     */
    public const MP_ORDER_ID = 'mp_order_id';

    /**
     * Response Status - Block Name.
     */
    public const RESPONSE_STATUS = 'status';

    /**
     * Response Status Canceled - Value.
     */
    public const RESPONSE_STATUS_CANCELED = 'canceled';

    /**
     * Response Id - Block Name.
     */
    public const RESPONSE_ID = 'id';

    /**
     * Content-Type JSON header literal.
     */
    public const CONTENT_TYPE_JSON = 'Content-Type: application/json';

    /**
     * Orders cancel endpoint path pattern.
     */
    public const ORDERS_CANCEL_URI = '/plugins-platforms/v1/orders/%s/cancel';

    /**
     * Idempotency Key block name.
     */
    public const X_IDEMPOTENCY_KEY = 'x-idempotency-key';

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var Json
     */
    protected $json;

    /**
     * @var MetricsClient
     */
    protected $metricsClient;

    /**
     * @param Logger        $logger
     * @param Config        $config
     * @param Json          $json
     * @param MetricsClient $metricsClient
     */
    public function __construct(
        Logger $logger,
        Config $config,
        Json $json,
        MetricsClient $metricsClient
    ) {
        $this->config = $config;
        $this->logger = $logger;
        $this->json = $json;
        $this->metricsClient = $metricsClient;
    }

    /**
     * Places request to Order API to cancel order.
     *
     * @param TransferInterface $transferObject
     * @return array
     */
    public function placeRequest(TransferInterface $transferObject)
    {
        $baseUrl = $this->config->getApiUrl();
        $client = new HttpClient($baseUrl, new CurlRequester());

        $request = $transferObject->getBody();
        $storeId = $request[self::STORE_ID] ?? null;
        $mpOrderId = $request[self::MP_ORDER_ID] ?? null;

        $idempotencyKey = IdempotencyKeyGenerator::generateForCancel($mpOrderId);

        $uri = sprintf(self::ORDERS_CANCEL_URI, $mpOrderId);
        $headers = $this->buildClientHeaders($storeId, $idempotencyKey);

        try {
            $response = $this->normalizeCancelResponse($client->post($uri, $headers, null)->getData());

            $this->logger->debug([
                'url'      => $baseUrl . $uri,
                'request'  => null,
                'response' => $this->json->serialize($response),
            ]);

            $this->sendResponseMetric($response);

            return $response;
        } catch (InvalidArgumentException $e) {
            $this->logError($baseUrl . $uri, null, $e->getMessage());
            $this->sendCancelErrorMetric('400', $e->getMessage());
            // phpcs:ignore Magento2.Exceptions.DirectThrow
            throw new Exception('Invalid JSON was returned by the gateway');
        } catch (\Throwable $e) {
            $this->logError($baseUrl . $uri, null, $e->getMessage());
            $errorCode = HttpErrorCodeExtractor::extract($e);
            $this->sendCancelErrorMetric($errorCode, $e->getMessage());
            // phpcs:ignore Magento2.Exceptions.DirectThrow
            throw new Exception($e->getMessage());
        }
    }

    /**
     * Log error details.
     *
     * @param string $url
     * @param string|null $request
     * @param string $error
     * @return void
     */
    protected function logError(string $url, ?string $request, string $error): void
    {
        $this->logger->debug([
            'url'     => $url,
            'request' => $request,
            'error'   => $error,
        ]);
    }

    /**
     * Build client headers for the Order API cancel request.
     *
     * @param mixed $storeId
     * @param string|null $idempotencyKey
     * @return array
     */
    protected function buildClientHeaders($storeId, ?string $idempotencyKey = null): array
    {
        $headers = $this->config->getClientHeadersMpPluginsPhpSdk($storeId);
        $headers[] = self::CONTENT_TYPE_JSON;

        if ($idempotencyKey) {
            $headers[] = self::X_IDEMPOTENCY_KEY . ': ' . $idempotencyKey;
        }

        return $headers;
    }

    /**
     * Normalize Order API cancel response into module response format.
     *
     * @param mixed $data
     * @return array
     */
    protected function normalizeCancelResponse($data): array
    {
        $response = is_array($data) ? $data : [];
        
        $hasId = !empty($response[self::RESPONSE_ID]);
        $isCanceled = ($response[self::RESPONSE_STATUS] ?? null) === self::RESPONSE_STATUS_CANCELED;

        $response[self::RESULT_CODE] = ($hasId && $isCanceled) ? 1 : 0;

        return $response;
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
            $this->sendCancelSuccessMetric();
            return;
        }

        $this->sendCancelErrorMetric(
            OrderApiResponseValidator::getErrorCode($response),
            OrderApiResponseValidator::getErrorMessage($response)
        );
    }

    /**
     * Send metric for successful order cancellation.
     *
     * @return void
     */
    private function sendCancelSuccessMetric(): void
    {
        try {
            $this->metricsClient->sendEvent(
                'magento_order_api_cancel_success',
                'success',
                'origin_magento'
            );
        } catch (\Throwable $e) {
            $this->logger->debug(['metric_error' => $e->getMessage()]);
        }
    }

    /**
     * Send metric for order cancellation error.
     *
     * @param string $errorCode HTTP error code or generic error code
     * @param string $errorMessage Error message description
     * @return void
     */
    private function sendCancelErrorMetric(string $errorCode, string $errorMessage): void
    {
        try {
            $this->metricsClient->sendEvent(
                'magento_order_api_cancel_error',
                $errorCode,
                $errorMessage
            );
        } catch (\Throwable $e) {
            $this->logger->debug([
                'metric_error' => $e->getMessage(),
                'metric_error_class' => get_class($e),
                'metric_error_trace' => $e->getTraceAsString()
            ]);
        }
    }
}

