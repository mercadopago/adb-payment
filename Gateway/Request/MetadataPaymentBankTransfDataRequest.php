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
 * Gateway requests for Payment Metadata by Bank Transferer.
 */
class MetadataPaymentBankTransfDataRequest implements BuilderInterface
{
    /**
     * Checkout block name.
     */
    public const CHECKOUT = 'checkout';

    /**
     * Checkout Type block name.
     */
    public const CHECKOUT_TYPE = 'checkout_type';

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
        $result = [];

        $result[MetadataPaymentDataRequest::METADATA] = [
            self::CHECKOUT      => 'custom',
            self::CHECKOUT_TYPE => 'bank_transfer',
        ];

        return $result;
    }
}
