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
    protected $_template = 'MercadoPago_PaymentMagento::info/webpay/instructions.phtml';
}
