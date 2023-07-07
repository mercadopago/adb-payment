<?php

/**
 * Copyright © MercadoPago. All rights reserved.
 *
 * @author      Mercado Pago
 * @license     See LICENSE for license details.
 */

namespace MercadoPago\AdbPayment\Gateway\Http\Client;


use Magento\Payment\Gateway\Http\ClientInterface;
use MercadoPago\AdbPayment\Gateway\Http\Client\CreateOrderPaymentCheckoutProClient as CreateOrderPaymentCheckoutProClient;

/**
 * Communication with the Gateway to create a payment by Checkout Credits.
 */
class CreateOrderPaymentCheckoutCreditsClient extends CreateOrderPaymentCheckoutProClient implements ClientInterface
{
}
