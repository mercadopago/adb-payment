<?php
/**
 * Copyright © MercadoPago. All rights reserved.
 *
 * @license     See LICENSE for license details.
 */

namespace MercadoPago\AdbPayment\Block\Sales\Form;

use Magento\Payment\Block\Form\Cc as NativeCc;

/**
 * Payment form block by card.
 *
 * @SuppressWarnings(PHPMD.CamelCasePropertyName)
 */
class TwoCc extends NativeCc
{
    /**
     * TwoCc template.
     *
     * @var string
     */
    protected $_template = 'MercadoPago_AdbPayment::form/twocc.phtml';
}
