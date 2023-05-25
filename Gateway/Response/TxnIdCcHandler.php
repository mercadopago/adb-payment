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
 * Gateway response to Transaction Details by Card.
 */
class TxnIdCcHandler implements HandlerInterface
{
    /**
     * Payment Id response value.
     */
    public const PAYMENT_ID = 'id';

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
     * Card Type - Payment Addtional Information.
     */
    public const CARD_TYPE = 'card_type';

    /**
     * Card Number - Payment Addtional Information.
     */
    public const CARD_NUMBER = 'card_number';

    /**
     * Card Holder Name - Payment Addtional Information.
     */
    public const CARD_HOLDER_NAME = 'card_holder_name';

    /**
     * Card Exp Month - Payment Addtional Information.
     */
    public const CARD_EXP_MONTH = 'card_exp_month';

    /**
     * Card Exp Year - Payment Addtional Information.
     */
    public const CARD_EXP_YEAR = 'card_exp_year';

    /**
     * Card Number Token - Payment Addtional Information.
     */
    public const NUMBER_TOKEN = 'card_number_token';

    /**
     * Payer Document Type - Payment Addtional Information.
     */
    public const PAYER_DOCUMENT_TYPE = 'payer_document_type';

    /**
     * Payer Document Identification- Payment Addtional Information.
     */
    public const PAYER_DOCUMENT_IDENTIFICATION = 'payer_document_identification';

    /**
     * Response Pay Credit - Block name.
     */
    public const CREDIT = 'credit';

    /**
     * Response Pay Transaction Id - Block name.
     */
    public const RESPONSE_TRANSACTION_ID = 'transaction_id';

    /**
     * Response Pay Delayed - Block name.
     */
    public const RESPONSE_DELAYED = 'delayed';

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

        $payment->setAdditionalInformation(
            self::MP_PAYMENT_ID,
            $response[self::PAYMENT_ID]
        );

        $payment->setAdditionalInformation(
            self::MP_STATUS,
            $response[self::STATUS]
        );

        $payment->setAdditionalInformation(
            self::MP_STATUS_DETAIL,
            $response[self::STATUS_DETAIL]
        );

        $cardType = $payment->getAdditionalInformation(self::CARD_TYPE);
        $payment->setCcType($cardType);

        $cardLast4 = $payment->getAdditionalInformation(self::CARD_NUMBER);
        $cardLast4 = substr($cardLast4, -4);
        $payment->setCcLast4($cardLast4);

        $cardOwner = $payment->getAdditionalInformation(self::CARD_HOLDER_NAME);
        $payment->setCcOwner($cardOwner);

        $cardExpMonth = $payment->getAdditionalInformation(self::CARD_EXP_MONTH);
        $payment->setCcExpMonth($cardExpMonth);

        $cardExpYear = $payment->getAdditionalInformation(self::CARD_EXP_YEAR);
        $payment->setCcExpYear($cardExpYear);

        $cardNumberEnc = $payment->getAdditionalInformation(self::NUMBER_TOKEN);
        $payment->setCcNumberEnc($cardNumberEnc);

        $documentType = $payment->getAdditionalInformation(self::PAYER_DOCUMENT_TYPE);
        $payment->setPayerDocumentType($documentType);

        $documentIdentification = $payment->getAdditionalInformation(self::PAYER_DOCUMENT_IDENTIFICATION);
        $payment->setPayerDocumentIdentification($documentIdentification);
    }
}
