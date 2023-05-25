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
 * Gateway Requests for Additional Ip Address Data.
 */
class AdditionalInfoIpDataRequest implements BuilderInterface
{
    /**
     * Ip Address block name.
     */
    public const IP_ADDRESS = 'ip_address';

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

        $ipAddress = $orderAdapter->getRemoteIp() ?: '127.0.0.1';

        $result[AdditionalInfoDataRequest::ADDITIONAL_INFO][self::IP_ADDRESS] = $ipAddress;

        return $result;
    }
}
