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
 * Payment details form block by Pix.
 *
 * @SuppressWarnings(PHPMD.CamelCasePropertyName)
 */
class Pix extends ConfigurableInfo
{
    /**
     * Pix Info template.
     *
     * @var string
     */
    protected $_template = 'MercadoPago_AdbPayment::info/pix/instructions.phtml';
}
