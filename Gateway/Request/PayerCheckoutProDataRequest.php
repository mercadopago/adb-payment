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
use MercadoPago\AdbPayment\Gateway\Data\Order\OrderAdapterFactory;
use MercadoPago\AdbPayment\Gateway\SubjectReader;

/**
 * Gateway requests for Payer data in method Checkout Pro.
 */
class PayerCheckoutProDataRequest implements BuilderInterface
{
    /**
     * Payer block name.
     */
    public const PAYER = 'payer';

    /**
     * The customer email address block name.
     */
    public const EMAIL = 'email';

    /**
     * The first name block name.
     */
    public const NAME = 'name';

    /**
     * The surname block name.
     */
    public const SURNAME = 'surname';

    /**
     * Phone block name.
     */
    public const PHONE = 'phone';

    /**
     * Phone Area Code block name.
     */
    public const PHONE_AREA_CODE = 'area_code';

    /**
     * Phone Number block name.
     */
    public const PHONE_NUMBER = 'number';

    /**
     * @var SubjectReader
     */
    protected $subjectReader;

    /**
     * @var OrderAdapterFactory
     */
    protected $orderAdapterFactory;

    /**
     * @param SubjectReader       $subjectReader
     * @param OrderAdapterFactory $orderAdapterFactory
     */
    public function __construct(
        SubjectReader $subjectReader,
        OrderAdapterFactory $orderAdapterFactory
    ) {
        $this->subjectReader = $subjectReader;
        $this->orderAdapterFactory = $orderAdapterFactory;
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
        $result = [];

        /** @var OrderAdapterFactory $orderAdapter */
        $orderAdapter = $this->orderAdapterFactory->create(
            ['order' => $payment->getOrder()]
        );

        $billingAddress = $orderAdapter->getBillingAddress();

        $phone = preg_replace('/[^0-9]/', '', $billingAddress->getTelephone());
        $phoneAreaCode = substr($phone, 0, 2);
        $phoneNumber = substr($phone, 2);

        $payerFirstName =
            $payment->getAdditionalInformation('payer_first_name') ?: $billingAddress->getFirstname();
        $payerLastName =
            $payment->getAdditionalInformation('payer_last_name') ?: $billingAddress->getLastname();

        $result[self::PAYER] = [
            self::EMAIL     => $billingAddress->getEmail(),
            self::NAME      => $payerFirstName,
            self::SURNAME   => $payerLastName,
            self::PHONE     => [
                self::PHONE_AREA_CODE => $phoneAreaCode,
                self::PHONE_NUMBER    => $phoneNumber,
            ],
        ];

        return $result;
    }
}
