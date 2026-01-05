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
 * Gateway Response Order Fetch.
 * 
 * Handles Order API fetch responses (different structure from Payment API).
 */
class FetchOrderHandler implements HandlerInterface
{
    /**
     * Order ID response value.
     */
    public const ID = 'id';

    /**
     * Status response value.
     */
    public const STATUS = 'status';

    /**
     * Status detail response value.
     */
    public const STATUS_DETAIL = 'status_detail';

    /**
     * Response Payment Method Info block name.
     */
    public const PAYMENT_METHOD = 'payment_method';

    /**
     * Response Payments array block name (Order API uses 'payments' not 'payments_details').
     */
    public const PAYMENTS = 'payments';

    /**
     * Response Total Paid Amount - Value.
     */
    public const TOTAL_PAID_AMOUNT = 'total_paid_amount';

    /** 
     * Amount response value. 
     */
    public const AMOUNT = 'amount';

    /**
     * Response Paid Amount - Value.
     */
    public const PAID_AMOUNT = 'paid_amount';

    /**
     * Response Pay Status Processed - Value (Order API).
     */
    public const RESPONSE_STATUS_PROCESSED = 'processed';

    /**
     * Response Pay Status Canceled - Value (Order API).
     */
    public const RESPONSE_STATUS_CANCELED = 'canceled';

    /**
     * Response Pay Status Failed - Value (Order API).
     */
    public const RESPONSE_STATUS_FAILED = 'failed';

    /**
     * Response Pay Status Expired - Value (Order API).
     */
    public const RESPONSE_STATUS_EXPIRED = 'expired';

    /**
     * Additional Information key for Payment ID (from references).
     */
    public const MP_PAYMENT_ID = 'mp_payment_id';

    /**
     * Additional Information key for Order ID.
     */
    public const MP_ORDER_ID = 'mp_order_id';

    /**
     * Additional Information key for Payment ID Order
     */
    public const MP_PAYMENT_ID_ORDER = 'mp_payment_id_order';

    /**
     * Additional Information key for Payment Type ID.
     */
    public const MP_PAYMENT_TYPE_ID = 'mp_payment_type_id';

    /**
     * Additional Information key for Transaction Amount.
     */
    public const MP_TRANSACTION_AMOUNT = 'mp_transaction_amount';

    /**
     * Additional Information key for Paid Amount.
     */
    public const MP_PAID_AMOUNT = 'mp_paid_amount';

    /**
     * Additional Information key for Payment Status.
     */
    public const MP_STATUS = 'mp_status';

    /**
     * Additional Information key for Payment Status Detail.
     */
    public const MP_STATUS_DETAIL = 'mp_status_detail';

    /**
     * References block name.
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
     * Reference source mp payments.
     */
    public const MP_PAYMENTS = 'mp_payments';

    /**
     * Reference source mp order.
     */
    public const MP_ORDER = 'mp_order';

    /**
     * Handles Order API fetch response.
     *
     * @param array $handlingSubject
     * @param array $response
     *
     * @return void
     */
    public function handle(array $handlingSubject, array $response)
    {
        $this->validateHandlingSubject($handlingSubject);
        
        if (!isset($response[self::STATUS])) {
            return;
        }

        $paymentDO = $handlingSubject['payment'];
        $payment = $paymentDO->getPayment();
        $order = $payment->getOrder();
        $orderApiStatus = $response[self::STATUS];
        
        $amount = $order->getGrandTotal();
        $baseAmount = $order->getBaseGrandTotal();

        if ($this->shouldProcessAsApproved($order, $orderApiStatus)) {
            $this->processApprovedPayment($payment, $baseAmount, $response);
        }

        if ($this->shouldProcessAsCanceled($orderApiStatus)) {
            $this->processCanceledPayment($payment, $amount, $baseAmount);
        }

        if (isset($response[self::PAYMENTS][0])) {
            $this->updatePaymentInfo($payment, $response);
        }
    }

    /**
     * Validate handling subject has payment data object.
     *
     * @param array $handlingSubject
     * @return void
     * @throws InvalidArgumentException
     */
    private function validateHandlingSubject(array $handlingSubject)
    {
        if (!isset($handlingSubject['payment']) 
            || !$handlingSubject['payment'] instanceof PaymentDataObjectInterface
        ) {
            throw new InvalidArgumentException('Payment data object should be provided');
        }
    }

    /**
     * Check if order should be processed as approved.
     *
     * @param $order
     * @param string $status
     * @return bool
     */
    private function shouldProcessAsApproved($order, string $status): bool
    {
        $allowedStatuses = ['payment_review', 'pending'];
        return in_array($order->getStatus(), $allowedStatuses) 
            && $status === self::RESPONSE_STATUS_PROCESSED;
    }

    /**
     * Check if order should be processed as canceled.
     *
     * @param string $status
     * @return bool
     */
    private function shouldProcessAsCanceled(string $status): bool
    {
        return in_array($status, [
            self::RESPONSE_STATUS_FAILED,
            self::RESPONSE_STATUS_CANCELED,
            self::RESPONSE_STATUS_EXPIRED
        ]);
    }

    /**
     * Process approved payment.
     *
     * @param $payment
     * @param string|float $baseAmount
     * @param array $response
     * @return void
     */
    private function processApprovedPayment($payment, $baseAmount, array $response)
    {
        $paidAmount = $response[self::TOTAL_PAID_AMOUNT] ?? $baseAmount;

        if ($paidAmount !== $baseAmount) {
            $order = $payment->getOrder();
            $this->createInvoice($order, $payment, $paidAmount);
        }

        $payment->registerCaptureNotification($paidAmount, true);
        $payment->setIsTransactionApproved(true);
        $payment->setIsTransactionDenied(false);
        $payment->setIsInProcess(true);
        $payment->setIsTransactionClosed(true);
        $payment->setShouldCloseParentTransaction(true);
        $payment->setAmountAuthorized($paidAmount);
    }

    /**
     * Process canceled payment.
     *
     * @param $payment
     * @param string $amount
     * @param string $baseAmount
     * @return void
     */
    private function processCanceledPayment($payment, $amount, $baseAmount)
    {
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

    /**
     * Create invoice for partial or full payment.
     *
     * @param $order
     * @param $payment
     * @param $paidAmount
     * @return void
     */
    private function createInvoice($order, $payment, $paidAmount)
    {
        $invoice = $order->prepareInvoice()->register();
        $invoice->setOrder($order);

        $invoice->setBaseGrandTotal($paidAmount);
        $invoice->setGrandTotal($paidAmount);
        $invoice->setSubtotal($paidAmount);
        $invoice->setBaseSubtotal($paidAmount);

        $invoice->addComment(__('Captured by collector from Mercado Pago Order API'));

        $order->addRelatedObject($invoice);
        $payment->setCreatedInvoice($invoice);
        $payment->setShouldCloseParentTransaction(true);
    }

    /**
     * Update payment information from Order API response.
     *
     * @param $payment
     * @param array $response
     * @return void
     */
    private function updatePaymentInfo($payment, array $response)
    {
        $mpPayment = $response[self::PAYMENTS][0] ?? [];
        $paymentReference = $mpPayment[self::REFERENCES] ?? [];
        $referenceSource = $paymentReference[self::REFERENCE_SOURCE] ?? null;
        
        $referenceKey = [
            self::MP_PAYMENTS => self::REFERENCE_PAYMENT_ID,
            self::MP_ORDER => self::REFERENCE_ORDER_ID,
        ][$referenceSource] ?? null;
        
        $paymentId = $referenceKey ? ($paymentReference[$referenceKey] ?? null) : null;

        $payment->setAdditionalInformation(
            self::MP_PAYMENT_ID,
            $paymentId
        );

        $payment->setAdditionalInformation(
            self::MP_ORDER_ID,
            $response[self::ID] ?? null
        );

        $payment->setAdditionalInformation(
            self::MP_PAYMENT_ID_ORDER,
            $mpPayment[self::ID] ?? null
        );

        $payment->setAdditionalInformation(
            self::MP_PAYMENT_TYPE_ID,
            $mpPayment[self::PAYMENT_METHOD][self::ID] ?? null
        );

        $payment->setAdditionalInformation(
            self::MP_TRANSACTION_AMOUNT,
            $mpPayment[self::AMOUNT] ?? 0
        );

        $payment->setAdditionalInformation(
            self::MP_PAID_AMOUNT,
            $mpPayment[self::PAID_AMOUNT] ?? 0
        );

        $payment->setAdditionalInformation(
            self::MP_STATUS,
            $response[self::STATUS] ?? null
        );

        $payment->setAdditionalInformation(
            self::MP_STATUS_DETAIL,
            $response[self::STATUS_DETAIL] ?? null
        );
    }
}


