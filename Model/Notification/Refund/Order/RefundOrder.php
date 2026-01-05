<?php
/**
 * Copyright © MercadoPago. All rights reserved.
 *
 * @author      Mercado Pago
 * @license     See LICENSE for license details.
 */

namespace MercadoPago\AdbPayment\Model\Notification\Refund\Order;

use Exception;
use MercadoPago\AdbPayment\Gateway\Config\Config;
use MercadoPago\AdbPayment\Model\Order\UpdatePayment;
use MercadoPago\AdbPayment\Model\Metrics\MetricsClient;
use Magento\Framework\Notification\NotifierInterface;
use Magento\Payment\Model\Method\Logger;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Creditmemo;
use Magento\Sales\Model\Order\CreditmemoFactory;
use Magento\Sales\Model\Order\Payment\Transaction;
use Magento\Sales\Model\ResourceModel\Order\Creditmemo\Collection as CreditmemoCollection;
use Magento\Sales\Model\Service\CreditmemoService;
use Magento\Framework\Notification\MessageInterface;

/**
 * Process refund notifications from Order API.
 */
class RefundOrder
{
    /**
     * Refund status processed.
     */
    public const STATUS_PROCESSED = 'processed';

    /**
     * Refund status processing (pending).
     */
    public const STATUS_PROCESSING = 'processing';

    /**
     * Refund status failed.
     */
    public const STATUS_FAILED = 'failed';

    /**
     * @var Config
     */
    private $config;

    /**
     * @var NotifierInterface
     */
    private $notifier;

    /**
     * @var CreditmemoFactory
     */
    private $creditmemoFactory;

    /**
     * @var CreditmemoService
     */
    private $creditmemoService;

    /**
     * @var Order
     */
    private $order;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var UpdatePayment
     */
    private $updatePayment;

    /**
     * @var array
     */
    private $mpNotification;

    /**
     * @var CreditmemoCollection
     */
    private $creditmemoCollection;

    /**
     * @var RefundOrderData[]
     */
    private $refundsData = [];

    /**
     * @var MetricsClient
     */
    private $metricsClient;

    /**
     * @param Config $config
     * @param NotifierInterface $notifier
     * @param CreditmemoFactory $creditmemoFactory
     * @param CreditmemoService $creditmemoService
     * @param Order $order
     * @param Logger $logger
     * @param UpdatePayment $updatePayment
     * @param array $mpNotification
     * @param MetricsClient $metricsClient
     */
    public function __construct(
        Config $config,
        NotifierInterface $notifier,
        CreditmemoFactory $creditmemoFactory,
        CreditmemoService $creditmemoService,
        Order $order,
        Logger $logger,
        UpdatePayment $updatePayment,
        array $mpNotification,
        MetricsClient $metricsClient
    ) {
        $this->config = $config;
        $this->notifier = $notifier;
        $this->creditmemoFactory = $creditmemoFactory;
        $this->creditmemoService = $creditmemoService;
        $this->order = $order;
        $this->logger = $logger;
        $this->updatePayment = $updatePayment;
        $this->mpNotification = $mpNotification;
        $this->metricsClient = $metricsClient;
        $this->creditmemoCollection = $this->order->getCreditmemosCollection();

        $this->prepareRefundsData();
    }

    /**
     * Prepare refunds data from Order API notification.
     * @return void
     */
    private function prepareRefundsData(): void
    {
        $paymentsDetails = $this->mpNotification['payments_details'] ?? [];
        $notificationId = $this->mpNotification['notification_id'] ?? '';

        foreach ($paymentsDetails as $paymentData) {
            $refunds = $paymentData['refunds'] ?? [];

            foreach ($refunds as $refundKey => $refund) {
                if (!($refund['notifying'] ?? false)) {
                    continue;
                }

                $references = $refund['references'] ?? [];
                $refundId = $this->extractRefundId($references, (string) $refundKey);

                $status = $refund['status'] ?? null;
                $amount = $refund['amount'] ?? 0;
                $source = $refund['source'] ?? '';

                if ($amount > 0 && $status) {
                    $this->refundsData[$refundId] = new RefundOrderData(
                        $refundId,
                        $amount,
                        $status,
                        $notificationId,
                        $source
                    );
                }
            }
        }
    }

    /**
     * Extract refund ID from references array.
     *
     * @param array $references
     * @param string $fallback
     * @return string
     */
    private function extractRefundId(array $references, string $fallback): string
    {
        $refundOrderId = $references['refund_order_id'] ?? '';
        if (!empty($refundOrderId)) {
            return $refundOrderId;
        }

        $refundPaymentId = $references['refund_payment_id'] ?? '';
        if (!empty($refundPaymentId)) {
            return $refundPaymentId;
        }

        return $fallback;
    }

    /**
     * Process refund notifications.
     *
     * @return array
     */
    public function process(): array
    {
        $results = [];
        $invoices = $this->order->getInvoiceCollection();

        if (count($invoices) == 0) {
            $errorMessage = 'Order ' . $this->order->getIncrementId() . ' has no invoice to refund';
            $this->sendRefundErrorMetric('400', $errorMessage);
            return [
                'code' => 400,
                'msg'  => $errorMessage,
            ];
        }

        $this->updatePayment->updateInformation($this->order, $this->mpNotification);

        if ($this->order->getState() === Order::STATE_CLOSED) {
            return [
                'code' => 200,
                'msg'  => 'Refund notification for order ' . $this->order->getIncrementId() . ' already closed. Status updated.',
            ];
        }

        $storeId = $this->order->getStoreId();
        if (!$this->config->isApplyRefund($storeId)) {
            return [
                'code' => 200,
                'msg'  => 'Refund notification process disabled in config',
            ];
        }

        /** @var RefundOrderData $refundData */
        foreach ($this->refundsData as $refundId => $refundData) {

            if ($refundData->isFailed()) {
                $results[] = $this->handleFailedRefund($refundData);
                continue;
            }

            if ($refundData->getSource() === 'mp-op-pp-order-api') {
                $existingCreditmemo = $this->findCreditmemoByRefundId($refundData->getId());
                if ($existingCreditmemo) {
                    $results[] = $this->handleExistingCreditmemo($existingCreditmemo, $refundData);
                    continue;
                }
            }

            $existingCreditmemo = $this->findCreditmemoByRefundId($refundData->getId());

            if ($existingCreditmemo) {
                $results[] = $this->handleExistingCreditmemo($existingCreditmemo, $refundData);
                continue;
            }

            if ($refundData->isProcessed()) {
                $results[] = $this->createRefund($refundData);
            }
        }

        return $this->buildFinalResult($results);
    }

    /**
     * Handle failed refund notification.
     *
     * @param RefundOrderData $refundData
     * @return array
     */
    private function handleFailedRefund(RefundOrderData $refundData): array
    {
        $existingCreditmemo = $this->findCreditmemoByRefundId($refundData->getId());

        if ($existingCreditmemo && $existingCreditmemo->getState() == Creditmemo::STATE_OPEN) {
            $existingCreditmemo->setState(Creditmemo::STATE_CANCELED);
            $existingCreditmemo->addComment(__('Refund failed in Mercado Pago.'));
            $existingCreditmemo->save();

            return [
                'code' => 200,
                'msg'  => $this->prepareMessage($refundData, 'Refund failed. CreditMemo canceled.'),
            ];
        }

        return [
            'code' => 200,
            'msg'  => $this->prepareMessage($refundData, 'Refund failed. Ignoring.'),
        ];
    }

    /**
     * Handle existing creditMemo based on its state.
     *
     * @param Creditmemo $creditmemo
     * @param RefundOrderData $refundData
     * @return array
     */
    private function handleExistingCreditmemo(Creditmemo $creditmemo, RefundOrderData $refundData): array
    {
        $currentState = $creditmemo->getState();

        if ($currentState == Creditmemo::STATE_REFUNDED) {
            return [
                'code' => 200,
                'msg'  => $this->prepareMessage($refundData, 'Refund already processed. Ignoring.'),
            ];
        }

        if ($currentState == Creditmemo::STATE_CANCELED) {
            return [
                'code' => 200,
                'msg'  => $this->prepareMessage($refundData, 'CreditMemo was canceled. Ignoring.'),
            ];
        }

        if ($currentState == Creditmemo::STATE_OPEN) {
            if ($refundData->isProcessed()) {
                $creditmemo->setState(Creditmemo::STATE_REFUNDED);
                $creditmemo->addComment(__('Refund confirmed by Mercado Pago webhook.'));
                $creditmemo->save();

                return [
                    'code' => 200,
                    'msg'  => $this->prepareMessage($refundData, 'CreditMemo updated to REFUNDED.'),
                ];
            }

            return [
                'code' => 200,
                'msg'  => $this->prepareMessage($refundData, 'CreditMemo is OPEN, refund still processing.'),
            ];
        }

        return [
            'code' => 200,
            'msg'  => $this->prepareMessage($refundData, 'CreditMemo in unknown state: ' . $currentState),
        ];
    }

    /**
     * Find creditmemo by refund ID.
     * Searches by transaction_id stored in creditmemo.
     *
     * @param string $refundId
     * @return Creditmemo|null
     */
    private function findCreditmemoByRefundId(string $refundId): ?Creditmemo
    {
        $this->creditmemoCollection->clear()->load();

        foreach ($this->creditmemoCollection as $creditmemo) {
            $transactionId = $creditmemo->getTransactionId();
            
            if ($transactionId === $refundId) {
                return $creditmemo;
            }
        }

        return null;
    }

    /**
     * Create refund (creditmemo) for refund created in Mercado Pago.
     *
     * @param RefundOrderData $refundData
     * @return array
     */
    private function createRefund(RefundOrderData $refundData): array
    {
        try {
            $creditMemo = $this->creditmemoFactory->createByOrder($this->order);
            $payment = $this->order->getPayment();

            $payment->setTransactionId($refundData->getId());
            $payment->setIsTransactionClosed(true);
            $payment->setAdditionalInformation('mp_refund_id', $refundData->getId());

            $parentTransactionId = $this->getParentTransactionId($payment);
            if ($parentTransactionId) {
                $payment->setParentTransactionId($parentTransactionId);
            }

            $refundBalance = $this->order->getTotalPaid() - $this->order->getTotalRefunded();
            $refundAmount = $refundData->getAmount();

            if ($refundAmount < $creditMemo->getBaseGrandTotal() &&
                $refundAmount < $refundBalance
            ) {
                $creditMemo->setItems([]);
            }

            $payment->addTransaction(Transaction::TYPE_REFUND);
            $this->order->save();

            $creditMemo->setState(Creditmemo::STATE_REFUNDED);
            $creditMemo->setBaseGrandTotal($refundAmount);
            $creditMemo->setGrandTotal($refundAmount);
            $creditMemo->addComment(__('Order refunded in Mercado Pago, refunded offline in the store.'));

            $this->creditmemoService->refund($creditMemo, false);
            $this->order->addCommentToStatusHistory(__('Order refunded.'));

            $this->notifier->add(MessageInterface::SEVERITY_CRITICAL, __('Mercado Pago, refund notification'), __(
                    'The order %1, was refunded directly on Mercado Pago, you need to check stock of sold items.',
                    $this->order->getIncrementId()
                ));

            return [
                'code' => 200,
                'msg'  => $this->prepareMessage($refundData, 'Refunded successfully'),
            ];
        } catch (Exception $e) {
            $this->logger->debug([
                'refund_id' => $refundData->getId(),
                'exception' => $e->getMessage(),
                'stack'     => $e->getTraceAsString()
            ]);

            $errorMessage = 'Failed to process refund. Order: ' . $this->order->getIncrementId() . 
                           ', Refund ID: ' . $refundData->getId() . ', Error: ' . $e->getMessage();
            $this->sendRefundErrorMetric('400', $errorMessage);

            return [
                'code' => 400,
                'msg'  => $this->prepareMessage($refundData, 'Failed to process refund. ' . $e->getMessage()),
            ];
        }
    }

    /**
     * Build final result from all refund results.
     *
     * @param array $results
     * @return array
     */
    private function buildFinalResult(array $results): array
    {
        if (empty($results)) {
            return [
                'code' => 200,
                'msg'  => 'No refunds to process for order ' . $this->order->getIncrementId(),
            ];
        }

        $statusResponse = 200;
        $messages = [];

        foreach ($results as $r) {
            if ($r['code'] > 299) {
                $statusResponse = 400;
            }
            $messages[] = $r['msg'];
        }

        return [
            'code' => $statusResponse,
            'msg'  => implode(', ', $messages)
        ];
    }

    /**
     * Prepare log message.
     *
     * @param RefundOrderData $refundData
     * @param string $msg
     * @return string
     */
    private function prepareMessage(RefundOrderData $refundData, string $msg): string
    {
        return 'Notification ' . $refundData->getNotificationId() . ' - Refund ' . $refundData->getId() . ': ' . $msg;
    }

    /**
     * Get parent transaction ID
     *
     * @param mixed $payment
     * @return string|null
     */
    private function getParentTransactionId($payment): ?string
    {
        $additionalInfo = $payment->getAdditionalInformation();
        
        return $additionalInfo['mp_order_id'] ?? $additionalInfo['notification_id'] ?? null;
    }

    /**
     * Send metric for refund error.
     *
     * @param string $errorCode HTTP error code or generic error code
     * @param string $errorMessage Error message description
     * @return void
     */
    private function sendRefundErrorMetric(string $errorCode, string $errorMessage): void
    {
        try {
            $this->metricsClient->sendEvent(
                'magento_order_refund_error',
                $errorCode,
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

