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
 * Gateway response handler for Order API and Payment API Refund.
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
     * Response Pay Status Approved - Value (Payment API).
     */
    public const RESPONSE_STATUS_APPROVED = 'approved';

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
     * Response Refund Id - Block name (Payment API).
     */
    public const RESPONSE_ID = 'id';

    /**
     * Handles Order API and Payment API refund response.
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
        [$refundId, $status] = $this->extractRefundData($response);

        if (!$refundId) {
            return;
        }

        // Save refund ID for webhook identification
        $payment->setTransactionId($refundId);
        $payment->setAdditionalInformation('mp_refund_id', $refundId);

        $creditmemo = $payment->getCreditmemo();
        $creditmemo->setTransactionId($refundId);

        $stateMap = [
            self::RESPONSE_STATUS_PROCESSING => Creditmemo::STATE_OPEN,
            self::RESPONSE_STATUS_PROCESSED  => Creditmemo::STATE_REFUNDED,
            self::RESPONSE_STATUS_FAILED     => Creditmemo::STATE_CANCELED,
            // Payment API statuses
            self::RESPONSE_STATUS_APPROVED   => Creditmemo::STATE_REFUNDED,
        ];

        if (isset($stateMap[$status])) {
            $creditmemo->setState($stateMap[$status]);
        }
    }
    /**
     * Extract refund ID and status from response (Payment API or Order API).
     *
     * @param array $response
     * @return array [refundId, status]
     */
    private function extractRefundData(array $response): array
    {
        // Payment API: { id, status }
        if (isset($response[self::RESPONSE_ID]) && !isset($response[self::RESPONSE_PAYMENTS])) {
            return [$response[self::RESPONSE_ID], $response[self::RESPONSE_STATUS] ?? null];
        }

        // Order API: { payments: [{ status, reference: { refund_payment_id } }] }
        $payment = $response[self::RESPONSE_PAYMENTS][0] ?? [];
        $reference = $payment[self::RESPONSE_REFERENCE] ?? [];

        return [
            $reference[self::RESPONSE_REFUND_PAYMENT_ID] ?? $reference[self::RESPONSE_REFUND_ORDER_ID] ?? null,
            $payment[self::RESPONSE_STATUS] ?? null
        ];
    }
}
