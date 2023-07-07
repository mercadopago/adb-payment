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
use MercadoPago\AdbPayment\Gateway\Request\CheckoutProPaymentDataRequest as CheckoutProPaymentDataRequest;

/**
 * Gateway Requests Payment by Checkout Credits Data.
 */
class CheckoutCreditsPaymentDataRequest extends CheckoutProPaymentDataRequest implements BuilderInterface
{
    /**
     * @var ConfigCheckoutCredits
     */
    protected $configCheckoutCredits;

    /**
     * @param ConfigCheckoutCredits $configCheckoutCredits
     */
    public function __construct(
        ConfigCheckoutCredits $configCheckoutCredits
    ) {
        $this->configCheckoutCredits = $configCheckoutCredits;
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

        $result = [
            self::DATE_OF_EXPIRATION => $this->configCheckoutCredits->getExpirationFormatted(),
            self::AUTO_RETURN        => 'all',
        ];

        return $result;
    }
}
