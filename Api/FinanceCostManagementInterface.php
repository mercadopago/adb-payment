<?php
/**
 * Copyright © MercadoPago. All rights reserved.
 *
 * @author      Bruno Elisei <brunoelisei@o2ti.com>
 * @license     See LICENSE for license details.
 */

namespace MercadoPago\PaymentMagento\Api;

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
     * @param \MercadoPago\PaymentMagento\Api\Data\FinanceCostInterface         $userSelect
     * @param \MercadoPago\PaymentMagento\Api\Data\RulesForFinanceCostInterface $rules
     *
     * @return mixed
     */
    public function saveFinanceCost(
        $cartId,
        \MercadoPago\PaymentMagento\Api\Data\FinanceCostInterface $userSelect,
        \MercadoPago\PaymentMagento\Api\Data\RulesForFinanceCostInterface $rules
    );
}
