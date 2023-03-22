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
        
        $transactionInfo = [];

        for ($i = 0; $i < 2; $i++):
            $cardInfo = [
                str_replace('%', $i, self::CARD_TYPE)        => $payment->getAdditionalInformation(str_replace('%', $i, self::CARD_TYPE)),
                str_replace('%', $i, self::CARD_NUMBER)      => $payment->getAdditionalInformation(str_replace('%', $i, self::CARD_NUMBER)),
                str_replace('%', $i, self::CARD_HOLDER_NAME) => $payment->getAdditionalInformation(str_replace('%', $i, self::CARD_HOLDER_NAME)),
                str_replace('%', $i, self::CARD_EXP_MONTH)   => $payment->getAdditionalInformation(str_replace('%', $i, self::CARD_EXP_MONTH)),
                str_replace('%', $i, self::CARD_EXP_YEAR)    => $payment->getAdditionalInformation(str_replace('%', $i, self::CARD_EXP_YEAR)),
                str_replace('%', $i, self::NUMBER_TOKEN)     => $payment->getAdditionalInformation(str_replace('%', $i, self::NUMBER_TOKEN)),
            ];

            array_push($transactionInfo, $cardInfo);

        endfor;
        
        $payment->setTransactionInfo($transactionInfo);
    }
}
