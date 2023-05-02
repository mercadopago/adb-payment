<?php
/**
 * Copyright © MercadoPago. All rights reserved.
 *
 * @author      Mercado Pago
 * @license     See LICENSE for license details.
 */

namespace MercadoPago\AdbPayment\Model\Ui\Vault;

use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Asset\Source;
use Magento\Payment\Model\CcConfig;
use Magento\Quote\Api\Data\CartInterface;
use MercadoPago\AdbPayment\Gateway\Config\ConfigCc;
use MercadoPago\AdbPayment\Gateway\Config\ConfigCcVault;

/**
 * User interface model for settings Vault.
 */
class ConfigProvider implements ConfigProviderInterface
{
    public const CODE = 'mercadopago_adbpayment_cc_vault';

    /**
     * @var Config
     */
    protected $configCcVault;

    /**
     * @var CartInterface
     */
    protected $cart;

    /**
     * @var array
     */
    protected $icons = [];

    /**
     * @var CcConfig
     */
    protected $ccConfig;

    /**
     * @var CcConfig
     */
    protected $configCc;

    /**
     * @var Source
     */
    protected $assetSource;

    /**
     * ConfigProvider constructor.
     *
     * @param CartInterface $cart
     * @param ConfigCc      $configCc
     * @param ConfigCcVault $configCcVault
     * @param CcConfig      $ccConfig
     * @param Source        $assetSource
     */
    public function __construct(
        CartInterface $cart,
        ConfigCc $configCc,
        ConfigCcVault $configCcVault,
        CcConfig $ccConfig,
        Source $assetSource
    ) {
        $this->cart = $cart;
        $this->configCc = $configCc;
        $this->assetSource = $assetSource;
        $this->ccConfig = $ccConfig;
        $this->configCcVault = $configCcVault;
    }

    /**
     * Retrieve assoc array of checkout configuration.
     *
     * @throws InputException
     * @throws NoSuchEntityException
     *
     * @return array
     */
    public function getConfig()
    {
        $storeId = $this->cart->getStoreId();

        return [
            'payment' => [
                self::CODE => [
                    'useCvv' => $this->configCcVault->useCvv($storeId),
                    'icons'  => $this->getIcons(),
                ],
            ],
        ];
    }

    /**
     * Get icons for available payment methods.
     *
     * @return array
     */
    public function getIcons()
    {
        if (!empty($this->icons)) {
            return $this->icons;
        }
        $storeId = $this->cart->getStoreId();
        $ccTypes = $this->configCc->getCcAvailableTypes($storeId);
        $types = explode(',', $ccTypes);
        foreach ($types as $code => $label) {
            if (!array_key_exists($code, $this->icons)) {
                $asset = $this->ccConfig->createAsset(
                    'MercadoPago_AdbPayment::images/cc/'.strtolower($label).'.svg'
                );
                $placeholder = $this->assetSource->findSource($asset);
                if ($placeholder) {
                    list($width, $height) = getimagesizefromstring($asset->getSourceFile());
                    $this->icons[$label] = [
                        'url'    => $asset->getUrl(),
                        'width'  => $width,
                        'height' => $height,
                        'title'  => __($label),
                    ];
                }
            }
        }

        return $this->icons;
    }
}
