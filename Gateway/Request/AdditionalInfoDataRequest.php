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
 * Gateway Requests for Additional Data.
 */
class AdditionalInfoDataRequest implements BuilderInterface
{
    /**
     * Additional Info block name.
     */
    public const ADDITIONAL_INFO = 'additional_info';

    /**
     * Referral URL block name.
     */
    public const REFERRAL_URL = 'referral_url';

     /**
     * Drop Sipping block name.
     */
    public const DROP_SHIPPING = 'drop_shipping';

     /**
     * Delivery Promise block name.
     */
    public const DELIVERY_PROMISE = 'delivery_promise';

     /**
     * Contrated Plan block name.
     */
    public const CONTRATED_PLAN = 'contrated_plan';

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
        $result[self::ADDITIONAL_INFO] = [
            self::REFERRAL_URL        => null,
            self::DROP_SHIPPING       => null,
            self::DELIVERY_PROMISE    => null,
            self::CONTRATED_PLAN      => null  
        ];

        return $result;
    }
}
