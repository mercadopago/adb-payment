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
use MercadoPago\AdbPayment\Gateway\Http\Client\CancelPaymentClient;
use MercadoPago\AdbPayment\Helper\HttpErrorCodeExtractor;
use MercadoPago\AdbPayment\Helper\IdempotencyKeyGenerator;
use MercadoPago\AdbPayment\Helper\OrderApiResponseValidator;
use MercadoPago\AdbPayment\Helper\ApiTypeDetector;
use MercadoPago\AdbPayment\Model\Metrics\MetricsClient;
use MercadoPago\PP\Sdk\HttpClient\HttpClient;
use MercadoPago\PP\Sdk\HttpClient\Requester\CurlRequester;

/**
 * HTTP client to cancel Order using Plugins & Platforms Order API or Payment API.
 * - Order API: POST /plugins-platforms/v1/orders/{order_id}/cancel
 * - Payment API: PUT /v1/payments/{payment_id} with status=cancelled
 *
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
     * Mercado Pago Payment Id Order - Block Name.
     */
    public const ORDER_API_PAYMENT_ID_KEY = 'mp_payment_id_order';

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
     * @var CancelPaymentClient
     */
    protected $cancelPaymentClient;

    /**
     * @var ApiTypeDetector
     */
    protected $apiTypeDetector;

    /**
     * @var TransferBuilder
     */
    protected $transferBuilder;

    /**
     * @param Logger              $logger
     * @param Config              $config
     * @param Json                $json
     * @param MetricsClient       $metricsClient
     * @param CancelPaymentClient $cancelPaymentClient
     * @param ApiTypeDetector     $apiTypeDetector
     * @param TransferBuilder     $transferBuilder
     */
    public function __construct(
        Logger $logger,
        Config $config,
        Json $json,
        MetricsClient $metricsClient,
        CancelPaymentClient $cancelPaymentClient,
        ApiTypeDetector $apiTypeDetector,
        TransferBuilder $transferBuilder
    ) {
        $this->config = $config;
        $this->logger = $logger;
        $this->json = $json;
        $this->metricsClient = $metricsClient;
        $this->cancelPaymentClient = $cancelPaymentClient;
        $this->apiTypeDetector = $apiTypeDetector;
        $this->transferBuilder = $transferBuilder;
    }

    /**
     * Places request to Order API or Payment API to cancel payment.
     *
     * @param TransferInterface $transferObject
     * @return array
     */
    public function placeRequest(TransferInterface $transferObject)
    {
        $request = $transferObject->getBody();

        if ($this->apiTypeDetector->isOrderApiFromRequest($request)) {
            return $this->cancelViaOrderApi($request);
        }

        return $this->cancelViaPaymentApi($transferObject);
    }

    /**
     * Cancel payment using Order API (new PIX).
     *
     * @param array $request
     * @return array
     */
    protected function cancelViaOrderApi(array $request): array
    {
        $baseUrl = $this->config->getApiUrl();
        $client = new HttpClient($baseUrl, new CurlRequester());

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
                'api_type' => 'order_api',
            ]);

            $this->sendResponseMetric($response, 'order_api');

            return $response;
        } catch (InvalidArgumentException $e) {
            $this->logError($baseUrl . $uri, null, $e->getMessage());
            $this->sendCancelErrorMetric('400', $e->getMessage(), 'order_api');
            // phpcs:ignore Magento2.Exceptions.DirectThrow
            throw new Exception('Invalid JSON was returned by the gateway');
        } catch (\Throwable $e) {
            $errorCode = HttpErrorCodeExtractor::extract($e);

            $this->logError($baseUrl . $uri, null, $e->getMessage());
            $this->sendCancelErrorMetric($errorCode, $e->getMessage(), 'order_api');
            // phpcs:ignore Magento2.Exceptions.DirectThrow
            throw new Exception($e->getMessage());
        }
    }

    /**
     * Cancel payment using Payment API (old PIX).
     *
     * @param TransferInterface $transferObject
     * @return array
     */
    protected function cancelViaPaymentApi(TransferInterface $transferObject): array
    {
        try {
            $request = $transferObject->getBody();

            // Prepare request for Payment API
            $request = $this->preparePaymentApiRequest($request);

            // Create new TransferObject with modified request
            $transferObject = $this->transferBuilder
                ->setBody($request)
                ->setMethod('PUT')
                ->build();

            $response = $this->cancelPaymentClient->placeRequest($transferObject);

            $this->logger->debug([
                'response' => $this->json->serialize($response),
                'api_type' => 'payment_api',
            ]);

            $this->sendResponseMetric($response, 'payment_api');

            return $response;
        } catch (\Throwable $e) {
            $errorCode = HttpErrorCodeExtractor::extract($e);

            $this->sendCancelErrorMetric($errorCode, $e->getMessage(), 'payment_api');

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
     * @param string $apiType 'order_api' or 'payment_api'
     * @return void
     */
    private function sendResponseMetric(array $response, string $apiType): void
    {
        if (!OrderApiResponseValidator::isError($response)) {
            $this->sendCancelSuccessMetric($apiType);
            return;
        }

        $this->sendCancelErrorMetric(
            OrderApiResponseValidator::getErrorCode($response),
            OrderApiResponseValidator::getErrorMessage($response),
            $apiType
        );
    }

    /**
     * Send metric for successful order cancellation.
     *
     * @param string $apiType 'order_api' or 'payment_api'
     * @return void
     */
    private function sendCancelSuccessMetric(string $apiType): void
    {
        try {
            $eventName = $apiType === 'order_api'
                ? 'magento_order_api_cancel_success'
                : 'magento_payment_api_cancel_success';

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
     * Send metric for order cancellation error.
     *
     * @param string $errorCode HTTP error code or generic error code
     * @param string $errorMessage Error message description
     * @param string $apiType 'order_api' or 'payment_api'
     * @return void
     */
    private function sendCancelErrorMetric(string $errorCode, string $errorMessage, string $apiType): void
    {
        try {
            $eventName = $apiType === 'order_api'
                ? 'magento_order_api_cancel_error'
                : 'magento_payment_api_cancel_error';

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

    /**
     * Prepare request data for Payment API.
     *
     * Transforms Order API request format to Payment API format:
     * - Renames mp_order_id to mp_payment_id (required by CancelPaymentClient)
     * - Removes Order API specific fields
     *
     * @param array $request Original request array
     * @return array Prepared request for Payment API
     */
    protected function preparePaymentApiRequest(array $request): array
    {
        // Rename mp_order_id to mp_payment_id (CancelPaymentClient expects mp_payment_id)
        if (isset($request[self::MP_ORDER_ID])) {
            $request['mp_payment_id'] = $request[self::MP_ORDER_ID];
            unset($request[self::MP_ORDER_ID]);
            unset($request[self::ORDER_API_PAYMENT_ID_KEY]);
        }

        return $request;
    }
}
