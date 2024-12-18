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
use MercadoPago\AdbPayment\Model\Order\ValidateUpdateStatus\ValidateFactory;
use MercadoPago\AdbPayment\Model\Order\UpdatePayment;

/**
 * Class Mercado Pago Index.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
abstract class MpIndex extends Action
{
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
     * @var UpdatePayment
     */
    protected $updatePayment;

    /**
     * MP Status Approved - Value.
     */
    public const MP_STATUS_APPROVED = 'approved';

    /**
     * MP Status Cancelled - Value.
     */
    public const MP_STATUS_CANCELLED = 'cancelled';

    /**
     * MP Status Pending - Value.
     */
    public const MP_STATUS_PENDING = 'pending';

    /**
     * MP Status Charged Back - Value.
     */
    public const MP_STATUS_CHARGED_BACK = 'charged_back';

    /**
     * MP Status Refunded - Value.
     */
    public const MP_STATUS_REFUNDED = 'refunded';

    /**
     * MP Status In Mediation - Value.
     */
    public const MP_STATUS_IN_MEDIATION = 'in_mediation';

    /**
     * MP Status In Rejected - Value.
     */
    public const MP_STATUS_REJECTED = 'rejected';

    /**
     * Adobe Status Pending - Value.
     */
    public const ADOBE_STATUS_PENDING = 'pending';

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
     * @param UpdatePayment                  $updatePayment
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
        UpdatePayment $updatePayment
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
        $this->updatePayment = $updatePayment;
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
            'action'    => 'notification',
            'payload'   => $response
        ]);

        $storeId = isset($mercadopagoData["payments_metadata"]["store_id"]) ? $mercadopagoData["payments_metadata"]["store_id"] : 1;
        $notificationId = $mercadopagoData['notification_id'];

        return $this->mpApiNotification->get($notificationId, $storeId);
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
            $result = [
                'isInvalid' => !$response->getIsValid(),
                'code'      => $response->getCode(),
                'msg'       => $response->getMessage(),
            ];
        }

        return $result;
    }
}
