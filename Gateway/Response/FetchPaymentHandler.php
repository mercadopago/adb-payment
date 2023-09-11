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
 * Gateway Response Payment Fetch.
 */
class FetchPaymentHandler implements HandlerInterface
{

    /**
     * Payment Id response value.
     */
    public const ID = 'id';

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
     * Response Payment Type Id block name.
     */
    public const PAYMENT_TYPE_ID = 'payment_type_id';

    /**
     * MP Payment Type Id block name.
     */
    public const MP_PAYMENT_TYPE_ID = 'mp_payment_type_id';

    /**
     * Response Installments block name.
     */
    public const INSTALLMENTS = 'installments';

    /**
     * Response Installments block name.
     */
    public const PAYMENT_METHOD_INFO = 'payment_method_info';

    /**
     * Response Date of Expiration block name.
     */
    public const DATE_OF_EXPIRATION = 'date_of_expiration';

    /**
     * Response Payment Method Id block name.
     */
    public const PAYMENT_METHOD_ID = 'payment_method_id';

    /**
     * MP Installments block name.
     */
    public const MP_INSTALLMENTS = 'mp_installments';

    /**
     * Response Pay Status - Block Name.
     */
    public const RESPONSE_STATUS = 'status';

    /**
     * Response Pay Status Approved - Value.
     */
    public const RESPONSE_STATUS_APPROVED = 'approved';

    /**
     * Response Pay Status Refunded - Value.
     */
    public const RESPONSE_STATUS_REFUNDED = 'refunded';
    
    /**
     * Response Pay Status Cancelled - Value.
     */
    public const RESPONSE_STATUS_CANCELLED = 'cancelled';

    /**
     * Response Pay Status Rejected - Value.
     */
    public const RESPONSE_STATUS_REJECTED = 'rejected';

    /**
     * Response Pay Status Pending - Value.
     */
    public const RESPONSE_STATUS_PENDING = 'pending';

    /**
     * Response Payment Details - Value.
     */
    public const PAYMENT_DETAILS = 'payments_details';
    
    /**
     * Response Total Amount - Value.
     */
    public const TOTAL_AMOUNT = 'total_amount';

    /**
     * Response Paid Amount - Value.
     */
    public const PAID_AMOUNT = 'paid_amount';

    /**
     * Response Last Four Digits - Value.
     */
    public const LAST_FOUR_DIGITS = 'last_four_digits';

    /**
     * Payment Id - Payment Addtional Information.
     */
    public const PAYMENT_ID = 'payment_%_id';

    /**
     * Payment Type - Payment Addtional Information.
     */
    public const PAYMENT_TYPE = 'payment_%_type';

    /**
     * Payment Card Number - Payment Addtional Information.
     */
    public const PAYMENT_CARD_NUMBER = 'payment_%_card_number';

    /**
     * Payment Installments - Payment Addtional Information.
     */
    public const PAYMENT_INSTALLMENTS = 'payment_%_installments';
    
    /**
     * Payment Total Amount - Payment Addtional Information.
     */
    public const PAYMENT_TOTAL_AMOUNT = 'payment_%_total_amount';

    /**
     * Payment Paid Amount - Payment Addtional Information.
     */
    public const PAYMENT_PAID_AMOUNT = 'payment_%_paid_amount';

    /**
     * Payment Refund Amount - Payment Addtional Information.
     */
    public const PAYMENT_REFUNDED_AMOUNT = 'payment_%_refunded_amount';

    /**
     * Payment Expiration - Payment Addtional Information.
     */
    public const PAYMENT_EXPIRATION = 'payment_%_expiration';

    /**
     * Payment Status - Payment Addtional Information.
     */
    public const PAYMENT_STATUS = 'mp_%_status';

    /**
     * Payment Status Detail- Payment Addtional Information.
     */
    public const PAYMENT_STATUS_DETAIL = 'mp_%_status_detail';

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

            $allowedApproveStatus = ['payment_review', 'pending'];

            if (in_array($order->getStatus(), $allowedApproveStatus) && $response[self::RESPONSE_STATUS] === self::RESPONSE_STATUS_APPROVED) {
                $paidAmount = !empty($response['total_paid']) ? $response['total_paid'] : $baseAmount;

                if ($paidAmount !== $baseAmount) {
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

            if ($response[self::RESPONSE_STATUS] === self::RESPONSE_STATUS_REFUNDED) {
                foreach ($response[self::PAYMENT_DETAILS] as $mpPayment) {
                    $index = $this->getIndexPayment($payment, $mpPayment[self::ID]);
                    if (isset($index)){
                        $this->updatePaymentByIndex($payment, $index, $mpPayment);
                    }
                }
            } else {
                $i = 0;
                $paymentIndexList = [];
                foreach ($response[self::PAYMENT_DETAILS] as $mpPayment) {
                    $this->updatePaymentByIndex($payment, $i, $mpPayment);
                    array_push($paymentIndexList, $i);
                    $i++;
                }
                $payment->setAdditionalInformation(
                    'payment_index_list',
                    $paymentIndexList
                );
            }

            $payment->setAdditionalInformation(
                self::MP_STATUS,
                $response[self::STATUS]
            );

            $payment->setAdditionalInformation(
                self::MP_STATUS_DETAIL,
                $response[self::PAYMENT_DETAILS][0][self::STATUS_DETAIL]
            );

        }
    }

    private function createInvoice($order, $payment, $paidAmount)
    {
        $invoice = $order->prepareInvoice()->register();
        $invoice->setOrder($order);

        $invoice->setBaseGrandTotal($paidAmount);
        $invoice->setGrandTotal($paidAmount);
        $invoice->setSubtotal($paidAmount);
        $invoice->setBaseSubtotal($paidAmount);

        $invoice->addComment(_('Captured by collector from Mercado Pago API'));

        $order->addRelatedObject($invoice);
        $payment->setCreatedInvoice($invoice);
        $payment->setShouldCloseParentTransaction(true);
    }

    /**
     * Get index of payment by payment id.
     * @param $payment
     * @param $paymentId
     * 
     * @return int|null
     */
    public function getIndexPayment(
        $payment,
        $paymentId
    ) {     
        $i = 0;
        while ($i < 2) {
            if($payment->getAdditionalInformation(str_replace('%', $i, self::PAYMENT_ID)) == $paymentId){
                return $i;
            }
            $i++;
        }
        return null;
    }

    /**
     * Update payment by index.
     * @param $payment
     * @param $index
     * @param $mpPayment
     * 
     * Return void.
     */
    public function updatePaymentByIndex(
        $payment,
        $index,
        $mpPayment
    ) {
        $cardPaymentId = str_replace('%', $index, self::PAYMENT_ID);
        $cardType = str_replace('%', $index, self::PAYMENT_TYPE);
        $cardNumber = str_replace('%', $index, self::PAYMENT_CARD_NUMBER);
        $cardInstallments = str_replace('%', $index, self::PAYMENT_INSTALLMENTS);
        $cardTotalAmount = str_replace('%', $index, self::PAYMENT_TOTAL_AMOUNT);
        $cardPaidAmount = str_replace('%', $index, self::PAYMENT_PAID_AMOUNT);
        $cardRefundedAmount = str_replace('%', $index, self::PAYMENT_REFUNDED_AMOUNT);
        $mpStatus = str_replace('%', $index, self::PAYMENT_STATUS);
        $mpStatusDetail = str_replace('%', $index, self::PAYMENT_STATUS_DETAIL);
        $paymentExpiration = str_replace('%', $index, self::PAYMENT_EXPIRATION);

        $payment->setAdditionalInformation(
            $cardPaymentId,
            $mpPayment[self::ID]
        );

        $payment->setAdditionalInformation(
            $cardType,
            $mpPayment[self::PAYMENT_METHOD_ID]
        );

        $payment->setAdditionalInformation(
            $cardTotalAmount,
            $mpPayment[self::TOTAL_AMOUNT]
        );
        
        $payment->setAdditionalInformation(
            $cardPaidAmount,
            $mpPayment[self::PAID_AMOUNT]
        );

        $value = $payment->getAdditionalInformation($cardRefundedAmount) ?? 0;

        $payment->setAdditionalInformation(
            $cardRefundedAmount,
            $value
        );

        $payment->setAdditionalInformation(
            $cardNumber,
            $mpPayment[self::PAYMENT_METHOD_INFO][self::LAST_FOUR_DIGITS]
        );

        $payment->setAdditionalInformation(
            $cardInstallments,
            $mpPayment[self::PAYMENT_METHOD_INFO][self::INSTALLMENTS]
        );

        $payment->setAdditionalInformation(
            $mpStatus,
            $mpPayment[self::STATUS]
        );

        $payment->setAdditionalInformation(
            $mpStatusDetail,
            $mpPayment[self::STATUS_DETAIL]
        );

        if (isset($mpPayment[self::PAYMENT_METHOD_INFO][self::DATE_OF_EXPIRATION])){
            $payment->setAdditionalInformation(
                $paymentExpiration,
                $mpPayment[self::PAYMENT_METHOD_INFO][self::DATE_OF_EXPIRATION]
            );
        }
    }
}
