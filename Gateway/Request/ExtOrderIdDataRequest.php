<?php
/**
 * Copyright © MercadoPago. All rights reserved.
 *
 * @author      Mercado Pago
 * @license     See LICENSE for license details.
 */

namespace MercadoPago\AdbPayment\Gateway\Request;

use InvalidArgumentException;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Gateway\Request\BuilderInterface;

/**
 * Gateway requests to define Order external reference (Order API).
 */
class ExtOrderIdDataRequest implements BuilderInterface
{
    /**
     * Additional Information key for Order ID.
     */
    public const ORDER_API_ID_KEY = 'mp_order_id';

    /**
     * Additional Information key for Payment ID Order.
     */
    public const ORDER_API_PAYMENT_ID_KEY = 'mp_payment_id_order';



    /**
     * Build.
     *
     * @param array $buildSubject
     */
    public function build(array $buildSubject)
    {
        if (!isset($buildSubject['payment'])
        || !$buildSubject['payment'] instanceof PaymentDataObjectInterface
        ) {
            throw new InvalidArgumentException('Payment data object should be provided');
        }

        $paymentDO = $buildSubject['payment'];

        $payment = $paymentDO->getPayment();

        $addInfo = $payment->getAdditionalInformation();

        $mpOrderId = $addInfo[self::ORDER_API_ID_KEY] ?? $payment->getLastTransId();
        $mpPaymentIdOrder = $addInfo[self::ORDER_API_PAYMENT_ID_KEY] ?? null;

        return [
            self::ORDER_API_ID_KEY => $mpOrderId,
            self::ORDER_API_PAYMENT_ID_KEY => $mpPaymentIdOrder,
        ];
    }
}
