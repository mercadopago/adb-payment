<?php
/**
 * Copyright © MercadoPago. All rights reserved.
 *
 * @author      Mercado Pago
 * @license     See LICENSE for license details.
 */

namespace MercadoPago\AdbPayment\Model\Api\Data;

use Magento\Framework\Api\AbstractSimpleObject;
use MercadoPago\AdbPayment\Api\Data\RulesForFinanceCostInterface;

/**
 * Date Model for the Financing Cost Rules.
 */
class RulesForFinanceCost extends AbstractSimpleObject implements
    RulesForFinanceCostInterface
{
    /**
     * @inheritdoc
     */
    public function getInstallments()
    {
        return $this->_get(RulesForFinanceCostInterface::INSTALLMENTS);
    }

    /**
     * @inheritdoc
     */
    public function setInstallments($instalments)
    {
        return $this->setData(RulesForFinanceCostInterface::INSTALLMENTS, $instalments);
    }

    /**
     * @inheritdoc
     */
    public function getInstallmentRate()
    {
        return $this->_get(RulesForFinanceCostInterface::INSTALLMENT_RATE);
    }

    /**
     * @inheritdoc
     */
    public function setInstallmentRate($instalmentRate)
    {
        return $this->setData(RulesForFinanceCostInterface::INSTALLMENT_RATE, $instalmentRate);
    }

    /**
     * @inheritdoc
     */
    public function getDiscountRate()
    {
        return $this->_get(RulesForFinanceCostInterface::DISCOUNT_RATE);
    }

    /**
     * @inheritdoc
     */
    public function setDiscountRate($discountRate)
    {
        return $this->setData(RulesForFinanceCostInterface::DISCOUNT_RATE, $discountRate);
    }

    /**
     * @inheritdoc
     */
    public function getReimbursementRate()
    {
        return $this->_get(RulesForFinanceCostInterface::REIMBURSEMENT_RATE);
    }

    /**
     * @inheritdoc
     */
    public function setReimbursementRate($discountRate)
    {
        return $this->setData(RulesForFinanceCostInterface::REIMBURSEMENT_RATE, $discountRate);
    }

    /**
     * @inheritdoc
     */
    public function getTotalAmount()
    {
        return $this->_get(RulesForFinanceCostInterface::TOTAL_AMOUNT);
    }

    /**
     * @inheritdoc
     */
    public function setTotalAmount($discountRate)
    {
        return $this->setData(RulesForFinanceCostInterface::TOTAL_AMOUNT, $discountRate);
    }

    /**
     * @inheritdoc
     */
    public function getCardAmount()
    {
        return $this->_get(RulesForFinanceCostInterface::CARD_AMOUNT);
    }

    /**
     * @inheritdoc
     */
    public function setCardAmount($cardAmount)
    {
        return $this->setData(RulesForFinanceCostInterface::CARD_AMOUNT, $cardAmount);
    }

    /**
     * @inheritdoc
     */
    public function getCardIndex()
    {
        return $this->_get(RulesForFinanceCostInterface::CARD_INDEX);
    }

    /**
     * @inheritdoc
     */
    public function setCardIndex($cardIndex)
    {
        return $this->setData(RulesForFinanceCostInterface::CARD_INDEX, $cardIndex);
    }

    /**
     * @inheritdoc
     */
    public function getPaymentMethod()
    {
        return $this->_get(RulesForFinanceCostInterface::PAYMENT_METHOD);
    }

    /**
     * @inheritdoc
     */
    public function setPaymentMethod($paymentMethod)
    {
        return $this->setData(RulesForFinanceCostInterface::PAYMENT_METHOD, $paymentMethod);
    }
}
