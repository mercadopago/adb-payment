<?php

namespace MercadoPago\PaymentMagento\Block\Sales\Info;

use Magento\Payment\Block\ConfigurableInfo;

/**
 * Payment details form block by ticket boleto.
 *
 * @SuppressWarnings(PHPMD.CamelCasePropertyName)
 */
class PaymentMethodsOff extends ConfigurableInfo
{
    /**
     * Ticket Info template.
     *
     * @var string
     */
    protected $_template = 'MercadoPago_PaymentMagento::info/payment-methods-off/instructions.phtml';
}
