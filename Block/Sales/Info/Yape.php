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
 * Payment details form block by Yape.
 *
 * @SuppressWarnings(PHPMD.CamelCaseMethodName)
 */
class Yape extends Info
{
    /**
     * Yape Info template.
     *
     * @var string
     */
    protected $_template = 'MercadoPago_AdbPayment::info/yape/instructions.phtml';
}
