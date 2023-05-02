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
 * Gateway requests for Payer data.
 */
class PayerDataRequest implements BuilderInterface
{
    /**
     * Payer block name.
     */
    public const PAYER = 'payer';

    /**
     * Type block name.
     */
    public const TYPE = 'type';

    /**
     * Customer id block name.
     */
    public const ID = 'id';

    /**
     * Email block name.
     */
    public const EMAIL = 'email';

    /**
     * First name block name.
     */
    public const FIRST_NAME = 'first_name';

    /**
     * Last name block name.
     */
    public const LAST_NAME = 'last_name';

    /**
     * Entity Type block name.
     */
    public const ENTITY_TYPE = 'entity_type';

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
        $mpUserId = null;

        /** @var OrderAdapterFactory $orderAdapter */
        $orderAdapter = $this->orderAdapterFactory->create(
            ['order' => $payment->getOrder()]
        );

        $mpUserId = $payment->getAdditionalInformation('mp_user_id');
        $type = isset($mpUserId) ? 'customer' : null;

        $payerEntityType = $payment->getAdditionalInformation('payer_entity_type');

        $billingAddress = $orderAdapter->getBillingAddress();

        $result[self::PAYER] = [
            self::TYPE              => $type,
            self::ID                => $mpUserId,
            self::EMAIL             => $billingAddress->getEmail(),
            self::FIRST_NAME        => $billingAddress->getFirstname(),
            self::LAST_NAME         => $billingAddress->getLastname(),
        ];

        if ($payerEntityType) {
            $result[self::PAYER][self::ENTITY_TYPE] = $payerEntityType;
        }

        return $result;
    }
}
