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
 * Payment details form block by Webpay.
 *
 * @SuppressWarnings(PHPMD.CamelCasePropertyName)
 */
class Webpay extends ConfigurableInfo
{
    /**
     * Webpay Info template.
     *
     * @var string
     */
    protected $_template = 'MercadoPago_AdbPayment::info/webpay/instructions.phtml';
}
