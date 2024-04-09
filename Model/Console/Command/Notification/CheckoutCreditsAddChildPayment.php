<?php

/**
 * Copyright © MercadoPago. All rights reserved.
 *
 * @author      Mercado Pago
 * @license     See LICENSE for license details.
 */

namespace MercadoPago\AdbPayment\Model\Console\Command\Notification;

use MercadoPago\AdbPayment\Model\Console\Command\Notification\CheckoutProAddChildPayment as CheckoutProAddChildPayment;

/**
 * Model for Command lines to add child transaction in Checkout Credits.
 */
class CheckoutCreditsAddChildPayment extends CheckoutProAddChildPayment
{
    protected function _getInitMessage(): string
    {
        return 'Init Fetch Checkout Credits Payments';
    }
}
