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
 * Gateway requests for Payment Metadata by Yape.
 */
class MetadataPaymentYapeDataRequest implements BuilderInterface
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
     * Payment block name.
     */
    public const PAYMENT = 'payment';

    /**
     * Custom block name.
     */
    public const CUSTOM = 'custom';

    /**
     * Payment Method block name.
     */
    public const YAPE = 'yape';

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
        $result = [];

        $result[MetadataPaymentDataRequest::METADATA] = [
            self::CHECKOUT      => self::CUSTOM,
            self::CHECKOUT_TYPE => self::YAPE,
        ];

        $result[MetadataPaymentDataRequest::METADATA][MetadataPaymentDataRequest::CPP_EXTRA] = [
            self::CHECKOUT      => self::CUSTOM,
            self::CHECKOUT_TYPE => self::YAPE,
        ];

        return $result;
    }
}
