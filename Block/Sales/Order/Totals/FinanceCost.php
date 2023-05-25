<?php
/**
 * Copyright © MercadoPago. All rights reserved.
 *
 * @author      Mercado Pago
 * @license     See LICENSE for license details.
 */

namespace MercadoPago\AdbPayment\Block\Sales\Order\Totals;

use Magento\Framework\DataObject;
use Magento\Framework\View\Element\Template;
use Magento\Sales\Model\Order;

/**
 * Information block on the Finance Cost.
 */
class FinanceCost extends Template
{
    /**
     * @var Order
     */
    protected $order;

    /**
     * @var DataObject
     */
    protected $source;

    /**
     * Type display in Full Sumary.
     *
     * @return bool
     */
    public function displayFullSummary()
    {
        return true;
    }

    /**
     * Get data (totals) source model.
     *
     * @return DataObject
     */
    public function getSource()
    {
        return $this->source;
    }

    /**
     * Get Store.
     *
     * @return string
     */
    public function getStore()
    {
        return $this->order->getStore();
    }

    /**
     * Get Order.
     *
     * @return order
     */
    public function getOrder()
    {
        return $this->order;
    }

    /**
     * Initialize payment finance cost totals.
     *
     * @return void
     */
    public function initTotals()
    {
        $parent = $this->getParentBlock();
        $this->order = $parent->getOrder();
        $this->source = $parent->getSource();

        if (!$this->source->getFinanceCostAmount() || (int) $this->source->getFinanceCostAmount() === 0) {
            return $this;
        }

        $financeCost = $this->source->getFinanceCostAmount();

        $baseFinanceCost = $this->source->getBaseFinanceCostAmount();

        $label = $this->getLabel($financeCost);

        $financeCostAmount = new DataObject(
            [
                'code'          => 'finance_cost_amount',
                'strong'        => false,
                'value'         => $financeCost,
                'base_value'    => $baseFinanceCost,
                'label'         => $label,
            ]
        );

        $parent->addTotal($financeCostAmount, 'finance_cost_amount');
    }

    /**
     * Get Subtotal label.
     *
     * @param string|null $financeCost
     *
     * @return Phrase
     */
    public function getLabel($financeCost)
    {
        if ($financeCost >= 0) {
            return __('Finance Cost');
        }

        return __('Discount for payment at sight');
    }
}
