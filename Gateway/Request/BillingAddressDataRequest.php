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
 * Gateway Requests for Payer Address.
 */
class BillingAddressDataRequest implements BuilderInterface
{
    /**
     * Address block name.
     */
    public const ADDRESS = 'address';

    /**
     * The street name address block name.
     */
    public const STREET_NAME = 'street_name';

    /**
     * The street number block name.
     */
    public const STREET_NUMBER = 'street_number';

    /**
     * The street complement block name.
     */
    public const STREET_COMPLEMENT = 'street_complement';

    /**
     * The Street Neighborhood address block name.
     */
    public const STREET_NEIGHBORHOOD = 'neighborhood';

    /**
     * The city address block name.
     */
    public const CITY = 'city';

    /**
     * The Federal Unit block name.
     */
    public const FEDERAL_UNIT = 'federal_unit';

    /**
     * The Contry Code block name.
     */
    public const COUNTRY_CODE = 'country';

    /**
     * The Zip Code block name.
     */
    public const ZIP_CODE = 'zip_code';

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
            $result[PayerDataRequest::PAYER][self::ADDRESS] = [
                self::STREET_NAME           => $this->config->getValueForAddress(
                    $billingAddress,
                    self::STREET_NAME
                ),
                self::STREET_NUMBER         => (int) $this->config->getValueForAddress(
                    $billingAddress,
                    self::STREET_NUMBER
                ),
                self::STREET_NEIGHBORHOOD   => $this->config->getValueForAddress(
                    $billingAddress,
                    self::STREET_NEIGHBORHOOD
                ),
                self::CITY                  => $billingAddress->getCity(),
                self::FEDERAL_UNIT          => $billingAddress->getRegionCode(),
                self::ZIP_CODE              => preg_replace('/[^0-9]/', '', $billingAddress->getPostcode()),
            ];
        }

        return $result;
    }
}
