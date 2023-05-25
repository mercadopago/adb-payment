<?php
/**
 * Copyright © MercadoPago. All rights reserved.
 *
 * @license     See LICENSE for license details.
 */

namespace MercadoPago\AdbPayment\Block\Sales\Info;

use Magento\Payment\Block\ConfigurableInfo;

/**
 * Payment details form block by Webpay.
 *
 * @SuppressWarnings(PHPMD.CamelCasePropertyName)
 */
class TwoCc extends ConfigurableInfo
{
    /**
     * TwoCc Info template.
     *
     * @var string
     */
    protected $_template = 'MercadoPago_AdbPayment::info/twocc/instructions.phtml';
}
