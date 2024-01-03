<?php

/**
 * Copyright © MercadoPago. All rights reserved.
 *
 * @author      Mercado Pago
 * @license     See LICENSE for license details.
 */

namespace MercadoPago\AdbPayment\Block\Sales\Info;

use MercadoPago\AdbPayment\Block\Sales\Info\Info;

/**
 * Payment details form block by Pse.
 *
 * @SuppressWarnings(PHPMD.CamelCasePropertyName)
 */
class Pse extends Info
{
    /**
     * Pse Info template.
     *
     * @var string
     */
    protected $_template = 'MercadoPago_AdbPayment::info/pse/instructions.phtml';
}
