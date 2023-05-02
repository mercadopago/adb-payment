<?php
/**
 * Copyright © MercadoPago. All rights reserved.
 *
 * @author      Mercado Pago
 * @license     See LICENSE for license details.
 */

namespace MercadoPago\AdbPayment\Block\Sales\Form;

use Magento\Payment\Block\Form\Cc as NativeCc;

/**
 * Payment form block by card.
 *
 * @SuppressWarnings(PHPMD.CamelCasePropertyName)
 */
class Card extends NativeCc
{
    /**
     * Cc template.
     *
     * @var string
     */
    protected $_template = 'MercadoPago_AdbPayment::form/cc.phtml';
}
