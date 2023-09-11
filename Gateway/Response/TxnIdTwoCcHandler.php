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
class TxnIdTwoCcHandler implements HandlerInterface
{
    /**
     * Id response value.
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
     * Payment Id - Payment Addtional Information.
     */
    public const CARD_PAYMENT_ID = 'payment_%_id';

    /**
     * Card Type - Payment Addtional Information.
     */
    public const CARD_TYPE = 'card_%_type';

    /**
     * Card Number - Payment Addtional Information.
     */
    public const CARD_NUMBER = 'card_%_number';

    /**
     * Card Holder Name - Payment Addtional Information.
     */
    public const CARD_HOLDER_NAME = 'card_%_holder_name';

    /**
     * Card Exp Month - Payment Addtional Information.
     */
    public const CARD_EXP_MONTH = 'card_%_exp_month';

    /**
     * Card Exp Year - Payment Addtional Information.
     */
    public const CARD_EXP_YEAR = 'card_%_exp_year';

    /**
     * Card Number Token - Payment Addtional Information.
     */
    public const NUMBER_TOKEN = 'card_%_number_token';

    /**
     * Transaction Info - Payment Addtional Information.
     */
    public const TRANSACTION_INFO = 'transaction_info';

    /**
     * Response Pay Transaction Id - Block name.
     */
    public const RESPONSE_TRANSACTION_ID = 'transaction_id';

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

        $transactionInfo = [];

        for ($i = 0; $i < 2; $i++):

            $cardType = str_replace('%', $i, self::CARD_TYPE);
            $cardNumber = str_replace('%', $i, self::CARD_NUMBER);
            $cardHolderName = str_replace('%', $i, self::CARD_HOLDER_NAME);
            $cardExpMonth = str_replace('%', $i, self::CARD_EXP_MONTH);
            $cardExpYear = str_replace('%', $i, self::CARD_EXP_YEAR);
            $cardNumberToken = str_replace('%', $i, self::NUMBER_TOKEN);

            $cardInfo = [
                $cardType        => $payment->getAdditionalInformation($cardType),
                $cardNumber      => $payment->getAdditionalInformation($cardNumber),
                $cardHolderName => $payment->getAdditionalInformation($cardHolderName),
                $cardExpMonth   => $payment->getAdditionalInformation($cardExpMonth),
                $cardExpYear    => $payment->getAdditionalInformation($cardExpYear),
                $cardNumberToken    => $payment->getAdditionalInformation($cardNumberToken),
            ];

            array_push($transactionInfo, $cardInfo);
            
            $payment->setAdditionalInformation(
                str_replace('%', $i,self::CARD_PAYMENT_ID),
                $response[self::TRANSACTION_INFO][$i][self::PAYMENT_ID]
            );

            $payment->setAdditionalInformation(
                str_replace('%', $i,'mp_%_status'),
                $response[self::TRANSACTION_INFO][$i][self::STATUS]
            );

            $payment->setAdditionalInformation(
                str_replace('%', $i,'mp_%_status_detail'),
                $response[self::TRANSACTION_INFO][$i][self::STATUS_DETAIL]
            );

        endfor;

        $payment->setTransactionInfo($transactionInfo);
    }
}
