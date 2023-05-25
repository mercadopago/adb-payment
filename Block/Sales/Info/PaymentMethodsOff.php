<?php

namespace MercadoPago\AdbPayment\Block\Sales\Info;

use Magento\Payment\Block\ConfigurableInfo;

/**
 * Payment details form block by PaymentMethodsOff.
 *
 * @SuppressWarnings(PHPMD.CamelCasePropertyName)
 */
class PaymentMethodsOff extends ConfigurableInfo
{
    /**
     * PaymentMethodsOff Info template.
     *
     * @var string
     */
    protected $_template = 'MercadoPago_AdbPayment::info/payment-methods-off/instructions.phtml';
}
