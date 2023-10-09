<?php

/**
 * Copyright © MercadoPago. All rights reserved.
 *
 * @author      Mercado Pago
 * @license     See LICENSE for license details.
 */

namespace MercadoPago\AdbPayment\Model\Api;

use MercadoPago\AdbPayment\Api\PaymentStatusManagementInterface;
use MercadoPago\AdbPayment\Model\MPApi\PaymentGet;
use Magento\Quote\Api\CartRepositoryInterface;

/**
 * Model for get Payment Status.
 */
class PaymentStatusManagement implements PaymentStatusManagementInterface
{

    /**
     * @var PaymentGet
     */
    protected $paymentGet;

    /**
     * @var CartRepositoryInterface
     */
    protected $quoteRepository;

    /**
     * Mp Payment Id.
     */
    public const PAYMENT_ID = 'payment_id';

    /**
     * Mp Payment Id value.
     */
    public const ID = 'id';

    /**
     * Mp Payment Status.
     */
    public const STATUS = 'status';

    /**
     * Mp Payment Status detail.
     */
    public const STATUS_DETAIL = 'status_detail';

    /**
     * Mp Payment Data.
     */
    public const DATA = 'data';

    /**
     * @param PaymentGet        $paymentGet
     * @param CartRepositoryInterface $quoteRepository
     */
    public function __construct(
        PaymentGet $paymentGet,
        CartRepositoryInterface $quoteRepository
    ) {
        $this->paymentGet = $paymentGet;
        $this->quoteRepository = $quoteRepository;
    }

    /**
     * Payment Status.
     *
     * @param string $paymentId
     * @param string $cartId
     * @return array
     */
    public function getPaymentStatus(
        $paymentId,
        $cartId
    ) {

        $quote = $this->quoteRepository->getActive($cartId);
        $storeId = $quote->getStoreId();
        $payment = $this->paymentGet->get($paymentId, $storeId);

        $paymentInfo[self::DATA] = [
            self::PAYMENT_ID    => $payment[self::ID],
            self::STATUS        => $payment[self::STATUS],
            self::STATUS_DETAIL   => $payment[self::STATUS_DETAIL],
        ];

        return $paymentInfo;
    }
}
