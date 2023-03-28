<?php
/**
 * Copyright © MercadoPago. All rights reserved.
 *
 * @author      Bruno Elisei <brunoelisei@o2ti.com>
 * @license     See LICENSE for license details.
 */

namespace MercadoPago\PaymentMagento\Model\Console\Command\Notification;

use Exception;
use Magento\Payment\Model\Method\Logger;
use Magento\Sales\Model\Order;
use MercadoPago\PaymentMagento\Model\Console\Command\AbstractModel;

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
     * @return void
     */
    public function fetch($orderId, $notificationId)
    {
        $this->writeln('Init Fetch Status');
        /** @var Order $order */
        $order = $this->order->load($orderId);

        $payment = $order->getPayment();

        $additionalData = json_decode($payment->getAdditionalData(), false);
        $additionalData->notificationId = $notificationId;
        $payment->setAdditionalData(json_encode($additionalData));

        try {
            $payment->update(true);
        } catch (Exception $exc) {
            $this->writeln('<error>'.$exc->getMessage().'</error>');
        }

        if ($order->getState() === Order::STATE_PAYMENT_REVIEW && $order->getStatus() !== Order::STATE_CLOSED) {
            $order = $payment->getOrder();
            $order->setState(Order::STATE_NEW);
            $order->setStatus('pending');
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
    }
}
