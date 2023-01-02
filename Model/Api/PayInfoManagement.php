<?php
/**
 * Copyright © MercadoPago. All rights reserved.
 *
 * @author      Bruno Elisei <brunoelisei@o2ti.com>
 * @license     See LICENSE for license details.
 */

namespace MercadoPago\PaymentMagento\Model\Api;

use Magento\Sales\Api\OrderRepositoryInterface;
use MercadoPago\PaymentMagento\Api\PayInfoManagementInterface;

/**
 * Model for payment details.
 */
class PayInfoManagement implements PayInfoManagementInterface
{
    /**
     * @var OrderRepositoryInterface
     */
    protected $orderRepository;

    /**
     * CreateVaultManagement constructor.
     *
     * @param OrderRepositoryInterface $orderRepository
     */
    public function __construct(
        OrderRepositoryInterface $orderRepository
    ) {
        $this->orderRepository = $orderRepository;
    }

    /**
     * Payment Information.
     *
     * @param int $orderId
     *
     * @return array
     */
    public function paymentInformation(
        $orderId
    ) {
        $info = [];
        $order = $this->orderRepository->get($orderId);
        $payment = $order->getPayment();

        if ($payment->getMethod() === 'mercadopago_paymentmagento_checkout_pro') {
            $info['data'] = [
                'id'            => $order->getPayment()->getAdditionalInformation('id'),
                'init_point'    => $order->getPayment()->getAdditionalInformation('init_point'),
            ];
        }

        return $info;
    }
}
