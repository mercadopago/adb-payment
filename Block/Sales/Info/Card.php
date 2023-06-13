<?php
/**
 * Copyright © MercadoPago. All rights reserved.
 *
 * @author      Mercado Pago
 * @license     See LICENSE for license details.
 */

namespace MercadoPago\AdbPayment\Block\Sales\Info;

use Magento\Payment\Block\ConfigurableInfo;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\View\Element\Template\Context;
use Magento\Payment\Gateway\ConfigInterface;

/**
 * Payment details form block by card.
 *
 * @SuppressWarnings(PHPMD.CamelCaseMethodName)
 */
class Card extends ConfigurableInfo
{
    /**
     * Checkout Pro Info template.
     *
     * @var string
     */
    protected $_template = 'MercadoPago_AdbPayment::info/cc/instructions.phtml';

    /** @var PriceCurrencyInterface $priceCurrency */
    protected $priceCurrency;

    public function __construct(
        Context $context,
        ConfigInterface $config,
        PriceCurrencyInterface $priceCurrency,
        array $data = []
    ) {
        parent::__construct($context, $config, $data);

        $this->priceCurrency = $priceCurrency;
    }

    /**
     * Function getFormatedPrice
     *
     * @param float $price
     *
     * @return string
     */
    public function getFormatedPrice($amount)
    {
        return $this->priceCurrency->convertAndFormat($amount);
    }
}
