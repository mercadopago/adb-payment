<?php
/**
 * Copyright © MercadoPago. All rights reserved.
 *
 * @author      Mercado Pago
 * @license     See LICENSE for license details.
 */

namespace MercadoPago\AdbPayment\Gateway\Request;

use InvalidArgumentException;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Gateway\Request\BuilderInterface;
use MercadoPago\AdbPayment\Gateway\Config\Config;
use MercadoPago\AdbPayment\Gateway\SubjectReader;

/**
 * Gateway requests for Payment Metadata.
 */
class MetadataPaymentDataRequest implements BuilderInterface
{
    /**
     * Metadata block name.
     */
    public const METADATA = 'metadata';

    /**
     * Cpp extra block name.
     */
    public const CPP_EXTRA = 'cpp_extra';

    /**
     * Plataform block name.
     */
    public const PLATAFORM = 'platform';

    /**
     * Plataform Version block name.
     */
    public const PLATAFORM_VERSION = 'platform_version';

    /**
     * Module Version block name.
     */
    public const MODULE_VERSION = 'module_version';

    /**
     * Test Mode block name.
     */
    public const TEST_MODE = 'test_mode';

    /**
     * Sponsor Id block name.
     */
    public const SPONSOR_ID = 'sponsor_id';

    /**
     * Site Id block name.
     */
    public const SITE_ID = 'site_id';

    /**
     * Store Id block name.
     */
    public const STORE_ID = 'store_id';

    /**
     * Mp Flow Id block name.
     */
    public const MP_FLOW_ID = 'mp_flow_id';

    /**
     * @var SubjectReader
     */
    protected $subjectReader;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @param SubjectReader $subjectReader
     * @param Config        $config
     */
    public function __construct(
        SubjectReader $subjectReader,
        Config $config
    ) {
        $this->subjectReader = $subjectReader;
        $this->config = $config;
    }

    /**
     * Build.
     *
     * @param array $buildSubject
     */
    public function build(array $buildSubject)
    {
        if (!isset($buildSubject['payment'])
            || !$buildSubject['payment'] instanceof PaymentDataObjectInterface
        ) {
            throw new InvalidArgumentException('Payment data object should be provided');
        }

        $paymentDO = $buildSubject['payment'];
        $order = $paymentDO->getOrder();
        $storeId = $order->getStoreId();

        $flowId = $paymentDO->getPayment()->getAdditionalInformation(MpFlowId::MP_FLOW_ID);

        $result = $this->getMetadata($storeId, $flowId);

        return $result;
    }

    /**
     * Get Metadata.
     *
     * @param int $storeId
     *
     * @return array
     */
    public function getMetadata($storeId, $flowId)
    {
        $request = [];
        $mpSiteId = $this->config->getMpSiteId($storeId);

        $request[self::METADATA] = [
            self::PLATAFORM         => Config::PLATAFORM_ID,
            self::PLATAFORM_VERSION => $this->config->getMagentoVersion(),
            self::MODULE_VERSION    => $this->config->getModuleVersion(),
            self::TEST_MODE         => $this->config->isTestMode(),
            self::SPONSOR_ID        => $this->config->getMpSponsorId($mpSiteId),
            self::SITE_ID           => $mpSiteId,
            self::STORE_ID          => $storeId,
        ];

        $request[self::METADATA][self::CPP_EXTRA] = [
            self::PLATAFORM         => Config::PLATAFORM_ID,
            self::PLATAFORM_VERSION => $this->config->getMagentoVersion(),
            self::MODULE_VERSION    => $this->config->getModuleVersion(),
            self::TEST_MODE         => $this->config->isTestMode(),
            self::SPONSOR_ID        => $this->config->getMpSponsorId($mpSiteId),
            self::SITE_ID           => $mpSiteId,
            self::STORE_ID          => $storeId
        ];

        
        if ($flowId) {
            $request[self::METADATA][self::MP_FLOW_ID] = $flowId;
            $request[self::METADATA][self::CPP_EXTRA][self::MP_FLOW_ID] = $flowId;
        }

        return $request;
    }
}
