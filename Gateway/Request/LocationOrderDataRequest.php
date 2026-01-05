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
 * Gateway Requests for Location data in Order API format.
 * Uses shipping address to build location field.
 */
class LocationOrderDataRequest implements BuilderInterface
{
    /**
     * Location block name.
     */
    public const LOCATION = 'location';

    /**
     * State Id block name.
     */
    public const STATE_ID = 'state_id';

    /**
     * Source block name.
     */
    public const SOURCE = 'source';

    /**
     * Source Shipment value.
     */
    public const SOURCE_SHIPMENT = 'shipment';

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
     * @return array
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
            $stateId = $shippingAddress->getCountryId() . '-' . $shippingAddress->getRegionCode();
            
            $result[self::LOCATION] = [
                self::STATE_ID => $stateId,
                self::SOURCE   => self::SOURCE_SHIPMENT,
            ];
        }

        return $result;
    }
}

