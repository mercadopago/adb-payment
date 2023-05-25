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
use MercadoPago\AdbPayment\Gateway\Config\ConfigPix;

/**
 * Payment form block by Pix.
 *
 * @SuppressWarnings(PHPMD.CamelCasePropertyName)
 */
class Pix extends \Magento\Payment\Block\Form
{
    /**
     * Pix template.
     *
     * @var string
     */
    protected $_template = 'MercadoPago_AdbPayment::form/pix.phtml';

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var ConfigPix
     */
    protected $configPix;

    /**
     * @var Quote
     */
    protected $sessionQuote;

    /**
     * @param Context   $context
     * @param Config    $config
     * @param ConfigPix $configPix
     * @param Quote     $sessionQuote
     */
    public function __construct(
        Context $context,
        Config $config,
        ConfigPix $configPix,
        Quote $sessionQuote
    ) {
        parent::__construct($context);
        $this->config = $config;
        $this->configPix = $configPix;
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

        return $this->configPix->getTitle($storeId);
    }

    /**
     * Instruction.
     *
     * @return Phrase
     */
    public function getInstruction()
    {
        $storeId = $this->getBackendSessionQuote()->getStoreId();

        $time = $this->configPix->getExpirationTextFormatted($storeId);
        $text = $this->configPix->getInstructionCheckout($storeId);

        return __($text, $time);
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
