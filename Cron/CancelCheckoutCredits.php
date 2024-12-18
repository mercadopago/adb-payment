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
use MercadoPago\AdbPayment\Gateway\Config\ConfigCheckoutCredits;
use MercadoPago\AdbPayment\Model\Console\Command\Notification\FetchStatus;
use MercadoPago\AdbPayment\Cron\CancelCheckoutPro as CancelCheckoutPro;

/**
 * CronTab for cancel Checkout Credits.
 */
class CancelCheckoutCredits extends CancelCheckoutPro
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
     * @var ConfigCheckoutCredits
     */
    protected $configCheckoutCredits;

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
        ConfigCheckoutCredits $configCheckoutCredits,
        CollectionFactory $collectionFactory,
        ResourceConnection $resource
    ) {
        $this->logger = $logger;
        $this->fetchStatus = $fetchStatus;
        $this->configCheckoutCredits = $configCheckoutCredits;
        $this->collectionFactory = $collectionFactory;
        $this->resource = $resource;
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
            ->where(
                new \Zend_Db_Expr(
                    "sop.method = ?
                        AND TIME_TO_SEC(
                            TIMEDIFF(CURRENT_TIMESTAMP(),
                                STR_TO_DATE(
                                    REPLACE(
                                        SUBSTRING_INDEX(
                                            JSON_UNQUOTE(JSON_EXTRACT(sop.additional_information, '$.date_of_expiration')),
                                            '.',
                                            1
                                        ),
                                        'T', ' '
                                    ),
                                    '%Y-%m-%d %H:%i:%s'
                                )
                            )
                        ) >= 0"
                ), ConfigCheckoutCredits::METHOD);

        foreach ($orders as $order) {
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
            $this->logger->debug([
                'fetch'   => 'Cancel Order Id ' . $orderId,
            ]);
        }
    }
}
