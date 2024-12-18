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

/**
 * Gateway response to Transaction Details by Yape.
 */
class TxnIdYapeHandler implements HandlerInterface
{
    /**
     * Payment Id response value.
     */
    public const PAYMENT_ID = 'id';

    /**
     * Payment Id block name.
     */
    public const MP_PAYMENT_ID = 'mp_payment_id';

    /**
     * Status response value.
     */
    public const STATUS = 'status';

    /**
     * MP Status block name.
     */
    public const MP_STATUS = 'mp_status';

    /**
     * Status response value.
     */
    public const STATUS_DETAIL = 'status_detail';

    /**
     * MP Status Detail block name.
     */
    public const MP_STATUS_DETAIL = 'mp_status_detail';

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
        if (!isset($handlingSubject['payment'])
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
        $payment->setAuthorizationTransaction($transactionId);
        $payment->addTransaction(Transaction::TYPE_AUTH);

        $order = $payment->getOrder();
        $order->setState(\Magento\Sales\Model\Order::STATE_NEW);
        $order->setStatus('pending');
        $comment = __('Awaiting payment through Yape.');
        $order->addStatusHistoryComment($comment, $payment->getOrder()->getStatus());
    }

    /**
     * Set Additional Information.
     *
     * @param InfoInterface $payment
     * @param array         $response
     *
     * @return void
     */
    public function setAddtionalInformation($payment, $response)
    {
        $payment->setAdditionalInformation(
            self::MP_PAYMENT_ID,
            $response[self::PAYMENT_ID]
        );

        $payment->setAdditionalInformation(
            self::MP_STATUS,
            $response[self::STATUS]
        );

        $payment->setAdditionalInformation(
            self::MP_STATUS_DETAIL,
            $response[self::STATUS_DETAIL]
        );
    }
}
