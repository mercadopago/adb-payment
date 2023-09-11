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
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Sales\Api\ShipmentTrackRepositoryInterface;

/**
 * Gateway Requests for Additional Data Shipping Data.
 */
class AdditionalInfoShippingsDataRequest implements BuilderInterface
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
     * Number address block name.
     */
    public const NUMBER = 'number';

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
     * Apartment address block name.
     */
    public const APARTMENT = 'apartment';

    /**
     * floor address block name.
     */
    public const FLOOR = 'floor';

    /**
     * Country address block name.
     */
    public const COUNTRY = 'country';

     /**
     * State address block name.
     */
    public const STATE = 'state';

    /**
     * Federal Unit address block name.
     */
    public const FEDERAL_UNIT = 'federal_unit';

    /**
     * Zip Code address block name.
     */
    public const ZIP_CODE = 'zip_code';

    /**
     * Tracking block name.
     */
    public const TRACKING = 'tracking';

    /**
     * Code tracking block name.
     */
    public const CODE_TRACKING = 'code';

    /**
     * Status tracking block name.
     */
    public const STATUS_TRACKING = 'status';

    /**
     * Delivery promise block name.
     */
    public const DELIVERY_PROMISE = 'delivery_promise';

    /**
     * Drop shipping block name.
     */
    public const DROP_SHIPPING = 'drop_shipping';

    /**
     * Local pickup block name.
     */
    public const LOCAL_PICKUP = 'local_pickup';

    /**
     * Express shipment block name.
     */
    public const EXPRESS_SHIPMENT = 'express_shipment';

    /**
     * Safety block name.
     */
    public const SAFETY = 'safety';

    /**
     * Withdrawn block name.
     */
    public const WITHDRAWN = 'withdrawn';

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
     * @var ShipmentTrackRepositoryInterface
     */
    protected $shipmentTrackRepository;

    /**
     * @var CheckoutSession
     */
    protected $checkoutSession;

    /**
     * @param SubjectReader       $subjectReader
     * @param Config              $config
     * @param OrderAdapterFactory $orderAdapterFactory
     * @param ShipmentTrackRepositoryInterface $shipmentTrackRepository
     * @param CheckoutSession $checkoutSession
     */
    public function __construct(
        SubjectReader $subjectReader,
        Config $config,
        OrderAdapterFactory $orderAdapterFactory,
        ShipmentTrackRepositoryInterface $shipmentTrackRepository,
        CheckoutSession $checkoutSession
    ) {
        $this->subjectReader = $subjectReader;
        $this->config = $config;
        $this->orderAdapterFactory = $orderAdapterFactory;
        $this->checkoutSession = $checkoutSession;
        $this->shipmentTrackRepository = $shipmentTrackRepository;
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

            $result[AdditionalInfoDataRequest::ADDITIONAL_INFO][self::SHIPMENTS] = [
                self::DELIVERY_PROMISE => null, 
                self::DROP_SHIPPING => null,
                self::LOCAL_PICKUP => null,
                self::EXPRESS_SHIPMENT => null,
                self::SAFETY => null,
                self::WITHDRAWN => null,
            ];
            
            $result[AdditionalInfoDataRequest::ADDITIONAL_INFO][self::SHIPMENTS][self::RECEIVER_ADDRESS] = [
                self::ZIP_CODE              => preg_replace('/[^0-9]/', '', $shippingAddress->getPostcode()),
                self::STREET_NAME           => $this->config->getValueForAddress(
                    $shippingAddress,
                    self::STREET_NAME
                ),
                self::NUMBER     => (int) $this->config->getValueForAddress(
                    $shippingAddress,
                    self::STREET_NUMBER
                ),
                self::APARTMENT         => null,
                self::FLOOR             => null,
                self::CITY              => $shippingAddress->getCity(),
                self::COUNTRY           => $shippingAddress->getCountryId(),
                self::STATE             => $shippingAddress->getRegionCode(),
                self::STREET_COMPLEMENT => $this->config->getValueForAddress(
                    $shippingAddress,
                    self::STREET_COMPLEMENT
                )
            ];

            $shipmentTrack = $this->shipmentTrackRepository->get($this->checkoutSession->getShipmentTrackId());
            $trackNumber = $shipmentTrack->getTrackNumber();

            $result[AdditionalInfoDataRequest::ADDITIONAL_INFO][self::SHIPMENTS][self::TRACKING] = [
                self::CODE_TRACKING => $trackNumber,
                self::STATUS_TRACKING => null, 
            ];
        }

        return $result;
    }
}
