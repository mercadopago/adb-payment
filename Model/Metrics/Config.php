<?php
/**
 * Copyright © MercadoPago. All rights reserved.
 *
 * @author      Mercado Pago
 * @license     See LICENSE for license details.
 */

namespace MercadoPago\AdbPayment\Model\Metrics;

use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\Module\ModuleListInterface;
use Magento\Framework\Module\ResourceInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Configuration class for Metrics.
 */
class Config
{
    /**
     * @var ProductMetadataInterface
     */
    private $productMetadata;

    /**
     * @var ResourceInterface
     */
    private $resourceModule;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var ModuleListInterface
     */
    private $moduleList;

    /**
     * @param ProductMetadataInterface $productMetadata
     * @param ResourceInterface $resourceModule
     * @param StoreManagerInterface $storeManager
     * @param ModuleListInterface $moduleList
     */
    public function __construct(
        ProductMetadataInterface $productMetadata,
        ResourceInterface $resourceModule,
        StoreManagerInterface $storeManager,
        ModuleListInterface $moduleList
    ) {
        $this->productMetadata = $productMetadata;
        $this->resourceModule = $resourceModule;
        $this->storeManager = $storeManager;
        $this->moduleList = $moduleList;
    }

    /**
     * Get module version.
     *
     * @return string|null
     */
    public function getModuleVersion(): ?string
    {
        $version = $this->resourceModule->getDbVersion('MercadoPago_AdbPayment');
        
        if ($version === null) {
            // Fallback: obter do ModuleList (lê do module.xml)
            $moduleInfo = $this->moduleList->getOne('MercadoPago_AdbPayment');
            $version = $moduleInfo['setup_version'] ?? null;
        }
        
        return $version;
    }

    /**
     * Get Magento version.
     *
     * @return string
     */
    public function getMagentoVersion(): string
    {
        return $this->productMetadata->getVersion();
    }

    /**
     * Get base URL.
     *
     * @return string
     */
    public function getBaseUrl(): string
    {
        try {
            return $this->storeManager->getStore()->getBaseUrl();
        } catch (\Exception $e) {
            return '';
        }
    }

    /**
     * Get location (region/store/theme/currency).
     *
     * @return string|null
     */
    public function getLocation(): ?string
    {
        try {
            $store = $this->storeManager->getStore();
            $region = getenv('DD_REGION') ?: 'unknown';
            $storeCode = $store->getCode();
            $currency = $store->getCurrentCurrencyCode();
            
            return $region . '_' . $storeCode . '_' . $currency;
        } catch (\Exception $e) {
            return null;
        }
    }
}

