<?php

/**
 * Copyright © MercadoPago. All rights reserved.
 *
 * @author      Mercado Pago
 * @license     See LICENSE for license details.
 */

namespace MercadoPago\AdbPayment\Controller\Notification;

use Magento\Framework\App\CsrfAwareActionInterface;
use MercadoPago\AdbPayment\Controller\Notification\CheckoutPro as CheckoutProNotification;

/**
 * Controler Notification Checkout Credits - Notification of receivers for Checkout Credits Methods.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class CheckoutCredits extends CheckoutProNotification implements CsrfAwareActionInterface
{
}
