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
 * Gateway response to Transaction Details by Card.
 */
class TxnIdCcHandler implements HandlerInterface
{
    /**
     * Card Terminal NSU - Payment Addtional Information.
     */
    public const TERMINAL_NSU = 'terminal_nsu';

    /**
     * Card Authorization Code - Payment Addtional Information.
     */
    public const AUTHORIZATION_CODE = 'authorization_code';

    /**
     * Card Acquirer Transaction Id - Payment Addtional Information.
     */
    public const ACQUIRER_TRANSACTION_ID = 'acquirer_transaction_id';

    /**
     * Card Transaction Id - Payment Addtional Information.
     */
    public const TRANSACTION_ID = 'transaction_id';

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
     * Response Pay Credit - Block name.
     */
    public const CREDIT = 'credit';

    /**
     * Response Pay Payment Id - Block name.
     */
    public const RESPONSE_PAYMENT_ID = 'payment_id';

    /**
     * Response Pay Terminal NSU - Block name.
     */
    public const RESPONSE_TERMINAL_NSU = 'terminal_nsu';

    /**
     * Response Pay Authorization Code - Block name.
     */
    public const RESPONSE_AUTHORIZATION_CODE = 'authorization_code';

    /**
     * Response Pay Acquirer Transaction Id - Block name.
     */
    public const RESPONSE_ACQUIRER_TRANSACTION_ID = 'acquirer_transaction_id';

    /**
     * Response Pay Transaction Id - Block name.
     */
    public const RESPONSE_TRANSACTION_ID = 'transaction_id';

    /**
     * Response Pay Delayed - Block name.
     */
    public const RESPONSE_DELAYED = 'delayed';

    /**
     * Response Pay Status - Block name.
     */
    public const RESPONSE_STATUS = 'status';

    /**
     * Response Pay Approved - Block name.
     */
    public const RESPONSE_APPROVED = 'APPROVED';

    /**
     * Response Pay Authorized - Block name.
     */
    public const RESPONSE_AUTHORIZED = 'AUTHORIZED';

    /**
     * Response Pay Pending - Block name.
     */
    public const RESPONSE_PENDING = 'PENDING';

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

        $response['RESULT_CODE'];

        $paymentDO = $handlingSubject['payment'];

        $payment = $paymentDO->getPayment();

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
    }
}
