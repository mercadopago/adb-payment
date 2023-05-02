<?php
/**
 * Copyright © MercadoPago. All rights reserved.
 *
 * @author      Mercado Pago
 * @license     See LICENSE for license details.
 */

namespace MercadoPago\AdbPayment\Api;

/**
 * Interface to calculate the cost of financing.
 *
 * @api
 */
interface FinanceCostManagementInterface
{
    /**
     * Finance Cost.
     *
     * @param int                                                               $cartId
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
