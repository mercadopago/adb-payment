<?php

namespace MercadoPago\PaymentMagento\Model\Adminhtml\Source;

use Magento\Framework\Option\ArrayInterface;
use Magento\Framework\App\RequestInterface;
use MercadoPago\PaymentMagento\Gateway\Config\Config as MercadoPagoConfig;


/**
 * Categories Options in Mercado Pago.
 */
class PaymentMethodsOff implements ArrayInterface
{

    public const PAYMENT_METHODS_ALLOWED = ['ticket', 'bank_transfer', 'atm'];

    public const PAYMENT_METHODS_EXCLUDED = ['pix', 'pse'];

    /**
     * @var RequestInterface
     */
    protected $request;

    /**
     * @var MercadoPagoConfig
     */
    protected $mercadopagoConfig;


    /**
     * @param RequestInterface  $request
     * @param MercadoPagoConfig $mercadopagoConfig
     */
    public function __construct(
        RequestInterface $request,
        MercadoPagoConfig $mercadopagoConfig
    ) {
        $this->request = $request;
        $this->mercadopagoConfig = $mercadopagoConfig;
    }

    public function toOptionArray(): array
    {
        $options = [];

        $storeId = $this->request->getParam('store', 0);

        $options[] = [
            'value' => null,
            'label' => __('Accept all payment methods'),
        ];

        $payments = $this->mercadopagoConfig->getMpPaymentMethods($storeId);

        if ($payments['success'] === true) {
            $options = array_merge($options, $this->filterPaymentMethods($payments['response']));
        }

        return $options;
    }

    public function filterPaymentMethods(array $paymentMethods): ?array {

        $options = [];
        foreach ($paymentMethods as $payment) {
            if (in_array($payment['payment_type_id'], self::PAYMENT_METHODS_ALLOWED)
                && !in_array($payment['id'], self::PAYMENT_METHODS_EXCLUDED)) {

                if (empty($payment['payment_places'])) {
                    $options[] = [
                        'value' => $payment['id'],
                        'label' => $payment['name'],
                    ];
                } else {
                    foreach ($payment['payment_places'] as $payment_place) {
                        $options[] = [
                            'value' => $payment_place['payment_option_id'],
                            'label' => $payment_place['name'],
                        ];
                    }
                }
            }
        }

        $labels = array();
        foreach ($options as $key => $row)
        {
            $labels[$key] = $row['label'];
            
        }
        array_multisort($labels, SORT_ASC, $options);
        return $options;
    }

}
