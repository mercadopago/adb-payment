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
 * HTTP client to refund Order using Plugins & Platforms Order API.
 * Endpoint: POST /plugins-platforms/v1/orders/{order_id}/refund
 *
 * Supports both total and partial refunds.
 */
class RefundOrderClient implements ClientInterface
{
    /**
     * Result Code - Block name.
     */
    public const RESULT_CODE = 'RESULT_CODE';

    /** 
     * Payments - Block name.
     */
    public const PAYMENTS = 'payments';

    /**
     * Store Id - Block name.
     */
    public const STORE_ID = 'store_id';

    /**
     * Mercado Pago Order Id - Block Name.
     */
    public const MP_ORDER_ID = 'mp_order_id';

    /**
     * Mercado Pago Payment Id Order - Block Name.
     */
    public const MP_PAYMENT_ID_ORDER = 'mp_payment_id_order';

    /**
     * Payment Id in payload - Block Name.
     */
    public const PAYMENT_ID_ORDER = 'id';

    /**
     * Response Refund Payment Id - Block name.
     */
    public const RESPONSE_REFUND_PAYMENT_ID = 'refund_payment_id';

    /**
     * Response Refund Order Id - Block name.
     */
    public const RESPONSE_REFUND_ORDER_ID = 'refund_order_id';

    /**
     * Response Reference - Block name.
     */
    public const RESPONSE_REFERENCE = 'reference';
    /**
     * Response Status - Block Name.
     */
    public const RESPONSE_STATUS = 'status';

    /**
     * Response Status Failed - Value.
     */
    public const RESPONSE_STATUS_FAILED = 'FAILED';

    /**
     * Idempotency Key block name.
     */
    public const X_IDEMPOTENCY_KEY = 'x-idempotency-key';

    /**
     * Content-Type JSON header literal.
     */
    public const CONTENT_TYPE_JSON = 'Content-Type: application/json';

    /**
     * Orders refund endpoint path pattern.
     */
    public const ORDERS_REFUND_URI = '/plugins-platforms/v1/orders/%s/refund';

    /**
     * Refund amount - Block name.
     */
    public const REFUND_AMOUNT = 'amount';

    /**
     * Refund unique key - Block name.
     */
    public const REFUND_KEY = 'refund_key';

    /**
     * Is partial refund flag - Block name.
     */
    public const IS_PARTIAL_REFUND = 'is_partial_refund';

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
     * Places request to Order API to refund order.
     *
     * Supports:
     * - Total refund: Do not send amount in request body
     * - Partial refund: Send amount in request body
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

        $idempotencyKey = IdempotencyKeyGenerator::generateForRefund(
            $mpOrderId,
            $request[self::REFUND_AMOUNT] ?? null,
            $request[self::REFUND_KEY] ?? null
        );

        $uri = sprintf(self::ORDERS_REFUND_URI, $mpOrderId);
        $headers = $this->buildClientHeaders($storeId, $idempotencyKey);
        $sanitizedRequest = $this->sanitizeRequest($request);
        $payload = !empty($sanitizedRequest) ? $this->json->serialize($sanitizedRequest) : null;

        try {
            $response = $this->normalizeRefundResponse($client->post($uri, $headers, $payload)->getData());

            $this->logger->debug([
                'url'      => $baseUrl . $uri,
                'request'  => $payload,
                'response' => $this->json->serialize($response),
            ]);

            $this->sendResponseMetric($response);

            return $response;
        } catch (InvalidArgumentException $e) {
            $this->logError($baseUrl . $uri, $payload, $e->getMessage());
            $this->sendRefundErrorMetric('400', $e->getMessage());
            // phpcs:ignore Magento2.Exceptions.DirectThrow
            throw new Exception('Invalid JSON was returned by the gateway');
        } catch (\Throwable $e) {
            $this->logError($baseUrl . $uri, $payload, $e->getMessage());
            $errorCode = HttpErrorCodeExtractor::extract($e);
            $this->sendRefundErrorMetric($errorCode, $e->getMessage());
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
     * Build client headers for the Order API refund request.
     *
     * @param mixed       $storeId
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
     * Build request body for refund.
     *
     * For total refund, returns empty array (null payload).
     * For partial refund, returns structured payments array.
     *
     * @param array $request
     * @return array
     */
    protected function sanitizeRequest(array $request): array
    {
        $isPartialRefund = $request[self::IS_PARTIAL_REFUND] ?? false;

        // Total refund: empty payload
        if (!$isPartialRefund) {
            return [];
        }

        // Partial refund: structured payload
        return [
            self::PAYMENTS => [
                [
                    self::PAYMENT_ID_ORDER    => $request[self::MP_PAYMENT_ID_ORDER] ?? null,
                    self::REFUND_AMOUNT       => $request[self::REFUND_AMOUNT] ?? null,
                ],
            ],
        ];
    }

    /**
     * Normalize Order API refund response into module response format.
     *
     * @param mixed $data
     * @return array
     */
    protected function normalizeRefundResponse($data): array
    {
        $response = is_array($data) ? $data : [];
        $payment = $response[self::PAYMENTS][0] ?? [];
        $reference = $payment[self::RESPONSE_REFERENCE] ?? [];

        $hasRefundId = !empty($reference[self::RESPONSE_REFUND_PAYMENT_ID])
            || !empty($reference[self::RESPONSE_REFUND_ORDER_ID]);
        $isFailed = ($payment[self::RESPONSE_STATUS] ?? null) === self::RESPONSE_STATUS_FAILED;

        $response[self::RESULT_CODE] = ($hasRefundId && !$isFailed) ? 1 : 0;

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
            $this->sendRefundSuccessMetric();
            return;
        }

        $this->sendRefundErrorMetric(
            OrderApiResponseValidator::getErrorCode($response),
            OrderApiResponseValidator::getErrorMessage($response)
        );
    }

    /**
     * Send metric for successful refund.
     *
     * @return void
     */
    private function sendRefundSuccessMetric(): void
    {
        try {
            $this->metricsClient->sendEvent(
                'magento_order_api_refund_success',
                'success',
                'origin_magento'
            );
        } catch (\Throwable $e) {
            $this->logger->debug(['metric_error' => $e->getMessage()]);
        }
    }

    /**
     * Send metric for refund error.
     *
     * @param string $errorCode HTTP error code or generic error code
     * @param string $errorMessage Error message description
     * @return void
     */
    private function sendRefundErrorMetric(string $errorCode, string $errorMessage): void
    {
        try {
            $this->metricsClient->sendEvent(
                'magento_order_api_refund_error',
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

