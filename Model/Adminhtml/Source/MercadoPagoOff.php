<?php

namespace MercadoPago\PaymentMagento\Model\Adminhtml\Source;

use Magento\Framework\Option\ArrayInterface;
use Magento\Framework\App\RequestInterface;
use MercadoPago\PaymentMagento\Gateway\Config\Config as MercadoPagoConfig;


/**
 * Categories Options in Mercado Pago.
 */
class MercadoPagoOff implements ArrayInterface
{

    public const PAYMENT_METHOD_ID = 'ticket';

    /**
     * @var RequestInterface
     */
    protected $request;

    /**
     * @var MercadoPagoConfig
     */
    protected $mercadopagoConfig;


    /**
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

        $payments = $this->mercadopagoConfig->getMpPaymentMethods($storeId);

        if ($payments['success'] === true) {
            foreach ($payments['response'] as $payment) {
                if ($payment['payment_type_id'] === self::PAYMENT_METHOD_ID) {
                    $options[] = [
                        'value' => $payment['id'],
                        'label' => __($payment['name']),
                    ];
                }
            }
        }

        return $options;
    }

}
