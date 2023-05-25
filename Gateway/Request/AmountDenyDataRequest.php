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
use MercadoPago\AdbPayment\Gateway\Config\ConfigCc;
use MercadoPago\AdbPayment\Gateway\SubjectReader;

/**
 * Gateway requests for Deny Payment amount.
 */
class AmountDenyDataRequest implements BuilderInterface
{
    /**
     * Amount block name.
     */
    public const CANCEL_AMOUNT = 'cancel_amount';

    /**
     * @var SubjectReader
     */
    protected $subjectReader;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var ConfigCc
     */
    protected $configCc;

    /**
     * @param SubjectReader $subjectReader
     * @param Config        $config
     * @param ConfigCc      $configCc
     */
    public function __construct(
        SubjectReader $subjectReader,
        Config $config,
        ConfigCc $configCc
    ) {
        $this->subjectReader = $subjectReader;
        $this->config = $config;
        $this->configCc = $configCc;
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

        $result = [];

        $order = $paymentDO->getOrder();

        $storeId = $order->getStoreId();

        $grandTotal = $order->getGrandTotalAmount();

        $result[self::CANCEL_AMOUNT] = $this->config->formatPrice($grandTotal, $storeId);

        return $result;
    }
}
