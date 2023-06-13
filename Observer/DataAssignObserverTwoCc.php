<?php
/**
 * Copyright © MercadoPago. All rights reserved.
 *
 * @author      Mercado Pago
 * @license     See LICENSE for license details.
 */

namespace MercadoPago\AdbPayment\Observer;

use Magento\Framework\Event\Observer;
use Magento\Payment\Observer\AbstractDataAssignObserver;
use Magento\Quote\Api\Data\PaymentInterface;

/**
 * Payment data assignment class by card.
 */
class DataAssignObserverTwoCc extends AbstractDataAssignObserver
{
    public const MAX_CARDS = 2;

    public const PAYER_DOCUMENT_TYPE = 'payer_{COUNT}_document_type';

    public const PAYER_DOCUMENT_IDENTIFICATION = 'payer_{COUNT}_document_identification';

    public const CARD_NUMBER_TOKEN = 'card_{COUNT}_number_token';

    public const CARD_NUMBER = 'card_{COUNT}_number';

    public const CARD_TYPE = 'card_{COUNT}_type';

    public const CARD_EXP_M = 'card_{COUNT}_exp_month';

    public const CARD_EXP_Y = 'card_{COUNT}_exp_year';

    public const CARD_INSTALLMENTS = 'card_{COUNT}_installments';

    public const CARD_FINANCE_COST = 'card_{COUNT}_finance_cost';

    public const CARD_HOLDER_NAME = 'card_{COUNT}_holder_name';

    public const CARD_SAVE = 'is_active_payment_token_enabler';

    public const CARD_PUBLIC_ID = 'card_{COUNT}_public_id';

    public const CARD_AMOUNT = 'card_{COUNT}_amount';

    public const MP_USER_ID = 'mp_{COUNT}_user_id';

    /**
     * @var array
     */
    protected $addInformationList = [
        self::PAYER_DOCUMENT_TYPE,
        self::PAYER_DOCUMENT_IDENTIFICATION,
        self::CARD_NUMBER_TOKEN,
        self::CARD_HOLDER_NAME,
        self::CARD_NUMBER,
        self::CARD_TYPE,
        self::CARD_EXP_M,
        self::CARD_EXP_Y,
        self::CARD_INSTALLMENTS,
        self::CARD_FINANCE_COST,
        self::CARD_SAVE,
        self::CARD_PUBLIC_ID,
        self::CARD_AMOUNT,
        self::MP_USER_ID,
    ];

    /**
     * Execute.
     *
     * @param Observer $observer
     *
     * @return void
     */
    public function execute(Observer $observer)
    {
        $data = $this->readDataArgument($observer);

        $additionalData = $data->getData(PaymentInterface::KEY_ADDITIONAL_DATA);

        if (!is_array($additionalData)) {
            return;
        }

        $paymentInfo = $this->readPaymentModelArgument($observer);

        foreach ($this->addInformationList as $addInformationKey) {
            for ($i = 0; $i < self::MAX_CARDS; $i ++) {
                $key = str_replace('{COUNT}', $i, $addInformationKey);
                if (isset($additionalData[$key])) {
                    $paymentInfo->setAdditionalInformation(
                        $key,
                        $additionalData[$key]
                    );
                }
            }
        }
    }
}
