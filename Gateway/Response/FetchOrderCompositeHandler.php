<?php
/**
 * Copyright © MercadoPago. All rights reserved.
 *
 * @author      Mercado Pago
 * @license     See LICENSE for license details.
 */

namespace MercadoPago\AdbPayment\Gateway\Response;

use Magento\Payment\Gateway\Response\HandlerInterface;
use MercadoPago\AdbPayment\Gateway\Http\Client\Order\FetchOrderClient;

/**
 * Composite handler that routes to the appropriate handler based on API source.
 *
 * - Payment API responses → FetchPaymentHandler
 * - Order API responses → FetchOrderHandler
 */
class FetchOrderCompositeHandler implements HandlerInterface
{
    /**
     * @var FetchPaymentHandler
     */
    private $fetchPaymentHandler;

    /**
     * @var FetchOrderHandler
     */
    private $fetchOrderHandler;

    /**
     * @param FetchPaymentHandler $fetchPaymentHandler
     * @param FetchOrderHandler $fetchOrderHandler
     */
    public function __construct(
        FetchPaymentHandler $fetchPaymentHandler,
        FetchOrderHandler $fetchOrderHandler
    ) {
        $this->fetchPaymentHandler = $fetchPaymentHandler;
        $this->fetchOrderHandler = $fetchOrderHandler;
    }

    /**
     * Routes to appropriate handler based on api_source in response.
     *
     * @param array $handlingSubject
     * @param array $response
     * @return void
     */
    public function handle(array $handlingSubject, array $response)
    {
        $apiSource = $response[FetchOrderClient::API_SOURCE] ?? FetchOrderClient::API_SOURCE_ORDER;

        if ($apiSource === FetchOrderClient::API_SOURCE_PAYMENT) {
            $this->fetchPaymentHandler->handle($handlingSubject, $response);
            return;
        }

        $this->fetchOrderHandler->handle($handlingSubject, $response);
    }
}
