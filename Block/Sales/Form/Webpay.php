<?php
/**
 * Copyright © MercadoPago. All rights reserved.
 *
 * @author      Mercado Pago
 * @license     See LICENSE for license details.
 */

namespace MercadoPago\AdbPayment\Block\Sales\Form;

use Magento\Backend\Model\Session\Quote;
use Magento\Framework\Phrase;
use Magento\Framework\View\Element\Template\Context;
use MercadoPago\AdbPayment\Gateway\Config\Config;
use MercadoPago\AdbPayment\Gateway\Config\ConfigWebpay;

/**
 * Payment form block by Webpay.
 *
 * @SuppressWarnings(PHPMD.CamelCasePropertyName)
 */
class Webpay extends \Magento\Payment\Block\Form
{
    /**
     * Webpay template.
     *
     * @var string
     */
    protected $_template = 'MercadoPago_AdbPayment::form/webpay.phtml';

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var ConfigWebpay
     */
    protected $configWebpay;

    /**
     * @var Quote
     */
    protected $sessionQuote;

    /**
     * @param Context      $context
     * @param Config       $config
     * @param ConfigWebpay $configWebpay
     * @param Quote        $sessionQuote
     */
    public function __construct(
        Context $context,
        Config $config,
        ConfigWebpay $configWebpay,
        Quote $sessionQuote
    ) {
        parent::__construct($context);
        $this->config = $config;
        $this->configWebpay = $configWebpay;
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

        return $this->configWebpay->getTitle($storeId);
    }

    /**
     * Expiration.
     *
     * @return string
     */
    public function getExpiration()
    {
        $storeId = $this->getBackendSessionQuote()->getStoreId();

        return $this->configWebpay->getExpirationFormat($storeId);
    }

    /**
     * Instruction.
     *
     * @return Phrase
     */
    public function getInstruction()
    {
        $storeId = $this->getBackendSessionQuote()->getStoreId();

        $text = $this->configWebpay->getInstructionCheckoutWebpay($storeId);

        return __($text);
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

    /**
     * Get Payment Method Id.
     *
     * @return string
     */
    public function getPaymentMethodId()
    {
        return ConfigWebpay::PAYMENT_METHOD_ID;
    }

    /**
     * Get Options Finance.
     */
    public function getOptionsFinancialInstitution()
    {
        $storeId = $this->getBackendSessionQuote()->getStoreId();

        return $this->configWebpay->getListFinancialInstitution($storeId);
    }
}
