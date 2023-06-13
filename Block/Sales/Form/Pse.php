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
use MercadoPago\AdbPayment\Gateway\Config\ConfigPse;

/**
 * Payment form block by Pse.
 *
 * @SuppressWarnings(PHPMD.CamelCasePropertyName)
 */
class Pse extends \Magento\Payment\Block\Form
{
    /**
     * Pse template.
     *
     * @var string
     */
    protected $_template = 'MercadoPago_AdbPayment::form/pse.phtml';

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var ConfigPse
     */
    protected $configPse;

    /**
     * @var Quote
     */
    protected $sessionQuote;

    /**
     * @param Context   $context
     * @param Config    $config
     * @param ConfigPse $configPse
     * @param Quote     $sessionQuote
     */
    public function __construct(
        Context $context,
        Config $config,
        ConfigPse $configPse,
        Quote $sessionQuote
    ) {
        parent::__construct($context);
        $this->config = $config;
        $this->configPse = $configPse;
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

        return $this->configPse->getTitle($storeId);
    }

    /**
     * Expiration.
     *
     * @return string
     */
    public function getExpiration()
    {
        $storeId = $this->getBackendSessionQuote()->getStoreId();

        return $this->configPse->getExpirationFormat($storeId);
    }

    /**
     * Instruction.
     *
     * @return Phrase
     */
    public function getInstruction()
    {
        $storeId = $this->getBackendSessionQuote()->getStoreId();

        $text = $this->configPse->getInstructionCheckoutPse($storeId);

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
        return ConfigPse::PAYMENT_METHOD_ID;
    }

    /**
     * Get Options Finance.
     */
    public function getOptionsFinancialInstitution()
    {
        $storeId = $this->getBackendSessionQuote()->getStoreId();

        return $this->configPse->getListFinancialInstitution($storeId);
    }

    /**
     * Get Options Payer Entity Type.
     */
    public function getOptionsPayerEntityType()
    {
        $storeId = $this->getBackendSessionQuote()->getStoreId();

        return $this->configPse->getListPayerEntityTypes($storeId);
    }
}
