<?php

namespace MercadoPago\PaymentMagento\Gateway\Request;

use MercadoPago\AdbPayment\Gateway\Request\MetadataPaymentDataRequest;

class MinhaInformacao
{
    public function build(array $buildSubject)
    {
        $result = [];

        $result[MetadataPaymentDataRequest::METADATA] = [
            'meu_novo_campo' => 'teste'
        ];

        return $result;
    }
}
