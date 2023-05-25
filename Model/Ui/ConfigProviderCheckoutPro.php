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
use MercadoPago\AdbPayment\Gateway\Config\ConfigCheckoutPro;

/**
 * User interface model for settings Checkout Pro.
 */
class ConfigProviderCheckoutPro implements ConfigProviderInterface
{
    /**
     * Mercado Pago Payment Magento Checkout Pro Code.
     */
    public const CODE = 'mercadopago_adbpayment_checkout_pro';

    /**
     * @var ConfigCheckoutPro
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
     * @param ConfigCheckoutPro $config
     * @param CartInterface     $cart
     * @param CcConfig          $ccConfig
     * @param Escaper           $escaper
     * @param Source            $assetSource
     */
    public function __construct(
        ConfigCheckoutPro $config,
        CartInterface $cart,
        CcConfig $ccConfig,
        Escaper $escaper,
        Source $assetSource
    ) {
        $this->config = $config;
        $this->cart = $cart;
        $this->escaper = $escaper;
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
        $isActive = $this->config->isActive($storeId);

        if (!$isActive) {
            return [];
        }

        return [
            'payment' => [
                ConfigCheckoutPro::METHOD => [
                    'isActive'              => $isActive,
                    'title'                 => $this->config->getTitle($storeId),
                    'expiration'            => $this->config->getExpirationFormat($storeId),
                    'type_redirect'         => $this->config->getTypeRedirect($storeId),
                    'logo'                  => $this->getLogo(),
                    'instruction_checkout'  => nl2br($this->getDescriptions($storeId)),
                    'theme'                 => [
                        'headerColor'   => $this->config->getStylesHeaderColor($storeId),
                        'elementsColor' => $this->config->getStylesElementsColor($storeId),
                    ],
                    'fingerprint'           => $this->config->getFingerPrintLink($storeId),
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
                'title'  => __('Checkout Pro - MercadoPago'),
            ];
        }

        return $logo;
    }

    /**
     * Get Descriptions.
     *
     * @param int|null $storeId
     *
     * @return Phrase
     */
    public function getDescriptions($storeId)
    {
        $text = $this->config->getInstructionCheckout($storeId);

        return __($text);
    }
}
