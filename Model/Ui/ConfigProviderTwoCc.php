<?php
/**
 * Copyright © MercadoPago. All rights reserved.
 *
 * @author      Bruno Elisei <brunoelisei@o2ti.com>
 * @license     See LICENSE for license details.
 */

namespace MercadoPago\PaymentMagento\Model\Ui;

use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Framework\View\Asset\Source;
use Magento\Payment\Model\CcConfig;
use Magento\Quote\Api\Data\CartInterface;
use MercadoPago\PaymentMagento\Gateway\Config\ConfigTwoCc;

/**
 * User interface model for settings Card.
 */
class ConfigProviderTwoCc implements ConfigProviderInterface
{
    /**
     * Mercadopago Payment Magento Cc Code.
     */
    public const CODE = 'mercadopago_paymentmagento_twocc';

    /**
     * Mercadopago Payment Magento Cc Vault Code.
     */
    public const VAULT_CODE = 'mercadopago_paymentmagento_cc_vault';

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
     * @param ConfigTwoCc      $configTwoCc
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
                    'MercadoPago_PaymentMagento::images/cc/'.$label.'.svg'
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
        $asset = $this->ccConfig->createAsset('MercadoPago_PaymentMagento::images/cc/logo.svg');
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
