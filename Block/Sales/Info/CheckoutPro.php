<?php
/**
 * Copyright © MercadoPago. All rights reserved.
 *
 * @author      Mercado Pago
 * @license     See LICENSE for license details.
 */

namespace MercadoPago\AdbPayment\Block\Sales\Info;

use Magento\Payment\Block\ConfigurableInfo;

/**
 * Payment details form block by Checkout Pro.
 *
 * @SuppressWarnings(PHPMD.CamelCasePropertyName)
 */
class CheckoutPro extends ConfigurableInfo
{
    /**
     * Checkout Pro Info template.
     *
     * @var string
     */
    protected $_template = 'MercadoPago_AdbPayment::info/checkout-pro/instructions.phtml';
}
