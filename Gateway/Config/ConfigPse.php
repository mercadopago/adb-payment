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
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Payment\Gateway\Config\Config as PaymentConfig;
use Magento\Store\Model\ScopeInterface;
use MercadoPago\AdbPayment\Gateway\Config\Config as BaseConfig;
use MercadoPago\AdbPayment\Gateway\Data\Checkout\Fingerprint;

/**
 * Gateway setting for the payment method for Pse.
 */
class ConfigPse extends PaymentConfig
{
    /**
     * Method.
     */
    public const METHOD = 'mercadopago_adbpayment_pse';

    /**
     * Payment Method Id Pse.
     */
    public const PAYMENT_METHOD_ID = 'pse';

    /**
     * Active.
     */
    public const ACTIVE = 'active';

    /**
     * Title.
     */
    public const TITLE = 'title';

    /**
     * Instruction Checkout Pse.
     */
    public const INSTRUCTION_CHECKOUT_PSE = 'instruction_checkout_pse';

    /**
     * Expiration.
     */
    public const EXPIRATION = 'expiration';

    /**
     * Get Document Identification.
     */
    public const USE_GET_DOCUMENT_IDENTIFICATION = 'get_document_identification';

    /**
     * Payer Entity Types.
     */
    public const PAYER_ENTITY_TYPES = 'payer_entity_types';

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var DateTime
     */
    protected $date;

    /**
     * @var BaseConfig
     */
    protected $configBase;

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
     * @param DateTime             $date
     * @param BaseConfig           $configBase
     * @param Json                 $json
     * @param Fingerprint          $fingerprint
     * @param string               $methodCode
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        DateTime $date,
        BaseConfig $configBase,
        Json $json,
        Fingerprint $fingerprint,
        $methodCode = self::METHOD
    ) {
        parent::__construct($scopeConfig, $methodCode);
        $this->scopeConfig = $scopeConfig;
        $this->date = $date;
        $this->configBase = $configBase;
        $this->json = $json;
        $this->fingerprint = $fingerprint;
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
     * Get Expiration Formatted.
     *
     * @param int|null $storeId
     *
     * @return string
     */
    public function getExpirationFormatted($storeId = null): ?string
    {
        $pathPattern = 'payment/%s/%s';
        $due = $this->scopeConfig->getValue(
            sprintf($pathPattern, self::METHOD, self::EXPIRATION),
            ScopeInterface::SCOPE_STORE,
            $storeId
        );

        return $this->date->gmtDate('Y-m-d\T23:59:59.000O', strtotime("+{$due} days"));
    }

    /**
     * Get Instruction Checkoout for Pse.
     *
     * @param int|null $storeId
     *
     * @return string
     */
    public function getInstructionCheckoutPse($storeId = null): string
    {
        $pathPattern = 'payment/%s/%s';

        return $this->scopeConfig->getValue(
            sprintf($pathPattern, self::METHOD, self::INSTRUCTION_CHECKOUT_PSE),
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
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
     * Get List Financial Institution.
     *
     * @param int|null $storeId
     *
     * @return array
     */
    public function getListFinancialInstitution($storeId = null): array
    {
        $finInstitutions = [];
        $mpSiteId = $this->configBase->getMpSiteId($storeId);

        if ($mpSiteId === 'MCO') {
            $payments = $this->configBase->getMpPaymentMethods($storeId);

            foreach ($payments['response'] as $payment) {
                if ($payment['id'] === self::PAYMENT_METHOD_ID) {
                    $finInstitutions = $payment['financial_institutions'];
                }
            }
        }

        return $finInstitutions;
    }

    /**
     * Get List Payer Entity Types.
     *
     * @param int|null $storeId
     *
     * @return array
     */
    public function getListPayerEntityTypes($storeId = null): array
    {
        $pathPattern = 'payment/%s/%s';

        $types = $this->scopeConfig->getValue(
            sprintf($pathPattern, self::METHOD, self::PAYER_ENTITY_TYPES),
            ScopeInterface::SCOPE_STORE,
            $storeId
        );

        $result = $this->json->unserialize($types);

        return is_array($result) ? $result : [];
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
        $mpSiteId = $this->configBase->getMpSiteId($storeId);

        return $this->fingerprint->getFingerPrintLink($mpSiteId);
    }
}
