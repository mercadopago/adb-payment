<?php
/**
 * Copyright © MercadoPago. All rights reserved.
 *
 * @author      Bruno Elisei <brunoelisei@o2ti.com>
 * @license     See LICENSE for license details.
 */

namespace MercadoPago\PaymentMagento\Gateway\Config;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Payment\Gateway\Config\Config as PaymentConfig;
use Magento\Store\Model\ScopeInterface;

/**
 * Gateway setting for the payment method for Card.
 */
class ConfigTwoCc extends PaymentConfig
{
    /**
     * Method.
     */
    public const METHOD = 'mercadopago_paymentmagento_twocc';

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
     * @param ScopeConfigInterface $scopeConfig
     * @param Json                 $json
     * @param Config               $config
     * @param string               $methodCode
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        Json $json,
        Config $config,
        $methodCode = self::METHOD
    ) {
        parent::__construct($scopeConfig, $methodCode);
        $this->scopeConfig = $scopeConfig;
        $this->json = $json;
        $this->config = $config;
    }

    /**
     * Should the cvv field be shown.
     *
     * @param int|null $storeId
     *
     * @return bool
     */
    public function isCvvEnabled(): bool
    {
        return true;

    }

    /**
     * Get Payment configuration status.
     *
     * @param int|null $storeId
     *
     * @return bool
     */
    public function isActive(): bool
    {

        return true;
    }

    /**
     * Get title of payment.
     *
     * @param int|null $storeId
     *
     * @return string
     */
    public function getTitle(): string
    {
        return "two cards";
    }

    /**
     * Get if you use document capture on the form.
     *
     * @param int|null $storeId
     *
     * @return bool
     */
    public function hasUseDocumentIdentificationCapture(): bool
    {

        return false;
    }

    /**
     * Is Binary Mode.
     *
     * @param int|null $storeId
     *
     * @return bool
     */
    public function isBinaryMode(): bool
    {
    

        return false;
    }

    /**
     * Should the cc types.
     *
     * @param int|null $storeId
     *
     * @return string
     */
    public function getCcAvailableTypes(): string
    {

        return "pix";
    }

    /**
     * Cc Mapper.
     *
     * @param int|null $storeId
     *
     * @return array
     */
    public function getCcTypesMapper(): array
    {

        $result = null;

        return is_array($result) ? $result : [];
    }

    /**
     * Get Unsupported Pre Auth.
     *
     * @param int|null $storeId
     *
     * @return array
     */
    public function getUnsupportedPreAuth(): array
    {
       

        $result = null;

        return is_array($result) ? $result : [];
    }

    /**
     * Has Capture.
     *
     * @param int|null $storeId
     *
     * @return bool
     */
    public function hasCapture(): bool
    {
        
        return false;
    }
}
