<?php
/**
 * Copyright © MercadoPago. All rights reserved.
 *
 * @license     See LICENSE for license details.
 */

namespace MercadoPago\AdbPayment\Gateway\Request;

use InvalidArgumentException;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Payment\Model\InfoInterface;
use MercadoPago\AdbPayment\Gateway\Config\Config;
use MercadoPago\AdbPayment\Gateway\Config\ConfigTwoCc;
use MercadoPago\AdbPayment\Gateway\SubjectReader;

/**
 * Gateway Requests Payment by Card Data.
 */
class TransactionInfoDataRequest implements BuilderInterface
{

    /**
     * Transaction Info block name.
     */
    public const TRANSACTION_INFO = 'transaction_info';

    /**
     * Transaction Amount block name.
     */
    public const TRANSACTION_AMOUNT = 'transaction_amount';

    /**
     * Payment Method Id name block name.
     */
    public const PAYMENT_METHOD_ID = 'payment_method_id';

    /**
     * Installments block name.
     */
    public const INSTALLMENTS = 'installments';

    /**
     * Cc Token block name.
     */
    public const TOKEN = 'token';

    /**
     * Num Cards.
     */
    public const NUM_CARDS = 2;

    /**
     * @var SubjectReader
     */
    protected $subjectReader;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var ConfigTwoCc
     */
    protected $configTwoCc;

    /**
     * @param SubjectReader       $subjectReader
     * @param Config              $config
     * @param ConfigTwoCc         $configTwoCc
     */
    public function __construct(
        SubjectReader $subjectReader,
        Config $config,
        ConfigTwoCc $configTwoCc
    ) {
        $this->subjectReader = $subjectReader;
        $this->config = $config;
        $this->configTwoCc = $configTwoCc;
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

        $paymentDO = $buildSubject['payment'];
        $payment = $paymentDO->getPayment();
        $order = $paymentDO->getOrder();
        $result = [];

        $result = $this->getDataPaymetTwoCc($payment, $order);

        return $result;

    }

    /**
     * Data for CC.
     *
     * @param InfoInterface $payment
     *
     * @return array
     */
    public function getDataPaymetTwoCc($payment, $order)
    {
        $instruction = [];

        $instruction[self::TRANSACTION_INFO] = [];

        for ($i = 0; $i < self::NUM_CARDS; $i++):
            $cardInfo = [
                self::TRANSACTION_AMOUNT => $this->config->formatPrice((double) $payment->getAdditionalInformation('card_'.$i.'_amount'), $order->getStoreId()),
                self::INSTALLMENTS       => (int) $payment->getAdditionalInformation('card_'.$i.'_installments') ?: 1,
                self::TOKEN              => $payment->getAdditionalInformation('card_'.$i.'_number_token'),
                self::PAYMENT_METHOD_ID  => strtolower((string) $payment->getAdditionalInformation('card_'.$i.'_type')),
            ];

            array_push($instruction[self::TRANSACTION_INFO], $cardInfo);
        endfor;

        return $instruction;
    }

}
