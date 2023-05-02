<?php
/**
 * Copyright © MercadoPago. All rights reserved.
 *
 * @author      Mercado Pago
 * @license     See LICENSE for license details.
 */

namespace MercadoPago\AdbPayment\Gateway\Request;

use InvalidArgumentException;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Gateway\Request\BuilderInterface;
use MercadoPago\AdbPayment\Gateway\SubjectReader;

/**
 * Gateway requests for Bank Trans Details  data.
 */
class BankTransDetailsDataRequest implements BuilderInterface
{
    /**
     * Transaction Details block name.
     */
    public const TRANSACTION_DETAILS = 'transaction_details';

    /**
     * Financial Institution block name.
     */
    public const FINANCIAL_INSTITUTION = 'financial_institution';

    /**
     * @var SubjectReader
     */
    protected $subjectReader;

    /**
     * @param SubjectReader $subjectReader
     */
    public function __construct(
        SubjectReader $subjectReader
    ) {
        $this->subjectReader = $subjectReader;
    }

    /**
     * Build.
     *
     * @param array $buildSubject
     */
    public function build(array $buildSubject)
    {
        if (!isset($buildSubject['payment'])
        || !$buildSubject['payment'] instanceof PaymentDataObjectInterface
        ) {
            throw new InvalidArgumentException('Payment data object should be provided');
        }

        $paymentDO = $this->subjectReader->readPayment($buildSubject);
        $payment = $paymentDO->getPayment();
        $result = [];

        $financialInstitution = $payment->getAdditionalInformation('financial_institution');

        $result[self::TRANSACTION_DETAILS] = [
            self::FINANCIAL_INSTITUTION => $financialInstitution,
        ];

        return $result;
    }
}
