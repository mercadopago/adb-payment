<?php

namespace MercadoPago\AdbPayment\Model\Ui;

use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Framework\Escaper;
use Magento\Framework\Phrase;
use Magento\Quote\Api\Data\CartInterface;
use MercadoPago\AdbPayment\Gateway\Config\ConfigPaymentMethodsOff;
use MercadoPago\AdbPayment\Gateway\Config\Config as MercadoPagoConfig;
use Magento\Framework\View\Asset\Repository;

/**
 * User interface model for settings Payment Methods Off.
 */
class ConfigProviderPaymentMethodsOff implements ConfigProviderInterface
{
    /**
     * Mercado Pago Payment Magento Payment Methods Off Code.
     */
    public const CODE = 'mercadopago_adbpayment_payment_methods_off';

    /**
     * Payment Types Id Allowed.
     */
    public const PAYMENT_TYPE_ID_ALLOWED = ['ticket', 'atm'];

    /**
     * Payment Status.
     */
    public const PAYMENT_STATUS_ACTIVE = 'active';

    /**
     * Path Logo use in checkout
     */
    public const PATH_LOGO = 'MercadoPago_AdbPayment::images/boleto/logo.svg';

    /**
     * @var ConfigPaymentMethodsOff
     */
    protected $config;

    /**
     * @var CartInterface
     */
    protected $cart;

     /**
     * @var MercadoPagoConfig
     */
    protected $mercadopagoConfig;

    /**
     * @var Escaper
     */
    protected $escaper;

        /**
     * @var Repository
     */
    protected $assetRepo;

    /**
     * @param ConfigPaymentMethodsOff  $config
     * @param CartInterface $cart
     * @param Escaper       $escaper
     */
    public function __construct(
        ConfigPaymentMethodsOff $config,
        CartInterface $cart,
        Escaper $escaper,
        MercadoPagoConfig $mercadopagoConfig,
        Repository $assetRepo
    ) {
        $this->config = $config;
        $this->cart = $cart;
        $this->escaper = $escaper;
        $this->mercadopagoConfig = $mercadopagoConfig;
        $this->assetRepo = $assetRepo;
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
        $url = $this->assetRepo->getUrl(Self::PATH_LOGO);
        if ($url) {
            $logo = [
                'url'    => $url,
                'title'  => __('Ticket - MercadoPago"'),
            ];
        }

        return $logo;
    }

    /**
     * Get Payment methods.
     *
     * @return array
     */
    public function getPaymentMethodsOffActive($storeId)
    {
        $methodsOffActive = $this->config->getPaymentMethodsOffActive($storeId);

        $options = [];
        $payments = $this->mercadopagoConfig->getMpPaymentMethods($storeId);

        if ($payments['success'] === true) {
            $options = $this->mountPaymentMethodsOff($payments['response']);
        }

        return $this->filterPaymentMethodsOffConfigActive($options, $methodsOffActive);
    }


    /**
     * Filters the list of configured in admin payment methods off.
     *
     * @return array
     */
    public function filterPaymentMethodsOffConfigActive(array $paymentMethods, ?string $methodsOffActive): ?array
    {
        if (empty($methodsOffActive)) {
            return $paymentMethods;
        }

        $options = [];
        $actives = explode(",", $methodsOffActive);

        foreach ($paymentMethods as $payment) {
            if(isset($payment['value']) && !in_array($payment['value'], $actives)){
                $options[] = $payment;
            }
        }

        return $options;
    }

    /**
     * Make a list with the necessary information from the payment Methods Off.
     *
     * @return array
     */
    public function mountPaymentMethodsOff(array $paymentMethods = []): array
    {
        $options = [];
        foreach ($paymentMethods as $payment) {
            if (in_array($payment['payment_type_id'], self::PAYMENT_TYPE_ID_ALLOWED) &&
                $payment['status'] === self::PAYMENT_STATUS_ACTIVE) {

                if (empty($payment['payment_places'])) {
                    $options[] = [
                        'value' => $payment['id'],
                        'label' => $payment['name'],
                        'logo' => $payment['secure_thumbnail'],
                        'payment_method_id' => $payment['id'],
                        'payment_type_id' => $payment['payment_type_id'],

                    ];
                } else {
                    foreach ($payment['payment_places'] as $paymentPlace) {
                        if ($paymentPlace['status'] === self::PAYMENT_STATUS_ACTIVE) {
                            $options[] = [
                                'value' => $paymentPlace['payment_option_id'],
                                'label' => $paymentPlace['name'],
                                'logo' => $paymentPlace['thumbnail'],
                                'payment_method_id' => $payment['id'],
                                'payment_type_id' => $payment['payment_type_id'],
                                'payment_option_id' => $paymentPlace['payment_option_id'],
                            ];
                        }
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
