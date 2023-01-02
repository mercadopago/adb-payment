<?php
/**
 * Copyright © MercadoPago. All rights reserved.
 *
 * @author      Bruno Elisei <brunoelisei@o2ti.com>
 * @license     See LICENSE for license details.
 */

namespace MercadoPago\PaymentMagento\Block\Sales\Form;

use Magento\Backend\Model\Session\Quote;
use Magento\Framework\Phrase;
use Magento\Framework\View\Element\Template\Context;
use MercadoPago\PaymentMagento\Gateway\Config\ConfigCheckoutPro;

/**
 * Payment form block by checkout pro.
 *
 * @SuppressWarnings(PHPMD.CamelCasePropertyName)
 */
class CheckoutPro extends \Magento\Payment\Block\Form
{
    /**
     * CheckoutPro template.
     *
     * @var string
     */
    protected $_template = 'MercadoPago_PaymentMagento::form/checkout-pro.phtml';

    /**
     * @var ConfigCheckoutPro
     */
    protected $configCheckoutPro;

    /**
     * @var Quote
     */
    protected $sessionQuote;

    /**
     * @param Context           $context
     * @param ConfigCheckoutPro $configCheckoutPro
     * @param Quote             $sessionQuote
     */
    public function __construct(
        Context $context,
        ConfigCheckoutPro $configCheckoutPro,
        Quote $sessionQuote
    ) {
        parent::__construct($context);
        $this->configCheckoutPro = $configCheckoutPro;
        $this->sessionQuote = $sessionQuote;
    }

    /**
     * Get Backend Session Quote.
     */
    public function getBackendSessionQuote()
    {
        return $this->sessionQuote->getQuote();
    }

    /**
     * Title.
     *
     * @return string
     */
    public function getTitle()
    {
        $storeId = $this->getBackendSessionQuote()->getStoreId();

        return $this->configCheckoutPro->getTitle($storeId);
    }

    /**
     * Expiration.
     *
     * @return string
     */
    public function getExpiration()
    {
        $storeId = $this->getBackendSessionQuote()->getStoreId();

        return $this->configCheckoutPro->getExpirationFormat($storeId);
    }

    /**
     * Instruction.
     *
     * @return Phrase
     */
    public function getInstruction()
    {
        $storeId = $this->getBackendSessionQuote()->getStoreId();

        $text = $this->configCheckoutPro->getInstructionCheckout($storeId);

        return __($text);
    }
}
