<?php
/**
 * Copyright © MercadoPago. All rights reserved.
 *
 * @author  Mercado Pago
 * @license See LICENSE for license details.
 */

namespace MercadoPago\AdbPayment\Controller\Notification;

use Exception;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\ResultInterface;
use MercadoPago\AdbPayment\Controller\MpIndex;
use MercadoPago\AdbPayment\Model\Notification\Refund\Order\RefundOrder;

/**
 * Controller Notification Order - Notification receiver for Order API.
 * 
 * Now extends directly from MpIndex which handles both Payment and Order API.
 */
class Order extends MpIndex implements CsrfAwareActionInterface
{
    /**
     * Create Csrf Validation Exception.
     *
     * @param RequestInterface $request
     *
     * @return null
     */
    public function createCsrfValidationException(RequestInterface $request): ?InvalidRequestException
    {
        if ($request) {
            return null;
        }
    }

    /**
     * Validate For Csrf.
     *
     * @param RequestInterface $request
     *
     * @return bool true
     */
    public function validateForCsrf(RequestInterface $request): bool
    {
        if ($request) {
            return true;
        }
    }

    /**
     * Execute.
     *
     * @return ResultInterface
     */
    public function execute()
    {
        if (!$this->getRequest()->isPost()) {
            return $this->createResult(
                404,
                [
                    'error'   => 404,
                    'message' => __('You should not be here...'),
                ]
            );
        }

        try {
            $notificationData = $this->loadNotificationData();
        } catch (Exception $e) {
            $this->sendNotificationErrorMetric(500, $e->getMessage());
            return $this->createResult(
                500,
                [
                    'error'   => 500,
                    'message' => $e->getMessage(),
                ]
            );
        }

        return $this->initProcess($notificationData);
    }

    /**
     * Init Process.
     *
     * @param array $notificationData
     *
     * @return ResultInterface
     */
    public function initProcess(array $notificationData)
    {
        $mpTransactionId = $notificationData['notification_id'] ?? null;
        $mpStatus = $notificationData['status'] ?? null;

        if (!$mpTransactionId || !$mpStatus) {
            $this->sendNotificationErrorMetric(422, 'Missing required fields: notification_id or status');
            return $this->createResult(422, [
                'error' => 422,
                'message' => 'Missing required fields: notification_id or status',
            ]);
        }

        $transactions = $this->getTransactionsByTxnId($mpTransactionId);
        
        if (empty($transactions)) {
            $this->sendNotificationErrorMetric(422, 'Nothing to process - Transaction not found');
            return $this->createResult(422, [
                'error' => 422,
                'message' => 'Nothing to process - Transaction not found',
            ]);
        }

        $resultData = [];

        foreach ($transactions as $transaction) {
            $order = $this->getOrderData($transaction->getOrderId());

            try {
                $result = $this->processNotificationByStatus($mpStatus, $order, $mpTransactionId, $notificationData);
                $resultData[] = $result['msg'];

                if ($result['code'] !== 200) {
                    return $this->createResult($result['code'], $resultData);
                }
            } catch (Exception $e) {
                $this->sendNotificationErrorMetric(500, $e->getMessage());
                return $this->createResult(500, [
                    'error' => 500,
                    'message' => $e->getMessage(),
                ]);
            }
        }

        return $this->createResult(200, $resultData);
    }

    /**
     * Get transactions by transaction ID
     *
     * @param string $mpTransactionId
     * @return array
     * @throws Exception
     */
    private function getTransactionsByTxnId($mpTransactionId): array
    {
        try {
            $searchCriteria = $this->searchCriteria
                ->addFilter('txn_id', $mpTransactionId)
                ->setPageSize(1)
                ->create();

            return $this->transaction->getList($searchCriteria)->getItems();
        } catch (Exception $exc) {
            $this->logger->debug([
                'action' => 'error_getting_transactions',
                'txn_id' => $mpTransactionId,
                'error' => $exc->getMessage()
            ]);
            throw $exc;
        }
    }

    /**
     * Process notification based on status.
     *
     * @param string $mpStatus
     * @param mixed $order
     * @param string $mpTransactionId
     * @param array|null $notificationData
     * @return array
     */
    private function processNotificationByStatus($mpStatus, $order, $mpTransactionId, $notificationData): array
    {
        if ($mpStatus === 'failed') {
            $this->handleRejectedOrder($order);
        }

        if ($mpStatus === 'refunded' && $notificationData !== null) {
            return $this->handleRefundedOrder($order, $notificationData);
        }

        return $this->processNotification($mpStatus, $order, $mpTransactionId);
    }

    /**
     * Handle rejected order by cancelling it
     *
     * @param mixed $order
     * @return void
     * @throws Exception
     */
    private function handleRejectedOrder($order): void
    {
        try {
            $order->cancel()->save();
            $this->sendNotificationRejectedSuccessMetric();
        } catch (Exception $e) {
            $this->sendNotificationRejectedErrorMetric($e->getMessage());
            throw new Exception(
                "Error cancelling order with payment rejected: " . $e->getMessage()
            );
        }
    }

    /**
     * Handle refunded order by processing refund notification.
     *
     * @param mixed $order
     * @param array $respData
     * @return array
     */
    private function handleRefundedOrder($order, array $respData): array
    {
        try {
            $source = $this->extractSourceFromNotification($respData);
            
            $this->sendRefundNotificationOriginMetric($source);
            
            $refund = new RefundOrder(
                $this->config,
                $this->notifierPool,
                $this->creditMemoFactory,
                $this->creditMemoService,
                $order,
                $this->logger,
                $this->updatePayment,
                $respData,
                $this->metricsClient
            );

            return $refund->process();
        } catch (Exception $e) {
            $this->logger->debug([
                'action' => 'error_processing_refund',
                'order_id' => $order->getIncrementId(),
                'error' => $e->getMessage()
            ]);

            $this->sendNotificationErrorMetric(500, 'Error processing refund: ' . $e->getMessage());

            return [
                'code' => 500,
                'msg' => 'Error processing refund: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Process Notification for non-refund statuses.
     *
     * @param string $mpStatus MercadoPago status
     * @param mixed $order
     * @param string $mpTransactionId
     *
     * @return array
     */
    public function processNotification(
        $mpStatus,
        $order,
        $mpTransactionId
    ) {
        $isNotApplicable = $this->filterInvalidNotification($mpStatus, $order, null);

        if ($isNotApplicable['isInvalid']) {
            $errorCode = $isNotApplicable['code'] ?? 'validation_failed';
            if ($errorCode !== 200 && $errorCode !== 'validation_failed') {
                $errorMessage = is_array($isNotApplicable['msg']) 
                    ? json_encode($isNotApplicable['msg']) 
                    : (string)($isNotApplicable['msg'] ?? 'Invalid notification');
                $this->sendNotificationErrorMetric($errorCode, $errorMessage);
            }
            
            return $isNotApplicable;
        }
        $order = $this->fetchStatus->fetch($order->getEntityId(), $mpTransactionId);

        $result = [
            'code'  => 200,
            'msg'   => [
                'order'     => $order->getIncrementId(),
                'state'     => $order->getState(),
                'status'    => $order->getStatus(),
            ],
        ];

        $this->sendNotificationSuccessMetric($order->getStatus());

        return $result;
    }

    /**
     * Send metric for successful notification processing.
     *
     * @param string $orderStatus Order status (approved, processing, rejected, etc.)
     * @return void
     */
    private function sendNotificationSuccessMetric(string $orderStatus): void
    {
        try {
            $this->metricsClient->sendEvent(
                'magento_pix_notification_success',
                'success',
                'origin_mercadopago'
            );
        } catch (\Throwable $e) {
            $this->logger->debug(['metric_error' => $e->getMessage()]);
        }
    }

    /**
     * Send metric for notification processing error.
     *
     * @param string|int $errorCode Error code (422, 500, validation_failed, etc.)
     * @param string $errorMessage Descriptive error message
     * @return void
     */
    private function sendNotificationErrorMetric($errorCode, string $errorMessage): void
    {
        try {
            $this->metricsClient->sendEvent(
                'magento_pix_notification_error',
                (string)$errorCode,
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

    /**
     * Send metric for successful rejected order cancellation.
     *
     * @return void
     */
    private function sendNotificationRejectedSuccessMetric(): void
    {
        try {
            $this->metricsClient->sendEvent(
                'magento_pix_notification_rejected_success',
                'success',
                'Order rejected and cancelled successfully'
            );
        } catch (\Throwable $e) {
            $this->logger->debug(['metric_error' => $e->getMessage()]);
        }
    }

    /**
     * Send metric for error when cancelling rejected order.
     *
     * @param string $errorMessage Error message
     * @return void
     */
    private function sendNotificationRejectedErrorMetric(string $errorMessage): void
    {
        try {
            $this->metricsClient->sendEvent(
                'magento_pix_notification_rejected_error',
                'error',
                'Error cancelling order with payment rejected: ' . $errorMessage
            );
        } catch (\Throwable $e) {
            $this->logger->error([
                'metric_error' => $e->getMessage(),
                'metric_error_class' => get_class($e),
                'metric_error_trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Extract source from notification data.
     *
     * @param array $notificationData
     * @return string
     */
    private function extractSourceFromNotification(array $notificationData): string
    {
        $paymentsDetails = $notificationData['payments_details'] ?? [];
        
        foreach ($paymentsDetails as $paymentData) {
            $refunds = $paymentData['refunds'] ?? [];
            
            foreach ($refunds as $refund) {
                if ($refund['notifying'] ?? false) {
                    return $refund['source'] ?? '';
                }
            }
        }
        
        return '';
    }

    /**
     * Send metric for refund notification origin.
     *
     * @param string $source Source from notification (mp-op-pp-order-api, other, etc.)
     * @return void
     */
    private function sendRefundNotificationOriginMetric(string $source): void
    {
        try {
            // Map source to origin name
            $origin = $this->mapSourceToOrigin($source);
            
            $this->metricsClient->sendEvent(
                'magento_pix_refund_notification_origin',
                $origin,
                "Refund notification received from: {$origin} (source: {$source})"
            );
        } catch (\Throwable $e) {
            $this->logger->error([
                'metric_error' => $e->getMessage(),
                'metric_error_class' => get_class($e),
                'metric_error_trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Map source to origin name.
     *
     * @param string $source
     * @return string
     */
    private function mapSourceToOrigin(string $source): string
    {
        if ($source === 'mp-op-pp-order-api') {
            return 'Magento';
        }
        
        if ($source === 'other') {
            return 'Panel_Mercado_Pago';
        }
        
        // Fallback: return source as-is if not mapped
        return $source;
    }
}
