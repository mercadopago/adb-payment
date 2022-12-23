<?php
/**
 * Copyright © MercadoPago. All rights reserved.
 *
 * @author      Bruno Elisei <brunoelisei@o2ti.com>
 * @license     See LICENSE for license details.
 */

namespace MercadoPago\PaymentMagento\Model\Ui;

use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Framework\Escaper;
use Magento\Framework\Phrase;
use Magento\Framework\View\Asset\Source;
use Magento\Payment\Model\CcConfig;
use Magento\Quote\Api\Data\CartInterface;
use MercadoPago\PaymentMagento\Gateway\Config\ConfigPayCash;

/**
 * User interface model for settings PayCash.
 */
class ConfigProviderPayCash implements ConfigProviderInterface
{
    /**
     * Mercado Pago Payment Magento PayCash Code.
     */
    public const CODE = 'mercadopago_paymentmagento_paycash';

    /**
     * @var ConfigPayCash
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
     * @param ConfigPayCash $config
     * @param CartInterface $cart
     * @param CcConfig      $ccConfig
     * @param Escaper       $escaper
     * @param Source        $assetSource
     */
    public function __construct(
        ConfigPayCash $config,
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
        $captureDocument = $this->config->hasUseDocumentIdentificationCapture($storeId);
        $isActive = $this->config->isActive($storeId);

        if (!$isActive) {
            return [];
        }

        return [
            'payment' => [
                ConfigPayCash::METHOD => [
                    'isActive'                            => $isActive,
                    'title'                               => $this->config->getTitle($storeId),
                    'document_identification_capture'     => $captureDocument,
                    'expiration'                          => $this->config->getExpirationFormat($storeId),
                    'instruction_checkout_paycash'        => nl2br($this->getDescriptions($storeId)),
                    'logo'                                => $this->getLogo(),
                    'payment_method_id'                   => ConfigPayCash::PAYMENT_METHOD_ID,
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
        $asset = $this->ccConfig->createAsset('MercadoPago_PaymentMagento::images/pay-cash/logo.svg');
        $placeholder = $this->assetSource->findSource($asset);
        if ($placeholder) {
            list($width, $height) = getimagesizefromstring($asset->getSourceFile());
            $logo = [
                'url'    => $asset->getUrl(),
                'width'  => $width,
                'height' => $height,
                'title'  => __('PayCash - MercadoPago'),
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
        $text = $this->config->getInstructionCheckoutPayCash($storeId);

        return __($text);
    }
}
