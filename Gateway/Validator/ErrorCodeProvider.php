<?php
/**
 * Copyright © MercadoPago. All rights reserved.
 *
 * @author      Bruno Elisei <brunoelisei@o2ti.com>
 * @license     See LICENSE for license details.
 */

namespace MercadoPago\PaymentMagento\Gateway\Validator;

use PaymentMagento\Result\Error;
use PaymentMagento\Result\Successful;
use PaymentMagento\Transaction;

/**
 * Error Code Validation String.
 */
class ErrorCodeProvider
{
    /**
     * Error list.
     *
     * @param Successful|Error $response
     *
     * @return array
     */
    public function getErrorCodes($response): array
    {
        $result = [];
        if (!$response instanceof \Error) {
            return $result;
        }

        /** @var ErrorCollection $collection */
        $collection = $response->errors;

        /** @var Validation $error */
        foreach ($collection->deepAll() as $error) {
            $result[] = $error->code;
        }

        if (isset($response->transaction) && $response->transaction) {

            /* @phpstan-ignore-next-line */
            if ($response->transaction->status === Transaction::GATEWAY_REJECTED) {
                $result[] = $response->transaction->gatewayRejectionReason;
            }

            /* @phpstan-ignore-next-line */
            if ($response->transaction->status === Transaction::PROCESSOR_DECLINED) {
                $result[] = $response->transaction->processorResponseCode;
            }
        }

        return $result;
    }
}
