<?php

namespace MercadoPago\PaymentMagento\Gateway\Request;

use InvalidArgumentException;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Gateway\Request\BuilderInterface;
use MercadoPago\PaymentMagento\Gateway\Config\ConfigPaymentMethodsOff;
use MercadoPago\PaymentMagento\Gateway\SubjectReader;

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
    protected $configPaymentMethodsOff;

    /**
     * @var SubjectReader
     */
    protected $subjectReader;

    /**
     * @param SubjectReader   $subjectReader
     * @param ConfigPaymentMethodsOff    $configPaymentMethodsOff
     *
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        SubjectReader $subjectReader,
        ConfigPaymentMethodsOff $configPaymentMethodsOff
    ) {
        $this->subjectReader = $subjectReader;
        $this->configPaymentMethodsOff = $configPaymentMethodsOff;
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

        $expirationDate = $this->configPaymentMethodsOff->getExpirationFormatted();

        $result = [
            self::PAYMENT_METHOD_ID  => $paymentIdMethod,
            self::DATE_OF_EXPIRATION => $expirationDate
        ];

        return $result;
    }
}
