<?php
/**
 * Copyright © MercadoPago. All rights reserved.
 *
 * @author      Bruno Elisei <brunoelisei@o2ti.com>
 * @license     See LICENSE for license details.
 */

namespace MercadoPago\PaymentMagento\Gateway\Config;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Payment\Gateway\Config\Config as PaymentConfig;
use Magento\Store\Model\ScopeInterface;

/**
 * Gateway setting for the payment method for Checkout Pro.
 */
class ConfigCheckoutPro extends PaymentConfig
{
    /**
     * Method.
     */
    public const METHOD = 'mercadopago_paymentmagento_checkout_pro';

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
     * Facebook Ad.
     */
    public const FACEBOOK_AD = 'facebook_ad';

    /**
     * Conversion Id.
     */
    public const CONVERSION_ID = 'conversion_id';

    /**
     * Conversion Label.
     */
    public const CONVERSION_LABEL = 'conversion_label';

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
     * @param ScopeConfigInterface $scopeConfig
     * @param DateTime             $date
     * @param Config               $config
     * @param string               $methodCode
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        DateTime $date,
        Config $config,
        $methodCode = self::METHOD
    ) {
        parent::__construct($scopeConfig, $methodCode);
        $this->setMethodCode(self::METHOD);
        $this->scopeConfig = $scopeConfig;
        $this->date = $date;
        $this->config = $config;
    }

    /**
     * Get Payment configuration status.
     *
     * @param int|null $storeId
     *
     * @return bool
     */
    public function isActive($storeId = null): bool
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
    public function getTitle($storeId = null): string
    {
        $pathPattern = 'payment/%s/%s';

        return $this->scopeConfig->getValue(
            sprintf($pathPattern, self::METHOD, self::TITLE),
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Get Instruction - Checkout.
     *
     * @param int|null $storeId
     *
     * @return string
     */
    public function getInstructionCheckout($storeId = null): string
    {
        $pathPattern = 'payment/%s/%s';

        return $this->scopeConfig->getValue(
            sprintf($pathPattern, self::METHOD, self::INSTRUCTION_CHECKOUT),
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
    public function getExcluded($storeId = null): ?array
    {
        $pathPattern = 'payment/%s/%s';

        $excluded = $this->scopeConfig->getValue(
            sprintf($pathPattern, self::METHOD, self::EXCLUDED),
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
    public function getExpirationFormatted($storeId = null): string
    {
        $pathPattern = 'payment/%s/%s';
        $due = $this->scopeConfig->getValue(
            sprintf($pathPattern, self::METHOD, self::EXPIRATION),
            ScopeInterface::SCOPE_STORE,
            $storeId
        );

        return $this->date->gmtDate('Y-m-d\T23:59:59.0000', strtotime("+{$due} days"));
    }

    /**
     * Get Expired Payment Date.
     *
     * @param int|null $storeId
     *
     * @return string
     */
    public function getExpiredPaymentDate($storeId = null): string
    {
        $pathPattern = 'payment/%s/%s';

        $due = $this->scopeConfig->getValue(
            sprintf($pathPattern, self::METHOD, self::EXPIRATION),
            ScopeInterface::SCOPE_STORE,
            $storeId
        ) + 2;

        return $this->date->gmtDate('Y-m-d 23:59:59', strtotime("-{$due} days"));
    }

    /**
     * Get Expiration.
     *
     * @param int|null $storeId
     *
     * @return string
     */
    public function getExpiration($storeId = null): string
    {
        $pathPattern = 'payment/%s/%s';
        $due = $this->scopeConfig->getValue(
            sprintf($pathPattern, self::METHOD, self::EXPIRATION),
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
    public function getExpirationFormat($storeId = null): string
    {
        $pathPattern = 'payment/%s/%s';
        $due = $this->scopeConfig->getValue(
            sprintf($pathPattern, self::METHOD, self::EXPIRATION),
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
    public function isBinaryMode($storeId = null): bool
    {
        $pathPattern = 'payment/%s/%s';

        return $this->scopeConfig->getValue(
            sprintf($pathPattern, self::METHOD, self::BINARY_MODE),
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
    public function getTypeRedirect($storeId = null): string
    {
        $pathPattern = 'payment/%s/%s';

        return $this->scopeConfig->getValue(
            sprintf($pathPattern, self::METHOD, self::TYPE_REDIRECT),
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
    public function getMaxInstallments($storeId = null): ?int
    {
        $pathPattern = 'payment/%s/%s';

        return $this->scopeConfig->getValue(
            sprintf($pathPattern, self::METHOD, self::MAX_INSTALLMENTS),
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
    public function getFacebookAd($storeId = null): ?string
    {
        $pathPattern = 'payment/%s/%s';

        return $this->scopeConfig->getValue(
            sprintf($pathPattern, self::METHOD, self::FACEBOOK_AD),
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
    public function getGoogleAdsId($storeId = null): ?string
    {
        $pathPattern = 'payment/%s/%s';

        return $this->scopeConfig->getValue(
            sprintf($pathPattern, self::METHOD, self::CONVERSION_ID),
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
    public function getGoogleAdsLabel($storeId = null): ?string
    {
        $pathPattern = 'payment/%s/%s';

        return $this->scopeConfig->getValue(
            sprintf($pathPattern, self::METHOD, self::CONVERSION_LABEL),
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
    public function getStylesHeaderColor($storeId = null): string
    {
        $pathPattern = 'payment/%s/%s';

        return $this->scopeConfig->getValue(
            sprintf($pathPattern, self::METHOD, self::THEME_HEADER),
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
    public function getStylesElementsColor($storeId = null): string
    {
        $pathPattern = 'payment/%s/%s';

        return $this->scopeConfig->getValue(
            sprintf($pathPattern, self::METHOD, self::THEME_ELEMENTS),
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }
}
