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
     * Payment Id response value.
     */
    public const PAYMENT_ID = 'transaction_id';

    /**
     * Payment Id block name.
     */
    public const MP_PAYMENT_ID = 'mp_payment_id';

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
     * Response multiple_payment_transaction_id - Block Name.
     */
    public const MULTIPAYMENT_TRANSACTION_ID = 'multiple_payment_transaction_id';

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
            
            if(!empty($response["multiple_payment_transaction_id"])){

                $i = 0;
                foreach ($response["payments_details"] as $mpPayment) {

                    $mpStatus = 'mp_'.$i.'_status';
                    $payment->setAdditionalInformation(
                        $mpStatus,$mpPayment["status"]
                    );

                    $mpStatusDetail = 'mp_'.$i.'_status_detail';
                    $payment->setAdditionalInformation(
                        $mpStatusDetail,
                        $mpPayment["status_detail"]
                    );

                    $i++;
                }

            } else {

                $payment->setAdditionalInformation(
                    self::MP_PAYMENT_ID,
                    $response[self::PAYMENT_ID]
                );
    
                $payment->setAdditionalInformation(
                    self::MP_PAYMENT_TYPE_ID,
                    $response["payments_details"][0][self::PAYMENT_TYPE_ID]
                );
    
                $payment->setAdditionalInformation(
                    self::MP_INSTALLMENTS,
                    $response["payments_details"][0]["payment_method_info"][self::INSTALLMENTS]
                );
    
                $payment->setAdditionalInformation(
                    self::MP_STATUS,
                    $response[self::STATUS]
                );
    
                $payment->setAdditionalInformation(
                    self::MP_STATUS_DETAIL,
                    $response["payments_details"][0][self::STATUS_DETAIL]
                );
            }  
            
        }
    }
}
