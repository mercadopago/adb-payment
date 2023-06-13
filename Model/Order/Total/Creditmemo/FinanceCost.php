<?php
/**
 * Copyright © MercadoPago. All rights reserved.
 *
 * @author      Mercado Pago
 * @license     See LICENSE for license details.
 */

namespace MercadoPago\AdbPayment\Model\Order\Total\Creditmemo;

use Magento\Sales\Model\Order\Creditmemo;
use Magento\Sales\Model\Order\Creditmemo\Total\AbstractTotal;

/**
 * Model for implementing the Finance Cost in Creditmemo.
 */
class FinanceCost extends AbstractTotal
{
    /**
     * Collect Finance Cost.
     *
     * @param Creditmemo $creditmemo
     *
     * @return void
     */
    public function collect(Creditmemo $creditmemo)
    {
        $order = $creditmemo->getOrder();

        $financeCost = $order->getFinanceCostAmount();
        $baseFinanceCost = $order->getBaseFinanceCostAmount();

        $creditmemo->setFinanceCostAmount($financeCost);
        $creditmemo->setBaseFinanceCostAmount($baseFinanceCost);
    }
}
