<?php
/**
 * Copyright © MercadoPago. All rights reserved.
 *
 * @author      Mercado Pago
 * @license     See LICENSE for license details.
 */

namespace MercadoPago\AdbPayment\Api;

/**
 * Interface for collecting quote MP details.
 *
 * @api
 */
interface QuoteMpPaymentManagementInterface
{
    /**
     * Quote MP Information.
     *
     * @param string $quoteId
     *
     * @return mixed
     */
    public function getQuoteMpPayment(
        $quoteId
    );
}
