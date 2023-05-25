<?php

namespace MercadoPago\AdbPayment\Gateway\Request;

use InvalidArgumentException;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Gateway\Request\BuilderInterface;
use MercadoPago\AdbPayment\Gateway\Config\ConfigPaymentMethodsOff;
use MercadoPago\AdbPayment\Gateway\SubjectReader;

/**
 * Gateway Requests Payment by PaymentMethodsOff Data.
 *
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class PaymentMethodsOffDataRequest implements BuilderInterface
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
     * @var ConfigPaymentMethodsOff
     */
    protected $configMethodsOff;

    /**
     * @var SubjectReader
     */
    protected $subjectReader;

    /**
     * @param SubjectReader   $subjectReader
     * @param ConfigPaymentMethodsOff    $configMethodsOff
     *
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        SubjectReader $subjectReader,
        ConfigPaymentMethodsOff $configMethodsOff
    ) {
        $this->subjectReader = $subjectReader;
        $this->configMethodsOff = $configMethodsOff;
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

        $expirationDate = $this->configMethodsOff->getExpirationFormatted();

        $result = [
            self::PAYMENT_METHOD_ID  => $paymentIdMethod,
            self::DATE_OF_EXPIRATION => $expirationDate
        ];

        return $result;
    }
}
