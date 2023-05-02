<?php
/**
 * Copyright © MercadoPago. All rights reserved.
 *
 * @author      Mercado Pago
 * @license     See LICENSE for license details.
 */

namespace MercadoPago\AdbPayment\Gateway\Response;

use InvalidArgumentException;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Gateway\Response\HandlerInterface;
use Magento\Payment\Model\InfoInterface;
use Magento\Sales\Api\Data\OrderPaymentExtensionInterface;
use Magento\Sales\Api\Data\OrderPaymentExtensionInterfaceFactory;
use Magento\Vault\Api\Data\PaymentTokenFactoryInterface;
use Magento\Vault\Api\Data\PaymentTokenInterface;
use Magento\Vault\Api\Data\PaymentTokenInterfaceFactory;

/**
 * Gateway response to Transaction Details by Vault.
 */
class VaultDetailsHandler implements HandlerInterface
{
    /**
     * @var PaymentTokenInterfaceFactory
     */
    protected $paymentTokenFactory;

    /**
     * @var OrderPaymentExtensionInterfaceFactory
     */
    protected $payExtensionFactory;

    /**
     * @var ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * @var Json
     */
    protected $json;

    /**
     * @param Json                                  $json
     * @param ObjectManagerInterface                $objectManager
     * @param OrderPaymentExtensionInterfaceFactory $payExtensionFactory
     * @param PaymentTokenFactoryInterface          $paymentTokenFactory
     */
    public function __construct(
        Json $json,
        ObjectManagerInterface $objectManager,
        OrderPaymentExtensionInterfaceFactory $payExtensionFactory,
        PaymentTokenFactoryInterface $paymentTokenFactory = null
    ) {
        if ($paymentTokenFactory === null) {
            $paymentTokenFactory = $objectManager->get(PaymentTokenFactoryInterface::class);
        }

        $this->objectManager = $objectManager;
        $this->payExtensionFactory = $payExtensionFactory;
        $this->paymentTokenFactory = $paymentTokenFactory;
        $this->json = $json;
    }

    /**
     * Handle.
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
        $paymentToken = $this->getVaultPaymentToken($payment, $response);
        if (null !== $paymentToken) {
            $extensionAttributes = $this->getExtensionAttributes($payment);
            $extensionAttributes->setVaultPaymentToken($paymentToken);
        }
    }

    /**
     * Get vault payment token entity.
     *
     * @param InfoInterface $payment
     * @param array         $response
     *
     * @return PaymentTokenInterface|null
     */
    protected function getVaultPaymentToken($payment, $response)
    {
        $response['RESULT_CODE'];
        $mpUserId = $payment->getAdditionalInformation('mp_user_id');
        $cardHolderName = $payment->getAdditionalInformation('card_holder_name');
        $cardNumberToken = $payment->getAdditionalInformation('card_public_id');
        $cardNumber = $payment->getAdditionalInformation('card_number');
        $cardType = $payment->getAdditionalInformation('card_type');
        $cardExpMonth = $payment->getAdditionalInformation('card_exp_month');
        $cardExpYear = $payment->getAdditionalInformation('card_exp_year');
        $payerDocumentType = $payment->getAdditionalInformation('payer_document_type');
        $payerDocumentNumber = $payment->getAdditionalInformation('payer_document_identification');

        if (empty($cardNumberToken)) {
            return null;
        }

        $cardNumbers = preg_replace('/[^0-9]/', '', $cardNumber);
        $firstSix = substr($cardNumbers, 0, 6);
        $lastFour = substr($cardNumber, 12);

        $paymentToken = $this->paymentTokenFactory->create();
        $paymentToken->setGatewayToken($cardNumberToken);
        $paymentToken->setExpiresAt(strtotime('+1 year'));
        $paymentToken->setType(PaymentTokenFactoryInterface::TOKEN_TYPE_CREDIT_CARD);

        $details = [
            'mp_public_id'          => $cardNumberToken,
            'mp_user_id'            => $mpUserId,
            'card_holder_name'      => $cardHolderName,
            'card_first6'           => $firstSix,
            'card_last4'            => $lastFour,
            'card_exp_year'         => $cardExpYear,
            'card_exp_month'        => $cardExpMonth,
            'card_type'             => $cardType,
            'payer_document_type'   => $payerDocumentType,
            'payer_document_number' => $payerDocumentNumber,
        ];

        $paymentToken->setTokenDetails($this->json->serialize($details));

        return $paymentToken;
    }

    /**
     * Get payment extension attributes.
     *
     * @param InfoInterface $payment
     *
     * @return OrderPaymentExtensionInterface
     */
    protected function getExtensionAttributes(InfoInterface $payment): OrderPaymentExtensionInterface
    {
        $extensionAttributes = $payment->getExtensionAttributes();
        if (null === $extensionAttributes) {
            $extensionAttributes = $this->payExtensionFactory->create();
            $payment->setExtensionAttributes($extensionAttributes);
        }

        return $extensionAttributes;
    }
}
