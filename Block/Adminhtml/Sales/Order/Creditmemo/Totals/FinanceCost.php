<?php
/**
 * Copyright © MercadoPago. All rights reserved.
 *
 * @author      Mercado Pago
 * @license     See LICENSE for license details.
 */

namespace MercadoPago\AdbPayment\Block\Adminhtml\Sales\Order\Creditmemo\Totals;

use Magento\Framework\DataObject;
use Magento\Framework\View\Element\Template;

/**
 * Totals Finance Cost Block - Method CreditMemo.
 */
class FinanceCost extends Template
{
    /**
     * Get data (totals) source model.
     *
     * @return DataObject
     */
    public function getSource()
    {
        return $this->getParentBlock()->getSource();
    }

    /**
     * Get Invoice data.
     *
     * @return invoice
     */
    public function getCreditmemo()
    {
        return $this->getParentBlock()->getCreditmemo();
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
}
