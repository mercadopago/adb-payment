<?php

namespace MercadoPago\PaymentMagento\Model\Ui;

use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Framework\Escaper;
use Magento\Framework\Phrase;
use Magento\Framework\View\Asset\Source;
use Magento\Payment\Model\CcConfig;
use Magento\Quote\Api\Data\CartInterface;
use MercadoPago\PaymentMagento\Gateway\Config\ConfigPaymentMethodsOff;
use MercadoPago\PaymentMagento\Gateway\Config\Config as MercadoPagoConfig;


/**
 * User interface model for settings Payment Methods Off.
 */
class ConfigProviderPaymentMethodsOff implements ConfigProviderInterface
{
    /**
     * Mercado Pago Payment Magento Payment Methods Off Code.
     */
    public const CODE = 'mercadopago_paymentmagento_payment_methods_off';

    public const PAYMENT_METHODS_ALLOWED = ['ticket', 'atm'];

    /**
     * @var ConfigPaymentMethodsOff
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
     * @var MercadoPagoConfig
     */
    protected $mercadopagoConfig;

    /**
     * @var Escaper
     */
    protected $escaper;

    /**
     * @var Source
     */
    protected $assetSource;

    /**
     * @param ConfigPaymentMethodsOff  $config
     * @param CartInterface $cart
     * @param CcConfig      $ccConfig
     * @param Escaper       $escaper
     * @param Source        $assetSource
     */
    public function __construct(
        ConfigPaymentMethodsOff $config,
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

        if (!$isActive) {
            return [];
        }

        return [
            'payment' => [
                ConfigPaymentMethodsOff::METHOD => [
                    'isActive'                        => $isActive,
                    'title'                           => $this->config->getTitle($storeId),
                    'name_capture'                    => $this->config->hasUseNameCapture($storeId),
                    'document_identification_capture' => $this->config->hasUseDocumentIdentificationCapture($storeId),
                    'expiration'                      => $this->config->getExpirationFormat($storeId),
                    'logo'                            => $this->getLogo(),
                    'payment_methods_off_active'      => $this->getPaymentMethodsOffActive($storeId),
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
        $asset = $this->ccConfig->createAsset('MercadoPago_PaymentMagento::images/boleto/logo.svg');
        $placeholder = $this->assetSource->findSource($asset);
        if ($placeholder) {
            list($width, $height) = getimagesizefromstring($asset->getSourceFile());
            $logo = [
                'url'    => $asset->getUrl(),
                'width'  => $width,
                'height' => $height,
                'title'  => __('Boleto - MercadoPago'),
            ];
        }

        return $logo;
    }
    
    public function getPaymentMethodsOffActive($storeId) {

        $paymentMethodsOffActive = $this->config->getPaymentMethodsOffActive($storeId);

        $options = [];
        $payments = $this->mercadopagoConfig->getMpPaymentMethods($storeId);

        if ($payments['success'] === true) {
            $options = array_merge($options, $this->filterPaymentMethods($payments['response']));
        }

        return $this->filterPaymentMethodsOffActive($options, $paymentMethodsOffActive);
    }

    public function filterPaymentMethodsOffActive(array $paymentMethods, ?string $paymentMethodsOffActive): ?array {
        
        if (empty($paymentMethodsOffActive)) {
            return $paymentMethods;
        }

        $options = [];
        $actives = explode(",", $paymentMethodsOffActive);

        foreach ($paymentMethods as $payment) {
            if(in_array($payment['value'], $actives)){
                $options[] = $payment;
            }
        }

        return $options;
    }

    public function filterPaymentMethods(array $paymentMethods): ?array {
        $options = [];
        foreach ($paymentMethods as $payment) {
            if (in_array($payment['payment_type_id'], self::PAYMENT_METHODS_ALLOWED)) {

                if (empty($payment['payment_places'])) {
                    $options[] = [
                        'value' => $payment['id'],
                        'label' => $payment['name'],
                        'logo' => $payment['thumbnail'],
                        'payment_type_id' => $payment['payment_type_id'],
                    ];
                } else {
                    foreach ($payment['payment_places'] as $payment_place) {
                        $options[] = [
                            'value' => $payment_place['payment_option_id'],
                            'label' => $payment_place['name'],
                            'logo' => $payment_place['thumbnail'],
                            'payment_type_id' => $payment['payment_type_id'],
                        ];
                    }
                }
            }
        }

        $labels = array();
        foreach ($options as $key => $row) {
            $labels[$key] = $row['label'];
        }
        array_multisort($labels, SORT_ASC, $options);
        return $options;
    }

}
