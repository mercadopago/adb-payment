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
use MercadoPago\PaymentMagento\Gateway\Config\ConfigAbitab;
use MercadoPago\PaymentMagento\Gateway\Config\ConfigBoleto;
use MercadoPago\PaymentMagento\Gateway\Config\ConfigEfecty;
use MercadoPago\PaymentMagento\Gateway\Config\ConfigOxxo;
use MercadoPago\PaymentMagento\Gateway\Config\ConfigPagoFacil;
use MercadoPago\PaymentMagento\Gateway\Config\ConfigPayCash;
use MercadoPago\PaymentMagento\Gateway\Config\ConfigPec;
use MercadoPago\PaymentMagento\Gateway\Config\ConfigRapiPago;
use MercadoPago\PaymentMagento\Gateway\Config\ConfigRedpagos;
use MercadoPago\PaymentMagento\Gateway\SubjectReader;

/**
 * Gateway Requests Payment by Ticket Data.
 *
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class TicketPaymentDataRequest implements BuilderInterface
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
     * @var ConfigAbitab
     */
    protected $configAbitab;

    /**
     * @var ConfigBoleto
     */
    protected $configBoleto;

    /**
     * @var ConfigEfecty
     */
    protected $configEfecty;

    /**
     * @var ConfigOxxo
     */
    protected $configOxxo;

    /**
     * @var ConfigPagoFacil
     */
    protected $configPagoFacil;

    /**
     * @var ConfigPayCash
     */
    protected $configPayCash;

    /**
     * @var ConfigPec
     */
    protected $configPec;

    /**
     * @var ConfigRedpagos
     */
    protected $configRedpagos;

    /**
     * @var ConfigRapiPago
     */
    protected $configRapiPago;

    /**
     * @var SubjectReader
     */
    protected $subjectReader;

    /**
     * @param SubjectReader   $subjectReader
     * @param ConfigAbitab    $configAbitab
     * @param ConfigBoleto    $configBoleto
     * @param ConfigEfecty    $configEfecty
     * @param ConfigOxxo      $configOxxo
     * @param ConfigPagoFacil $configPagoFacil
     * @param ConfigPayCash   $configPayCash
     * @param ConfigPec       $configPec
     * @param ConfigRapiPago  $configRapiPago
     * @param ConfigRedpagos  $configRedpagos
     *
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        SubjectReader $subjectReader,
        ConfigAbitab $configAbitab,
        ConfigBoleto $configBoleto,
        ConfigEfecty $configEfecty,
        ConfigOxxo $configOxxo,
        ConfigPagoFacil $configPagoFacil,
        ConfigPayCash $configPayCash,
        ConfigPec $configPec,
        ConfigRapiPago $configRapiPago,
        ConfigRedpagos $configRedpagos
    ) {
        $this->subjectReader = $subjectReader;
        $this->configAbitab = $configAbitab;
        $this->configBoleto = $configBoleto;
        $this->configEfecty = $configEfecty;
        $this->configOxxo = $configOxxo;
        $this->configPagoFacil = $configPagoFacil;
        $this->configPayCash = $configPayCash;
        $this->configPec = $configPec;
        $this->configRapiPago = $configRapiPago;
        $this->configRedpagos = $configRedpagos;
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
            ConfigAbitab::PAYMENT_METHOD_ID     => $this->configAbitab->getExpirationFormatted(),
            ConfigBoleto::PAYMENT_METHOD_ID     => $this->configBoleto->getExpirationFormatted(),
            ConfigEfecty::PAYMENT_METHOD_ID     => $this->configEfecty->getExpirationFormatted(),
            ConfigOxxo::PAYMENT_METHOD_ID       => $this->configOxxo->getExpirationFormatted(),
            ConfigPagoFacil::PAYMENT_METHOD_ID  => $this->configPagoFacil->getExpirationFormatted(),
            ConfigPec::PAYMENT_METHOD_ID        => $this->configPec->getExpirationFormatted(),
            ConfigPayCash::PAYMENT_METHOD_ID    => $this->configPayCash->getExpirationFormatted(),
            ConfigRapiPago::PAYMENT_METHOD_ID   => $this->configRapiPago->getExpirationFormatted(),
            ConfigRedpagos::PAYMENT_METHOD_ID   => $this->configRedpagos->getExpirationFormatted(),
        ];

        $result = [
            self::PAYMENT_METHOD_ID  => $paymentIdMethod,
            self::DATE_OF_EXPIRATION => $options[$paymentIdMethod],
        ];

        return $result;
    }
}
