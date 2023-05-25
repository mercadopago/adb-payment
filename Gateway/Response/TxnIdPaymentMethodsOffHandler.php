<?php

namespace MercadoPago\AdbPayment\Gateway\Response;

use InvalidArgumentException;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Gateway\Response\HandlerInterface;
use Magento\Sales\Model\Order\Payment\Transaction;
use MercadoPago\AdbPayment\Gateway\Config\ConfigPaymentMethodsOff;

/**
 * Gateway response to Transaction Details by PaymentMethodsOff.
 */
class TxnIdPaymentMethodsOffHandler implements HandlerInterface
{
    /**
     * Payment Id response value.
     */
    public const PAYMENT_ID = 'id';

    /**
     * Payment Id block name.
     */
    public const MP_PAYMENT_ID = 'mp_payment_id';

    /**
     * Status response value.
     */
    public const STATUS = 'status';

    /**
     * MP Status block name.
     */
    public const MP_STATUS = 'mp_status';

    /**
     * Status response value.
     */
    public const STATUS_DETAIL = 'status_detail';

    /**
     * MP Status Detail block name.
     */
    public const MP_STATUS_DETAIL = 'mp_status_detail';

    /**
     * Date of Expiration block name.
     */
    public const DATE_OF_EXPIRATION = 'date_of_expiration';

    /**
     * Barcode block name.
     */
    public const BARCODE = 'barcode';

    /**
     * Line Code block name.
     */
    public const LINE_CODE = 'line_code';

    /**
     * Barcode Content block name.
     */
    public const CONTENT = 'content';

    /**
     * Transaction Details block name.
     */
    public const TRANSACTION_DETAILS = 'transaction_details';

    /**
     * Verification Code block name.
     */
    public const VERIFICATION_CODE = 'verification_code';

    /**
     * External Resource Url block name.
     */
    public const EXTERNAL_RESOURCE_URL = 'external_resource_url';

    /**
     * Financial Institution block name.
     */
    public const FINANCIAL_INSTITUTION = 'financial_institution';

    /**
     * Site Id block name.
     */
    public const SITE_ID = 'site_id';

    /**
     * Meta Data block name.
     */
    public const METADATA = 'metadata';

    /**
     * Message information.
     */
    public const MESSAGE_INFO = 'message_info';

    /**
     * Message from line code.
     */
    public const MESSAGE_LINE_CODE = 'message_line_code';

    /**
     * Message from document.
     */
    public const MESSAGE_DOCUMENT = 'message_document';

    /**
     * Message Will be approved.
     */
    public const MESSAGE_WILL_APPROVED = 'message_will_approved';

    /**
     * Payment Method Id block name.
     */
    public const PAYMENT_METHOD_ID = 'payment_method_id';

    /**
     * Payment Method Id MLB PEC.
     */
    public const PEC = 'pec';

    /**
     * Payment Method Id MLB Boleto.
     */
    public const BOLETO = 'bolbradesco';

    /**
     * Payment Method Id MLB.
     */
    public const MLB = 'MLB';

    /**
     * @var ConfigPaymentMethodsOff
     */
    protected $configMethodsOff;

    /**
     * @param ConfigPaymentMethodsOff $configMethodsOff
     */
    public function __construct(
        ConfigPaymentMethodsOff $configMethodsOff
    ) {
        $this->configMethodsOff = $configMethodsOff;
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

        $paymentDO = $handlingSubject['payment'];

        $payment = $paymentDO->getPayment();

        $this->setAddtionalInformation($payment, $response);

        $transactionId = $response[self::PAYMENT_ID];
        $payment->setTransactionId($transactionId);
        $payment->setIsTransactionPending(1);
        $payment->setIsTransactionClosed(false);
        $payment->setAuthorizationTransaction($transactionId);
        $payment->addTransaction(Transaction::TYPE_AUTH);

        $order = $payment->getOrder();
        $order->setState(\Magento\Sales\Model\Order::STATE_NEW);
        $order->setStatus('pending');
        $comment = __('Awaiting payment.');
        $order->addStatusHistoryComment($comment, $payment->getOrder()->getStatus());
    }

    /**
     * Set Additional Information.
     *
     * @param InfoInterface $payment
     * @param array         $response
     *
     * @return void
     */
    public function setAddtionalInformation($payment, $response)
    {
        $payment->setAdditionalInformation(
            self::MP_PAYMENT_ID,
            $response[self::PAYMENT_ID]
        );

        $payment->setAdditionalInformation(
            self::MP_STATUS,
            $response[self::STATUS]
        );

        $payment->setAdditionalInformation(
            self::MP_STATUS_DETAIL,
            $response[self::STATUS_DETAIL]
        );

        if (isset($response[self::BARCODE])) {
            if (isset($response[self::BARCODE][self::CONTENT])) {
                $barcode = $response[self::BARCODE][self::CONTENT];

                $payment->setAdditionalInformation(
                    self::BARCODE,
                    $barcode
                );
            }
        }

        $payment->setAdditionalInformation(
            self::DATE_OF_EXPIRATION,
            $response[self::DATE_OF_EXPIRATION]
        );

        $transactionDetails = $response[self::TRANSACTION_DETAILS];

        $payment->setAdditionalInformation(
            self::FINANCIAL_INSTITUTION,
            $transactionDetails[self::FINANCIAL_INSTITUTION]
        );

        $payment->setAdditionalInformation(
            self::EXTERNAL_RESOURCE_URL,
            $transactionDetails[self::EXTERNAL_RESOURCE_URL]
        );

        $payment->setAdditionalInformation(
            self::VERIFICATION_CODE,
            $transactionDetails[self::VERIFICATION_CODE]
        );

        $payment->setAdditionalInformation(
            self::MESSAGE_INFO,
            'Generate the ticket and pay it wherever you want.'
        );

        if ($response[self::METADATA][self::SITE_ID] === self::MLB) {
            $this->setAddtionalInformationMLB($payment, $response);
        }
    }

    public function setAddtionalInformationMLB($payment, $response) {
        if ($response[self::PAYMENT_METHOD_ID] === self::BOLETO) {
            $barcode = $response[self::BARCODE][self::CONTENT];
            $lineCode = $this->configMethodsOff->getLineCode($barcode);

            $payment->setAdditionalInformation(
                self::LINE_CODE,
                $lineCode
            );

            $payment->setAdditionalInformation(
                self::MESSAGE_LINE_CODE,
                'The Line Code is'
            );

            $payment->setAdditionalInformation(
                self::MESSAGE_WILL_APPROVED,
                'Will be approved within 2 business days.'
            );
        } else if ($response[self::PAYMENT_METHOD_ID] === self::PEC) {
            $transactionDetails = $response[self::TRANSACTION_DETAILS];

            $payment->setAdditionalInformation(
                self::LINE_CODE,
                $transactionDetails[self::FINANCIAL_INSTITUTION]
            );

            $payment->setAdditionalInformation(
                self::MESSAGE_LINE_CODE,
                'Mercado Pago partnership code'
            );

            $payment->setAdditionalInformation(
                self::MESSAGE_DOCUMENT,
                'Enter the ID Document you used in the purchase'
            );
        }
    }
}
