<?php

namespace MercadoPago\PaymentMagento\Block\Sales\Form;

use Magento\Backend\Model\Session\Quote;
use Magento\Framework\Phrase;
use Magento\Framework\View\Element\Template\Context;
use MercadoPago\PaymentMagento\Gateway\Config\Config;
use MercadoPago\PaymentMagento\Gateway\Config\ConfigPaymentMethodsOff;

/**
 * Payment form block by PaymentMethodsOff.
 *
 * @SuppressWarnings(PHPMD.CamelCasePropertyName)
 */
class PaymentMethodsOff extends \Magento\Payment\Block\Form
{
    /**
     * Payment Methods Off template.
     *
     * @var string
     */
    protected $_template = 'MercadoPago_PaymentMagento::form/payment-methods-off.phtml';

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var ConfigPaymentMethodsOff
     */
    protected $configPaymentMethodsOff;

    /**
     * @var Quote
     */
    protected $sessionQuote;

    /**
     * @param Context      $context
     * @param Config       $config
     * @param ConfigPaymentMethodsOff $configPaymentMethodsOff
     * @param Quote        $sessionQuote
     */
    public function __construct(
        Context $context,
        Config $config,
        ConfigPaymentMethodsOff $configPaymentMethodsOff,
        Quote $sessionQuote
    ) {
        parent::__construct($context);
        $this->config = $config;
        $this->configPaymentMethodsOff = $configPaymentMethodsOff;
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

        return $this->configPaymentMethodsOff->getTitle($storeId);
    }

    /**
     * Expiration.
     *
     * @return string
     */
    public function getExpiration()
    {
        $storeId = $this->getBackendSessionQuote()->getStoreId();

        return $this->configPaymentMethodsOff->getExpirationFormat($storeId);
    }

    /**
     * Mp Public Key.
     *
     * @return string
     */
    public function getMpPublicKey()
    {
        $storeId = $this->getBackendSessionQuote()->getStoreId();

        return $this->config->getMerchantGatewayClientId($storeId);
    }

}
