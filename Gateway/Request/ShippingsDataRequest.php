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
 * Gateway requests for Shipping details.
 */
class ShippingsDataRequest implements BuilderInterface
{
    /**
     * Shipments block name.
     */
    public const SHIPMENTS = 'shipments';

    /**
     * Receiver Address block name.
     */
    public const RECEIVER_ADDRESS = 'receiver_address';

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
    public const CITY = 'city_name';

    /**
     * Federal Unit address block name.
     */
    public const FEDERAL_UNIT = 'state_name';

    /**
     * Zip Code address block name.
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

        $shippingAddress = $orderAdapter->getShippingAddress();
        if ($shippingAddress) {
            $result[self::SHIPMENTS][self::RECEIVER_ADDRESS] = [
                self::ZIP_CODE              => $shippingAddress->getPostcode(),
                self::STREET_NAME           => $this->config->getValueForAddress(
                    $shippingAddress,
                    self::STREET_NAME
                ).', '.
                    $this->config->getValueForAddress(
                        $shippingAddress,
                        self::STREET_COMPLEMENT
                    ).', '.
                    $this->config->getValueForAddress(
                        $shippingAddress,
                        self::STREET_NEIGHBORHOOD
                    ),
                self::STREET_NUMBER         => $this->config->getValueForAddress(
                    $shippingAddress,
                    self::STREET_NUMBER
                ),
                self::CITY                  => $shippingAddress->getCity(),
                self::FEDERAL_UNIT          => $shippingAddress->getRegionCode(),
            ];
        }

        return $result;
    }
}
