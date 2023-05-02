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
 * Gateway response to Transaction Details by Checkout Pro.
 */
class TxnIdCheckoutProHandler implements HandlerInterface
{
    /**
     * Payment Id block name.
     */
    public const PAYMENT_ID = 'id';

    /**
     * Date of Expiration block name.
     */
    public const DATE_OF_EXPIRATION = 'date_of_expiration';

    /**
     * External Init Point block name.
     */
    public const INIT_POINT = 'init_point';

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
        $payment->addTransaction(Transaction::TYPE_ORDER);

        $order = $payment->getOrder();
        $order->setState(\Magento\Sales\Model\Order::STATE_NEW);
        $order->setStatus('pending');
        $comment = __('Awaiting payment through Checkout Pro.');
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
        if (isset($response[self::DATE_OF_EXPIRATION])) {
            $payment->setAdditionalInformation(
                self::DATE_OF_EXPIRATION,
                $response[self::DATE_OF_EXPIRATION]
            );
        }

        $payment->setAdditionalInformation(
            self::INIT_POINT,
            $response[self::INIT_POINT]
        );

        $payment->setAdditionalInformation(
            self::PAYMENT_ID,
            $response[self::PAYMENT_ID]
        );
    }
}
