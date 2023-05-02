<?php
/**
 * Copyright © MercadoPago. All rights reserved.
 *
 * @author      Mercado Pago
 * @license     See LICENSE for license details.
 */

namespace MercadoPago\AdbPayment\Api\Data;

use Magento\Framework\Api\ExtensibleDataInterface;

/**
 * Interface Rules for Finance Cost - Data to calculate the cost of financing.
 *
 * @api
 *
 * @since 100.0.0
 */
interface RulesForFinanceCostInterface extends ExtensibleDataInterface
{
    /**
     * Installments in rules.
     */
    public const INSTALLMENTS = 'installments';

    /**
     * Installments rate.
     */
    public const INSTALLMENT_RATE = 'installment_rate';

    /**
     * Discount rate.
     */
    public const DISCOUNT_RATE = 'discount_rate';

    /**
     * Reimbursement Rate.
     */
    public const REIMBURSEMENT_RATE = 'reimbursement_rate';

    /**
     * Total Amount.
     */
    public const TOTAL_AMOUNT = 'total_amount';

    /**
     * Card Amount.
     */
    public const CARD_AMOUNT = 'card_amount';

    /**
     * Card Index.
     */
    public const CARD_INDEX = 'card_index';

    /**
     * Payment Method.
     */
    public const PAYMENT_METHOD = 'payment_method';

    /**
     * Get installments for rule.
     *
     * @return int|null
     */
    public function getInstallments();

    /**
     * Set installments for rule.
     *
     * @param int $installments
     *
     * @return $this
     */
    public function setInstallments($installments);

    /**
     * Get installment rate.
     *
     * @return float|null
     */
    public function getInstallmentRate();

    /**
     * Set installment rate.
     *
     * @param float $installmentRate
     *
     * @return $this
     */
    public function setInstallmentRate($installmentRate);

    /**
     * Get discount rate.
     *
     * @return float|null
     */
    public function getDiscountRate();

    /**
     * Set discount rate.
     *
     * @param float $discountRate
     *
     * @return $this
     */
    public function setDiscountRate($discountRate);

    /**
     * Get reimbursement rate.
     *
     * @return bool|null
     */
    public function getReimbursementRate();

    /**
     * Set reimbursement rate.
     *
     * @param bool $reimbursementRate
     *
     * @return $this
     */
    public function setReimbursementRate($reimbursementRate);

    /**
     * Get Total Amount.
     *
     * @return float|null
     */
    public function getTotalAmount();

    /**
     * Set Total Amount.
     *
     * @param bool $totalAmount
     *
     * @return $this
     */
    public function setTotalAmount($totalAmount);

    /**
     * Get Card Amount.
     *
     * @return float|null
     */
    public function getCardAmount();

    /**
     * Set Card Amount.
     *
     * @param float $cardAmount
     *
     * @return $this
     */
    public function setCardAmount($cardAmount);

    /**
     * Get Card Index.
     *
     * @return int|null
     */
    public function getCardIndex();

    /**
     * Set Card Index.
     *
     * @param int $cartIndex
     *
     * @return $this
     */
    public function setCardIndex($cartIndex);


    /**
     * Get Payment Method.
     *
     * @return string|null
     */
    public function getPaymentMethod();

    /**
     * Set Payment Method.
     *
     * @param string $paymentMethod
     *
     * @return $this
     */
    public function setPaymentMethod($paymentMethod);
}
