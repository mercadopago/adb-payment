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
class DataAssignObserverCc extends AbstractDataAssignObserver
{
    /**
     * Payer Document Type.
     */
    public const PAYER_DOCUMENT_TYPE = 'payer_document_type';

    /**
     * Payer Document Identification.
     */
    public const PAYER_DOCUMENT_IDENTIFICATION = 'payer_document_identification';

    /**
     * Card Number Token.
     */
    public const CARD_NUMBER_TOKEN = 'card_number_token';

    /**
     * Card Number.
     */
    public const CARD_NUMBER = 'card_number';

    /**
     * Card Type.
     */
    public const CARD_TYPE = 'card_type';

    /**
     * Card Expiration Month.
     */
    public const CARD_EXP_M = 'card_exp_month';

    /**
     * Card Expiration Year.
     */
    public const CARD_EXP_Y = 'card_exp_year';

    /**
     * Card Installments.
     */
    public const CARD_INSTALLMENTS = 'card_installments';

    /**
     * Card Finance Cost.
     */
    public const CARD_FINANCE_COST = 'card_finance_cost';

    /**
     * Card Holder Name.
     */
    public const CARD_HOLDER_NAME = 'card_holder_name';

    /**
     * Is Active Payment Token Enabler.
     */
    public const CARD_SAVE = 'is_active_payment_token_enabler';

    /**
     * Card Public Id.
     */
    public const CARD_PUBLIC_ID = 'card_public_id';

    /**
     * Mp User Id.
     */
    public const MP_USER_ID = 'mp_user_id';

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
            if (isset($additionalData[$addInformationKey])) {
                if ($additionalData[$addInformationKey]) {
                    $paymentInfo->setAdditionalInformation(
                        $addInformationKey,
                        $additionalData[$addInformationKey]
                    );
                }
            }
        }
    }
}
