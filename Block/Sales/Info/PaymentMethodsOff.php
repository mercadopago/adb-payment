<?php

namespace MercadoPago\AdbPayment\Block\Sales\Info;

use MercadoPago\AdbPayment\Block\Sales\Info\Info;

/**
 * Payment details form block by PaymentMethodsOff.
 *
 * @SuppressWarnings(PHPMD.CamelCasePropertyName)
 */
class PaymentMethodsOff extends Info
{
    /**
     * PaymentMethodsOff Info template.
     *
     * @var string
     */
    protected $_template = 'MercadoPago_AdbPayment::info/payment-methods-off/instructions.phtml';
}
