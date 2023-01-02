<?php
/**
 * Copyright © MercadoPago. All rights reserved.
 *
 * @author      Bruno Elisei <brunoelisei@o2ti.com>
 * @license     See LICENSE for license details.
 */

namespace MercadoPago\PaymentMagento\Api;

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
