<?php
/**
 * Copyright © MercadoPago. All rights reserved.
 *
 * @author      Mercado Pago
 * @license     See LICENSE for license details.
 */

namespace MercadoPago\AdbPayment\Model\Api\Data;

use Magento\Framework\Api\AbstractSimpleObject;
use MercadoPago\AdbPayment\Api\Data\FinanceCostInterface;

/**
 * Date Model for Cost of Financing.
 */
class FinanceCost extends AbstractSimpleObject implements FinanceCostInterface
{
    /**
     * @inheritdoc
     */
    public function getSelectedInstallment()
    {
        return $this->_get(FinanceCostInterface::FINANCE_COST_AMOUNT);
    }

    /**
     * @inheritdoc
     */
    public function setSelectedInstallment($financeCost)
    {
        return $this->setData(FinanceCostInterface::FINANCE_COST_AMOUNT, $financeCost);
    }
}
