<?php
/**
 * Copyright © MercadoPago. All rights reserved.
 *
 * @author      Mercado Pago
 * @license     See LICENSE for license details.
 */

namespace MercadoPago\AdbPayment\Controller\Notification;

use Exception;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Order\Payment\Transaction;
use MercadoPago\AdbPayment\Controller\MpIndex;
use MercadoPago\AdbPayment\Model\Notification\Refund\CheckoutPro as CheckoutProRefund;

/**
 * Controler Notification Checkout Pro - Notification of receivers for Checkout Pro Methods.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class CheckoutPro extends MpIndex implements CsrfAwareActionInterface
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

        $mpAmountRefund = null;

        try {
            $mercadopagoData = $this->loadNotificationData();
        } catch(\Exception $e) {
            return $this->createResult(
                500,
                [
                    'error'   => 500,
                    'message' => $e->getMessage(),
                ]
            );
        }

        $mpTransactionId = $mercadopagoData['preference_id'];
        $mpStatus = $mercadopagoData['status'];
        $childTransactionId = $mercadopagoData['payments_details'][0]['id'];
        $paymentsDetails = $mercadopagoData['payments_details'];

        if ($mpStatus !== 'approved'
            && $mpStatus !== 'refunded'
            && $mpStatus !== 'pending'
            && $mpStatus !== 'cancelled'
            && $mpStatus !== 'complete'
        ) {
            /** @var ResultInterface $result */
            $result = $this->createResult(200, ['empty' => null]);
            return $result;
        }

        if ($mpStatus === 'refunded' && !empty($mercadopagoData["multiple_payment_transaction_id"])) {
            $mpTransactionId = $mercadopagoData["multiple_payment_transaction_id"];
        }

        $searchCriteria = $this->searchCriteria
            ->addFilter('txn_id', $mpTransactionId)
            ->addFilter('txn_type', 'order')
            ->create();

        try {
            /** @var TransactionRepositoryInterface $transactions */
            $transactions = $this->transaction->getList($searchCriteria)->getItems();
        } catch (Exception $exc) {
            return $this->createResult(
                500,
                [
                    'error'   => 500,
                    'message' => $exc->getMessage(),
                ]
            );
        }

        $origin = '';
        $resultData = [];
        $refundId = null;

        foreach ($transactions as $transaction) {
            $order = $this->getOrderData($transaction->getOrderId());
            $payment = $order->getPayment();

            if ($mpStatus === 'refunded') {
                $refund = new CheckoutProRefund(
                    $this->config,
                    $this->notifierPool,
                    $this->invoice,
                    $this->creditMemoFactory,
                    $this->creditMemoService,
                    $order,
                    $this->logger,
                    $this->updatePayment,
                    $mercadopagoData
                );

                $res = $refund->process();
                return $this->createResult($res['code'], $res['msg'] ?? '');
            } else {
                $process = $this->processNotification(
                    $mpTransactionId,
                    $mpStatus,
                    $childTransactionId,
                    $order,
                    $refundId,
                    $mpAmountRefund,
                    $mercadopagoData,
                    $origin
                );

                array_push($resultData, $process['msg']);

                if ($process['code'] !== 200) {
                    /** @var ResultInterface $result */
                    return $this->createResult(
                        $process['code'],
                        $resultData
                    );
                }
            }

            if (sizeof($resultData) === 0) {
                /** @var ResultInterface $result */
                $result = $this->createResult(
                    422,
                    'Nothing to proccess'
                );
                return $result;
            }
        }

        /** @var ResultInterface $result */
        $result = $this->createResult(200, ['empty' => null]);

        return $result;
    }

    /**
     * Update Details.
     *
     * @param array           $mercadopagoData
     * @param OrderRepository $order
     */
    public function updateDetails(
        $mercadopagoData,
        $order
    ) {
        $orderId = $order->getId();
        $childTransctions = $mercadopagoData['payments_details'];

        foreach ($childTransctions as $child) {
            $this->checkoutProAddChildInformation($orderId, $child['id']);
        }
    }

    /**
     * Create Child.
     *
     * @param string          $mpTransactionId
     * @param string          $childTransactionId
     * @param OrderRepository $order
     *
     * @return void
     */
    public function createChild(
        $mpTransactionId,
        $childTransactionId,
        $order
    ) {
        $payment = $order->getPayment();
        $payment->setShouldCloseParentTransaction(true);
        $payment->setParentTransactionId($mpTransactionId);
        $payment->setTransactionId($childTransactionId);
        $payment->setIsTransactionPending(1);
        $payment->setIsTransactionClosed(false);
        $payment->setAuthorizationTransaction($childTransactionId);
        $payment->addTransaction(Transaction::TYPE_AUTH);
        $order->save();
    }

    /**
     * Process Notification.
     *
     * @param string          $mpTransactionId
     * @param string          $mpStatus
     * @param string          $childTransactionId
     * @param OrderRepository $order
     * @param string|null     $mpAmountRefund
     *
     * @return array
     */
    public function processNotification(
        $mpTransactionId,
        $mpStatus,
        $childTransactionId,
        $order,
        $refundId,
        $mpAmountRefund = null,
        $mercadopagoData = null,
        $origin = null
    ) {
        $result = [];

        $isNotApplicable = $this->filterInvalidNotification($mpStatus, $order, $refundId, $mpAmountRefund, $origin);

        if ($isNotApplicable['isInvalid']) {
            if (strcmp($isNotApplicable['msg'], 'Refund notification for order refunded directly in Mercado Pago.') === 0) {
                $this->updateDetails($mercadopagoData, $order);

                $result = [
                    'isInvalid' => true,
                    'code'      => 200,
                    'msg'       => [
                        'error'   => 200,
                        'message' => __('Order not yet closed in Magento.'),
                        'state'   => $order->getState(),
                        'status'   => $order->getStatus(),
                    ],
                ];

                return $result;
            } else if (strcmp($isNotApplicable['msg'], 'Refund notification for order already closed.') === 0) {
                $this->updateDetails($mercadopagoData, $order);

                $result = [
                    'isInvalid' => true,
                    'code'      => 200,
                    'msg'       => [
                        'error'   => 200,
                        'message' => __('Order already closed in Magento.'),
                        'state'   => $order->getState(),
                        'status'   => $order->getStatus(),
                    ],
                ];

                return $result;
            } else if (strcmp($isNotApplicable['msg'], 'Notification response for online refund created in magento') === 0) {
                $this->updateDetails($mercadopagoData, $order);

                $result = [
                    'isInvalid' => true,
                    'code'      => 200,
                    'msg'       => [
                        'error'   => 200,
                        'message' => __('Notification response for online refund.'),
                        'state'   => $order->getState(),
                        'status'   => $order->getStatus(),
                    ],
                ];

                return $result;
            } else if (strcmp($isNotApplicable['msg'], 'An error occured with refund.') === 0) {
                $this->updateDetails($mercadopagoData, $order);

                $result = [
                    'isInvalid' => true,
                    'code'      => 200,
                    'msg'       => [
                        'error'   => 200,
                        'message' => $isNotApplicable['description'],
                        'state'   => $order->getState(),
                        'status'   => $order->getStatus(),
                    ],
                ];

                return $result;
            } else {
                return $isNotApplicable;
            }
        }

        $notificationId = $mercadopagoData['notification_id'];

        $order = $this->fetchStatus->fetch($order->getEntityId(), $notificationId);

        $this->updateInvoice($order, $mpTransactionId, $childTransactionId);

        $result = [
            'code'  => 200,
            'msg'   => [
                'order'     => $order->getIncrementId(),
                'state'     => $order->getState(),
                'status'    => $order->getStatus(),
            ],
        ];

        return $result;
    }

    /**
     * Update Invoice.
     *
     * @param OrderRepository $order
     * @param string          $mpTransactionId
     * @param string          $transactionId
     *
     * @return void
     */
    private function updateInvoice(OrderInterface $order, $mpTransactionId, $transactionId): void
    {
        $payment = $order->getPayment();

        if ($payment->getIsTransactionApproved()) {
            $payment->setShouldCloseParentTransaction(true);
            $payment->setParentTransactionId($mpTransactionId);
            $payment->setTransactionId($transactionId);
            $payment->setIsTransactionPending(false);
            $payment->setIsTransactionClosed(true);
            $payment->addTransaction(Transaction::TYPE_CAPTURE);
            $order->save();
        }
    }
}
