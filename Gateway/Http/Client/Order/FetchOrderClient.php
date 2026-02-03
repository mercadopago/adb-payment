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
use Magento\Payment\Gateway\Http\ClientInterface;
use Magento\Payment\Gateway\Http\TransferInterface;
use Magento\Payment\Model\Method\Logger;
use MercadoPago\AdbPayment\Helper\HttpErrorCodeExtractor;
use MercadoPago\AdbPayment\Helper\OrderApiResponseValidator;
use MercadoPago\AdbPayment\Helper\ApiTypeDetector;
use MercadoPago\AdbPayment\Model\Metrics\MetricsClient;
use MercadoPago\AdbPayment\Model\MPApi\Order\OrderGet;
use MercadoPago\AdbPayment\Model\MPApi\PaymentGet;

/**
 * Communication with the Gateway to seek Order or Payment information.
 * - Order API: GET /plugins-platforms/v1/notification/order/{order_id}
 * - Payment API: GET /v1/payments/{payment_id}
 *
 */
class FetchOrderClient implements ClientInterface
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
     * Mercado Pago Order Id - Block name.
     */
    public const MP_ORDER_ID = 'mp_order_id';

    /**
     * Mercado Pago Payment Id - Block name.
     */
    public const PAYMENT_ID_KEY = 'mp_payment_id';

     /**
      * Response Payment Id - Block name.
      */
    public const RESPONSE_TRANSACTION_ID = 'id';

     /**
      * Response Pay Status - Block Name.
      */
    public const RESPONSE_STATUS = 'status';

    /**
     * API Source - Block Name (to identify which API was used).
     */
    public const API_SOURCE = 'api_source';

    /**
     * API Source Payment API - Value.
     */
    public const API_SOURCE_PAYMENT = 'payment_api';

    /**
     * API Source Order API - Value.
     */
    public const API_SOURCE_ORDER = 'order_api';

    /**
     * @var OrderGet
     */
    protected $mpApiOrderGet;

    /**
     * @var PaymentGet
     */
    protected $mpApiPaymentGet;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @var MetricsClient
     */
    protected $metricsClient;

    /**
     * @var ApiTypeDetector
     */
    protected $apiTypeDetector;

    /**
     * @param OrderGet        $mpApiOrderGet
     * @param PaymentGet      $mpApiPaymentGet
     * @param Logger          $logger
     * @param MetricsClient   $metricsClient
     * @param ApiTypeDetector $apiTypeDetector
     */
    public function __construct(
        OrderGet $mpApiOrderGet,
        PaymentGet $mpApiPaymentGet,
        Logger $logger,
        MetricsClient $metricsClient,
        ApiTypeDetector $apiTypeDetector
    ) {
        $this->mpApiOrderGet = $mpApiOrderGet;
        $this->mpApiPaymentGet = $mpApiPaymentGet;
        $this->logger = $logger;
        $this->metricsClient = $metricsClient;
        $this->apiTypeDetector = $apiTypeDetector;
    }

    /**
     * Places request to Order API or Payment API to fetch payment information.
     *
     *
     * @param TransferInterface $transferObject
     *
     * @return array
     */
    public function placeRequest(TransferInterface $transferObject)
    {
        $request = $transferObject->getBody();

        if ($this->apiTypeDetector->isOrderApiFromRequest($request)) {
            return $this->fetchViaOrderApi($request);
        }

        return $this->fetchViaPaymentApi($request);
    }

    /**
     * Fetch payment information using Order API (new PIX).
     *
     * @param array $request
     * @return array
     */
    protected function fetchViaOrderApi(array $request): array
    {
        try {
            $data = $this->mpApiOrderGet->get(
                $request[self::MP_ORDER_ID],
                $request[self::STORE_ID]
            );

            $resultCode = isset($data[self::RESPONSE_TRANSACTION_ID]) ? 1 : 0;
            $response = array_merge([
                self::RESULT_CODE => $resultCode,
                self::API_SOURCE => self::API_SOURCE_ORDER
            ], $data);

            $this->logger->debug([
                'response' => $response,
                'api_type' => 'order_api',
            ]);

            $this->sendResponseMetric($response, 'order_api');

            return $response;

        } catch (InvalidArgumentException $exc) {
            $this->sendFetchErrorMetric('400', $exc->getMessage(), 'order_api');
            // phpcs:ignore Magento2.Exceptions.DirectThrow
            throw new Exception($exc->getMessage());
        } catch (\Throwable $e) {
            $errorCode = HttpErrorCodeExtractor::extract($e);

            $this->sendFetchErrorMetric($errorCode, $e->getMessage(), 'order_api');
            // phpcs:ignore Magento2.Exceptions.DirectThrow
            throw new Exception($e->getMessage());
        }
    }

    /**
     * Fetch payment information using Payment API (old PIX).
     *
     * @param array $request
     * @return array
     */
    protected function fetchViaPaymentApi(array $request): array
    {
        try {
            // Payment API uses mp_payment_id instead of mp_order_id
            $paymentId = $request[self::PAYMENT_ID_KEY] ?? $request[self::MP_ORDER_ID];

            $data = $this->mpApiPaymentGet->get(
                $paymentId,
                $request[self::STORE_ID]
            );

            // Ensure $data is an array
            $data = is_array($data) ? $data : [];

            $resultCode = isset($data[self::RESPONSE_TRANSACTION_ID]) ? 1 : 0;

            $normalizedPaymentDetails = $this->normalizePaymentApiResponse($data);

            $response = array_merge([
                self::RESULT_CODE => $resultCode,
                self::API_SOURCE => self::API_SOURCE_PAYMENT,
                self::RESPONSE_STATUS => $data[self::RESPONSE_STATUS] ?? null,
                'payments_details' => [$normalizedPaymentDetails]
            ], $data);

            $this->logger->debug([
                'response' => $response,
                'api_type' => 'payment_api',
            ]);

            $this->sendResponseMetric($response, 'payment_api');

            return $response;

        } catch (InvalidArgumentException $exc) {
            $this->sendFetchErrorMetric('400', $exc->getMessage(), 'payment_api');
            // phpcs:ignore Magento2.Exceptions.DirectThrow
            throw new Exception($exc->getMessage());
        } catch (\Throwable $e) {
            $errorCode = HttpErrorCodeExtractor::extract($e);
            $this->sendFetchErrorMetric($errorCode, $e->getMessage(), 'payment_api');
            // phpcs:ignore Magento2.Exceptions.DirectThrow
            throw new Exception($e->getMessage());
        }
    }

    /**
     * Normalize Payment API response to match expected handler structure.
     *
     * Payment API returns transaction_amount at root level,
     * but FetchPaymentHandler expects total_amount and paid_amount.
     *
     * @param array $data
     * @return array
     */
    private function normalizePaymentApiResponse(array $data): array
    {
        $normalized = $data;

        if (!isset($normalized['total_amount']) && isset($data['transaction_amount'])) {
            $normalized['total_amount'] = $data['transaction_amount'];
        }

        if (!isset($normalized['paid_amount'])) {
            $normalized['paid_amount'] = $normalized['total_amount'] ?? 0;
        }

        return $normalized;
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
            $this->sendFetchSuccessMetric($apiType);
            return;
        }

        $this->sendFetchErrorMetric(
            OrderApiResponseValidator::getErrorCode($response),
            OrderApiResponseValidator::getErrorMessage($response),
            $apiType
        );
    }

    /**
     * Send metric for successful order fetch.
     *
     * @param string $apiType 'order_api' or 'payment_api'
     * @return void
     */
    private function sendFetchSuccessMetric(string $apiType): void
    {
        try {
            $eventName = $apiType === 'order_api'
                ? 'magento_order_api_fetch_success'
                : 'magento_payment_api_fetch_success';

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
     * Send metric for order fetch error.
     *
     * @param string $errorCode HTTP error code or generic error code
     * @param string $errorMessage Error message description
     * @param string $apiType 'order_api' or 'payment_api'
     * @return void
     */
    private function sendFetchErrorMetric(string $errorCode, string $errorMessage, string $apiType): void
    {
        try {
            $eventName = $apiType === 'order_api'
                ? 'magento_order_api_fetch_error'
                : 'magento_payment_api_fetch_error';

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
