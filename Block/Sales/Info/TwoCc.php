<?php

/**
 * Copyright © MercadoPago. All rights reserved.
 *
 * @license     See LICENSE for license details.
 */

namespace MercadoPago\AdbPayment\Block\Sales\Info;

use MercadoPago\AdbPayment\Block\Sales\Info\Info;

/**
 * Payment details form block by Webpay.
 *
 * @SuppressWarnings(PHPMD.CamelCasePropertyName)
 */
class TwoCc extends Info
{
    /**
     * TwoCc Info template.
     *
     * @var string
     */
    protected $_template = 'MercadoPago_AdbPayment::info/twocc/instructions.phtml';
}
