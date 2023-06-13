<?php

namespace MercadoPago\AdbPayment\Cron;

use Magento\Payment\Model\Method\Logger;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;
use MercadoPago\AdbPayment\Gateway\Config\ConfigPaymentMethodsOff;
use MercadoPago\AdbPayment\Model\Console\Command\Notification\FetchStatus;

/**
 * CronTab for fetch Payment Methods Off Order Status.
 */
class FetchPaymentMethodsOffOrderStatus
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
     * Constructor.
     *
     * @param Logger            $logger
     * @param FetchStatus       $fetchStatus
     * @param CollectionFactory $collectionFactory
     */
    public function __construct(
        Logger $logger,
        FetchStatus $fetchStatus,
        CollectionFactory $collectionFactory
    ) {
        $this->logger = $logger;
        $this->fetchStatus = $fetchStatus;
        $this->collectionFactory = $collectionFactory;
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
                    ['sop' => 'sales_order_payment'],
                    'main_table.entity_id = sop.parent_id',
                    ['method']
                )
                ->where('sop.method = ?', ConfigPaymentMethodsOff::METHOD);

        foreach ($orders as $order) {
            $orderId = $order->getEntityId();

            $this->logger->debug([
                'fetch'   => 'Fetch Status Payment Methods Off for Order Id '.$orderId,
            ]);

            $this->fetchStatus->fetch($orderId);
        }
    }
}
