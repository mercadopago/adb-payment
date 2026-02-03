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
use Magento\Payment\Gateway\Http\TransferBuilder;
use Magento\Payment\Gateway\Http\TransferInterface;
use Magento\Payment\Model\Method\Logger;
use MercadoPago\AdbPayment\Gateway\Config\Config;
use MercadoPago\AdbPayment\Gateway\Http\Client\RefundClient;
use MercadoPago\AdbPayment\Helper\HttpErrorCodeExtractor;
use MercadoPago\AdbPayment\Helper\IdempotencyKeyGenerator;
use MercadoPago\AdbPayment\Helper\OrderApiResponseValidator;
use MercadoPago\AdbPayment\Helper\ApiTypeDetector;
use MercadoPago\AdbPayment\Model\Metrics\MetricsClient;
use MercadoPago\PP\Sdk\HttpClient\HttpClient;
use MercadoPago\PP\Sdk\HttpClient\Requester\CurlRequester;

/**
 * HTTP client to refund Order using Plugins & Platforms Order API or Payment API.
 * - Order API: POST /plugins-platforms/v1/orders/{order_id}/refund
 * - Payment API: POST /v1/payments/{payment_id}/refunds
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
     * @var RefundClient
     */
    protected $refundClient;

    /**
     * @var ApiTypeDetector
     */
    protected $apiTypeDetector;

    /**
     * @var TransferBuilder
     */
    protected $transferBuilder;

    /**
     * @param Logger          $logger
     * @param Config          $config
     * @param Json            $json
     * @param MetricsClient   $metricsClient
     * @param RefundClient    $refundClient
     * @param ApiTypeDetector $apiTypeDetector
     * @param TransferBuilder $transferBuilder
     */
    public function __construct(
        Logger $logger,
        Config $config,
        Json $json,
        MetricsClient $metricsClient,
        RefundClient $refundClient,
        ApiTypeDetector $apiTypeDetector,
        TransferBuilder $transferBuilder
    ) {
        $this->config = $config;
        $this->logger = $logger;
        $this->json = $json;
        $this->metricsClient = $metricsClient;
        $this->refundClient = $refundClient;
        $this->apiTypeDetector = $apiTypeDetector;
        $this->transferBuilder = $transferBuilder;
    }

    /**
     * Places request to Order API or Payment API to refund payment.
     *
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
        $request = $transferObject->getBody();

        if ($this->apiTypeDetector->isOrderApiFromRequest($request)) {
            return $this->refundViaOrderApi($request);
        }

        return $this->refundViaPaymentApi($request);
    }

    /**
     * Refund payment using Order API (new PIX).
     *
     * @param array $request
     * @return array
     */
    protected function refundViaOrderApi(array $request): array
    {
        $baseUrl = $this->config->getApiUrl();
        $client = new HttpClient($baseUrl, new CurlRequester());

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
                'api_type' => 'order_api',
            ]);

            $this->sendResponseMetric($response, 'order_api');

            return $response;
        } catch (InvalidArgumentException $e) {
            $this->logError($baseUrl . $uri, $payload, $e->getMessage());
            $this->sendRefundErrorMetric('400', $e->getMessage(), 'order_api');
            // phpcs:ignore Magento2.Exceptions.DirectThrow
            throw new Exception('Invalid JSON was returned by the gateway');
        } catch (\Throwable $e) {
            $errorCode = HttpErrorCodeExtractor::extract($e);

            $this->logError($baseUrl . $uri, $payload, $e->getMessage());
            $this->sendRefundErrorMetric($errorCode, $e->getMessage(), 'order_api');
            // phpcs:ignore Magento2.Exceptions.DirectThrow
            throw new Exception($e->getMessage());
        }
    }

    /**
     * Refund payment using Payment API (old PIX).
     *
     * @param array $request
     * @return array
     */
    protected function refundViaPaymentApi(array $request): array
    {
        try {
            // Generate idempotency key
            $idempotencyKey = IdempotencyKeyGenerator::generateForRefund(
                $request[self::MP_ORDER_ID] ?? null,
                $request[self::REFUND_AMOUNT] ?? null,
                $request[self::REFUND_KEY] ?? null
            );

            // Prepare request for Payment API
            $request = $this->preparePaymentApiRequest($request, $idempotencyKey);

            // Create new TransferObject with modified request
            $transferObject = $this->transferBuilder
                ->setBody($request)
                ->setMethod('POST')
                ->build();

            $response = $this->refundClient->placeRequest($transferObject);

            $this->logger->debug([
                'response' => $this->json->serialize($response),
                'api_type' => 'payment_api',
            ]);

            $this->sendResponseMetric($response, 'payment_api');

            return $response;
        } catch (\Throwable $e) {
            $errorCode = HttpErrorCodeExtractor::extract($e);

            $this->logger->debug([
                'error' => $e->getMessage(),
                'api_type' => 'payment_api',
            ]);

            $this->sendRefundErrorMetric($errorCode, $e->getMessage(), 'payment_api');

            // phpcs:ignore Magento2.Exceptions.DirectThrow
            throw new Exception($e->getMessage());
        }
    }

    /**
     * Prepare request data for Payment API.
     *
     * Transforms Order API request format to Payment API format:
     * - Renames mp_order_id to payment_id
     * - Adds idempotency key
     * - Removes Order API specific fields
     *
     * @param array $request Original request array
     * @param string $idempotencyKey Generated idempotency key
     * @return array Prepared request for Payment API
     */
    protected function preparePaymentApiRequest(array $request, string $idempotencyKey): array
    {
        // Rename mp_order_id to payment_id
        if (isset($request[self::MP_ORDER_ID])) {
            $request['payment_id'] = $request[self::MP_ORDER_ID];
        }

        // Add idempotency key
        $request[self::X_IDEMPOTENCY_KEY] = $idempotencyKey;

        // Remove Order API specific fields (unset multiple keys at once)
        unset(
            $request[self::MP_ORDER_ID],
            $request[self::REFUND_KEY],
            $request[self::MP_PAYMENT_ID_ORDER],
            $request[self::IS_PARTIAL_REFUND]
        );

        return $request;
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
     * @param string $apiType 'order_api' or 'payment_api'
     * @return void
     */
    private function sendResponseMetric(array $response, string $apiType): void
    {
        if (!OrderApiResponseValidator::isError($response)) {
            $this->sendRefundSuccessMetric($apiType);
            return;
        }

        $this->sendRefundErrorMetric(
            OrderApiResponseValidator::getErrorCode($response),
            OrderApiResponseValidator::getErrorMessage($response),
            $apiType
        );
    }

    /**
     * Send metric for successful refund.
     *
     * @param string $apiType 'order_api' or 'payment_api'
     * @return void
     */
    private function sendRefundSuccessMetric(string $apiType): void
    {
        try {
            $eventName = $apiType === 'order_api'
                ? 'magento_order_api_refund_success'
                : 'magento_payment_api_refund_success';

            $this->metricsClient->sendEvent(
                $eventName,
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
     * @param string $apiType 'order_api' or 'payment_api'
     * @return void
     */
    private function sendRefundErrorMetric(string $errorCode, string $errorMessage, string $apiType): void
    {
        try {
            $eventName = $apiType === 'order_api'
                ? 'magento_order_api_refund_error'
                : 'magento_payment_api_refund_error';

            $this->metricsClient->sendEvent(
                $eventName,
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
