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

/**
 * Gateway Requests for Three DS Data.
 */
class ThreeDSDataRequest implements BuilderInterface
{
    /**
     * Three D secure mode block name.
     */
    public const THREE_D_SECURE_MODE = 'three_d_secure_mode';


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

        $result = [];
        $result[self::THREE_D_SECURE_MODE] = "optional";

        return $result;
    }
}
