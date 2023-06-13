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
use MercadoPago\AdbPayment\Gateway\Config\ConfigTwoCc;
use Magento\Payment\Gateway\Config\Config;

/**
 * User interface model for settings Card.
 */
class ConfigProviderTwoCc implements ConfigProviderInterface
{
    /**
     * Mercadopago Payment Magento TwoCc Code.
     */
    public const CODE = 'mercadopago_adbpayment_twocc';

    /**
     * Mercadopago Payment Magento Cc Vault Code.
     */
    public const VAULT_CODE = 'mercadopago_adbpayment_cc_vault';

    /**
     * @var ConfigTwoCc
     */
    protected $configTwoCc;

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
     * @var Source
     */
    protected $assetSource;

    /**
     * @param ConfigTwoCc   $configTwoCc
     * @param CartInterface $cart
     * @param CcConfig      $ccConfig
     * @param Source        $assetSource
     */
    public function __construct(
        ConfigTwoCc $configTwoCc,
        CartInterface $cart,
        CcConfig $ccConfig,
        Source $assetSource
    ) {
        $this->configTwoCc = $configTwoCc;
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
        $isActive = $this->configTwoCc->isActive($storeId);

        if (!$isActive) {
            return [];
        }

        return [
            'payment' => [
                ConfigTwoCc::METHOD => [
                    'isActive'                        => $isActive,
                    'title'                           => $this->configTwoCc->getTitle($storeId),
                    'useCvv'                          => $this->configTwoCc->isCvvEnabled($storeId),
                    'ccTypesMapper'                   => $this->configTwoCc->getCcTypesMapper($storeId),
                    'logo'                            => $this->getLogo(),
                    'icons'                           => $this->getIcons(),
                    'document_identification_capture' => $this->configTwoCc->hasUseDocumentIdentificationCapture($storeId),
                    'unsupported_pre_auth'            => $this->configTwoCc->getUnsupportedPreAuth($storeId),
                    'ccVaultCode'                     => self::VAULT_CODE,
                    'fingerprint'                     => $this->configTwoCc->getFingerPrintLink($storeId),
                    'images'                          => $this->getImages()
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
        $ccTypes = $this->configTwoCc->getCcAvailableTypes($storeId);
        $types = explode(',', $ccTypes);
        foreach ($types as $code => $label) {
            if (!array_key_exists($code, $this->icons)) {
                $asset = $this->ccConfig->createAsset(
                    'MercadoPago_AdbPayment::images/cc/'.$label.'.svg'
                );
                $placeholder = $this->assetSource->findSource($asset);
                if ($placeholder) {
                    $this->icons[$label] = [
                        'url'    => $asset->getUrl(),
                        'code'   => $label,
                        'width'  => '38px',
                        'height' => '38px',
                        'title'  => $label,
                    ];
                }
            }
        }

        return $this->icons;
    }

    /**
     * Get icons for available payment methods.
     *
     * @return array
     */
    public function getLogo()
    {
        $logo = [];
        $asset = $this->ccConfig->createAsset('MercadoPago_AdbPayment::images/twocc/logo.svg');
        $placeholder = $this->assetSource->findSource($asset);
        if ($placeholder) {
            list($width, $height) = getimagesizefromstring($asset->getSourceFile());
            $logo = [
                'url'    => $asset->getUrl(),
                'width'  => $width,
                'height' => $height,
                'title'  => __('Card - MercadoPago'),
            ];
        }

        return $logo;
    }

    /**
     * Get icons for available payment methods.
     *
     * @return array
     */
    public function getImages()
    {
        $images = [
            'card' => $this->getImageByName('card'),
            'edit' => $this->getImageByName('edit'),
            'footer-logo' => $this->getImageByName('footer-logo')
        ];

        return $images;
    }

    /**
     * Get icons for available payment methods.
     *
     * @return array
     */
    private function getImageByName($name)
    {
        $image = [];
        $asset = $this->ccConfig->createAsset('MercadoPago_AdbPayment::images/twocc/'.$name.'.svg');
        $placeholder = $this->assetSource->findSource($asset);
        if ($placeholder) {
            list($width, $height) = getimagesizefromstring($asset->getSourceFile());
            $image = [
                'url'    => $asset->getUrl(),
                'width'  => $width,
                'height' => $height,
                'title'  => __('Card - MercadoPago'),
            ];
        }

        return $image;
    }
}
