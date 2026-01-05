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
    public const MP_ORDER_ID = 'mp_order_id';

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

        $mpOrderId = $addInfo[self::MP_ORDER_ID] ?? $payment->getLastTransId();

        return [
            self::MP_ORDER_ID => $mpOrderId,
        ];
    }
}
