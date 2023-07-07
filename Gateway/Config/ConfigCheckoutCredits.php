<?php

/**
 * Copyright © MercadoPago. All rights reserved.
 *
 * @author      Mercado Pago
 * @license     See LICENSE for license details.
 */

namespace MercadoPago\AdbPayment\Gateway\Config;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Store\Model\ScopeInterface;
use MercadoPago\AdbPayment\Gateway\Data\Checkout\Fingerprint;
use MercadoPago\AdbPayment\Gateway\Config\ConfigCheckoutPro as ConfigPro;

/**
 * Gateway setting for the payment method for Checkout Credits.
 */
class ConfigCheckoutCredits extends ConfigPro
{
    /**
     * Method.
     */
    public const METHOD = 'mercadopago_adbpayment_checkout_credits';

    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param DateTime             $date
     * @param Config               $config
     * @param string               $methodCode
     * @param Fingerprint          $fingerprint
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        DateTime $date,
        Config $config,
        Fingerprint $fingerprint,
        $methodCode = self::METHOD
    ) {
        parent::__construct($scopeConfig, $date, $config, $fingerprint, $methodCode);
    }

    /**
     * @param String $textId
     * @param String $storeId
     *
     * @return String
     */
    public function getBannerText($textId, $storeId = null): string
    {
        $pathPattern = 'payment/%s/%s';

        return $this->scopeConfig->getValue(
            sprintf($pathPattern, $this->methodCode, $textId),
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }
}
