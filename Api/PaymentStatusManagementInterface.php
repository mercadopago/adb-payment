<?php

/**
 * Copyright © MercadoPago. All rights reserved.
 *
 * @author      Mercado Pago
 * @license     See LICENSE for license details.
 */

namespace MercadoPago\AdbPayment\Api;

/**
 * Interface for get Payment Status.
 *
 * @api
 */
interface PaymentStatusManagementInterface
{
    /**
     * Payment ID.
     *
     * @param string $paymentId
     * @param string $cartId
     *
     * @return mixed
     */
    public function getPaymentStatus(
        $paymentId,
        $cartId
    );
}
