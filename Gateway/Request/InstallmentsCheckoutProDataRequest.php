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
use MercadoPago\AdbPayment\Gateway\Config\ConfigCheckoutPro;

/**
 * Gateway Requests for Installments in Checkout Pro.
 */
class InstallmentsCheckoutProDataRequest implements BuilderInterface
{
    /**
     * Payment Methods block name.
     */
    public const PAYMENT_METHODS = 'payment_methods';

    /**
     * Installments block name.
     */
    public const INSTALLMENTS = 'installments';

    /**
     * @var ConfigCheckoutPro
     */
    protected $config;

    /**
     * @param ConfigCheckoutPro $config
     */
    public function __construct(
        ConfigCheckoutPro $config
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
        if (!isset($buildSubject['payment'])
            || !$buildSubject['payment'] instanceof PaymentDataObjectInterface
        ) {
            throw new InvalidArgumentException('Payment data object should be provided');
        }

        $paymentDO = $buildSubject['payment'];
        $order = $paymentDO->getOrder();
        $storeId = $order->getStoreId();
        $result = [];

        $maxInstallment = $this->config->getMaxInstallments($storeId);

        if (isset($maxInstallment)) {
            $result[self::PAYMENT_METHODS][self::INSTALLMENTS] = $maxInstallment;
        }

        return $result;
    }
}
