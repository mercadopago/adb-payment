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
 * Gateway requests to define payment external reference.
 */
class ExtPaymentIdDataRequest implements BuilderInterface
{
    /**
     * External Reference Id block name.
     */
    public const MP_REFERENCE_ID = 'mp_payment_id';

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

        $mpPaymentId = $addInfo['mp_payment_id'] ?? $payment->getLastTransId();

        return [
            self::MP_REFERENCE_ID => preg_replace('/[^0-9]/', '', $mpPaymentId),
        ];
    }
}
