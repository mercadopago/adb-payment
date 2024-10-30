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
use MercadoPago\AdbPayment\Gateway\Config\ConfigCheckoutPro;
use MercadoPago\AdbPayment\Model\Console\Command\Notification\FetchStatus;

/**
 * CronTab for cancel Checkout Pro.
 */
class CancelCheckoutPro
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
     * @var ConfigCheckoutPro
     */
    protected $configCheckoutPro;

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
     * @param ConfigCheckoutPro $configCheckoutPro
     * @param CollectionFactory $collectionFactory
     * @param ResourceConnection $resource;
     */
    public function __construct(
        Logger $logger,
        FetchStatus $fetchStatus,
        ConfigCheckoutPro $configCheckoutPro,
        CollectionFactory $collectionFactory,
        ResourceConnection $resource
    ) {
        $this->logger = $logger;
        $this->fetchStatus = $fetchStatus;
        $this->configCheckoutPro = $configCheckoutPro;
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
                ['method', 'additional_information']
            )
            ->where(new \Zend_Db_Expr(
                "sop.method = ? AND TIME_TO_SEC(TIMEDIFF(CURRENT_TIMESTAMP, CAST(JSON_EXTRACT(sop.additional_information, '$.date_of_expiration') AS DATETIME))) >= 0 "
            ), ConfigCheckoutPro::METHOD);


        $this->logger->debug([
            'Total orders to cancel: ' => $orders->count()
        ]);

        $dateRange = [];
        $orderId = null;

        try {
            $this->logger->debug([
                'Cancel Start'
            ]);
            foreach ($orders as $order) {
                $orderAdditionalInformation = json_decode($order->getData('additional_information'));
                $orderId = $order->getEntityId();
                $amount = $order->getTotalDue();
                $baseAmount = $order->getBaseTotalDue();
                $payment = $order->getPayment();
                $payment->setPreparedMessage(__('Order Canceled.'));
                $payment->registerVoidNotification($amount);
                $payment->setIsTransactionApproved(false);
                $payment->setIsTransactionDenied(true);
                $payment->setIsTransactionPending(false);
                $payment->setIsInProcess(true);
                $payment->setIsTransactionClosed(true);
                $payment->setShouldCloseParentTransaction(true);
                $payment->setAmountCanceled($amount);
                $payment->setBaseAmountCanceled($baseAmount);
                $order->cancel();
                $order->save();

                $dateRange[] = $orderAdditionalInformation->date_of_expiration;
                $this->logger->debug([
                    'fetch'   => 'Cancel Order Id ' . $orderId . ' successfully',
                    'order_date_of_expiration' => $orderAdditionalInformation->date_of_expiration
                ]);
            }

            $this->calculateExpirationRange($dateRange);
            $this->logger->debug([
                'Cancel Finish'
            ]);

        } catch (\Exception $e) {
            $this->logger->debug([
                'Cancel order execution error' => $e->getMessage(),
                'Order Id' => $orderId
            ]);
        }
    }

    /**
     * Calculate expiration range date
     *
     * @param array $dateRange
     * @return void
     */
    private function calculateExpirationRange(array $dateRange): void
    {
        usort($dateRange, function ($a, $b) {
            return strtotime($a) - strtotime($b);
        });

        $initialDate = reset($dateRange);
        $endDate = end($dateRange);

        $this->logger->debug([
            'Orders expiration range: ' => "from:  $initialDate to: $endDate"
        ]);
    }
}
