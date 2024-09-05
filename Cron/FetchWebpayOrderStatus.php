<?php
/**
 * Copyright © MercadoPago. All rights reserved.
 *
 * @author      Mercado Pago
 * @license     See LICENSE for license details.
 */

namespace MercadoPago\AdbPayment\Cron;

use Magento\Framework\App\ResourceConnection;
use Magento\Payment\Model\Method\Logger;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;
use MercadoPago\AdbPayment\Gateway\Config\ConfigWebpay;
use MercadoPago\AdbPayment\Model\Console\Command\Notification\FetchStatus;

/**
 * CronTab for fetch Webpay Order Status.
 */
class FetchWebpayOrderStatus
{
    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @var FetchStatus
     */
    protected $fetchStatus;

    /**
     * @var CollectionFactory
     */
    protected $collectionFactory;

    /**
     * @var ResourceConnection
     */
    protected $resource;

    /**
     * Constructor.
     *
     * @param Logger            $logger
     * @param FetchStatus       $fetchStatus
     * @param CollectionFactory $collectionFactory
     * @param ResourceConnection $resource;
     */
    public function __construct(
        Logger $logger,
        FetchStatus $fetchStatus,
        CollectionFactory $collectionFactory,
        ResourceConnection $resource
    ) {
        $this->logger = $logger;
        $this->fetchStatus = $fetchStatus;
        $this->collectionFactory = $collectionFactory;
        $this->resource = $resource;
    }

    /**
     * Get sales_order_payment table name.
     *
     * @return string
     */
    public function getSalesOrderPaymentTableName()
    {
        return $this->resource->getTableName('sales_order_payment');
    }

    /**
     * Execute the cron.
     *
     * @return void
     */
    public function execute()
    {
        $orders = $this->collectionFactory->create()
                    ->addFieldToFilter('state', Order::STATE_NEW);

        $orders->getSelect()
                ->join(
                    ['sop' => $this->getSalesOrderPaymentTableName()],
                    'main_table.entity_id = sop.parent_id',
                    ['method']
                )
                ->where('sop.method = ?', ConfigWebpay::METHOD);

        foreach ($orders as $order) {
            $orderId = $order->getEntityId();

            $this->logger->debug([
                'fetch'   => 'Fetch Status Webpay for Order Id '.$orderId,
            ]);

            $this->fetchStatus->fetch($orderId);
        }
    }
}
