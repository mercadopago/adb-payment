<?php
/**
 * Copyright © MercadoPago. All rights reserved.
 *
 * @author      Mercado Pago
 * @license     See LICENSE for license details.
 */

namespace MercadoPago\AdbPayment\Gateway\Data\Order;

/**
 * Factory class for @see \MercadoPago\AdbPayment\Gateway\Data\Order\AddressAdapter.
 */
class AddressAdapterFactory
{
    /**
     * Object Manager instance.
     *
     * @var \Magento\Framework\ObjectManagerInterface
     */
    protected $objectManager = null;

    /**
     * Instance name to create.
     *
     * @var string
     */
    protected $instanceName = null;

    /**
     * Factory constructor.
     *
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     * @param string                                    $instanceName
     */
    public function __construct(
        \Magento\Framework\ObjectManagerInterface $objectManager,
        $instanceName = AddressAdapter::class
    ) {
        $this->objectManager = $objectManager;
        $this->instanceName = $instanceName;
    }

    /**
     * Create class instance with specified parameters.
     *
     * @param array $data
     *
     * @return \MercadoPago\AdbPayment\Gateway\Data\Order\AddressAdapter
     */
    public function create(array $data = [])
    {
        return $this->objectManager->create($this->instanceName, $data);
    }
}
