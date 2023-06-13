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
use MercadoPago\AdbPayment\Model\Console\Command\AbstractModel;

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
     * @var Order
     */
    protected $order;

    /**
     * @param Logger $logger
     * @param Order  $order
     */
    public function __construct(
        Logger $logger,
        Order $order
    ) {
        parent::__construct(
            $logger
        );
        $this->order = $order;
    }

    /**
     * Command Fetch.
     *
     * @param int $orderId
     * @param string $notificationId
     *
     * @return Order $order
     */
    public function fetch($orderId, $notificationId)
    {
        $this->writeln('Init Fetch Status');
        /** @var Order $order */
        $order = $this->order->load($orderId);

        $payment = $order->getPayment();

        $additionalData = (array('notificationId' => $notificationId));
        $additionalData = (object)$additionalData;

        $payment->setAdditionalData(json_encode($additionalData));

        try {
            $payment->update(true);
        } catch (Exception $exc) {
            $this->writeln('<error>'.$exc->getMessage().'</error>');
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
                $order->getState(),
            )
            .'</info>'
        );

        $order->save();

        $this->writeln(__('Finished'));

        return $order;
    }
}
