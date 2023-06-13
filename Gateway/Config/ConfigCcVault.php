<?php
/**
 * Copyright © MercadoPago. All rights reserved.
 *
 * @author      Mercado Pago
 * @license     See LICENSE for license details.
 */

namespace MercadoPago\AdbPayment\Gateway\Config;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Payment\Gateway\Config\Config as PaymentConfig;
use Magento\Store\Model\ScopeInterface;

/**
 * Gateway setting for the payment method for Vault.
 */
class ConfigCcVault extends PaymentConfig
{
    /**
     * Cvv Enabled.
     */
    public const CVV_ENABLED = 'cvv_enabled';

    /**
     * Mercadopago AdbPayment Cc Vault.
     */
    public const METHOD = 'mercadopago_adbpayment_cc_vault';

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param string               $methodCode
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        $methodCode = self::METHOD
    ) {
        parent::__construct($scopeConfig, $methodCode);
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * Get If Use Cvv.
     *
     * @param int|null $storeId
     *
     * @return bool
     */
    public function useCvv($storeId = null): bool
    {
        $pathPattern = 'payment/%s/%s';

        return (bool) $this->scopeConfig->getValue(
            sprintf($pathPattern, self::METHOD, self::CVV_ENABLED),
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }
}
