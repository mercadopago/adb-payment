<?php

/**
 * Copyright © MercadoPago. All rights reserved.
 *
 * @author      Mercado Pago
 * @license     See LICENSE for license details.
 */

namespace MercadoPago\AdbPayment\Model\Order;

use Magento\Payment\Model\Method\Logger;
use Magento\Sales\Model\Order;
use MercadoPago\AdbPayment\Model\MPApi\PaymentSearch;

/**
 * Decides whether an order that is still "new" can be cancelled by the expiration crons.
 *
 * Those crons assume that, if the payment had been approved, the notification would already have
 * moved the order out of "new". When Mercado Pago delays or loses the notification that assumption
 * is false and a paid order would be cancelled, so the real state of the payment is checked first.
 */
class CancelGuard {

    /**
     * Payment statuses in which the money was captured or is still being decided.
     */
    const BLOCKING_STATUSES = ['approved', 'authorized', 'in_process', 'in_mediation'];

    /**
     * @var PaymentSearch
     */
    protected $paymentSearch;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @param PaymentSearch $paymentSearch
     * @param Logger        $logger
     */
    public function __construct(PaymentSearch $paymentSearch, Logger $logger)
    {
        $this->paymentSearch = $paymentSearch;
        $this->logger = $logger;
    }

    /**
     * True only when Mercado Pago confirms that the order has no approved or in-progress payment.
     * If Mercado Pago cannot be queried the order is NOT cancelled; the next run evaluates it again.
     *
     * @param Order $order
     *
     * @return bool
     */
    public function canCancel(Order $order)
    {
        $incrementId = (string) $order->getIncrementId();

        try {
            $payments = $this->paymentSearch->searchByExternalReference(
                $incrementId,
                (string) $order->getStoreId()
            );
        } catch (\Throwable $e) {
            $this->logger->debug([
                'cancel_guard' => 'Order ' . $incrementId . ' not cancelled: payment lookup failed',
                'error'        => $e->getMessage()
            ]);

            return false;
        }

        foreach ($payments as $payment) {
            $status = isset($payment['status']) ? $payment['status'] : '';
            if (in_array($status, self::BLOCKING_STATUSES, true)) {
                $this->logger->debug([
                    'cancel_guard' => 'Order ' . $incrementId . ' not cancelled: Mercado Pago has a payment',
                    'status'       => $status
                ]);

                return false;
            }
        }

        return true;
    }
}
