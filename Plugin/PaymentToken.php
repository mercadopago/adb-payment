<?php
/**
 * Copyright © MercadoPago. All rights reserved.
 *
 * @author      Mercado Pago
 * @license     See LICENSE for license details.
 */

namespace MercadoPago\AdbPayment\Plugin;

use Magento\Sales\Api\Data\OrderPaymentInterface;
use Magento\Vault\Api\Data\PaymentTokenInterface;
use Magento\Vault\Api\PaymentTokenManagementInterface;

/**
 * Plugin to avoid double capture of Saved Card.
 */
class PaymentToken
{
    /**
     * @var PaymentTokenManagementInterface
     */
    protected $payTokenManagement;

    /**
     * @var PaymentTokenInterface
     */
    protected $token;

    /**
     * @var OrderPaymentInterface
     */
    protected $payment;

    /**
     * Around Save Token With Payment Link.
     *
     * @param PaymentTokenManagementInterface $payTokenManagement
     * @param callable                        $proceed
     * @param PaymentTokenInterface           $token
     * @param OrderPaymentInterface           $payment
     *
     * @return bool
     */
    public function aroundSaveTokenWithPaymentLink(
        PaymentTokenManagementInterface $payTokenManagement,
        callable $proceed,
        PaymentTokenInterface $token,
        OrderPaymentInterface $payment
    ): bool {
        $order = $payment->getOrder();

        if ($order->getCustomerIsGuest()) {
            return $proceed($token, $payment);
        }

        $existingToken = $payTokenManagement->getByGatewayToken(
            $token->getGatewayToken(),
            $payment->getMethodInstance()->getCode(),
            $order->getCustomerId()
        );

        if ($existingToken === null) {
            return $proceed($token, $payment);
        }

        $existingToken->addData($token->getData());

        return $proceed($existingToken, $payment);
    }
}
