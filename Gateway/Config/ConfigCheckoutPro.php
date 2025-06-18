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
use Magento\Payment\Gateway\Config\Config as PaymentConfig;
use Magento\Store\Model\ScopeInterface;
use MercadoPago\AdbPayment\Gateway\Data\Checkout\Fingerprint;

/**
 * Gateway setting for the payment method for Checkout Pro.
 */
class ConfigCheckoutPro extends PaymentConfig
{
    /**
     * Method.
     */
    public const METHOD = 'mercadopago_adbpayment_checkout_pro';

    /**
     * Active.
     */
    public const ACTIVE = 'active';

    /**
     * Title.
     */
    public const TITLE = 'title';

    /**
     * Expiration.
     */
    public const EXPIRATION = 'expiration';

    /**
     * Binary Mode.
     */
    public const BINARY_MODE = 'binary_mode';

    /**
     * Excluded.
     */
    public const EXCLUDED = 'excluded';

    /**
     * Type Redirect.
     */
    public const TYPE_REDIRECT = 'type_redirect';

    /**
     * Style Modal Theme Color Header.
     */
    public const THEME_HEADER = 'theme_header';

    /**
     * Style Modal Theme Color Elements.
     */
    public const THEME_ELEMENTS = 'theme_elements';

    /**
     * Max Installments.
     */
    public const MAX_INSTALLMENTS = 'max_installments';

    /**
     * Instruction Checkout.
     */
    public const INSTRUCTION_CHECKOUT = 'instruction_checkout';

    /**
     * Include Facebook.
     */
    public const INCLUDE_FACEBOOK = 'include_facebook';

    /**
     * Facebook Ad.
     */
    public const FACEBOOK_AD = 'facebook_ad';

    /**
     * Include Google.
     */
    public const INCLUDE_GOOGLE = 'include_google';

    /**
     * Conversion Id.
     */
    public const CONVERSION_ID = 'conversion_id';

    /**
     * Conversion Label.
     */
    public const CONVERSION_LABEL = 'conversion_label';

    /**
     * Cho Pro Types.
     */
    public const CHOPRO_TYPES = 'chopro_types';

    /**
     * Cho Pro Icons.
     */
    public const CHOPRO_ICONS = 'chopro_icons';

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var DateTime
     */
    protected $date;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var Fingerprint
     */
    protected $fingerprint;

    /**
     * @var string|null
     */
    protected $methodCode;

    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param DateTime             $date
     * @param Config               $config
     * @param Fingerprint          $fingerprint
     * @param string               $methodCode
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        DateTime $date,
        Config $config,
        Fingerprint $fingerprint,
        $methodCode = self::METHOD
    ) {
        parent::__construct($scopeConfig, $methodCode);
        $this->setMethodCode($methodCode);
        $this->scopeConfig = $scopeConfig;
        $this->date = $date;
        $this->config = $config;
        $this->fingerprint = $fingerprint;
        $this->methodCode = $methodCode;
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
            sprintf($pathPattern, $this->methodCode, self::ACTIVE),
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

        return __(
            $this->scopeConfig->getValue(
                sprintf($pathPattern, $this->methodCode, self::TITLE),
                ScopeInterface::SCOPE_STORE,
                $storeId
            )
        );
    }

    /**
     * Get Instruction - Checkout.
     *
     * @param int|null $storeId
     *
     * @return string
     */
    public function getInstructionCheckout(?int $storeId = null): string
    {
        $pathPattern = 'payment/%s/%s';

        return $this->scopeConfig->getValue(
            sprintf($pathPattern, $this->methodCode, self::INSTRUCTION_CHECKOUT),
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Get Excluded.
     *
     * @param int|null $storeId
     *
     * @return array|null
     */
    public function getExcluded(?int $storeId = null): ?array
    {
        $pathPattern = 'payment/%s/%s';

        $excluded = $this->scopeConfig->getValue(
            sprintf($pathPattern, $this->methodCode, self::EXCLUDED),
            ScopeInterface::SCOPE_STORE,
            $storeId
        );

        if (isset($excluded)) {
            return explode(',', $excluded);
        }

        return null;
    }

    /**
     * Get Expiration Formatted.
     *
     * @param int|null $storeId
     *
     * @return string
     */
    public function getExpirationFormatted(?int $storeId = null): string
    {
        $pathPattern = 'payment/%s/%s';
        $due = $this->scopeConfig->getValue(
            sprintf($pathPattern, $this->methodCode, self::EXPIRATION),
            ScopeInterface::SCOPE_STORE,
            $storeId
        );

        return $this->date->gmtDate('Y-m-d\T23:59:59.0000', strtotime("+{$due} days"));
    }

    /**
     * Get Expiration.
     *
     * @param int|null $storeId
     *
     * @return string
     */
    public function getExpiration(?int $storeId = null): string
    {
        $pathPattern = 'payment/%s/%s';
        $due = $this->scopeConfig->getValue(
            sprintf($pathPattern, $this->methodCode, self::EXPIRATION),
            ScopeInterface::SCOPE_STORE,
            $storeId
        );

        return $this->date->gmtDate('d/m/Y', strtotime("+{$due} days"));
    }

    /**
     * Get Expiration Formart.
     *
     * @param int|null $storeId
     *
     * @return string
     */
    public function getExpirationFormat(?int $storeId = null): string
    {
        $pathPattern = 'payment/%s/%s';
        $due = $this->scopeConfig->getValue(
            sprintf($pathPattern, $this->methodCode, self::EXPIRATION),
            ScopeInterface::SCOPE_STORE,
            $storeId
        );

        return $this->date->gmtDate('d/m/Y', strtotime("+{$due} days"));
    }

    /**
     * Is Binary Mode.
     *
     * @param int|null $storeId
     *
     * @return bool
     */
    public function isBinaryMode(?int $storeId = null): bool
    {
        $pathPattern = 'payment/%s/%s';

        return $this->scopeConfig->getValue(
            sprintf($pathPattern, $this->methodCode, self::BINARY_MODE),
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Get Type Redirect.
     *
     * @param int|null $storeId
     *
     * @return string
     */
    public function getTypeRedirect(?int $storeId = null): string
    {
        $pathPattern = 'payment/%s/%s';

        return $this->scopeConfig->getValue(
            sprintf($pathPattern, $this->methodCode, self::TYPE_REDIRECT),
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Get Max Installments.
     *
     * @param int|null $storeId
     *
     * @return int|null
     */
    public function getMaxInstallments(?int $storeId = null): ?int
    {
        $pathPattern = 'payment/%s/%s';

        return $this->scopeConfig->getValue(
            sprintf($pathPattern, $this->methodCode, self::MAX_INSTALLMENTS),
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Is Include Facebook.
     *
     * @param int|null $storeId
     *
     * @return bool
     */
    public function isIncludeFacebook(?int $storeId = null): bool
    {
        $pathPattern = 'payment/%s/%s';

        return (bool) $this->scopeConfig->getValue(
            sprintf($pathPattern, $this->methodCode, self::INCLUDE_FACEBOOK),
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Get Facebook Ad.
     *
     * @param int|null $storeId
     *
     * @return string|null
     */
    public function getFacebookAd(?int $storeId = null): ?string
    {
        $pathPattern = 'payment/%s/%s';

        return $this->scopeConfig->getValue(
            sprintf($pathPattern, $this->methodCode, self::FACEBOOK_AD),
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Is Include Google.
     *
     * @param int|null $storeId
     *
     * @return bool
     */
    public function isIncludeGoogle(?int $storeId = null): bool
    {
        $pathPattern = 'payment/%s/%s';

        return (bool) $this->scopeConfig->getValue(
            sprintf($pathPattern, $this->methodCode, self::INCLUDE_GOOGLE),
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Get Google Ads Id.
     *
     * @param int|null $storeId
     *
     * @return string|null
     */
    public function getGoogleAdsId(?int $storeId = null): ?string
    {
        $pathPattern = 'payment/%s/%s';

        return $this->scopeConfig->getValue(
            sprintf($pathPattern, $this->methodCode, self::CONVERSION_ID),
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Get Google Ads Label.
     *
     * @param int|null $storeId
     *
     * @return string|null
     */
    public function getGoogleAdsLabel(?int $storeId = null): ?string
    {
        $pathPattern = 'payment/%s/%s';

        return $this->scopeConfig->getValue(
            sprintf($pathPattern, $this->methodCode, self::CONVERSION_LABEL),
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Get Styles Header Color.
     *
     * @param int|null $storeId
     *
     * @return string
     */
    public function getStylesHeaderColor(?int $storeId = null): string
    {
        $pathPattern = 'payment/%s/%s';

        return $this->scopeConfig->getValue(
            sprintf($pathPattern, $this->methodCode, self::THEME_HEADER),
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Get Styles Elments Color.
     *
     * @param int|null $storeId
     *
     * @return string
     */
    public function getStylesElementsColor(?int $storeId = null): string
    {
        $pathPattern = 'payment/%s/%s';

        return $this->scopeConfig->getValue(
            sprintf($pathPattern, $this->methodCode, self::THEME_ELEMENTS),
            ScopeInterface::SCOPE_STORE,
            $storeId
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
     * Should return the flags for checkout pro .
     *
     * @param int|null $storeId
     *
     * @return string
     */
    public function getChoProAvailableTypes(?int $storeId = null): string
    {
        $pathPattern = 'payment/%s/%s_%s';

        $mpSiteId = $this->config->getMpSiteId($storeId);

        $result = $this->scopeConfig->getValue(
            sprintf(
                $pathPattern,
                self::METHOD,
                self::CHOPRO_TYPES,
                strtolower((string) $mpSiteId)
            ),
            ScopeInterface::SCOPE_STORE,
            $storeId
        );

        return $result ?: '';
    }

     /**
     * Should return the icons for checkout pro body.
     *
     * @param int|null $storeId
     *
     * @return string
     */
    public function getChoProInfoIcons(?int $storeId = null): string
    {
        $pathPattern = 'payment/%s/%s_%s';

        $mpSiteId = $this->config->getMpSiteId($storeId);

        $result = $this->scopeConfig->getValue(
            sprintf(
                $pathPattern,
                self::METHOD,
                self::CHOPRO_ICONS,
                strtolower((string) $mpSiteId)
            ),
            ScopeInterface::SCOPE_STORE,
            $storeId
        );

        return $result ?: '';
    }
}
