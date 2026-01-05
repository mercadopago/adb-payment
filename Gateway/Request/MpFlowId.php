<?php

namespace MercadoPago\AdbPayment\Gateway\Request;

use InvalidArgumentException;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Gateway\Request\BuilderInterface;

class MpFlowId implements BuilderInterface
{
    public const MP_FLOW_ID = 'mp_flow_id';

    public function build(array $buildSubject)
    {
        if (!isset($buildSubject['payment'])
        || !$buildSubject['payment'] instanceof PaymentDataObjectInterface
        ) {
            throw new InvalidArgumentException('Payment data object should be provided');
        }

        $paymentDO = $buildSubject['payment'];
        $payment = $paymentDO->getPayment();

        $flowId = $payment->getAdditionalInformation(self::MP_FLOW_ID);

        if ($flowId === null) {
            return [];
        }

        return [self::MP_FLOW_ID => $payment->getAdditionalInformation(self::MP_FLOW_ID)];
    }
}