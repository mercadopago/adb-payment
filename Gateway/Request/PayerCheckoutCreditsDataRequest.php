<?php

/**
 * Copyright © MercadoPago. All rights reserved.
 *
 * @author      Mercado Pago
 * @license     See LICENSE for license details.
 */

namespace MercadoPago\AdbPayment\Gateway\Request;

use Magento\Payment\Gateway\Request\BuilderInterface;
use MercadoPago\AdbPayment\Gateway\Request\PayerCheckoutProDataRequest as PayerCheckoutProDataRequest;

/**
 * Gateway requests for Payer data in method Checkout Credits.
 */
class PayerCheckoutCreditsDataRequest extends PayerCheckoutProDataRequest implements BuilderInterface
{
}
