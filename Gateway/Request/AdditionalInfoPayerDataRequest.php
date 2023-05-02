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
use MercadoPago\AdbPayment\Gateway\Config\Config;
use MercadoPago\AdbPayment\Gateway\Data\Order\OrderAdapterFactory;
use MercadoPago\AdbPayment\Gateway\SubjectReader;

/**
 * Gateway Requests for Additional Payer Details Data.
 */
class AdditionalInfoPayerDataRequest implements BuilderInterface
{
    /**
     * Payer block name.
     */
    public const PAYER = 'payer';

    /**
     * First Name block name.
     */
    public const FIRST_NAME = 'first_name';

    /**
     * Last Name block name.
     */
    public const LAST_NAME = 'last_name';

    /**
     * Address block name.
     */
    public const ADDRESS = 'address';

    /**
     * Street Name address block name.
     */
    public const STREET_NAME = 'street_name';

    /**
     * Street Number address block name.
     */
    public const STREET_NUMBER = 'street_number';

    /**
     * Street Complement address block name.
     */
    public const STREET_COMPLEMENT = 'complement';

    /**
     * Street Neighborhood address block name.
     */
    public const STREET_NEIGHBORHOOD = 'neighborhood';

    /**
     * City address block name.
     */
    public const CITY = 'city';

    /**
     * Federal Unit address block name.
     */
    public const FEDERAL_UNIT = 'federal_unit';

    /**
     * Zip Code address block name.
     */
    public const ZIP_CODE = 'zip_code';

    /**
     * Phone block name.
     */
    public const PHONE = 'phone';

    /**
     * Phone Area Code block name.
     */
    public const PHONE_AREA_CODE = 'area_code';

    /**
     * Phone Number block name.
     */
    public const PHONE_NUMBER = 'number';

    /**
     * @var SubjectReader
     */
    protected $subjectReader;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var OrderAdapterFactory
     */
    protected $orderAdapterFactory;

    /**
     * @param SubjectReader       $subjectReader
     * @param Config              $config
     * @param OrderAdapterFactory $orderAdapterFactory
     */
    public function __construct(
        SubjectReader $subjectReader,
        Config $config,
        OrderAdapterFactory $orderAdapterFactory
    ) {
        $this->subjectReader = $subjectReader;
        $this->config = $config;
        $this->orderAdapterFactory = $orderAdapterFactory;
    }

    /**
     * Build.
     *
     * @param array $buildSubject
     */
    public function build(array $buildSubject): array
    {
        if (!isset($buildSubject['payment'])
            || !$buildSubject['payment'] instanceof PaymentDataObjectInterface
        ) {
            throw new InvalidArgumentException('Payment data object should be provided');
        }

        $paymentDO = $this->subjectReader->readPayment($buildSubject);

        $payment = $paymentDO->getPayment();

        $result = [];

        /** @var OrderAdapterFactory $orderAdapter */
        $orderAdapter = $this->orderAdapterFactory->create(
            ['order' => $payment->getOrder()]
        );

        $billingAddress = $orderAdapter->getBillingAddress();

        if ($billingAddress) {
            $phone = preg_replace('/[^0-9]/', '', $billingAddress->getTelephone());
            $phoneAreaCode = substr($phone, 0, 2);
            $phoneNumber = substr($phone, 2);

            // Rewrite Payer from payment form
            $payerFirstName =
                $payment->getAdditionalInformation('payer_first_name') ?: $billingAddress->getFirstname();
            $payerLastName =
                $payment->getAdditionalInformation('payer_last_name') ?: $billingAddress->getLastname();

            $result[AdditionalInfoDataRequest::ADDITIONAL_INFO][self::PAYER] = [
                self::FIRST_NAME    => $payerFirstName,
                self::LAST_NAME     => $payerLastName,
            ];

            $result[AdditionalInfoDataRequest::ADDITIONAL_INFO][self::PAYER][self::ADDRESS] = [
                self::ZIP_CODE              => $billingAddress->getPostcode(),
                self::STREET_NAME           => $this->config->getValueForAddress(
                    $billingAddress,
                    self::STREET_NAME
                ),
                self::STREET_NUMBER         => $this->config->getValueForAddress(
                    $billingAddress,
                    self::STREET_NUMBER
                ),
            ];

            $result[AdditionalInfoDataRequest::ADDITIONAL_INFO][self::PAYER][self::PHONE] = [
                self::PHONE_AREA_CODE => $phoneAreaCode,
                self::PHONE_NUMBER    => $phoneNumber,
            ];
        }

        return $result;
    }
}
