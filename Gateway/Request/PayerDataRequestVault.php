<?php

/**
 * Copyright © MercadoPago. All rights reserved.
 *
 * @author      Mercado Pago
 * @license     See LICENSE for license details.
 */

namespace MercadoPago\AdbPayment\Gateway\Request;

use Magento\Payment\Gateway\Request\BuilderInterface;

/**
 * Gateway requests for Payer data.
 */
class PayerDataRequestVault extends PayerDataRequest implements BuilderInterface
{
    protected function _prepareData(array $buildSubject, array $result): array
    {
        $paymentDO = $this->subjectReader->readPayment($buildSubject);
        $payment = $paymentDO->getPayment();

        $mpUserId = $payment->getAdditionalInformation('mp_user_id');
        $type = isset($mpUserId) ? 'customer' : null;

        $result[self::PAYER][self::TYPE] = $type;
        $result[self::PAYER][self::ID] = $mpUserId;

        return $result;
    }
}
