<?php

/**
 * Copyright © MercadoPago. All rights reserved.
 *
 * @author      Mercado Pago
 * @license     See LICENSE for license details.
 */

namespace MercadoPago\AdbPayment\Controller;

use Exception;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Notification\NotifierInterface as NotifierPool;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\View\Result\PageFactory;
use Magento\Payment\Model\Method\Logger;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\TransactionRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\CreditmemoFactory;
use Magento\Sales\Model\Order\Invoice;
use Magento\Sales\Model\OrderRepository;
use Magento\Sales\Model\Service\CreditmemoService;
use MercadoPago\AdbPayment\Gateway\Config\Config;
use MercadoPago\AdbPayment\Model\Console\Command\Notification\CheckoutProAddChildPayment;
use MercadoPago\AdbPayment\Model\Console\Command\Notification\FetchStatus;
use Magento\Sales\Model\Order\Payment\Transaction;
use MercadoPago\AdbPayment\Model\MPApi\Notification;
use MercadoPago\AdbPayment\Model\MPApi\Order\OrderNotificationGet;
use MercadoPago\AdbPayment\Model\Order\ValidateUpdateStatus\ValidateFactory;
use MercadoPago\AdbPayment\Model\Order\ValidateUpdateStatus\OrderApiStatusMapper;
use MercadoPago\AdbPayment\Model\Order\UpdatePayment;
use MercadoPago\AdbPayment\Model\Metrics\MetricsClient;

/**
 * Class Mercado Pago Index.
 *
 * Handles both Payment API and Order API notifications.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
abstract class MpIndex extends Action
{
    /**
     * Notification type: Order API
     */
    public const NOTIFICATION_TYPE_ORDER = 'pp_order';

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var SearchCriteriaBuilder
     */
    protected $searchCriteria;

    /**
     * @var TransactionRepositoryInterface
     */
    protected $transaction;

    /**
     * @var Json
     */
    protected $json;

    /**
     * @var OrderRepository
     */
    protected $orderRepository;

    /**
     * @var PageFactory
     */
    protected $pageFactory;

    /**
     * @var JsonFactory
     */
    protected $resultJsonFactory;

    /**
     * @var logger
     */
    protected $logger;

    /**
     * @var FetchStatus
     */
    protected $fetchStatus;

    /**
     * @var NotifierPool
     */
    protected $notifierPool;

    /**
     * @var CreditmemoFactory
     */
    protected $creditMemoFactory;

    /**
     * @var CreditmemoService
     */
    protected $creditMemoService;

    /**
     * @var Invoice
     */
    protected $invoice;

    /**
     * @var CheckoutProAddChildPayment
     */
    protected $addChildPayment;

    /**
     * @var Notification
     */
    protected $mpApiNotification;

    /**
     * @var OrderNotificationGet
     */
    protected $orderNotificationGet;

    /**
     * @var UpdatePayment
     */
    protected $updatePayment;

    /**
     * @var MetricsClient
     */
    protected $metricsClient;

    /**
     * @param Config                         $config
     * @param Context                        $context
     * @param Json                           $json
     * @param SearchCriteriaBuilder          $searchCriteria
     * @param TransactionRepositoryInterface $transaction
     * @param OrderRepository                $orderRepository
     * @param PageFactory                    $pageFactory
     * @param JsonFactory                    $resultJsonFactory
     * @param Logger                         $logger
     * @param FetchStatus                    $fetchStatus
     * @param NotifierPool                   $notifierPool
     * @param CreditmemoFactory              $creditMemoFactory
     * @param CreditmemoService              $creditMemoService
     * @param Invoice                        $invoice
     * @param CheckoutProAddChildPayment     $addChildPayment
     * @param Notification                   $mpApiNotification
     * @param OrderNotificationGet           $orderNotificationGet
     * @param UpdatePayment                  $updatePayment
     * @param MetricsClient                  $metricsClient
     *
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        Config $config,
        Context $context,
        Json $json,
        SearchCriteriaBuilder $searchCriteria,
        TransactionRepositoryInterface $transaction,
        OrderRepository $orderRepository,
        PageFactory $pageFactory,
        JsonFactory $resultJsonFactory,
        Logger $logger,
        FetchStatus $fetchStatus,
        NotifierPool $notifierPool,
        CreditmemoFactory $creditMemoFactory,
        CreditmemoService $creditMemoService,
        Invoice $invoice,
        CheckoutProAddChildPayment $addChildPayment,
        Notification $mpApiNotification,
        OrderNotificationGet $orderNotificationGet,
        UpdatePayment $updatePayment,
        MetricsClient $metricsClient
    ) {
        parent::__construct($context);
        $this->config = $config;
        $this->json = $json;
        $this->searchCriteria = $searchCriteria;
        $this->transaction = $transaction;
        $this->orderRepository = $orderRepository;
        $this->pageFactory = $pageFactory;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->logger = $logger;
        $this->fetchStatus = $fetchStatus;
        $this->notifierPool = $notifierPool;
        $this->creditMemoFactory = $creditMemoFactory;
        $this->creditMemoService = $creditMemoService;
        $this->invoice = $invoice;
        $this->addChildPayment = $addChildPayment;
        $this->mpApiNotification = $mpApiNotification;
        $this->orderNotificationGet = $orderNotificationGet;
        $this->updatePayment = $updatePayment;
        $this->metricsClient = $metricsClient;
    }

    /**
     * Get Order Data.
     *
     * @param string $orderId
     *
     * @return OrderRepository|ResultInterface
     */
    public function getOrderData($orderId)
    {
        try {
            /** @var OrderRepository $order */
            $order = $this->orderRepository->get($orderId);
        } catch (Exception $exc) {
            return $this->createResult(
                500,
                [
                    'error'   => 500,
                    'message' => $exc->getMessage(),
                ]
            );
        }

        return $order;
    }

    /**
     * Create Result.
     *
     * @param int   $statusCode
     * @param array $data
     *
     * @return ResultInterface
     */
    public function createResult($statusCode, $data)
    {
        /** @var JsonFactory $resultPage */
        $resultPage = $this->resultJsonFactory->create();
        $resultPage->setHttpResponseCode($statusCode);
        $resultPage->setData($data);

        return $resultPage;
    }

    /**
     * Filter Invalid Notification.
     *
     * @param string          $mpStatus
     * @param OrderRepository $order
     * @param string|null     $mpAmountRefound
     *
     * @return array
     */
    public function filterInvalidNotification(
        $mpStatus,
        $order,
        $refundId,
        $mpAmountRefound = null,
        $origin = null
    ) {
        $result = [];

        if (!$order->getEntityId()) {
            $result = [
                'isInvalid' => true,
                'code'      => 406,
                'msg'       => __('Order not found.'),
            ];

            return $result;
        }

        // Map Order API status to Payment API status if needed
        $mpStatus = $this->mapOrderApiStatusIfNeeded($mpStatus);

        $statusRuleResult = $this->analyzeMpStatusAndAdobeStatus($mpStatus, $order->getStatus());

        if (isset($statusRuleResult['isInvalid'])) {
            return $statusRuleResult;
        }

        $result = [
            'isInvalid' => false,
        ];

        return $result;
    }

    /**
     * Map Order API status to Payment API status if needed.
     *
     * Handles Order API statuses, Payment API statuses, and unknown statuses.
     * Metrics are sent only for truly unknown statuses.
     *
     * @param string $status Status to map
     * @return string Mapped status (or original if not Order API status)
     */
    protected function mapOrderApiStatusIfNeeded(string $status): string
    {
        $mappedStatus = OrderApiStatusMapper::mapToPaymentApiStatus($status, $this->metricsClient);

        // Log when Order API status was mapped
        if ($mappedStatus !== $status) {
            $this->logger->debug([
                'action' => 'order_api_status_mapped',
                'original_status' => $status,
                'mapped_status' => $mappedStatus
            ]);
        }

        return $mappedStatus;
    }

    /**
     * Checkout Pro Add Child Information.
     *
     * @param int    $orderId
     * @param string $childId
     *
     * @return void
     */
    public function checkoutProAddChildInformation(
        $orderId,
        $childId
    ) {
        $this->addChildPayment->add($orderId, $childId);
    }

    protected function loadNotificationData(): array
    {
        $response = $this->getRequest()->getContent();
        $mercadopagoData = $this->json->unserialize($response);

        $this->logger->debug([
            'action'    => 'notification-received',
            'payload'   => $response
        ]);

        $storeId = $this->resolveStoreId($mercadopagoData) ?? 1;
        $notificationId = $mercadopagoData['notification_id'] ?? null;

        $notificationType = $mercadopagoData['transaction_type'] ?? null;

        if ($notificationType === self::NOTIFICATION_TYPE_ORDER) {
            return $this->orderNotificationGet->get($notificationId, $storeId);;
        }

        return $this->mpApiNotification->get($notificationId, $storeId);
    }

    /**
     * Resolve store ID from notification data.
     *
     * Tries to extract store_id from notification payload first,
     * then falls back to searching in Magento database by transaction ID.
     *
     * @param array $notificationData
     * @return string|null
     */
    protected function resolveStoreId(array $notificationData): ?string
    {
        // Try to get store_id from payments_metadata, fallback to database search
        return $notificationData['payments_metadata']['store_id']
            ?? $this->getStoreIdByTransactionId($notificationData);
    }

    /**
     * Get store ID by MercadoPago transaction ID.
     *
     * Searches in payment transactions table for the MP transaction.
     *
     * @param array $notificationData
     * @return string|null
     */
    protected function getStoreIdByTransactionId(array $notificationData): ?string
    {
        $transactionId = $this->extractTransactionId($notificationData);

        if (empty($transactionId)) {
            return null;
        }

        try {
            $searchCriteria = $this->searchCriteria
                ->addFilter('txn_id', $transactionId)
                ->setPageSize(1)
                ->create();

            $transactions = $this->transaction->getList($searchCriteria)->getItems();

            if (empty($transactions)) {
                return null;
            }

            /** @var Transaction $transaction */
            $transaction = reset($transactions);
            $orderId = $transaction->getOrderId();

            if (!$orderId) {
                return null;
            }

            $order = $this->orderRepository->get($orderId);

            return $order->getStoreId();

        } catch (Exception $e) {
            $this->logger->debug([
                'action' => 'error_getting_store_by_transaction_id',
                'txn_id' => $transactionId,
                'error' => $e->getMessage()
            ]);

            return null;
        }
    }

    /**
     * Extract transaction ID from notification data.
     *
     * Handles different notification formats (Payment API vs Order API).
     *
     * @param array $notificationData
     * @return string|null
     */
    protected function extractTransactionId(array $notificationData): ?string
    {
        $transactionType = $notificationData['transaction_type'] ?? null;

        // Order API uses notification_id, Payment API uses transaction_id
        return $transactionType === self::NOTIFICATION_TYPE_ORDER
            ? $notificationData['notification_id'] ?? null
            : $notificationData['transaction_id'] ?? null;
    }

    protected function analyzeMpStatusAndAdobeStatus(
        String $mpStatus,
        String $adobeStatus
    ) {
        $validate = ValidateFactory::createValidate($adobeStatus);

        $response = $validate->verifyStatus($mpStatus);

        $this->logger->debug([
            'action'    => 'notification',
            'isInvalid' => !$response->getIsValid(),
            'payload'   => $response->getMessage(),
        ]);

        $result = [];
        if (!$response->getIsValid()) {
            // Send metric for failed validation
            $this->sendValidationFailedMetric(
                $mpStatus,
                $adobeStatus,
                $response->getCode(),
                $response->getMessage()
            );

            $result = [
                'isInvalid' => !$response->getIsValid(),
                'code'      => $response->getCode(),
                'msg'       => $response->getMessage(),
            ];
        } else {
            // Send metric for successful validation
            $this->sendValidationSuccessMetric($mpStatus, $adobeStatus);
        }

        return $result;
    }

    /**
     * Send metric for successful validation.
     *
     * @param string $mpStatus MercadoPago status
     * @param string $adobeStatus Adobe/Magento status
     * @return void
     */
    private function sendValidationSuccessMetric(string $mpStatus, string $adobeStatus): void
    {
        try {
            $this->metricsClient->sendEvent(
                'magento_pix_notification_validation_success',
                'valid',
                "Status validation passed - MP: {$mpStatus}, Adobe: {$adobeStatus}"
            );
        } catch (\Throwable $e) {
            $this->logger->debug(['metric_error' => $e->getMessage()]);
        }
    }

    /**
     * Send metric for failed validation.
     *
     * @param string $mpStatus MercadoPago status
     * @param string $adobeStatus Adobe/Magento status
     * @param int|string $errorCode Validation error code
     * @param string $errorMessage Validation error message
     * @return void
     */
    private function sendValidationFailedMetric(
        string $mpStatus,
        string $adobeStatus,
        $errorCode,
        string $errorMessage
    ): void {
        try {
            $this->metricsClient->sendEvent(
                'magento_pix_notification_validation_failed',
                (string)$errorCode,
                "Status validation failed - MP: {$mpStatus}, Adobe: {$adobeStatus}, Error: {$errorMessage}"
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
