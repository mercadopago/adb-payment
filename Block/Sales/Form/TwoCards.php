<?php
/**
 * Copyright © MercadoPago. All rights reserved.
 *
 * @author      Bruno Elisei <brunoelisei@o2ti.com>
 * @license     See LICENSE for license details.
 */

namespace MercadoPago\PaymentMagento\Block\Sales\Form;

use Magento\Payment\Block\Form\Cc as NativeCc;

/**
 * Payment form block by card.
 *
 * @SuppressWarnings(PHPMD.CamelCasePropertyName)
 */
class TwoCards extends NativeCc
{
    /**
     * TwoCc template.
     *
     * @var string
     */
    protected $_template = 'MercadoPago_PaymentMagento::form/twocc.phtml';
}
