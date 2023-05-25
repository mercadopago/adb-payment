<?php

namespace MercadoPago\AdbPayment\Gateway\Request;

use InvalidArgumentException;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Gateway\Request\BuilderInterface;
use MercadoPago\AdbPayment\Gateway\SubjectReader;

/**
 * Gateway requests for Payment Metadata by PaymentMethodsOff.
 */
class MetadataPaymentMethodsOffDataRequest implements BuilderInterface
{
    /**
     * Checkout block name.
     */
    public const CHECKOUT = 'checkout';

    /**
     * Checkout Type block name.
     */
    public const CHECKOUT_TYPE = 'checkout_type';

    /**
     * Checkout Type Option block name.
     */
    public const CHECKOUT_TYPE_OPTION = 'checkout_type_option';

      /**
     * @var SubjectReader
     */
    protected $subjectReader;

    /**
     * @param SubjectReader   $subjectReader
     *
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        SubjectReader $subjectReader
    ) {
        $this->subjectReader = $subjectReader;
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

        $payment = $paymentDO->getPayment();

        $paymentTypeId = $payment->getAdditionalInformation('payment_type_id');

        $paymentOptionId = $payment->getAdditionalInformation('payment_option_id');

        $result = [];

        $result[MetadataPaymentDataRequest::METADATA] = [
            self::CHECKOUT              => 'custom',
            self::CHECKOUT_TYPE         => $paymentTypeId,
            self::CHECKOUT_TYPE_OPTION  => $paymentOptionId
        ];

        return $result;
    }
}
