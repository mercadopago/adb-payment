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
use Magento\Payment\Model\InfoInterface;
use MercadoPago\AdbPayment\Gateway\Config\Config;
use MercadoPago\AdbPayment\Gateway\Data\Order\OrderAdapterFactory;
use MercadoPago\AdbPayment\Gateway\SubjectReader;

/**
 * Gateway Requests the Payer Identification Document.
 */
class DocumentIdentificationDataRequest implements BuilderInterface
{
    /**
     * Document Identification name.
     */
    public const IDENTIFICATION = 'identification';

    /**
     * Identification Type block name.
     */
    public const IDENTIFICATION_TYPE = 'type';

    /**
     * Identification Number block name.
     */
    public const IDENTIFICATION_NUMBER = 'number';

    /**
     * @var SubjectReader
     */
    protected $subjectReader;

    /**
     * @var OrderAdapterFactory
     */
    protected $orderAdapterFactory;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @param SubjectReader       $subjectReader
     * @param OrderAdapterFactory $orderAdapterFactory
     * @param Config              $config
     */
    public function __construct(
        SubjectReader $subjectReader,
        OrderAdapterFactory $orderAdapterFactory,
        Config $config
    ) {
        $this->subjectReader = $subjectReader;
        $this->orderAdapterFactory = $orderAdapterFactory;
        $this->config = $config;
    }

    /**
     * Get Value For Document Identification.
     *
     * @param OrderAdapterFactory $orderAdapter
     *
     * @return string
     */
    public function getValueForDocumentIdentification($orderAdapter)
    {
        $obtainTaxDocFrom = $this->config->getAddtionalValue('get_document_identification_from');

        $docIdentification = $orderAdapter->getCustomerTaxvat();

        if ($obtainTaxDocFrom === 'address') {
            $docIdentification = $orderAdapter->getBillingAddress()->getVatId();
        }

        return $docIdentification;
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
        $typeDocument = $payment->getAdditionalInformation('payer_document_type');
        $result = [];

        /** @var OrderAdapterFactory $orderAdapter */
        $orderAdapter = $this->orderAdapterFactory->create(
            ['order' => $payment->getOrder()]
        );

        $docIdentification = $this->getFiscalNumber($payment, $orderAdapter);

        if ($docIdentification) {
            $docIdentification = preg_replace('/[^0-9]/', '', $docIdentification);
        }

        if ($typeDocument) {
            $result[PayerDataRequest::PAYER][self::IDENTIFICATION] = [
                self::IDENTIFICATION_TYPE   => $typeDocument,
                self::IDENTIFICATION_NUMBER => $docIdentification,
            ];
        }

        return $result;
    }

    /**
     * Get Fiscal Number.
     *
     * @param InfoInterface       $payment
     * @param OrderAdapterFactory $orderAdapter
     *
     * @return string
     */
    public function getFiscalNumber($payment, $orderAdapter): ?string
    {
        $docIdentification = null;

        if ($payment->getAdditionalInformation('payer_document_identification')) {
            $docIdentification = $payment->getAdditionalInformation('payer_document_identification');
        }

        if (!$docIdentification) {
            $docIdentification = $this->getValueForDocumentIdentification($orderAdapter);
        }

        return $docIdentification;
    }
}
