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
use MercadoPago\AdbPayment\Gateway\Config\ConfigCheckoutCredits;

/**
 * Gateway Requests for Default Payment Method Id in Checkout Credits.
 */
class DefaultMethodCheckoutCreditsDataRequest implements BuilderInterface
{
    /**
     * Payment Methods block name.
     */
    public const PAYMENT_METHODS = 'payment_methods';

    /**
     * Deafult Payment Method ID block name.
     */
    public const DEFAULT_PAYMENT_METHOD_ID = 'default_payment_method_id';

    /**
     * Consumer Credits block name.
     */
    public const CONSUMER_CREDITS = 'consumer_credits';

    /**
     * Purpose - Block name.
     */
    public const PURPOSE = 'purpose';

    /**
     * Onboarding Credits - Block name.
     */
    public const ONBOARDING_CREDITS = 'onboarding_credits';

    /**
     * @var ConfigCheckoutCredits
     */
    protected $config;

    /**
     * @param ConfigCheckoutCredits $config
     */
    public function __construct(
        ConfigCheckoutCredits $config
    ) {
        $this->config = $config;
    }

    /**
     * Build.
     *
     * @param array $buildSubject
     */
    public function build(array $buildSubject)
    {
        if (
            !isset($buildSubject['payment'])
            || !$buildSubject['payment'] instanceof PaymentDataObjectInterface
        ) {
            throw new InvalidArgumentException('Payment data object should be provided');
        }

        $result = [];

        $result[self::PAYMENT_METHODS][self::DEFAULT_PAYMENT_METHOD_ID] = self::CONSUMER_CREDITS;

        $result = array_merge(
            [
                self::PURPOSE => self::ONBOARDING_CREDITS,
            ],
            $result,
        );

        return $result;
    }
}
