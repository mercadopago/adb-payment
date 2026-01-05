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
 * Order Void Gateway Response.
 */
class VoidOrderHandler implements HandlerInterface
{
    /**
     * Result Code - Block name.
     */
    public const RESULT_CODE = 'RESULT_CODE';

    /**
     * Response Pay Status - Block Name.
     */
    public const RESPONSE_STATUS = 'status';

    /**
     * Status response value.
     */
    public const STATUS_DETAIL = 'status_detail';

    /**
     * Payment Id block name.
     */
    public const ID = 'id';

    /**
     * Payment Id block name.
     */
    public const MP_PAYMENT_ID = 'mp_payment_id';

    /**
     * Order Id block name.
     */
    public const MP_ORDER_ID = 'mp_order_id';

    /**
     * Payment Id Order block name.
     */
    public const MP_PAYMENT_ID_ORDER = 'mp_payment_id_order';


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
     * Payments block name.
     */
    public const PAYMENTS = 'payments';

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
            $amount = $order->getBaseGrandTotal();

            $this->setAddtionalInformation($payment, $response);

            $payment->setPreparedMessage(__('Order Canceled.'));
            $payment->setIsTransactionPending(false);
            $payment->setIsTransactionDenied(true);
            $payment->setAmountCanceled($amount);
            $payment->setBaseAmountCanceled($amount);
            $payment->setShouldCloseParentTransaction(true);
        }
    }

     /**
     * Set Additional Information.
     *
     * @param InfoInterface $payment
     * @param array         $response
     *
     * @return void
     */
    public function setAddtionalInformation($payment, $response)
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
            self::MP_STATUS,
            $response[self::RESPONSE_STATUS] ?? null
        );

        $payment->setAdditionalInformation(
            self::MP_STATUS_DETAIL,
            $response[self::STATUS_DETAIL] ?? null
        );
    }
}
