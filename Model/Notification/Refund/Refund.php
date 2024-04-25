<?php
/**
 * Copyright © MercadoPago. All rights reserved.
 *
 * @author      Mercado Pago
 * @license     See LICENSE for license details.
 */

namespace MercadoPago\AdbPayment\Model\Notification\Refund;

use MercadoPago\AdbPayment\Gateway\Config\Config;
use Magento\Framework\Notification\NotifierInterface;
use Magento\Sales\Model\Order\Invoice;
use Magento\Sales\Model\Order\CreditmemoFactory;
use Magento\Sales\Model\Service\CreditmemoService;
use Magento\Sales\Model\Order\Payment\Transaction;
use Magento\Sales\Model\Order;
use Magento\Payment\Model\Method\Logger;
use Magento\Sales\Model\ResourceModel\Order\Creditmemo\Collection as CreditmemoCollection;
use MercadoPago\AdbPayment\Model\Order\UpdatePayment;

abstract class Refund {
    private Config $config;
    private NotifierInterface $notifier;
    private Invoice $invoice;
    private CreditmemoFactory $creditmemoFactory;
    private CreditmemoService $creditmemoService;
    private Order $order;
    private array $mpNotification;
    private array $refundsData = [];
    private Logger $logger;
    private CreditmemoCollection $creditmemoCollection;
    private UpdatePayment $updatePayment;

    public function __construct(
        Config $config,
        NotifierInterface $notifier,
        Invoice $invoice,
        CreditmemoFactory $creditmemoFactory,
        CreditmemoService $creditmemoService,
        Order $order,
        Logger $logger,
        UpdatePayment $updatePayment,
        array $mpNotification
    ) {
        $this->config = $config;
        $this->notifier = $notifier;
        $this->invoice = $invoice;
        $this->creditmemoFactory = $creditmemoFactory;
        $this->creditmemoService = $creditmemoService;
        $this->order = $order;
        $this->logger = $logger;
        $this->mpNotification = $mpNotification;
        $this->creditmemoCollection = $this->order->getCreditmemosCollection();
        $this->updatePayment = $updatePayment;

        $this->prepareRefundsData();
    }

    private function prepareRefundsData(): void
    {
        foreach ($this->mpNotification['payments_details'] as $paymentData) {
            if (!isset($paymentData['refunds'])) {
                continue;
            }

            foreach ($paymentData['refunds'] as $refund) {
                $refundId = $refund['id'];

                foreach ($this->mpNotification['refunds_notifying'] as $notifying) {
                    if ($notifying['id'] === $refundId && isset($notifying['amount'])) {
                        $this->refundsData[$refundId] = new RefundData(
                            $refundId,
                            $refund['notifying'],
                            $notifying['amount'],
                            $refund['status'],
                            $this->mpNotification['notification_id'],
                            $refund['metadata']['origem'] ?? null
                        );
                    }
                }
            }
        }
    }

    public function process(): array
    {
        $results = [];

        $invoices = $this->order->getInvoiceCollection();

        $this->updatePayment->updateInformation($this->order, $this->mpNotification);

        if (count($invoices) == 0) {
            return [
                'code'          => 400,
                'msg'           => 'Order ' . $this->order->getIncrementId() . ' has no invoice to refund',
            ];
        }

        if ($this->order->getState() === \Magento\Sales\Model\Order::STATE_CLOSED) {
            return [
                'code'      => 200,
                'msg'       => 'Refund notification for order ' . $this->order->getIncrementId() . ' already closed.',
            ];
        }

        // store configuration to refund
        $storeId = $this->order->getStoreId();
        if (!$this->config->isApplyRefund($storeId)) {
            return [
                'code'      => 200,
                'msg'       => 'Refund notification process disabled in config',
            ];
        }

        /** @var RefundData $refundData */
        foreach ($this->refundsData as $k => $refundData) {
            if ($refundData->getNotifying() !== true) {
                continue;
            }

            if ($refundData->getOrigin() === 'magento') {
                $results[] = [
                    'code' => 200,
                    'msg' => $this->prepareMessage($refundData, 'Refund created from magento'),
                ];
                continue;
            }
            
            if ($this->hasBeenRefunded($refundData)) {
                unset($this->refundsData[$k]);
                $results[] = [
                    'code' => 200,
                    'msg' => $this->prepareMessage($refundData, 'Refund already refunded. Ignoring it.'),
                ];
                continue;
            }

            if ($refundData->getStatus() !== 'approved') {
                $results[] = [
                    'code' => 200,
                    'msg' => $this->prepareMessage($refundData, 'Refund status not approved. Current status is ' . $refundData->getStatus()),
                ];
                continue;
            }

            $results[] = $this->refund($refundData);
        }

        $statusResponse = 200;
        $msg = [];

        foreach ($results as $r) {
            if ($r['code'] > 299) {
                $statusResponse = 400;
            }

            $msg[] = $r['msg'];
        }

        return [
            'code' => $statusResponse,
            'msg' => implode(', ', $msg)
        ];
    }

    private function hasBeenRefunded(RefundData $refundData): bool
    {
        foreach ($this->creditmemoCollection as $creditMemo) {
            if ($creditMemo->getTransactionId() == $refundData->getId()) {
                return true;
            }
        }

        return false;
    }

    private function refund(RefundData $refundData): array
    {
        try {
            $creditMemo = $this->creditmemoFactory->createByOrder($this->order);

            $payment = $this->order->getPayment();
            $payment->setTransactionId($refundData->getId());
            $payment->setIsTransactionClosed(true);

            if ($refundData->getAmount() < $creditMemo->getBaseGrandTotal()) {
                $creditMemo->setItems([]);
            }

            $payment->addTransaction(Transaction::TYPE_REFUND);
            $this->order->save();

            $creditMemo->setState(1);
            $creditMemo->setBaseGrandTotal($refundData->getAmount());
            $creditMemo->setGrandTotal($refundData->getAmount());
            $creditMemo->addComment(__('Order refunded in Mercado Pago, refunded offline in the store.'));
        
            $this->creditmemoService->refund($creditMemo, false);
            $this->order->addCommentToStatusHistory(__('Order refunded.'));

            $this->notifier->add(1, __('Mercado Pago, refund notification'), __(
                'The order %1, was refunded directly on Mercado Pago, you need to check stock of sold items.',
                $this->order->getIncrementId()
            ));

            return [
                'code' => 200,
                'msg' => $this->prepareMessage($refundData, 'Refunded sucessfull'),
            ];
        } catch (\Exception $e) {
            $this->logger->debug([
                'refund_Id' => $refundData->getId(),
                'exception' => $e->getMessage(), 
                'stack' => $e->getTraceAsString()
            ]);
            return [
                'code' => 400,
                'msg' => $this->prepareMessage($refundData, 'Failed to process refund. ' . $e->getMessage()),
            ];
        }
    }

    private function prepareMessage(RefundData $refundData, string $msg): string
    {
        return 'Notification '. $refundData->getNotificationId() . ' - Refund ' . $refundData->getId() . ': ' . $msg;
    }
}
