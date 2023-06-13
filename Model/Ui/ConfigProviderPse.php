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
use Magento\Framework\Phrase;
use Magento\Framework\View\Asset\Source;
use Magento\Payment\Model\CcConfig;
use Magento\Quote\Api\Data\CartInterface;
use MercadoPago\AdbPayment\Gateway\Config\ConfigPse;

/**
 * User interface model for settings Pse.
 */
class ConfigProviderPse implements ConfigProviderInterface
{
    /**
     * Mercado Pago Payment Magento Pse Code.
     */
    public const CODE = 'mercadopago_adbpayment_pse';

    /**
     * @var ConfigPse
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
     * @param ConfigPse     $config
     * @param CartInterface $cart
     * @param CcConfig      $ccConfig
     * @param Escaper       $escaper
     * @param Source        $assetSource
     */
    public function __construct(
        ConfigPse $config,
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
                ConfigPse::METHOD => [
                    'isActive'                        => $isActive,
                    'title'                           => $this->config->getTitle($storeId),
                    'document_identification_capture' => $this->config->hasUseDocumentIdentificationCapture($storeId),
                    'expiration'                      => $this->config->getExpirationFormat($storeId),
                    'instruction_checkout_pse'        => nl2br($this->getDescriptions($storeId)),
                    'logo'                            => $this->getLogo(),
                    'payment_method_id'               => ConfigPse::PAYMENT_METHOD_ID,
                    'finance_inst_options'            => $this->config->getListFinancialInstitution($storeId),
                    'payer_entity_types'              => $this->config->getListPayerEntityTypes($storeId),
                    'fingerprint'                     => $this->config->getFingerPrintLink($storeId)
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
        $asset = $this->ccConfig->createAsset('MercadoPago_AdbPayment::images/pse/logo.svg');
        $placeholder = $this->assetSource->findSource($asset);
        if ($placeholder) {
            list($width, $height) = getimagesizefromstring($asset->getSourceFile());
            $logo = [
                'url'    => $asset->getUrl(),
                'width'  => $width,
                'height' => $height,
                'title'  => __('Pse - MercadoPago'),
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
        $text = $this->config->getInstructionCheckoutPse($storeId);

        return __($text);
    }
}
