<?php

/**
 * Copyright © MercadoPago. All rights reserved.
 *
 * @author      Mercado Pago
 * @license     See LICENSE for license details.
 */

namespace MercadoPago\AdbPayment\Gateway\Response;

use InvalidArgumentException;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Gateway\Response\HandlerInterface;
use Magento\Sales\Model\Order\Payment\Transaction;
use MercadoPago\AdbPayment\Gateway\Response\TxnIdCheckoutProHandler as TxnIdCheckoutProHandler;

/**
 * Gateway response to Transaction Details by Checkout Credits.
 */
class TxnIdCheckoutCreditsHandler extends TxnIdCheckoutProHandler implements HandlerInterface
{
    /**
     * Handles.
     *
     * @param array $handlingSubject
     * @param array $response
     *
     * @return void
     */
    public function handle(array $handlingSubject, array $response)
    {
        if (
            !isset($handlingSubject['payment'])
            || !$handlingSubject['payment'] instanceof PaymentDataObjectInterface
        ) {
            throw new InvalidArgumentException('Payment data object should be provided');
        }

        $paymentDO = $handlingSubject['payment'];

        $payment = $paymentDO->getPayment();

        $this->setAddtionalInformation($payment, $response);

        $transactionId = $response[self::PAYMENT_ID];

        $payment->setTransactionId($transactionId);
        $payment->setIsTransactionPending(1);
        $payment->setIsTransactionClosed(false);
        $payment->addTransaction(Transaction::TYPE_ORDER);

        $order = $payment->getOrder();
        $order->setState(\Magento\Sales\Model\Order::STATE_NEW);
        $order->setStatus('pending');
        $comment = __('Awaiting payment through Installments without card.');
        $order->addStatusHistoryComment($comment, $payment->getOrder()->getStatus());
    }
}
