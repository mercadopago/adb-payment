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
use MercadoPago\AdbPayment\Gateway\Config\Config;
use MercadoPago\AdbPayment\Gateway\Config\ConfigYape;

/**
 * Payment form block by YAPE.
 *
 * @SuppressWarnings(PHPMD.CamelCasePropertyName)
 */
class Yape extends \Magento\Payment\Block\Form
{
    /**
     * YAPE template.
     *
     * @var string
     */
    protected $_template = 'MercadoPago_AdbPayment::form/yape.phtml';

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var ConfigYape
     */
    protected $configYape;

    /**
     * @var Quote
     */
    protected $sessionQuote;

    /**
     * @param Context    $context
     * @param Config     $config
     * @param ConfigYape $configYape
     * @param Quote      $sessionQuote
     */
    public function __construct(
        Context $context,
        Config $config,
        ConfigYape $configYape,
        Quote $sessionQuote
    ) {
        parent::__construct($context);
        $this->config = $config;
        $this->configYape = $configYape;
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

        return $this->configYape->getTitle($storeId);
    }

}
