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
 * Payment data assignment class by Checkout Custom.
 */
class DataAssignCheckoutCustomObserver extends AbstractDataAssignObserver
{
    /**
     * Payment Id Method block name.
     */
    public const PAYMENT_METHOD_ID = 'payment_method_id';

    /**
     * Payer Document Type.
     */
    public const PAYER_DOCUMENT_TYPE = 'payer_document_type';

    /**
     * Payer Document Identification block name.
     */
    public const PAYER_DOCUMENT_IDENTIFICATION = 'payer_document_identification';

    /**
     * Payer First Name block name.
     */
    public const PAYER_FIRST_NAME = 'payer_first_name';

    /**
     * Payer Last Name block name.
     */
    public const PAYER_LAST_NAME = 'payer_last_name';

    /**
     * Payer Entity Type block name.
     */
    public const PAYER_ENTITY_TYPE = 'payer_entity_type';

    /**
     * Financial Institution block name.
     */
    public const FINANCIAL_INSTITUTION = 'financial_institution';

    /**
     * Payment method type ID.
     */
    public const PAYMENT_TYPE_ID =  'payment_type_id';

     /**
     * Payment method option ID.
     */
    public const PAYMENT_OPTION_ID =  'payment_option_id';

    /**
     * @var array
     */
    protected $addInformationList = [
        self::PAYMENT_METHOD_ID,
        self::PAYER_DOCUMENT_TYPE,
        self::PAYER_DOCUMENT_IDENTIFICATION,
        self::PAYER_FIRST_NAME,
        self::PAYER_LAST_NAME,
        self::PAYER_ENTITY_TYPE,
        self::FINANCIAL_INSTITUTION,
        self::PAYMENT_TYPE_ID,
        self::PAYMENT_OPTION_ID,
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
