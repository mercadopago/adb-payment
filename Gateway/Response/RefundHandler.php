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
use Magento\Sales\Model\Order\Creditmemo;

/**
 * Gateway response for Payment Refund.
 */
class RefundHandler implements HandlerInterface
{
    /**
     * Result Code - Block name.
     */
    public const RESULT_CODE = 'RESULT_CODE';

    /**
     * Response Refund Id - Block name.
     */
    public const RESPONSE_REFUND_ID = 'id';

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
     * Response Pay Status Approved - Value.
     */
    public const RESPONSE_STATUS_APPROVED = 'approved';

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

        $payment->setTransactionId($response[self::RESPONSE_REFUND_ID]);

        if ($response[self::RESPONSE_STATUS] === self::RESPONSE_STATUS_ACCEPTED || $response[self::RESPONSE_STATUS] === self::RESPONSE_STATUS_APPROVED) {
            $creditmemo = $payment->getCreditmemo();
            $creditmemo->setState(Creditmemo::STATE_REFUNDED);
        }
        if ($response[self::RESPONSE_STATUS] === self::RESPONSE_STATUS_DENIED) {
            $creditmemo = $payment->getCreditmemo();
            $creditmemo->setState(Creditmemo::STATE_CANCELED);
        }

        if ($response[self::RESULT_CODE]) {
            $paymentDO->getPayment();
        }
    }
}
