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
use MercadoPago\AdbPayment\Gateway\Request\MpDeviceSessionId;

/**
 * Payment data assignment class by Yape.
 */
class DataAssignObserverYape extends AbstractDataAssignObserver
{

    /**
     * Yape Token ID.
     */
    public const YAPE_TOKEN_ID = 'yape_token_id';


    /**
     * @var array
     */
    protected $addInformationList = [
        self::YAPE_TOKEN_ID,
        MpDeviceSessionId::MP_DEVICE_SESSION_ID,
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
