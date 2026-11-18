<?php
/**
 * Copyright © MercadoPago. All rights reserved.
 *
 * @author      Mercado Pago
 * @license     See LICENSE for license details.
 */

namespace MercadoPago\AdbPayment\Model\Api;

use Magento\Quote\Model\ResourceModel\Quote\QuoteIdMask as QuoteIdMaskResource;
use Magento\Quote\Model\QuoteIdMaskFactory;
use MercadoPago\AdbPayment\Api\Data\FinanceCostInterface;
use MercadoPago\AdbPayment\Api\Data\RulesForFinanceCostInterface;
use MercadoPago\AdbPayment\Api\FinanceCostManagementInterface;
use MercadoPago\AdbPayment\Api\GuestFinanceCostManagementInterface;

/**
 * Model for application of Financing Cost in Order totals when guest.
 */
class GuestFinanceCostManagement implements GuestFinanceCostManagementInterface
{
    /**
     * @var QuoteIdMaskFactory
     */
    protected $quoteIdMaskFactory;

    /**
     * @var FinanceCostManagementInterface
     */
    protected $financeCostInterface;

    /**
     * @var QuoteIdMaskResource
     */
    private $quoteIdMaskResource;

    /**
     * GuestFinanceCostManagement constructor.
     *
     * @param QuoteIdMaskFactory             $quoteIdMaskFactory
     * @param FinanceCostManagementInterface $financeCostInterface
     * @param QuoteIdMaskResource $quoteIdMaskResource
     */
    public function __construct(
        QuoteIdMaskFactory $quoteIdMaskFactory,
        FinanceCostManagementInterface $financeCostInterface,
        QuoteIdMaskResource $quoteIdMaskResource
    ) {
        $this->quoteIdMaskResource = $quoteIdMaskResource;
        $this->quoteIdMaskFactory = $quoteIdMaskFactory;
        $this->financeCostInterface = $financeCostInterface;
    }

    /**
     * @inheritDoc
     */
    public function saveFinanceCost(
        $cartId,
        FinanceCostInterface $userSelect,
        RulesForFinanceCostInterface $rules
    ) {
        /** @var $quoteIdMask \Magento\Quote\Model\QuoteIdMask */
        $quoteIdMask = $this->quoteIdMaskFactory->create();
        $this->quoteIdMaskResource->load($quoteIdMask, $cartId, 'masked_id');

        return $this->financeCostInterface->saveFinanceCost(
            $quoteIdMask->getQuoteId(),
            $userSelect,
            $rules
        );
    }
}
