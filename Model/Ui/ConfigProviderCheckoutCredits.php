<?php

/**
 * Copyright © MercadoPago. All rights reserved.
 *
 * @author      Mercado Pago
 * @license     See LICENSE for license details.
 */

namespace MercadoPago\AdbPayment\Model\Ui;

use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Framework\Escaper;
use Magento\Framework\View\Asset\Source;
use Magento\Payment\Model\CcConfig;
use Magento\Quote\Api\Data\CartInterface;
use MercadoPago\AdbPayment\Model\Ui\ConfigProviderCheckoutPro as ConfigProviderCheckoutPro;
use MercadoPago\AdbPayment\Gateway\Config\ConfigCheckoutCredits;
use MercadoPago\AdbPayment\Gateway\Config\Config as MercadoPagoConfig;

/**
 * User interface model for settings Checkout Credits.
 */
class ConfigProviderCheckoutCredits extends ConfigProviderCheckoutPro implements ConfigProviderInterface
{
    /**
     * Mercado Pago Payment Magento Checkout Credits Code.
     */
    public const CODE = 'mercadopago_adbpayment_checkout_credits';

    /**
     * Payment Method Id consumer_credits.
     */
    public const PAYMENT_METHOD_ID = 'consumer_credits';

    /**
     * Type Redirect  Checkout Credits Code.
     */
    public const TYPE_REDIRECT = 'redirect';

    /**
     * @var ConfigCheckoutCredits
     */
    protected $config;

    /**
     * @var CartInterface
     */
    protected $cart;

    /**
     * @var CcConfig
     */
    protected $ccConfig;

    /**
     * @var Escaper
     */
    protected $escaper;

    /**
     * @var Source
     */
    protected $assetSource;

    /**
     * @var MercadoPagoConfig
     */
    protected $mercadopagoConfig;

    /**
     * @var array
     */
    protected $images = [];

    /**
     * @var array
     */
    protected $texts = [];

    /**
     * @param ConfigCheckoutCredits $config
     * @param CartInterface     $cart
     * @param CcConfig          $ccConfig
     * @param Escaper           $escaper
     * @param Source            $assetSource
     * @param MercadoPagoConfig $mercadopagoConfig
     */
    public function __construct(
        ConfigCheckoutCredits $config,
        CartInterface $cart,
        CcConfig $ccConfig,
        Escaper $escaper,
        Source $assetSource,
        MercadoPagoConfig $mercadopagoConfig
    ) {
        $this->config = $config;
        $this->cart = $cart;
        $this->escaper = $escaper;
        $this->ccConfig = $ccConfig;
        $this->assetSource = $assetSource;
        $this->mercadopagoConfig = $mercadopagoConfig;
    }

    /**
     * Retrieve assoc array of checkout configuration.
     *
     * @return array
     */
    public function getConfig()
    {
        $storeId = $this->cart->getStoreId();
        $isActive = $this->config->isActive($storeId);
        $activeMethod = $this->isActive();

        if (!$isActive || !$activeMethod) {
            return [];
        }

        return [
            'payment' => [
                ConfigCheckoutCredits::METHOD => [
                    'isActive'              => $isActive,
                    'title'                 => $this->config->getTitle($storeId),
                    'expiration'            => $this->config->getExpirationFormat($storeId),
                    'type_redirect'         => self::TYPE_REDIRECT,
                    'logo'                  => $this->getLogo(),
                    'fingerprint'           => $this->config->getFingerPrintLink($storeId),
                    'images'                => $this->getImages(),
                    'texts'                 => $this->getBannerTexts($storeId),
                ],
            ],
        ];
    }

    /**
     * Get icons for available payment methods.
     *
     * @return array
     */
    public function getLogo()
    {
        $logo = [];
        $asset = $this->ccConfig->createAsset('MercadoPago_AdbPayment::images/checkout-pro/logo.svg');
        $placeholder = $this->assetSource->findSource($asset);
        if ($placeholder) {
            list($width, $height) = getimagesizefromstring($asset->getSourceFile());
            $logo = [
                'url'    => $asset->getUrl(),
                'width'  => $width,
                'height' => $height,
                'title'  => __('Up to 12 installments without card with Mercado Pago'),
            ];
        }

        return $logo;
    }

    /**
     * Verify active method.
     *
     * @return boolean
     */
    public function isActive()
    {

        $storeId = $this->cart->getStoreId();
        $payments = $this->mercadopagoConfig->getMpPaymentMethods($storeId);

        if ($payments['success'] === true && isset($payments['response'])) {
            foreach ($payments['response'] as $payment) {
                if (isset($payment['id']) && $payment['id'] === self::PAYMENT_METHOD_ID) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Get images for payment method banner.
     * 
     * @return array
     */
    public function getImages()
    {
        $this->images = [
            'credits-1' => $this->getImagesByName('credits-1'),
            'credits-2' => $this->getImagesByName('credits-2'),
            'credits-3' => $this->getImagesByName('credits-3'),
            'credits-lock' => $this->getImagesByName('credits-lock'),
        ];

        return $this->images;
    }


    /**
     * Get images for payment method banner.
     * 
     * @return array
     */
    public function getImagesByName($name)
    {
        $image = [];
        $asset = $this->ccConfig->createAsset('MercadoPago_AdbPayment::images/credits/' . $name . '.svg');
        $placeholder = $this->assetSource->findSource($asset);
        if ($placeholder) {
            list($width, $height) = getimagesizefromstring($asset->getSourceFile());
            $image = [
                'url' => $asset->getUrl(),
                'width' => $width,
                'height' => $height,
                'code'   => $name,
                'title' => __('Credits - MercadoPago'),
            ];
        }
        return $image;
    }

    /**
     * Get texts for Credits banner
     * @return array
     */
    public function getBannerTexts($storeId)
    {
        $this->texts = [
            'credits-use' => $this->getBannerTextById('banner_text_use', $storeId),
            'credits-1' => $this->getBannerTextById('banner_text_1', $storeId),
            'credits-2' => $this->getBannerTextById('banner_text_2', $storeId),
            'credits-3' => $this->getBannerTextById('banner_text_3', $storeId),
            'credits-lock' => $this->getBannerTextById('banner_text_lock', $storeId),
            'credits-lock-2' => $this->getBannerTextById('banner_text_lock_2', $storeId),
        ];
        // $texts = $this->config->getBannerTexts($storeId);

        return $this->texts;
    }

    /**
     * Get banner text in config
     * @return Phrase
     */
    public function getBannerTextById($id, $storeId)
    {
        $text = $this->config->getBannerText($id, $storeId);

        return __($text);
    }
}
