<?php

namespace MercadoPago\AdbPayment\Gateway\Request;

use InvalidArgumentException;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Gateway\Request\BuilderInterface;

class MpDeviceSessionId implements BuilderInterface
{
    public const MP_DEVICE_SESSION_ID = 'mp_device_session_id';

    public function build(array $buildSubject)
    {
        if (!isset($buildSubject['payment'])
        || !$buildSubject['payment'] instanceof PaymentDataObjectInterface
        ) {
            throw new InvalidArgumentException('Payment data object should be provided');
        }

        $paymentDO = $buildSubject['payment'];
        $payment = $paymentDO->getPayment();

        $sessionId = $payment->getAdditionalInformation(self::MP_DEVICE_SESSION_ID);

        if ($sessionId === null) {
            return [];
        }

        return [self::MP_DEVICE_SESSION_ID => $payment->getAdditionalInformation(self::MP_DEVICE_SESSION_ID)];
    }
}
