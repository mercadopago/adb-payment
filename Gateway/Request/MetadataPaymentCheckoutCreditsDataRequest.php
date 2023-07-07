<?php

/**
 * Copyright © MercadoPago. All rights reserved.
 *
 * @license     See LICENSE for license details.
 */

namespace MercadoPago\AdbPayment\Gateway\Request;

use InvalidArgumentException;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Gateway\Request\BuilderInterface;
use MercadoPago\AdbPayment\Gateway\Config\ConfigCheckoutCredits;
use MercadoPago\AdbPayment\Gateway\Request\MetadataPaymentCheckoutProDataRequest as MetadataPaymentCheckoutProDataRequest;

/**
 * Gateway requests for Payment Metadata by Checkout Credits.
 */
class MetadataPaymentCheckoutCreditsDataRequest extends MetadataPaymentCheckoutProDataRequest implements BuilderInterface
{
    /**
     * Credits block name.
     */
    public const CREDITS = 'credits';

    /**
     * Redirect block name.
     */
    public const REDIRECT = 'redirect';

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

        $result[MetadataPaymentDataRequest::METADATA] = [
            self::CHECKOUT      => self::CREDITS,
            self::CHECKOUT_TYPE => self::REDIRECT,
        ];

        return $result;
    }
}
