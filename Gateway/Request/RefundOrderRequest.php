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
use MercadoPago\AdbPayment\Gateway\Config\Config;

class RefundOrderRequest implements BuilderInterface
{
    /**
     * External Order Id block name.
     */
    public const MP_ORDER_ID = 'mp_order_id';

    /**
     * Amount block name.
     */
    public const AMOUNT = 'amount';

    /**
     * Payment block name.
     */
    public const PAYMENT = 'payment';

    /**
     * Store ID block name.
     */
    public const STORE_ID = 'store_id';

    /**
     * Refund unique key - combination of order + refunded amount.
     */
    public const REFUND_KEY = 'refund_key';

    /**
     * Is partial refund flag block name.
     */
    public const IS_PARTIAL_REFUND = 'is_partial_refund';

    /**
     * MP Payment ID block name.
     */
    public const MP_PAYMENT_ID_ORDER = 'mp_payment_id_order';

    /**
     * @var Config
     */
    protected $configPayment;


    /**
     * @param Config $configPayment
     */
    public function __construct(
        Config $configPayment
    ) {
        $this->configPayment = $configPayment;
    }

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

        $payment = $buildSubject[self::PAYMENT]->getPayment();
        $order = $payment->getOrder();
        $storeId = $order->getStoreId();

        $orderTotal = $this->configPayment->formatPrice($order->getGrandTotal(), $storeId);
        $refundAmount = $this->configPayment->formatPrice($payment->getCreditMemo()->getGrandTotal(), $storeId);

        return [
            self::MP_ORDER_ID         => preg_replace('/-(capture|refund|void)+.*$/', '', $payment->getTransactionId()),
            self::MP_PAYMENT_ID_ORDER => $payment->getAdditionalInformation(self::MP_PAYMENT_ID_ORDER),
            self::REFUND_KEY          => $order->getIncrementId() . '-' . (string) $payment->getAmountRefunded(),
            self::AMOUNT              => $refundAmount,
            self::IS_PARTIAL_REFUND   => $orderTotal !== $refundAmount,
        ];
    }
}