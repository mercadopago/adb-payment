<?php
/**
 * Copyright © MercadoPago. All rights reserved.
 *
 * @author      Bruno Elisei <brunoelisei@o2ti.com>
 * @license     See LICENSE for license details.
 */

namespace MercadoPago\PaymentMagento\Gateway\Request;

use InvalidArgumentException;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Gateway\Request\BuilderInterface;
use MercadoPago\PaymentMagento\Gateway\Config\ConfigBanamex;
use MercadoPago\PaymentMagento\Gateway\Config\ConfigBancomer;
use MercadoPago\PaymentMagento\Gateway\Config\ConfigPagoEfectivo;
use MercadoPago\PaymentMagento\Gateway\Config\ConfigSerfin;
use MercadoPago\PaymentMagento\Gateway\SubjectReader;

/**
 * Gateway Requests Payment by Ticket Data.
 *
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class AtmPaymentDataRequest implements BuilderInterface
{
    /**
     * Payment Method Id block name.
     */
    public const PAYMENT_METHOD_ID = 'payment_method_id';

    /**
     * Date of Expiration block name.
     */
    public const DATE_OF_EXPIRATION = 'date_of_expiration';

    /**
     * @var ConfigBanamex
     */
    protected $configBanamex;

    /**
     * @var ConfigBancomer
     */
    protected $configBancomer;

    /**
     * @var ConfigPagoEfectivo
     */
    protected $configPagoEfectivo;

    /**
     * @var ConfigSerfin
     */
    protected $configSerfin;

    /**
     * @var SubjectReader
     */
    protected $subjectReader;

    /**
     * @param SubjectReader      $subjectReader
     * @param ConfigBanamex      $configBanamex
     * @param ConfigBancomer     $configBancomer
     * @param ConfigPagoEfectivo $configPagoEfectivo
     * @param ConfigSerfin       $configSerfin
     *
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        SubjectReader $subjectReader,
        ConfigBanamex $configBanamex,
        ConfigBancomer $configBancomer,
        ConfigPagoEfectivo $configPagoEfectivo,
        ConfigSerfin $configSerfin
    ) {
        $this->subjectReader = $subjectReader;
        $this->configBanamex = $configBanamex;
        $this->configBancomer = $configBancomer;
        $this->configPagoEfectivo = $configPagoEfectivo;
        $this->configSerfin = $configSerfin;
    }

    /**
     * Build.
     *
     * @param array $buildSubject
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function build(array $buildSubject)
    {
        if (!isset($buildSubject['payment'])
            || !$buildSubject['payment'] instanceof PaymentDataObjectInterface
        ) {
            throw new InvalidArgumentException('Payment data object should be provided');
        }

        $result = [];

        $paymentDO = $this->subjectReader->readPayment($buildSubject);

        $payment = $paymentDO->getPayment();

        $paymentIdMethod = $payment->getAdditionalInformation('payment_method_id');

        $options = [
            ConfigBanamex::PAYMENT_METHOD_ID        => $this->configBanamex->getExpirationFormatted(),
            ConfigBancomer::PAYMENT_METHOD_ID       => $this->configBancomer->getExpirationFormatted(),
            ConfigPagoEfectivo::PAYMENT_METHOD_ID   => $this->configPagoEfectivo->getExpirationFormatted(),
            ConfigSerfin::PAYMENT_METHOD_ID         => $this->configSerfin->getExpirationFormatted(),
        ];

        $result = [
            self::PAYMENT_METHOD_ID  => $paymentIdMethod,
            self::DATE_OF_EXPIRATION => $options[$paymentIdMethod],
        ];

        return $result;
    }
}
