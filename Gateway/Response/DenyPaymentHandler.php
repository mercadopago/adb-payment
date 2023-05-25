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
 * Gateway response for Denial of Payment.
 */
class DenyPaymentHandler implements HandlerInterface
{
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
            $amount = $order->getTotalDue();
            $baseAmount = $order->getBaseTotalDue();

            $payment->setPreparedMessage(__('Order Canceled.'));
            $payment->registerVoidNotification($amount);
            $payment->setIsTransactionApproved(false);
            $payment->setIsTransactionDenied(true);
            $payment->setIsTransactionPending(false);
            $payment->setIsInProcess(true);
            $payment->setIsTransactionClosed(true);
            $payment->setAmountCanceled($amount);
            $payment->setBaseAmountCanceled($baseAmount);
            $payment->setShouldCloseParentTransaction(true);

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
}
