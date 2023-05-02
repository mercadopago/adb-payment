<?php
/**
 * Copyright © MercadoPago. All rights reserved.
 *
 * @author      Mercado Pago
 * @license     See LICENSE for license details.
 */

namespace MercadoPago\AdbPayment\Api;

/**
 * Interface to calculate the cost of financing in guest.
 *
 * @api
 */
interface GuestFinanceCostManagementInterface
{
    /**
     * Finance Cost.
     *
     * @param string                                                            $cartId
     * @param \MercadoPago\AdbPayment\Api\Data\FinanceCostInterface         $userSelect
     * @param \MercadoPago\AdbPayment\Api\Data\RulesForFinanceCostInterface $rules
     *
     * @return mixed
     */
    public function saveFinanceCost(
        $cartId,
        \MercadoPago\AdbPayment\Api\Data\FinanceCostInterface $userSelect,
        \MercadoPago\AdbPayment\Api\Data\RulesForFinanceCostInterface $rules
    );
}
