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
use MercadoPago\PaymentMagento\Gateway\Config\Config;
use MercadoPago\PaymentMagento\Gateway\Config\ConfigBoleto;

/**
 * Payment form block by ticket.
 *
 * @SuppressWarnings(PHPMD.CamelCasePropertyName)
 */
class Boleto extends \Magento\Payment\Block\Form
{
    /**
     * Boleto template.
     *
     * @var string
     */
    protected $_template = 'MercadoPago_PaymentMagento::form/boleto.phtml';

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var ConfigBoleto
     */
    protected $configBoleto;

    /**
     * @var Quote
     */
    protected $sessionQuote;

    /**
     * @param Context      $context
     * @param Config       $config
     * @param ConfigBoleto $configBoleto
     * @param Quote        $sessionQuote
     */
    public function __construct(
        Context $context,
        Config $config,
        ConfigBoleto $configBoleto,
        Quote $sessionQuote
    ) {
        parent::__construct($context);
        $this->config = $config;
        $this->configBoleto = $configBoleto;
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

        return $this->configBoleto->getTitle($storeId);
    }

    /**
     * Expiration.
     *
     * @return string
     */
    public function getExpiration()
    {
        $storeId = $this->getBackendSessionQuote()->getStoreId();

        return $this->configBoleto->getExpirationFormat($storeId);
    }

    /**
     * Instruction.
     *
     * @return Phrase
     */
    public function getInstruction()
    {
        $storeId = $this->getBackendSessionQuote()->getStoreId();

        $text = $this->configBoleto->getInstructionCheckoutBoleto($storeId);

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
        return ConfigBoleto::PAYMENT_METHOD_ID;
    }
}
