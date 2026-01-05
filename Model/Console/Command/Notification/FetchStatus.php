<?php
/**
 * Copyright © MercadoPago. All rights reserved.
 *
 * @author      Mercado Pago
 * @license     See LICENSE for license details.
 */

namespace MercadoPago\AdbPayment\Model\Console\Command\Notification;

use Exception;
use Magento\Payment\Model\Method\Logger;
use Magento\Sales\Model\Order;
use Magento\Sales\Api\OrderRepositoryInterface;
use MercadoPago\AdbPayment\Model\Console\Command\AbstractModel;
use MercadoPago\AdbPayment\Model\Metrics\MetricsClient;

/**
 * Model for Command lines to capture Status on Mercado Pago.
 */
class FetchStatus extends AbstractModel
{
    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @var OrderRepositoryInterface
     */
    protected $orderRepository;

    /**
     * @var MetricsClient
     */
    protected $metricsClient;

    /**
     * @param Logger $logger
     * @param OrderRepositoryInterface $orderRepository
     * @param MetricsClient $metricsClient
     */
    public function __construct(
        Logger $logger,
        OrderRepositoryInterface $orderRepository,
        MetricsClient $metricsClient
    ) {
        parent::__construct(
            $logger
        );
        $this->orderRepository = $orderRepository;
        $this->metricsClient = $metricsClient;
    }

    /**
     * Command Fetch.
     *
     * @param int $orderId
     * @param string $notificationId
     *
     * @return Order $order
     */
    public function fetch($orderId, ?string $notificationId = null)
    {
        $this->writeln('Init Fetch Status');
        /** @var Order $order */
        $order = $this->orderRepository->get($orderId);

        $payment = $order->getPayment();

        if( isset($notificationId)){
            $additionalData = (array('notificationId' => $notificationId));
            $additionalData = (object)$additionalData;
    
            $payment->setAdditionalData(json_encode($additionalData));
        }

        try {
            $payment->update(true);
        } catch (Exception $exc) {
            $this->writeln('<error>'.$exc->getMessage().'</error>');
            $errorMessage = 'Failed to sync order status. Order ID: ' . $orderId . 
                           ', Increment ID: ' . $order->getIncrementId() . 
                           ', Error: ' . $exc->getMessage();
            $this->sendSyncStatusErrorMetric($errorMessage);
        }
        if ($order->getState() === Order::STATE_PAYMENT_REVIEW) {
            if(
                $order->getStatus() === Order::STATE_CLOSED
                || $order->getStatus() === Order::STATE_PROCESSING
                || $order->getStatus() === Order::STATE_COMPLETE
            ) {
                $order = $payment->getOrder();
                $order->setState($order->getStatus());
            } else {
                $order = $payment->getOrder();
                $order->setState(Order::STATE_NEW);
                $order->setStatus('pending');
            }
        }

        $this->writeln(
            '<info>'.
            __(
                'Order %1 - Increment Id %2 - state %3',
                $orderId,
                $order->getIncrementId(),
                $order->getState()
            )
            .'</info>'
        );

        $this->orderRepository->save($order);

        $this->writeln(__('Finished'));

        return $order;
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
                'magento_sync_status_action',
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
