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
 * Gateway setting for the payment method for Card.
 */
class ConfigCc extends PaymentConfig
{
    /**
     * Method.
     */
    public const METHOD = 'mercadopago_adbpayment_cc';

    /**
     * Cc Types.
     */
    public const CC_TYPES = 'cctypes';

    /**
     * Cvv Enabled.
     */
    public const CVV_ENABLED = 'cvv_enabled';

    /**
     * Active.
     */
    public const ACTIVE = 'active';

    /**
     * Title.
     */
    public const TITLE = 'title';

    /**
     * Cc Types Mapper.
     */
    public const CC_MAPPER = 'cctypes_mapper';

    /**
     * Get Document Identification.
     */
    public const USE_GET_DOCUMENT_IDENTIFICATION = 'get_document_identification';

    /**
     * Unsupported Pre Auth.
     */
    public const UNSUPPORTED_PRE_AUTH = 'unsupported_pre_auth';

    /**
     * Can Initialize.
     */
    public const CAN_INITIALIZE = 'can_initialize';

    /**
     * Payment Action.
     */
    public const PAYMENT_ACTION = 'payment_action';

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
     * Should the cvv field be shown.
     *
     * @param int|null $storeId
     *
     * @return bool
     */
    public function isCvvEnabled($storeId = null): bool
    {
        $pathPattern = 'payment/%s/%s';

        return (bool) $this->scopeConfig->getValue(
            sprintf($pathPattern, self::METHOD, self::CVV_ENABLED),
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
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

        return __($this->scopeConfig->getValue(
            sprintf($pathPattern, self::METHOD, self::TITLE),
            ScopeInterface::SCOPE_STORE,
            $storeId)
        );
    }

    /**
     * Get if you use document capture on the form.
     *
     * @param int|null $storeId
     *
     * @return bool
     */
    public function hasUseDocumentIdentificationCapture($storeId = null): bool
    {
        $pathPattern = 'payment/%s/%s';

        return (bool) $this->scopeConfig->getValue(
            sprintf($pathPattern, self::METHOD, self::USE_GET_DOCUMENT_IDENTIFICATION),
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
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

        $canInitialize = $this->scopeConfig->getValue(
            sprintf($pathPattern, self::METHOD, self::CAN_INITIALIZE),
            ScopeInterface::SCOPE_STORE,
            $storeId
        );

        if ($canInitialize) {
            return false;
        }

        return true;
    }

    /**
     * Should the cc types.
     *
     * @param int|null $storeId
     *
     * @return string
     */
    public function getCcAvailableTypes($storeId = null): string
    {
        $pathPattern = 'payment/%s/%s_%s';

        $mpSiteId = $this->config->getMpSiteId($storeId);

        $result = $this->scopeConfig->getValue(
            sprintf(
                $pathPattern,
                self::METHOD,
                self::CC_TYPES,
                strtolower((string) $mpSiteId)
            ),
            ScopeInterface::SCOPE_STORE,
            $storeId
        );

        return $result ?: '';
    }

    /**
     * Cc Mapper.
     *
     * @param int|null $storeId
     *
     * @return array
     */
    public function getCcTypesMapper($storeId = null): array
    {
        $pathPattern = 'payment/%s/%s_%s';

        $mpSiteId = $this->config->getMpSiteId($storeId);

        $ccTypesMapper = $this->scopeConfig->getValue(
            sprintf(
                $pathPattern,
                self::METHOD,
                self::CC_MAPPER,
                strtolower((string) $mpSiteId)
            ),
            ScopeInterface::SCOPE_STORE,
            $storeId
        );

        $result = $ccTypesMapper ? $this->json->unserialize($ccTypesMapper) : null;

        return is_array($result) ? $result : [];
    }

    /**
     * Get Unsupported Pre Auth.
     *
     * @param int|null $storeId
     *
     * @return array
     */
    public function getUnsupportedPreAuth($storeId = null): array
    {
        $pathPattern = 'payment/%s/%s';

        $ccTypesMapper = $this->scopeConfig->getValue(
            sprintf(
                $pathPattern,
                self::METHOD,
                self::UNSUPPORTED_PRE_AUTH
            ),
            ScopeInterface::SCOPE_STORE,
            $storeId
        );

        $result = $this->json->unserialize($ccTypesMapper);

        return is_array($result) ? $result : [];
    }

    /**
     * Has Capture.
     *
     * @param int|null $storeId
     *
     * @return bool
     */
    public function hasCapture($storeId = null): bool
    {
        $pathPattern = 'payment/%s/%s';

        $typeAction = $this->scopeConfig->getValue(
            sprintf(
                $pathPattern,
                self::METHOD,
                'payment_action'
            ),
            ScopeInterface::SCOPE_STORE,
            $storeId
        );

        if ($typeAction === 'authorize') {
            return false;
        }

        return true;
    }

    /**
     * Get terms and conditions link
     *
     * @param int|null $storeId
     *
     * @return string
     */
    public function getFingerPrintLink($storeId = null): string
    {
        $mpSiteId = $this->config->getMpSiteId($storeId);

        return $this->fingerprint->getFingerPrintLink($mpSiteId);
    }
}
