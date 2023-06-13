<?php
/**
 * Copyright © MercadoPago. All rights reserved.
 *
 * @author      Mercado Pago
 * @license     See LICENSE for license details.
 */

namespace MercadoPago\AdbPayment\Model\Order\Total\Invoice;

use Magento\Sales\Model\Order\Invoice;
use Magento\Sales\Model\Order\Invoice\Total\AbstractTotal;

/**
 * Model for implementing the Finance Cost in Invoice.
 */
class FinanceCost extends AbstractTotal
{
    /**
     * Collect Finance Cost.
     *
     * @param Invoice $invoice
     *
     * @return void
     */
    public function collect(Invoice $invoice)
    {
        $order = $invoice->getOrder();

        $financeCost = $order->getFinanceCostAmount();
        $baseFinanceCost = $order->getBaseFinanceCostAmount();

        $invoice->setFinanceCostAmount($financeCost);
        $invoice->setBaseFinanceCostAmount($baseFinanceCost);
    }
}
