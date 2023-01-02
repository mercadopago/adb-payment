<?php
/**
 * Copyright © MercadoPago. All rights reserved.
 *
 * @author      Bruno Elisei <brunoelisei@o2ti.com>
 * @license     See LICENSE for license details.
 */

namespace MercadoPago\PaymentMagento\Model\Order\Total\Creditmemo;

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

        $creditmemo->setGrandTotal($creditmemo->getGrandTotal() + $financeCost);
        $creditmemo->setBaseGrandTotal($creditmemo->getBaseGrandTotal() + $financeCost);
        $creditmemo->setFinanceCostAmount($financeCost);
        $creditmemo->setBaseFinanceCostAmount($baseFinanceCost);
        $order->setFinanceCostAmountRefunded($financeCost);
        $order->setBaseFinanceCostAmountRefunded($baseFinanceCost);
    }
}
