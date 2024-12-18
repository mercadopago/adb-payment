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
 * Gateway requests for Payer data in method Pix.
 */
class YapePaymentDataRequest implements BuilderInterface
{
    /**
     * Payment Method Id block name.
     */
    public const PAYMENT_METHOD_ID = 'payment_method_id';

    /**
     * Payment Method block name.
     */
    public const YAPE = 'yape';

    /**
     * Yape Token block name.
     */
    public const TOKEN = 'token';

    /**
     * Yape Token id block name.
     */
    public const YAPE_TOKEN = 'yape_token_id';

    /**
     * Payment block name.
     */
    public const PAYMENT = 'payment';

    /**
     * Payment block name.
     */
    public const INSTALLMENTS = 'installments';

    /**
     * Build.
     *
     * @param array $buildSubject
     */
    public function build(array $buildSubject)
    {
        if (!isset($buildSubject[self::PAYMENT])
            || !$buildSubject[self::PAYMENT] instanceof PaymentDataObjectInterface
        ) {
            throw new InvalidArgumentException('Payment data object should be provided');
        }

        $paymentDO = $buildSubject[self::PAYMENT];
        $payment = $paymentDO->getPayment();

        $result = [];

        $result = [
            self::PAYMENT_METHOD_ID  => self::YAPE,
            self::TOKEN              => $payment->getAdditionalInformation(self::YAPE_TOKEN),
            self::INSTALLMENTS       => 1,
        ];

        return $result;
    }
}
