<?php
/**
 * Copyright © MercadoPago. All rights reserved.
 *
 * @author      Mercado Pago
 * @license     See LICENSE for license details.
 */

namespace MercadoPago\AdbPayment\Model\Api;

use Magento\Sales\Api\OrderRepositoryInterface;
use MercadoPago\AdbPayment\Api\PayInfoManagementInterface;

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

        if ($payment->getMethod() === 'mercadopago_adbpayment_checkout_pro') {
            $info['data'] = [
                'id'            => $order->getPayment()->getAdditionalInformation('id'),
                'init_point'    => $order->getPayment()->getAdditionalInformation('init_point'),
            ];
        }

        return $info;
    }
}
