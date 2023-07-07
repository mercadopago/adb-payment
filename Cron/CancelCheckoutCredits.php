<?php

/**
 * Copyright © MercadoPago. All rights reserved.
 *
 * @author      Mercado Pago
 * @license     See LICENSE for license details.
 */

namespace MercadoPago\AdbPayment\Cron;

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
     * Constructor.
     *
     * @param Logger            $logger
     * @param FetchStatus       $fetchStatus
     * @param ConfigCheckoutPro $configCheckoutPro
     * @param CollectionFactory $collectionFactory
     */
    public function __construct(
        Logger $logger,
        FetchStatus $fetchStatus,
        ConfigCheckoutCredits $configCheckoutCredits,
        CollectionFactory $collectionFactory
    ) {
        $this->logger = $logger;
        $this->fetchStatus = $fetchStatus;
        $this->configCheckoutCredits = $configCheckoutCredits;
        $this->collectionFactory = $collectionFactory;
    }

    /**
     * Execute the cron.
     *
     * @return void
     */
    public function execute()
    {
        $expiration = $this->configCheckoutCredits->getExpiredPaymentDate();

        $orders = $this->collectionFactory->create()
            ->addFieldToFilter('state', Order::STATE_NEW)
            ->addAttributeToFilter('created_at', [
                'lteq' => $expiration,
            ]);

        $orders->getSelect()
            ->join(
                ['sop' => 'sales_order_payment'],
                'main_table.entity_id = sop.parent_id',
                ['method']
            )
            ->where('sop.method = ?', ConfigCheckoutCredits::METHOD);

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
