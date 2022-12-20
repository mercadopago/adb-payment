<?php
/**
 * Copyright © MercadoPago. All rights reserved.
 *
 * @author      Bruno Elisei <brunoelisei@o2ti.com>
 * @license     See LICENSE for license details.
 */

namespace MercadoPago\PaymentMagento\Gateway\Response;

use InvalidArgumentException;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Gateway\Response\HandlerInterface;

/**
 * Gateway Response Payment Fetch.
 */
class FetchPaymentHandler implements HandlerInterface
{
    /**
     * Response Pay Status - Block Name.
     */
    public const RESPONSE_STATUS = 'status';

    /**
     * Response Pay Status Approved - Value.
     */
    public const RESPONSE_STATUS_APPROVED = 'approved';

    /**
     * Response Pay Status Cancelled - Value.
     */
    public const RESPONSE_STATUS_CANCELLED = 'cancelled';

    /**
     * Response Pay Status Rejected - Value
     */
    public const RESPONSE_STATUS_REJECTED = 'rejected';

    /**
     * Response Pay Status Pending - Value.
     */
    public const RESPONSE_STATUS_PENDING = 'pending';

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

        if (isset($response[self::RESPONSE_STATUS])) {
            $paymentDO = $handlingSubject['payment'];

            $payment = $paymentDO->getPayment();

            $order = $payment->getOrder();
            $amount = $order->getGrandTotal();
            $baseAmount = $order->getBaseGrandTotal();

            if ($response[self::RESPONSE_STATUS] === self::RESPONSE_STATUS_APPROVED) {
                $payment->registerAuthorizationNotification($baseAmount);
                $payment->registerCaptureNotification($baseAmount);
                $payment->setIsTransactionApproved(true);
                $payment->setIsTransactionDenied(false);
                $payment->setIsInProcess(true);
                $payment->setIsTransactionClosed(true);
                $payment->setShouldCloseParentTransaction(true);
                $payment->setAmountAuthorized($baseAmount);
            }

            if ($response[self::RESPONSE_STATUS] === self::RESPONSE_STATUS_CANCELLED ||
                $response[self::RESPONSE_STATUS] === self::RESPONSE_STATUS_REJECTED) {
                $payment->setPreparedMessage(__('Order Canceled.'));
                $payment->registerVoidNotification($amount);
                $payment->setIsTransactionApproved(false);
                $payment->setIsTransactionDenied(true);
                $payment->setIsTransactionPending(false);
                $payment->setIsInProcess(true);
                $payment->setIsTransactionClosed(true);
                $payment->setShouldCloseParentTransaction(true);
                $payment->setAmountCanceled($amount);
                $payment->setBaseAmountCanceled($baseAmount);
            }
        }
    }
}
