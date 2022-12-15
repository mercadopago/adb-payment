<?php
/**
 * Copyright © MercadoPago. All rights reserved.
 *
 * @author      Bruno Elisei <brunoelisei@o2ti.com>
 * @license     See LICENSE for license details.
 */

namespace MercadoPago\PaymentMagento\Block\Sales\Info;

use Magento\Framework\Phrase;
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
    protected $_template = 'MercadoPago_PaymentMagento::info/pix/instructions.phtml';

    /**
     * Returns label.
     *
     * @param string $field
     *
     * @return Phrase
     */
    protected function getLabel($field)
    {
        return __($field);
    }
}
