<?php

/**
 * Copyright © MercadoPago. All rights reserved.
 *
 * @author      Mercado Pago
 * @license     See LICENSE for license details.
 */

namespace MercadoPago\AdbPayment\Model;

use MercadoPago\AdbPayment\Api\Data\QuoteMpPaymentInterface;
use MercadoPago\AdbPayment\Api\QuoteMpPaymentRepositoryInterface;
use MercadoPago\AdbPayment\Model\ResourceModel\QuoteMpPayment as ResourceQuoteMpPayment;
use Magento\Framework\Exception\LocalizedException;

class QuoteMpPaymentRepository implements QuoteMpPaymentRepositoryInterface
{
    /**
     * @var ResourceQuoteMpPayment
     */
    protected $resourceQuoteMpPayment;

    /**
     * @var \MercadoPago\AdbPayment\Model\ResourceModel\CollectionFactory
     */
    protected $collectionFactory;

    /**
     * QuoteMpPaymentRepository constructor.
     *
     * @param ResourceQuoteMpPayment $resourceQuoteMpPayment
     * @param \MercadoPago\AdbPayment\Model\ResourceModel\CollectionFactory $collectionFactory
     */
    public function __construct(
        ResourceQuoteMpPayment $resourceQuoteMpPayment,
        \MercadoPago\AdbPayment\Model\ResourceModel\CollectionFactory $collectionFactory
    ) {
        $this->resourceQuoteMpPayment = $resourceQuoteMpPayment;
        $this->collectionFactory = $collectionFactory;
    }

    /**
     * @inheritdoc
     */
    public function save(QuoteMpPaymentInterface $quoteMpPayment)
    {
        try {
            $this->resourceQuoteMpPayment->save($quoteMpPayment);
        } catch (\Throwable $exception) {
            throw new LocalizedException(__('An error has occurred. Please refresh the page and try again.'));
        }
        return $quoteMpPayment;
    }

    /**
     * @inheritdoc
     */
    public function getByQuoteId($quoteId)
    {
        $quoteMpPaymentCollection = $this->collectionFactory->create($quoteId);
        $quoteMpPayment = $quoteMpPaymentCollection->getLastItem();

        if ($quoteMpPayment->getId()) {
            return $quoteMpPayment;
        }

        return null;
    }

    /**
     * @inheritdoc
     */
    public function deleteByQuoteId($quoteId)
    {
        try {
            $quoteMpPaymentCollection = $this->collectionFactory->create($quoteId);

            foreach ($quoteMpPaymentCollection->getItems() as $item) {
                if ($item->getId()) {
                    $this->resourceQuoteMpPayment->delete($item);
                }
            }
        } catch (\Throwable $e) {
            throw new LocalizedException(__($e->getMessage()));
        }
    }
}
