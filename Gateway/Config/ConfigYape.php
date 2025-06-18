<?php
/**
 * Copyright © MercadoPago. All rights reserved.
 *
 * @author      Mercado Pago
 * @license     See LICENSE for license details.
 */

namespace MercadoPago\AdbPayment\Gateway\Config;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Payment\Gateway\Config\Config as PaymentConfig;
use Magento\Store\Model\ScopeInterface;
use MercadoPago\AdbPayment\Gateway\Data\Checkout\Fingerprint;

/**
 * Gateway setting for the payment method for Yape.
 */
class ConfigYape extends PaymentConfig
{
    /**
     * Method.
     */
    public const METHOD = 'mercadopago_adbpayment_yape';

    /**
     * Active.
     */
    public const ACTIVE = 'active';

    /**
     * Title.
     */
    public const TITLE = 'title';

    /**
     * Maximum Order Amount Total.
     */
    public const MAX_ORDER_TOTAL = 'max_order_total';

    /**
     * Attention icon
     */
    public const ERROR_ICON = 'error_icon';

    /**
     * Icons
     */
    public const ICONS = 'icons';

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var Json
     */
    protected $json;

    /**
     * @var Fingerprint
     */
    protected $fingerprint;

    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param Json                 $json
     * @param Config               $config
     * @param Fingerprint          $fingerprint
     * @param string               $methodCode
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        Json $json,
        Config $config,
        Fingerprint $fingerprint,
        $methodCode = self::METHOD
    ) {
        parent::__construct($scopeConfig, $methodCode);
        $this->scopeConfig = $scopeConfig;
        $this->json = $json;
        $this->config = $config;
        $this->fingerprint = $fingerprint;
    }

    /**
     * Get Payment configuration status.
     *
     * @param int|null $storeId
     *
     * @return bool
     */
    public function isActive(?int $storeId = null): bool
    {
        $pathPattern = 'payment/%s/%s';

        return (bool) $this->scopeConfig->getValue(
            sprintf($pathPattern, self::METHOD, self::ACTIVE),
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Get title of payment.
     *
     * @param int|null $storeId
     *
     * @return string
     */
    public function getTitle(?int $storeId = null): string
    {
        $pathPattern = 'payment/%s/%s';

        return __($this->scopeConfig->getValue(
            sprintf($pathPattern, self::METHOD, self::TITLE),
            ScopeInterface::SCOPE_STORE,
            $storeId)
        );
    }

    /**
     * Get terms and conditions link
     *
     * @param int|null $storeId
     *
     * @return string
     */
    public function getFingerPrintLink(?int $storeId = null): string
    {
        $mpSiteId = $this->config->getMpSiteId($storeId);

        return $this->fingerprint->getFingerPrintLink($mpSiteId);
    }

    /**
     * Get Maximum order total
     *
     * @param int|null $storeId
     *
     * @return float
     */
    public function getMaximumOrderTotal(?int $storeId = null): float
    {
        $pathPattern = 'payment/%s/%s';

        return (float)$this->scopeConfig->getValue(
            sprintf($pathPattern, self::METHOD, self::MAX_ORDER_TOTAL),
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Should return info icons.
     *
     * @param int|null $storeId
     *
     * @return string
     */
    public function getIcons(?int $storeId = null): string
    {
        $pathPattern = 'payment/%s/%s';
        $result = $this->scopeConfig->getValue(
            sprintf(
                $pathPattern,
                self::METHOD,
                self::ICONS,
            ),
            ScopeInterface::SCOPE_STORE,
            $storeId
        );

        return $result ?: '';
    }

}
