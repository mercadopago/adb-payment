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
 * Payment details form block by card.
 *
 * @SuppressWarnings(PHPMD.CamelCaseMethodName)
 */
class Card extends ConfigurableInfo
{
    /**
     * Checkout Pro Info template.
     *
     * @var string
     */
    protected $_template = 'MercadoPago_AdbPayment::info/cc/instructions.phtml';
}
