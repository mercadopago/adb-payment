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
use MercadoPago\AdbPayment\Gateway\Config\ConfigPix;
use MercadoPago\AdbPayment\Gateway\Data\Order\OrderAdapterFactory;
use MercadoPago\AdbPayment\Gateway\SubjectReader;

/**
 * Gateway requests for Payment data in Order API format (PIX).
 * Mounts payments array with Order API structure.
 */
class PixOrderPaymentDataRequest implements BuilderInterface
{
    /**
     * Payments block name.
     */
    public const PAYMENTS = 'payments';

    /**
     * Type block name.
     */
    public const TYPE = 'type';

    /**
     * Amount block name.
     */
    public const AMOUNT = 'amount';

    /**
     * Payment Method block name.
     */
    public const PAYMENT_METHOD = 'payment_method';

    /**
     * Payment Method Id block name.
     */
    public const PAYMENT_METHOD_ID = 'id';

    /**
     * Payment Method Type block name.
     */
    public const PAYMENT_METHOD_TYPE = 'type';

    /**
     * Date of Expiration block name.
     */
    public const DATE_OF_EXPIRATION = 'date_of_expiration';

    /**
     * @var SubjectReader
     */
    protected $subjectReader;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var ConfigPix
     */
    protected $configPix;

    /**
     * @var OrderAdapterFactory
     */
    protected $orderAdapterFactory;

    /**
     * @param SubjectReader       $subjectReader
     * @param Config              $config
     * @param ConfigPix           $configPix
     * @param OrderAdapterFactory $orderAdapterFactory
     */
    public function __construct(
        SubjectReader $subjectReader,
        Config $config,
        ConfigPix $configPix,
        OrderAdapterFactory $orderAdapterFactory
    ) {
        $this->subjectReader = $subjectReader;
        $this->config = $config;
        $this->configPix = $configPix;
        $this->orderAdapterFactory = $orderAdapterFactory;
    }

    /**
     * Build.
     *
     * @param array $buildSubject
     * @return array
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

        /** @var OrderAdapterFactory $orderAdapter */
        $orderAdapter = $this->orderAdapterFactory->create(
            ['order' => $payment->getOrder()]
        );

        $grandTotal = $orderAdapter->getGrandTotalAmount();
        $order = $paymentDO->getOrder();
        $storeId = $order->getStoreId();

        $transactionAmount = $this->config->formatPrice($grandTotal, $storeId);

        $result = [
            self::TYPE => 'online',
            self::PAYMENTS => [
                [
                    self::AMOUNT => $transactionAmount,
                    self::PAYMENT_METHOD => [
                        self::PAYMENT_METHOD_ID => 'pix',
                        self::PAYMENT_METHOD_TYPE => 'bank_transfer',
                    ],
                    self::DATE_OF_EXPIRATION => $this->configPix->getExpirationDuration($storeId),
                ],
            ],
        ];

        return $result;
    }
}

