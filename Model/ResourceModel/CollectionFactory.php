<?php

/**
 * Copyright © MercadoPago. All rights reserved.
 *
 * @author      Mercado Pago
 * @license     See LICENSE for license details.
 */

namespace MercadoPago\AdbPayment\Model\ResourceModel;

use Magento\Framework\ObjectManagerInterface;
use MercadoPago\AdbPayment\Model\ResourceModel\QuoteMpPayment\Collection;

class CollectionFactory
{
    /**
     * Object Manager instance
     *
     * @var ObjectManagerInterface
     */
    private $objectManager = null;

    /**
     * Instance name to create
     *
     * @var string
     */
    private $instanceName = null;

    /**
     * Factory constructor
     *
     * @param ObjectManagerInterface $objectManager
     * @param string $instanceName
     */
    public function __construct(
        ObjectManagerInterface $objectManager,
        $instanceName = Collection::class
    ) {
        $this->objectManager = $objectManager;
        $this->instanceName = $instanceName;
    }

    /**
     * Create class instance with specified parameters
     *
     * @param int|null $quoteId
     * @return Collection
     */
    public function create(?int $quoteId = null)
    {
        /** @var Collection $collection */
        $collection = $this->objectManager->create($this->instanceName);

        if ($quoteId) {
            $collection->addFieldToFilter('quote_id', $quoteId);
        }

        return $collection;
    }
}
