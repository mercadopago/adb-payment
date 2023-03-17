<?php
/**
 * Copyright © MercadoPago. All rights reserved.
 *
 * @author      Bruno Elisei <brunoelisei@o2ti.com>
 * @license     See LICENSE for license details.
 */

namespace MercadoPago\PaymentMagento\Observer;

use Magento\Framework\Event\Observer;
use Magento\Payment\Observer\AbstractDataAssignObserver;
use Magento\Quote\Api\Data\PaymentInterface;

/**
 * Payment data assignment class by card.
 */
class DataAssignObserverTwoCc extends AbstractDataAssignObserver
{
    public const MAX_CARDS = 2;
    /**
     * Payer Document Type.
     */
    public const PAYER_DOCUMENT_TYPE = 'payer_{COUNT}_document_type';

    /**
     * Payer Document Identification.
     */
    public const PAYER_DOCUMENT_IDENTIFICATION = 'payer_{COUNT}_document_identification';

    /**
     * Card Number Token.
     */
    public const CARD_NUMBER_TOKEN = 'card_{COUNT}_number_token';

    /**
     * Card Number.
     */
    public const CARD_NUMBER = 'card_{COUNT}_number';

    /**
     * Card Type.
     */
    public const CARD_TYPE = 'card_{COUNT}_type';

    /**
     * Card Expiration Month.
     */
    public const CARD_EXP_M = 'card_{COUNT}_exp_month';

    /**
     * Card Expiration Year.
     */
    public const CARD_EXP_Y = 'card_{COUNT}_exp_year';

    /**
     * Card Installments.
     */
    public const CARD_INSTALLMENTS = 'card_{COUNT}_installments';

    /**
     * Card Holder Name.
     */
    public const CARD_HOLDER_NAME = 'card_{COUNT}_holder_name';

    /**
     * Is Active Payment Token Enabler.
     */
    public const CARD_SAVE = 'is_active_payment_token_enabler';

    /**
     * Card Public Id.
     */
    public const CARD_PUBLIC_ID = 'card_{COUNT}_public_id';

    /**
     * Mp User Id.
     */
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
        self::CARD_SAVE,
        self::CARD_PUBLIC_ID,
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
