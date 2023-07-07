<?php

/**
 * Copyright © MercadoPago. All rights reserved.
 *
 * @author      Mercado Pago
 * @license     See LICENSE for license details.
 */

namespace MercadoPago\AdbPayment\Block\Sales\Form;

use Magento\Backend\Model\Session\Quote;
use Magento\Framework\View\Element\Template\Context;
use MercadoPago\AdbPayment\Gateway\Config\ConfigCheckoutCredits;
use MercadoPago\AdbPayment\Block\Sales\Form\CheckoutPro as CheckoutProBlock;

/**
 * Payment form block by checkout credits.
 *
 * @SuppressWarnings(PHPMD.CamelCasePropertyName)
 */
class CheckoutCredits extends CheckoutProBlock
{
    /**
     * Checkout Credits template.
     *
     * @var string
     */
    protected $_template = 'MercadoPago_AdbPayment::form/checkout-credits.phtml';

    /**
     * @var ConfigCheckoutCredits
     */
    protected $configCheckoutCredits;

    /**
     * @param Context               $context
     * @param ConfigCheckoutCredits $configCheckoutCredits
     * @param Quote                 $sessionQuote
     */
    public function __construct(
        Context $context,
        ConfigCheckoutCredits $configCheckoutCredits,
        Quote $sessionQuote
    ) {
        $this->configCheckoutCredits = $configCheckoutCredits;
        $this->sessionQuote = $sessionQuote;
        parent::__construct($context, $configCheckoutCredits, $sessionQuote);
    }

    /**
     * Title.
     *
     * @return string
     */
    public function getTitle()
    {
        $storeId = $this->getBackendSessionQuote()->getStoreId();

        return $this->configCheckoutCredits->getTitle($storeId);
    }

    /**
     * Expiration.
     *
     * @return string
     */
    public function getExpiration()
    {
        $storeId = $this->getBackendSessionQuote()->getStoreId();

        return $this->configCheckoutCredits->getExpirationFormat($storeId);
    }

    /**
     * Get banner text.
     *
     * @return string
     */
    public function getBanner()
    {
        $storeId = $this->getBackendSessionQuote()->getStoreId();

        $text = $this->configCheckoutCredits->getBannerText($storeId);

        return __($text);
    }
}
