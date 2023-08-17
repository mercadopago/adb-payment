<?php
/**
 * Copyright © MercadoPago. All rights reserved.
 *
 * @author      Mercado Pago
 * @license     See LICENSE for license details.
 */

declare (strict_types = 1);

namespace MercadoPago\AdbPayment\Setup\Patch\Data;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

class MigrateOldConfig implements DataPatchInterface
{
    protected ModuleDataSetupInterface $moduleDataSetup;
    protected WriterInterface $writer;
    protected EncryptorInterface $encryptor;
    protected ScopeConfigInterface $scopeConfig;

    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param WriterInterface          $writer
     * @param EncryptorInterface       $encryptor
     * @param ScopeConfigInterface     $scopeConfig
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        WriterInterface $writer,
        EncryptorInterface $encryptor,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->writer = $writer;
        $this->encryptor = $encryptor;
        $this->scopeConfig = $scopeConfig;
    }

    public static function getDependencies()
    {
        return [];
    }

    public function getAliases()
    {
        return [];
    }

    public function apply()
    {
        $this->moduleDataSetup->startSetup();

        $configMap = [
            'payment/mercadopago_adbpayment/client_id_production' => 'payment/mercadopago/public_key',
            'payment/mercadopago_adbpayment/category' => 'payment/mercadopago/category_id',
            'payment/mercadopago_adbpayment/receive_refund' => 'payment/mercadopago/refund_available',
            'payment/mercadopago_adbpayment/statement_descriptor' => 'payment/mercadopago_basic/statement_desc',
            'payment/mercadopago_adbpayment/integrator_id' => 'payment/mercadopago/integrator_id',

            'payment/mercadopago_adbpayment_checkout_pro/active' => 'payment/mercadopago_basic/active',
            'payment/mercadopago_adbpayment_checkout_pro/title' => 'payment/mercadopago_basic/title',
            'payment/mercadopago_adbpayment_checkout_pro/excluded' => 'payment/mercadopago_basic/excluded_payment_methods',
            'payment/mercadopago_adbpayment_checkout_pro/max_installments' => 'payment/mercadopago_basic/max_installments',
            'payment/mercadopago_adbpayment_checkout_pro/binary_mode' => 'payment/mercadopago_basic/binary_mode',
            'payment/mercadopago_adbpayment_checkout_pro/expiration' => 'payment/mercadopago_basic/exp_time_pref',
            'payment/mercadopago_adbpayment_checkout_pro/sort_order' => 'payment/mercadopago_basic/sort_order',

            'payment/mercadopago_adbpayment_cc/active' => 'payment/mercadopago_custom/active',
            'payment/mercadopago_adbpayment_cc/title' => 'payment/mercadopago_custom/title',
            'payment/mercadopago_adbpayment_cc/sort_order' => 'payment/mercadopago_custom/sort_order',

            'payment/mercadopago_adbpayment_pix/active' => 'payment/mercadopago_custom_pix/active',
            'payment/mercadopago_adbpayment_pix/title' => 'payment/mercadopago_custom_pix/title',
            'payment/mercadopago_adbpayment_pix/expiration' => 'payment/mercadopago_custom_pix/expiration_minutes',
            'payment/mercadopago_adbpayment_pix/sort_order' => 'payment/mercadopago_custom_pix/sort_order',

        ];
        foreach ($configMap as $new => $old) {
            $this->writer->save($new, $this->scopeConfig->getValue($old));
        }

        $value = $this->scopeConfig->getValue('payment/mercadopago/access_token');
        // Avoid error if empty
        if ($value) {
            $this->writer->save('payment/mercadopago_adbpayment/client_secret_production', $this->encryptor->encrypt($value));
        }

        $this->moduleDataSetup->endSetup();
    }
}
