<?php
/**
 * Copyright © MercadoPago. All rights reserved.
 *
 * @author      Bruno Elisei <brunoelisei@o2ti.com>
 * @license     See LICENSE for license details.
 */

namespace MercadoPago\PaymentMagento\Model\Api\Data;

use Magento\Framework\Api\AbstractSimpleObject;
use MercadoPago\PaymentMagento\Api\Data\RulesForFinanceCostInterface;

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
}
