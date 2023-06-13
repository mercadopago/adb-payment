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

/**
 * Payment Void Gateway Response.
 */
class VoidPaymentHandler implements HandlerInterface
{
    /**
     * Result Code - Block name.
     */
    public const RESULT_CODE = 'RESULT_CODE';

    /**
     * Response Pay Cancel Request Id - Block Name.
     */
    public const RESPONSE_CANCEL_REQUEST_ID = 'cancel_request_id';

    /**
     * Response Pay Status - Block Name.
     */
    public const RESPONSE_STATUS = 'status';

    /**
     * Response Pay Status Approved - Value.
     */
    public const RESPONSE_STATUS_ACCEPTED = 'ACCEPTED';

    /**
     * Response Pay Status Denied - Value.
     */
    public const RESPONSE_STATUS_DENIED = 'DENIED';

     /**
     * Payment Id block name.
     */
    public const PAYMENT_ID = 'id';

    /**
     * Payment Id block name.
     */
    public const MP_PAYMENT_ID = 'payment_0_id';

    /**
     * Status response value.
     */
    public const STATUS_DETAIL = 'status_detail';

    /**
     * MP Status Detail block name.
     */
    public const MP_STATUS_DETAIL = 'mp_0_status_detail';

    /**
     * MP Status Detail block name.
     */
    public const MP_STATUS = 'mp_0_status';


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

        if ($response[self::RESULT_CODE]) {
            $paymentDO = $handlingSubject['payment'];
            $payment = $paymentDO->getPayment();

            $order = $payment->getOrder();
            $amount = $order->getBaseGrandTotal();

            $this->setAddtionalInformation($payment, $response);

            $payment->setPreparedMessage(__('Order Canceled.'));
            $payment->setIsTransactionPending(false);
            $payment->setIsTransactionDenied(true);
            $payment->setAmountCanceled($amount);
            $payment->setBaseAmountCanceled($amount);
            $payment->setShouldCloseParentTransaction(true);
        }
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
            $response[self::RESPONSE_STATUS]
        );

        $payment->setAdditionalInformation(
            self::MP_STATUS_DETAIL,
            $response[self::STATUS_DETAIL]
        );
    }
}
