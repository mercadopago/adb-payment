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
 * Payment data assignment class by vault.
 */
class DataAssignObserverCcVault extends AbstractDataAssignObserver
{
    /**
     * Cc Installments.
     */
    public const CC_INSTALLMENTS = 'cc_installments';

    /**
     * Card Finance Cost.
     */
    public const CARD_FINANCE_COST = 'card_finance_cost';

    /**
     * Cc Number Token.
     */
    public const NUMBER_TOKEN = 'cc_number_token';

    /**
     * Cc Number.
     */
    public const CC_NUMBER = 'cc_number';

    /**
     * Cc Type.
     */
    public const CC_TYPE = 'cc_type';

    /**
     * Cc Expiration Month.
     */
    public const CC_EXP_M = 'cc_exp_month';

    /**
     * Cc Expiration Year.
     */
    public const CC_EXP_Y = 'cc_exp_year';

    /**
     * Cc Card Holder Name.
     */
    public const CARDHOLDER_NAME = 'cc_cardholder_name';

    /**
     * Mp User Id.
     */
    public const MP_USER_ID = 'mp_user_id';

    /**
     * @var array
     */
    protected $addInformationList = [
        self::NUMBER_TOKEN,
        self::CARDHOLDER_NAME,
        self::CC_NUMBER,
        self::CC_TYPE,
        self::CC_EXP_M,
        self::CC_EXP_Y,
        self::CC_INSTALLMENTS,
        self::CARD_FINANCE_COST,
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
