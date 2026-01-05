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
 * Gateway Response Order Capture - Order API.
 */
class CaptureOrderHandler implements HandlerInterface
{
    /**
     * Result Code - Block name.
     */
    public const RESULT_CODE = 'RESULT_CODE';

    /**
     * Response Order ID - Block name.
     */
    public const ORDER_ID = 'id';

    /**
     * Response Status - Block name.
     */
    public const RESPONSE_STATUS = 'status';

    /**
     * Response Status Detail - Block name.
     */
    public const STATUS_DETAIL = 'status_detail';

    /**
     * Response Status Processed - Value.
     */
    public const RESPONSE_STATUS_PROCESSED = 'processed';

    /**
     * Additional Information keys.
     */
    public const MP_STATUS = 'mp_status';

    /**
     * Additional Information key for Payment Status Detail.
     */
    public const MP_STATUS_DETAIL = 'mp_status_detail';

    /**
     * Handles.
     *
     * The capture command is invoked when admin creates an invoice manually.
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

        if (!($response[self::RESULT_CODE] ?? false)) {
            return;
        }

        $paymentDO = $handlingSubject['payment'];
        $payment = $paymentDO->getPayment();

        $this->setTransactionId($payment, $response);
        $this->updatePaymentInfo($payment, $response);

        $isProcessed = $this->isPaymentProcessed($response);

        $payment->setIsTransactionPending(!$isProcessed);
    }

    /**
     * Set transaction ID for the capture.
     *
     * Uses mp_order_id so webhook can find and update the same invoice.
     *
     * @param $payment
     * @param array $response
     * @return void
     */
    private function setTransactionId($payment, array $response): void
    {
        $mpOrderId = $response[self::ORDER_ID] ?? null;

        if ($mpOrderId) {
            $payment->setTransactionId($mpOrderId);
        }
    }

    /**
     * Check if payment is processed (approved).
     *
     * @param array $response
     * @return bool
     */
    private function isPaymentProcessed(array $response): bool
    {
        return ($response[self::RESPONSE_STATUS] ?? null) === self::RESPONSE_STATUS_PROCESSED;
    }

    /**
     * Update payment status from Order API response.
     *
     * @param $payment
     * @param array $response
     * @return void
     */
    private function updatePaymentInfo($payment, array $response): void
    {
        $payment->setAdditionalInformation(
            self::MP_STATUS,
            $response[self::RESPONSE_STATUS] ?? null
        );

        $payment->setAdditionalInformation(
            self::MP_STATUS_DETAIL,
            $response[self::STATUS_DETAIL] ?? null
        );
    }
}
