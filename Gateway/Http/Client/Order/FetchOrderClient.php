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
use MercadoPago\AdbPayment\Model\Metrics\MetricsClient;
use MercadoPago\AdbPayment\Model\MPApi\Order\OrderGet;

/**
 * Communication with the Gateway to seek Order information.
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
     * Response Payment Id - Block name.
     */
    public const RESPONSE_TRANSACTION_ID = 'id';

    /**
     * Response Pay Status - Block Name.
     */
    public const RESPONSE_STATUS = 'status';

    /**
     * @var OrderGet
     */
    protected $mpApiOrderGet;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @var MetricsClient
     */
    protected $metricsClient;

    /**
     * @param OrderGet      $mpApiOrderGet
     * @param Logger        $logger
     * @param MetricsClient $metricsClient
     */
    public function __construct(
        OrderGet $mpApiOrderGet,
        Logger $logger,
        MetricsClient $metricsClient
    ) {
        $this->mpApiOrderGet = $mpApiOrderGet;
        $this->logger = $logger;
        $this->metricsClient = $metricsClient;
    }

    /**
     * Places request to gateway.
     *
     * @param TransferInterface $transferObject
     *
     * @return array
     */
    public function placeRequest(TransferInterface $transferObject)
    {
        $request = $transferObject->getBody();
        
        try {
            $data = $this->mpApiOrderGet->get(
                $request[self::MP_ORDER_ID],
                $request[self::STORE_ID]
            );

            $resultCode = isset($data[self::RESPONSE_TRANSACTION_ID]) ? 1 : 0;
            $response = array_merge([self::RESULT_CODE => $resultCode], $data);

            $this->sendResponseMetric($response);

            return $response;

        } catch (InvalidArgumentException $exc) {
            $this->sendFetchErrorMetric('400', $exc->getMessage());
            // phpcs:ignore Magento2.Exceptions.DirectThrow
            throw new Exception($exc->getMessage());
        } catch (\Throwable $e) {
            $errorCode = HttpErrorCodeExtractor::extract($e);
            $this->sendFetchErrorMetric($errorCode, $e->getMessage());
            // phpcs:ignore Magento2.Exceptions.DirectThrow
            throw new Exception($e->getMessage());
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
            $this->sendFetchSuccessMetric();
            return;
        }

        $this->sendFetchErrorMetric(
            OrderApiResponseValidator::getErrorCode($response),
            OrderApiResponseValidator::getErrorMessage($response)
        );
    }

    /**
     * Send metric for successful order fetch.
     *
     * @return void
     */
    private function sendFetchSuccessMetric(): void
    {
        try {
            $this->metricsClient->sendEvent(
                'magento_order_api_fetch_success',
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
     * @return void
     */
    private function sendFetchErrorMetric(string $errorCode, string $errorMessage): void
    {
        try {
            $this->metricsClient->sendEvent(
                'magento_order_api_fetch_error',
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
