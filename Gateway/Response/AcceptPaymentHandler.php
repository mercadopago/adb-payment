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
 * Gateway Response Payment Accepted..
 */
class AcceptPaymentHandler implements HandlerInterface
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
        if (!isset($handlingSubject['payment'])
            || !$handlingSubject['payment'] instanceof PaymentDataObjectInterface
        ) {
            throw new InvalidArgumentException('Payment data object should be provided');
        }

        if ($response['RESULT_CODE']) {
            $paymentDO = $handlingSubject['payment'];

            $payment = $paymentDO->getPayment();

            $order = $payment->getOrder();
            $amount = $order->getBaseGrandTotal();

            $payment->registerAuthorizationNotification($amount);
            $payment->registerCaptureNotification($amount);
            $payment->setIsTransactionApproved(true);
            $payment->setIsTransactionDenied(false);
            $payment->setIsInProcess(true);
            $payment->setIsTransactionClosed(true);
            $payment->setShouldCloseParentTransaction(true);
            $payment->setAmountAuthorized($amount);
        }
    }
}
