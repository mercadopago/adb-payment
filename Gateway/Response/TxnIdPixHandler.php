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
     * Order Id block name.
     */
    public const MP_ORDER_ID = 'mp_order_id';

    /**
     * Payment Id Order block name.
     */
    public const MP_PAYMENT_ID_ORDER = 'mp_payment_id_order';

    /**
     * Payments block name.
     */
    public const PAYMENTS = 'payments';

    /**
     * Payment Method block name.
     */
    public const PAYMENT_METHOD = 'payment_method';

    /**
     * Payment Url block name.
     */
    public const PAYMENT_URL = 'payment_url';

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
	 * References block and keys.
	 */
	public const REFERENCES = 'references';

	/**
	 * Reference source block name.
	 */
	public const REFERENCE_SOURCE = 'source';

	/**
	 * Reference payment id block name.
	 */
	public const REFERENCE_PAYMENT_ID = 'payment_id';

	/**
	 * Reference order id block name.
	 */
	public const REFERENCE_ORDER_ID = 'order_id';

	/**
	 * Reference source mp payments block name.
	 */
	public const MP_PAYMENTS = 'mp_payments';

	/**
	 * Reference source mp order block name.
	 */
	public const MP_ORDER = 'mp_order';

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

        /** @var Payment $payment */
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
        $comment = __('Awaiting payment through Pix.')->render();
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
        $paymentResponse = $response[self::PAYMENTS][0] ?? [];
        $paymentReference = $paymentResponse[self::REFERENCES] ?? [];
        $referenceSource = $paymentReference[self::REFERENCE_SOURCE] ?? null;
        $referenceKey = [
            self::MP_PAYMENTS => self::REFERENCE_PAYMENT_ID,
            self::MP_ORDER => self::REFERENCE_ORDER_ID,
        ][$referenceSource] ?? null;
        
        $paymentId = $referenceKey ? ($paymentReference[$referenceKey] ?? null) : null;

        $paymentMethod = $paymentResponse[self::PAYMENT_METHOD] ?? [];

        $payment->setAdditionalInformation(
            self::MP_STATUS,
            $response[self::STATUS] ?? null
        );

        $payment->setAdditionalInformation(
            self::MP_STATUS_DETAIL,
            $response[self::STATUS_DETAIL] ?? null
        );

        $payment->setAdditionalInformation(
            self::MP_PAYMENT_ID,
            $paymentId
        );

        $payment->setAdditionalInformation(
            self::MP_ORDER_ID,
            $response[self::PAYMENT_ID] ?? null
        );

        $payment->setAdditionalInformation(
            self::MP_PAYMENT_ID_ORDER,
            $paymentResponse[self::PAYMENT_ID] ?? null
        );

        $payment->setAdditionalInformation(
            self::DATE_OF_EXPIRATION,
            $paymentResponse[self::DATE_OF_EXPIRATION] ?? null
        );

        $payment->setAdditionalInformation(
            self::QR_CODE,
            $paymentMethod[self::QR_CODE] ?? null
        );

        $payment->setAdditionalInformation(
            self::QR_CODE_ENCODE,
            $paymentMethod[self::QR_CODE_ENCODE] ?? null
        );

        $payment->setAdditionalInformation(
            self::EXTERNAL_TICKET_URL,
            $paymentMethod[self::PAYMENT_URL] ?? null
        );
    }
}
