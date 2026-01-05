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
 * Gateway response handler for Order API Refund.
 */
class RefundOrderHandler implements HandlerInterface
{
    /**
     * Response Pay Status - Block Name.
     */
    public const RESPONSE_STATUS = 'status';

    /**
     * Response Pay Status Processing - Value.
     */
    public const RESPONSE_STATUS_PROCESSING = 'processing';

    /**
     * Response Pay Status Processed - Value.
     */
    public const RESPONSE_STATUS_PROCESSED = 'processed';

    /**
     * Response Pay Status Failed - Value.
     */
    public const RESPONSE_STATUS_FAILED = 'failed';

    /**
     * Response Refund Payment Id - Block name.
     */
    public const RESPONSE_REFUND_PAYMENT_ID = 'refund_payment_id';

    /**
     * Response Refund Order Id - Block name.
     */
    public const RESPONSE_REFUND_ORDER_ID = 'refund_order_id';

    /**
     * Response Reference - Block name.
     */
    public const RESPONSE_REFERENCE = 'reference';

    /**
     * Payments - Block name.
     */
    public const RESPONSE_PAYMENTS = 'payments';

    /**
     * Handles Order API refund response.
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

        $payment = $handlingSubject['payment']->getPayment();
        $paymentResponse = $response[self::RESPONSE_PAYMENTS][0] ?? [];
        $reference = $paymentResponse[self::RESPONSE_REFERENCE] ?? [];
        $status = $paymentResponse[self::RESPONSE_STATUS] ?? null;

        $refundId = $reference[self::RESPONSE_REFUND_PAYMENT_ID]
            ?? $reference[self::RESPONSE_REFUND_ORDER_ID]
            ?? null;

        // Save refund ID for webhook identification
        $payment->setTransactionId($refundId);
        $payment->setAdditionalInformation('mp_refund_id', $refundId);

        $creditmemo = $payment->getCreditmemo();
        $creditmemo->setTransactionId($refundId);

        $stateMap = [
            self::RESPONSE_STATUS_PROCESSING => Creditmemo::STATE_OPEN,
            self::RESPONSE_STATUS_PROCESSED  => Creditmemo::STATE_REFUNDED,
            self::RESPONSE_STATUS_FAILED     => Creditmemo::STATE_CANCELED,
        ];

        if (isset($stateMap[$status])) {
            $creditmemo->setState($stateMap[$status]);
        }
    }
}

