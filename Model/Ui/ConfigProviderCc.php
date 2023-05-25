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
use MercadoPago\AdbPayment\Gateway\Config\ConfigCc;

/**
 * User interface model for settings Card.
 */
class ConfigProviderCc implements ConfigProviderInterface
{
    /**
     * Mercadopago Payment Magento Cc Code.
     */
    public const CODE = 'mercadopago_adbpayment_cc';

    /**
     * Mercadopago Payment Magento Cc Vault Code.
     */
    public const VAULT_CODE = 'mercadopago_adbpayment_cc_vault';

    /**
     * @var configCc
     */
    protected $configCc;

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
     * @param ConfigCc      $configCc
     * @param CartInterface $cart
     * @param CcConfig      $ccConfig
     * @param Source        $assetSource
     */
    public function __construct(
        ConfigCc $configCc,
        CartInterface $cart,
        CcConfig $ccConfig,
        Source $assetSource
    ) {
        $this->configCc = $configCc;
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
        $isActive = $this->configCc->isActive($storeId);

        if (!$isActive) {
            return [];
        }

        return [
            'payment' => [
                ConfigCc::METHOD => [
                    'isActive'                        => $isActive,
                    'title'                           => $this->configCc->getTitle($storeId),
                    'useCvv'                          => $this->configCc->isCvvEnabled($storeId),
                    'ccTypesMapper'                   => $this->configCc->getCcTypesMapper($storeId),
                    'logo'                            => $this->getLogo(),
                    'icons'                           => $this->getIcons(),
                    'document_identification_capture' => $this->configCc->hasUseDocumentIdentificationCapture($storeId),
                    'unsupported_pre_auth'            => $this->configCc->getUnsupportedPreAuth($storeId),
                    'ccVaultCode'                     => self::VAULT_CODE,
                    'fingerprint'                     => $this->configCc->getFingerPrintLink($storeId)
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
        $asset = $this->ccConfig->createAsset('MercadoPago_AdbPayment::images/cc/logo.svg');
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
}
