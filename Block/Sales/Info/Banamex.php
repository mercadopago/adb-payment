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
 * Payment details form block by Banamex.
 *
 * @SuppressWarnings(PHPMD.CamelCasePropertyName)
 */
class Banamex extends ConfigurableInfo
{
    /**
     * Banamex Info template.
     *
     * @var string
     */
    protected $_template = 'MercadoPago_PaymentMagento::info/banamex/instructions.phtml';
}
