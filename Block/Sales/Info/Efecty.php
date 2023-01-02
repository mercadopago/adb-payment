<?php
/**
 * Copyright © MercadoPago. All rights reserved.
 *
 * @author      Bruno Elisei <brunoelisei@o2ti.com>
 * @license     See LICENSE for license details.
 */

namespace MercadoPago\PaymentMagento\Block\Sales\Info;

use Magento\Payment\Block\ConfigurableInfo;

/**
 * Payment details form block by Efecty.
 *
 * @SuppressWarnings(PHPMD.CamelCasePropertyName)
 */
class Efecty extends ConfigurableInfo
{
    /**
     * Efecty Info template.
     *
     * @var string
     */
    protected $_template = 'MercadoPago_PaymentMagento::info/efecty/instructions.phtml';
}
