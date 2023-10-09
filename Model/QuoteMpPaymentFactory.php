<?php

/**
 * Copyright © MercadoPago. All rights reserved.
 *
 * @author      Mercado Pago
 * @license     See LICENSE for license details.
 */

namespace MercadoPago\AdbPayment\Model;

use Magento\Framework\ObjectManagerInterface;
use MercadoPago\AdbPayment\Model\QuoteMpPayment;

class QuoteMpPaymentFactory
{
    /**
     * @var ObjectManagerInterface
     */
    protected $_objectManager;

    /**
     * @var string
     */
    protected $_instanceName;

    public function __construct(
        ObjectManagerInterface $objectManager,
        $instanceName = QuoteMpPayment::class
    ) {
        $this->_objectManager = $objectManager;
        $this->_instanceName = $instanceName;
    }

    /**
     * Create new QuoteMpPayment resource model
     *
     * @param array $data
     * @return QuoteMpPayment
     */
    public function create(array $data = [])
    {
        return $this->_objectManager->create($this->_instanceName, $data);
    }
}
