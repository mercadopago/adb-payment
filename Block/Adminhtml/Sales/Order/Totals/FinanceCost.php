<?php
/**
 * Copyright © MercadoPago. All rights reserved.
 *
 * @author      Mercado Pago
 * @license     See LICENSE for license details.
 */

namespace MercadoPago\AdbPayment\Block\Adminhtml\Sales\Order\Totals;

use Magento\Framework\DataObject;
use Magento\Framework\View\Element\Template;
use Magento\Sales\Model\Order;

/**
 * Totals Finance Cost Block - Method Order.
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
     * @return $this
     */
    public function initTotals()
    {
        return $this;
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
