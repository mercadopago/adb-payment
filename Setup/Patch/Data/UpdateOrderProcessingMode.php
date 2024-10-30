<?php
/**
 * Copyright © MercadoPago. All rights reserved.
 *
 * @author      Mercado Pago
 * @license     See LICENSE for license details.
 */

namespace MercadoPago\AdbPayment\Setup\Patch\Data;

use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;

class UpdateOrderProcessingMode implements DataPatchInterface
{
    /**
     * @var ModuleDataSetupInterface
     */
    private $moduleDataSetup;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var WriterInterface
     */
    protected $writerInterface;

    /**
     * Constructor
     *
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param ScopeConfigInterface $scopeConfig
     * @param WriterInterface $writerInterface
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup, 
        ScopeConfigInterface $scopeConfig,
        WriterInterface $writerInterface
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->scopeConfig = $scopeConfig;
        $this->writerInterface = $writerInterface;
    }

    /**
     * @inheritdoc
     */
    public function apply()
    {
        $this->moduleDataSetup->getConnection()->startSetup();
        
        $connection = $this->moduleDataSetup->getConnection();

        $path = 'payment/mercadopago_adbpayment_cc/can_initialize';

        $result = (string)$this->scopeConfig->getValue(
            $path,
            ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
        );

        if (!$result) {
            $this->writerInterface->save(
                $path, 
                1, 
                ScopeConfigInterface::SCOPE_TYPE_DEFAULT, 
                $scopeId = 0
            );
        }

        if ($result == 1) {
            $this->writerInterface->save(
                $path, 
                0, 
                ScopeConfigInterface::SCOPE_TYPE_DEFAULT, 
                $scopeId = 0
            );
        } else {
            $this->writerInterface->save(
                $path, 
                1, 
                ScopeConfigInterface::SCOPE_TYPE_DEFAULT, 
                $scopeId = 0
            );
        }

        $this->moduleDataSetup->getConnection()->endSetup();
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public static function getDependencies()
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function getAliases()
    {
        return [];
    }
}