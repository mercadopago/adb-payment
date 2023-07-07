<?php
/**
 * Copyright © MercadoPago. All rights reserved.
 *
 * @author      Mercado Pago
 * @license     See LICENSE for license details.
 */

namespace MercadoPago\AdbPayment\Gateway\Config;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Phrase;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Payment\Gateway\Config\Config as PaymentConfig;
use Magento\Store\Model\ScopeInterface;
use MercadoPago\AdbPayment\Gateway\Data\Checkout\Fingerprint;

/**
 * Gateway setting for the payment method for Pix.
 */
class ConfigPix extends PaymentConfig
{
    /**
     * Method.
     */
    public const METHOD = 'mercadopago_adbpayment_pix';

    /**
     * Active.
     */
    public const ACTIVE = 'active';

    /**
     * Title.
     */
    public const TITLE = 'title';

    /**
     * Instruction Checkout.
     */
    public const INSTRUCTION_CHECKOUT = 'instruction_checkout';

    /**
     * Expiration.
     */
    public const EXPIRATION = 'expiration';

    /**
     * Get Document Identification.
     */
    public const USE_GET_DOCUMENT_IDENTIFICATION = 'get_document_identification';

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
     * @param ScopeConfigInterface $scopeConfig
     * @param Config               $config
     * @param DateTime             $date
     * @param Fingerprint          $fingerprint
     * @param string               $methodCode
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        Config $config,
        DateTime $date,
        Fingerprint $fingerprint,
        $methodCode = self::METHOD
    ) {
        parent::__construct($scopeConfig, $methodCode);
        $this->scopeConfig = $scopeConfig;
        $this->config = $config;
        $this->date = $date;
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
    public function getExpirationFormatted($storeId = null): string
    {
        $pathPattern = 'payment/%s/%s';
        $due = $this->scopeConfig->getValue(
            sprintf($pathPattern, self::METHOD, self::EXPIRATION),
            ScopeInterface::SCOPE_STORE,
            $storeId
        );

        return $this->date->gmtDate('Y-m-d\TH:i:s.000O', strtotime("+{$due} minutes"));
    }

    /**
     * Get Expiration Text Formatted.
     *
     * @param int|null $storeId
     *
     * @return Phrase
     */
    public function getExpirationTextFormatted($storeId = null): Phrase
    {
        $pathPattern = 'payment/%s/%s';
        $due = $this->scopeConfig->getValue(
            sprintf($pathPattern, self::METHOD, self::EXPIRATION),
            ScopeInterface::SCOPE_STORE,
            $storeId
        );

        if ($due === '15' || $due === '30') {
            return __('%1 minutes', $due);
        }

        if ($due === '60') {
            return __('1 hour');
        }

        if ($due === '720') {
            return __('12 hours');
        }

        if ($due === '1440') {
            return __('1 day');
        }

        return __('%1 minutes', $due);
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
