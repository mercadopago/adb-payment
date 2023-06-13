<?php
/**
 * Copyright © MercadoPago. All rights reserved.
 *
 * @author      Mercado Pago
 * @license     See LICENSE for license details.
 */

namespace MercadoPago\AdbPayment\Api;

/**
 * Interface for collecting payment details.
 *
 * @api
 */
interface PayInfoManagementInterface
{
    /**
     * Payment Information.
     *
     * @param int $orderId
     *
     * @return mixed
     */
    public function paymentInformation(
        $orderId
    );
}
