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
use MercadoPago\AdbPayment\Gateway\Config\ConfigPix;
use MercadoPago\AdbPayment\Model\Console\Command\Notification\FetchStatus;
use MercadoPago\AdbPayment\Model\Metrics\MetricsClient;
use Exception;

/**
 * CronTab for fetch Pix Order Status.
 */
class FetchPixOrderStatus
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
     * @var MetricsClient
     */
    protected $metricsClient;

    /**
     * Constructor.
     *
     * @param Logger            $logger
     * @param FetchStatus       $fetchStatus
     * @param CollectionFactory $collectionFactory
     * @param ResourceConnection $resource
     * @param MetricsClient     $metricsClient
     */
    public function __construct(
        Logger $logger,
        FetchStatus $fetchStatus,
        CollectionFactory $collectionFactory,
        ResourceConnection $resource,
        MetricsClient $metricsClient
    ) {
        $this->logger = $logger;
        $this->fetchStatus = $fetchStatus;
        $this->collectionFactory = $collectionFactory;
        $this->resource = $resource;
        $this->metricsClient = $metricsClient;
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
                ->where('sop.method = ?', ConfigPix::METHOD);

        foreach ($orders as $order) {
            $orderId = $order->getEntityId();

            $this->logger->debug([
                'fetch'   => 'Fetch Status Pix for Order Id '.$orderId,
            ]);

            try {
                $this->fetchStatus->fetch($orderId);
            } catch (Exception $e) {
                $errorMessage = 'Failed to sync order status in cron. Order ID: ' . $orderId . 
                               ', Increment ID: ' . $order->getIncrementId() . 
                               ', Error: ' . $e->getMessage();
                $this->sendSyncStatusErrorMetric($errorMessage);
                $this->logger->error([
                    'action' => 'error_syncing_order_status',
                    'order_id' => $orderId,
                    'error' => $e->getMessage()
                ]);
            }
        }
    }

    /**
     * Send metric for order sync status error.
     *
     * @param string $errorMessage Descriptive error message
     * @return void
     */
    private function sendSyncStatusErrorMetric(string $errorMessage): void
    {
        try {
            $this->metricsClient->sendEvent(
                'magento_pix_order_sync_status_action',
                'error',
                $errorMessage
            );
        } catch (\Throwable $e) {
            $this->logger->error([
                'metric_error' => $e->getMessage(),
                'metric_error_class' => get_class($e),
                'metric_error_trace' => $e->getTraceAsString()
            ]);
        }
    }
}
