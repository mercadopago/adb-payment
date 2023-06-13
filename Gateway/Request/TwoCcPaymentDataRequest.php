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
use MercadoPago\AdbPayment\Gateway\Config\ConfigTwoCc;
use MercadoPago\AdbPayment\Gateway\SubjectReader;

/**
 * Gateway Requests Payment by Card Data.
 */
class TwoCcPaymentDataRequest implements BuilderInterface
{
    /**
     * Method Id.
     */
    public const METHOD_ID = 'pp_multiple_payments';

    /**
     * Binary Mode block name.
     */
    public const BINARY_MODE = 'binary_mode';

    /**
     * Credit card name block name.
     */
    public const PAYMENT_METHOD_ID = 'payment_method_id';

    /**
     * Soft descriptor.
     */
    public const SOFT_DESCRIPTOR = 'statement_descriptor';

    /**
     * Cc Capture block name.
     */
    public const CAPTURE = 'capture';

    /**
     * Two Cards block name.
     */
    public const TRANSACTION_INFO = 'transaction_info';

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
     * @param SubjectReader $subjectReader
     * @param Config        $config
     * @param ConfigTwoCc   $configTwoCc
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
        $storeId = $order->getStoreId();
        $result = [];

        $result = $this->getDataPaymet($payment, $storeId);

        return $result;
    }

    /**
     * Data for CC.
     *
     * @param InfoInterface $payment
     * @param int           $storeId
     *
     * @return array
     */
    public function getDataPaymet($payment, $storeId)
    {
        $instruction = [];

        $mpSiteId = $this->config->getMpSiteId($storeId);
        $capture = $this->configTwoCc->hasCapture($storeId);
        $binary = $this->configTwoCc->isBinaryMode($storeId);
        $unsupported = $this->configTwoCc->getUnsupportedPreAuth($storeId);

        if (in_array(self::METHOD_ID, $unsupported[$mpSiteId])) {
            $capture = true;
            $binary = true;
        }

        $instruction = [
            self::PAYMENT_METHOD_ID => self::METHOD_ID,
            self::SOFT_DESCRIPTOR   => $this->config->getStatementDescriptor($storeId),
            self::BINARY_MODE       => $binary,
            self::CAPTURE           => $capture,
        ];

        return $instruction;
    }
}
