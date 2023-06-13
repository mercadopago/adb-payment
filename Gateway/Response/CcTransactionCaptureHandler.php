<?php
/**
 * Copyright © MercadoPago. All rights reserved.
 *
 * @author      Mercado Pago
 * @license     See LICENSE for license details.
 */

namespace MercadoPago\AdbPayment\Gateway\Response;

use InvalidArgumentException;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Gateway\Response\HandlerInterface;
use MercadoPago\AdbPayment\Gateway\Http\Client\CapturePaymentClient;

/**
 * Gateway response Capture a Card Payment.
 */
class CcTransactionCaptureHandler implements HandlerInterface
{
    /**
     * Response Pay Credit - Block name.
     */
    public const CREDIT = 'credit';

    /**
     * Response Pay Payment Id - Block name.
     */
    public const RESPONSE_PAYMENT_ID = 'id';

    /**
     * Response Pay Status - Block name.
     */
    public const STATUS = 'status';

    /**
     * Response Pay Approved - Block name.
     */
    public const APPROVED = 'approved';

    /**
     * @var Json
     */
    protected $json;

    /**
     * @param Json $json
     */
    public function __construct(
        Json $json
    ) {
        $this->json = $json;
    }

    /**
     * Handles.
     *
     * @param array $handlingSubject
     * @param array $response
     *
     * @return void
     */
    public function handle(array $handlingSubject, array $response)
    {
        if (!isset($handlingSubject['payment'])
            || !$handlingSubject['payment'] instanceof PaymentDataObjectInterface
        ) {
            throw new InvalidArgumentException('Payment data object should be provided');
        }

        if (isset($response[CapturePaymentClient::IGNORE_TRANSACTION_CREATION]) && $response[CapturePaymentClient::IGNORE_TRANSACTION_CREATION]) {
            return;
        }

        $isApproved = false;

        $isDenied = true;

        $paymentDO = $handlingSubject['payment'];

        $payment = $paymentDO->getPayment();

        $order = $payment->getOrder();

        $amount = $order->getBaseGrandTotal();

        $status = $response[self::STATUS];

        $transactionId = $response[self::RESPONSE_PAYMENT_ID];

        if ($status === self::APPROVED) {
            $isApproved = true;
            $isDenied = false;

            $payment->setAuthorizationTransaction($transactionId);
            $payment->registerAuthorizationNotification($amount);
            $payment->setAmountAuthorized($amount);
            $payment->setIsTransactionApproved($isApproved);
            $payment->setIsTransactionDenied($isDenied);
            $payment->registerCaptureNotification($amount);
            $payment->setTransactionId($transactionId);
            $payment->setTransactionDetails($this->json->serialize($response));
            $payment->setAdditionalData($this->json->serialize($response));
        }
    }
}
