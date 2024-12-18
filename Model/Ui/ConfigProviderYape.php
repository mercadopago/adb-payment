<?php
/**
 * Copyright © MercadoPago. All rights reserved.
 *
 * @author      Mercado Pago
 * @license     See LICENSE for license details.
 */

namespace MercadoPago\AdbPayment\Model\Ui;

use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Framework\View\Asset\Source;
use Magento\Payment\Model\CcConfig;
use Magento\Quote\Api\Data\CartInterface;
use MercadoPago\AdbPayment\Gateway\Config\ConfigYape;

/**
 * User interface model for settings Yape.
 */
class ConfigProviderYape implements ConfigProviderInterface
{
    /**
     * Mercadopago Payment Magento Yape Code.
     */
    public const CODE = 'mercadopago_adbpayment_yape';

    /**
     * @var configYape
     */
    protected $configYape;

    /**
     * @var CartInterface
     */
    protected $cart;

    /**
     * @var CcConfig
     */
    protected $ccConfig;

    /**
     * @var Source
     */
    protected $assetSource;

    /**
     * @var array
     */
    protected $icons = [];

    /**
     * @param ConfigYape    $configYape
     * @param CartInterface $cart
     * @param CcConfig      $ccConfig
     * @param Source        $assetSource
     */
    public function __construct(
        ConfigYape $configYape,
        CartInterface $cart,
        CcConfig $ccConfig,
        Source $assetSource
    ) {
        $this->configYape = $configYape;
        $this->cart = $cart;
        $this->ccConfig = $ccConfig;
        $this->assetSource = $assetSource;
    }

    /**
     * Retrieve assoc array of checkout configuration.
     *
     * @return array
     */
    public function getConfig()
    {
        $storeId = $this->cart->getStoreId();
        $isActive = $this->configYape->isActive($storeId);

        if (!$isActive) {
            return [];
        }

        return [
            'payment' => [
                ConfigYape::METHOD => [
                    'isActive'             => $isActive,
                    'title'                => $this->configYape->getTitle($storeId),
                    'logo'                 => $this->getLogo(),
                    'fingerprint'          => $this->configYape->getFingerPrintLink($storeId),
                    'yapeIcons'            => $this->getIcons()
                ],
            ],
        ];
    }

    /**
     * Get logo for available payment method.
     *
     * @return array
     */
    public function getLogo()
    {
        $logo = [];
        $asset = $this->ccConfig->createAsset('MercadoPago_AdbPayment::images/yape/logo.svg');
        $placeholder = $this->assetSource->findSource($asset);
        if ($placeholder) {
            list($width, $height) = getimagesizefromstring($asset->getSourceFile());
            $logo = [
                'url'    => $asset->getUrl(),
                'width'  => $width,
                'height' => $height,
                'title'  => __('Yape - MercadoPago'),
            ];
        }

        return $logo;
    }

    /**
     * Get information icons.
     *
     * @return array
     */
    public function getIcons()
    {
        if (!empty($this->icons)) {
            return $this->icons;
        }
        $storeId = $this->cart->getStoreId();
        $yapeIcons = $this->configYape->getIcons($storeId);
        $types = explode(',', $yapeIcons);
        foreach ($types as $code => $label) {
            if (!array_key_exists($code, $this->icons)) {
                $asset = $this->ccConfig->createAsset(
                    'MercadoPago_AdbPayment::images/yape/'.$label.'.svg'
                );
                $placeholder = $this->assetSource->findSource($asset);
                if ($placeholder) {
                    $this->icons[$label] = [
                        'url'    => $asset->getUrl(),
                        'code'   => $label,
                        'title'  => $label,
                    ];
                }
            }
        }

        return $this->icons;
    }


}
