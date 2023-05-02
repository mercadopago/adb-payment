<?php

namespace MercadoPago\AdbPayment\Model\Adminhtml\Source;

use Magento\Framework\Option\ArrayInterface;
use Magento\Framework\App\RequestInterface;
use MercadoPago\AdbPayment\Gateway\Config\Config as MercadoPagoConfig;


/**
 * Categories Options in Mercado Pago.
 */
class PaymentMethodsOff implements ArrayInterface
{
    /**
     * Mercado Pago Payment Types Id Allowed.
     */
    public const PAYMENT_TYPE_ID_ALLOWED = ['ticket', 'atm'];

    /**
     * Mercado Pago Payment Status.
     */
    public const PAYMENT_STATUS_ACTIVE = 'active';

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
            $options = array_merge($options, $this->mountPaymentMethodsOff($payments['response']));
        }

        return $options;
    }

    public function mountPaymentMethodsOff(array $paymentMethods): ?array {

        $options = [];
        foreach ($paymentMethods as $payment) {
            if (in_array($payment['payment_type_id'], self::PAYMENT_TYPE_ID_ALLOWED) &&
                $payment['status'] === self::PAYMENT_STATUS_ACTIVE) {

                if (empty($payment['payment_places'])) {
                    $options[] = [
                        'value' => $payment['id'],
                        'label' => $payment['name'],
                    ];
                } else {
                    foreach ($payment['payment_places'] as $paymentPlace) {
                        if ($paymentPlace['status'] === self::PAYMENT_STATUS_ACTIVE) {
                            $options[] = [
                                'value' => $paymentPlace['payment_option_id'],
                                'label' => $paymentPlace['name'],
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
