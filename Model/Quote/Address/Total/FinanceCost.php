<?php
/**
 * Copyright © MercadoPago. All rights reserved.
 *
 * @author      Mercado Pago
 * @license     See LICENSE for license details.
 */

namespace MercadoPago\AdbPayment\Model\Quote\Address\Total;

use Magento\Checkout\Model\Session;
use Magento\Framework\Phrase;
use Magento\Quote\Api\Data\PaymentInterface;
use Magento\Quote\Api\Data\ShippingAssignmentInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Address\Total;
use Magento\Quote\Model\Quote\Address\Total\AbstractTotal;
use Magento\Quote\Model\QuoteValidator;

/**
 * Model for implementing the Finance Cost in Order totals.
 */
class FinanceCost extends AbstractTotal
{
    /**
     * @var Session
     */
    protected $checkoutSession;

    /**
     * @var QuoteValidator
     */
    protected $quoteValidator = null;

    /**
     * @var PaymentInterface
     */
    protected $payment;

    /**
     * Payment FinanceCost constructor.
     *
     * @param QuoteValidator   $quoteValidator
     * @param Session          $checkoutSession
     * @param PaymentInterface $payment
     */
    public function __construct(
        QuoteValidator $quoteValidator,
        Session $checkoutSession,
        PaymentInterface $payment
    ) {
        $this->quoteValidator = $quoteValidator;
        $this->checkoutSession = $checkoutSession;
        $this->payment = $payment;
    }

    /**
     * Collect totals process.
     *
     * @param Quote                       $quote
     * @param ShippingAssignmentInterface $shippingAssignment
     * @param Total                       $total
     *
     * @return $this
     */
    public function collect(
        Quote $quote,
        ShippingAssignmentInterface $shippingAssignment,
        Total $total
    ) {
        parent::collect($quote, $shippingAssignment, $total);

        if (!count($shippingAssignment->getItems())) {
            return $this;
        }

        $financeCost = $quote->getFinanceCostAmount();
        $baseFinanceCost = $quote->getBaseFinanceCostAmount();

        $total->setFinanceCostAmount($financeCost);
        $total->setBaseFinanceCostAmount($baseFinanceCost);

        $total->setTotalAmount('finance_cost_amount', $financeCost);
        $total->setBaseTotalAmount('base_finance_cost_amount', $baseFinanceCost);

        $total->setGrandTotal((float) $total->getGrandTotal());
        $total->setBaseGrandTotal((float) $total->getBaseGrandTotal());

        return $this;
    }

    /**
     * Clear Values.
     *
     * @param Total $total
     */
    protected function clearValues(Total $total)
    {
        $total->setTotalAmount('subtotal', 0);
        $total->setBaseTotalAmount('subtotal', 0);
        $total->setTotalAmount('tax', 0);
        $total->setBaseTotalAmount('tax', 0);
        $total->setTotalAmount('discount_tax_compensation', 0);
        $total->setBaseTotalAmount('discount_tax_compensation', 0);
        $total->setTotalAmount('shipping_discount_tax_compensation', 0);
        $total->setBaseTotalAmount('shipping_discount_tax_compensation', 0);
        $total->setSubtotalInclTax(0);
        $total->setBaseSubtotalInclTax(0);
    }

    /**
     * Assign subtotal amount and label to address object.
     *
     * @param Quote $quote
     * @param Total $total
     *
     * @return array
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function fetch(
        Quote $quote,
        Total $total
    ) {
        $result = null;
        $financeCost = $quote->getFinanceCostAmount();
        $labelByFinanceCost = $this->getLabelByFinanceCost($financeCost);

        if ($financeCost) {
            $result = [
                'code'  => $this->getCode(),
                'title' => $labelByFinanceCost,
                'value' => $financeCost,
            ];
        }

        return $result;
    }

    /**
     * Get Subtotal label.
     *
     * @return Phrase
     */
    public function getLabel()
    {
        return __('Finance Cost');
    }

    /**
     * Get Subtotal label by Finance Cost.
     *
     * @param float $financeCost
     *
     * @return Phrase
     */
    public function getLabelByFinanceCost($financeCost)
    {
        if ($financeCost >= 0) {
            return __('Finance Cost');
        }

        return __('Discount for payment at sight');
    }
}
