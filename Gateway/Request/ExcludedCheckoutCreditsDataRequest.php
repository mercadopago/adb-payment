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
use MercadoPago\AdbPayment\Gateway\Request\ExcludedCheckoutProDataRequest as ExcludedCheckoutProDataRequest;

/**
 * Gateway Requests for Deleting Payment Means.
 */
class ExcludedCheckoutCreditsDataRequest extends ExcludedCheckoutProDataRequest implements BuilderInterface
{
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

        $paymentDO = $buildSubject['payment'];
        $order = $paymentDO->getOrder();
        $storeId = $order->getStoreId();
        $result = [];

        $excludeds = $this->config->getExcluded($storeId);

        if (is_array($excludeds)) {
            foreach ($excludeds as $exclude) {
                $result[self::PAYMENT_METHODS][self::EXCLUDED_PAYMENT_METHODS][] = [
                    'id' => $exclude,
                ];
            }
        }

        return $result;
    }
}
