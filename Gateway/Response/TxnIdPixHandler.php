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
 * Gateway response to Transaction Details by Pix.
 */
class TxnIdPixHandler implements HandlerInterface
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
     * Date of Expiration block name.
     */
    public const DATE_OF_EXPIRATION = 'date_of_expiration';

    /**
     * Point Of Interaction block name.
     */
    public const POINT_OF_INTERACTION = 'point_of_interaction';

    /**
     * Transaction Data block name.
     */
    public const TRANSACTION_DATA = 'transaction_data';

    /**
     * Qr code block name.
     */
    public const QR_CODE = 'qr_code';

    /**
     * Qr code Encode block name.
     */
    public const QR_CODE_ENCODE = 'qr_code_base64';

    /**
     * External Ticket url block name.
     */
    public const EXTERNAL_TICKET_URL = 'ticket_url';

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
        $comment = __('Awaiting payment through Pix.');
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

        $payment->setAdditionalInformation(
            self::DATE_OF_EXPIRATION,
            $response[self::DATE_OF_EXPIRATION]
        );

        $transactionData = $response[self::POINT_OF_INTERACTION][self::TRANSACTION_DATA];

        $payment->setAdditionalInformation(
            self::QR_CODE,
            $transactionData[self::QR_CODE]
        );

        $payment->setAdditionalInformation(
            self::QR_CODE_ENCODE,
            $transactionData[self::QR_CODE_ENCODE]
        );

        $payment->setAdditionalInformation(
            self::EXTERNAL_TICKET_URL,
            $transactionData[self::EXTERNAL_TICKET_URL]
        );
    }
}
