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
use MercadoPago\AdbPayment\Controller\MpIndex;
use MercadoPago\AdbPayment\Model\Notification\Refund\CheckoutCustom as CheckoutCustomRefund;

/**
 * Controler Notification Checkout Custom - Notification of receivers for Checkout Custom Methods.
 */
class CheckoutCustom extends MpIndex implements CsrfAwareActionInterface
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

        $mpTransactionId = $mercadopagoData['transaction_id'];
        $mpStatus = $mercadopagoData['status'];
        $notificationId = $mercadopagoData['notification_id'];
        $paymentsDetails = $mercadopagoData['payments_details'];

        if ($mpStatus === 'refunded' && !empty($mercadopagoData["multiple_payment_transaction_id"])) {
            $mpTransactionId = $mercadopagoData["multiple_payment_transaction_id"];
        }

        return $this->initProcess($mpTransactionId, $mpStatus, $notificationId, $paymentsDetails, $mercadopagoData);
    }

    /**
     * Init Process.
     *
     * @param string $mpTransactionId
     * @param string $mpStatus
     * @param string $notificationId
     * @param $respData
     *
     * @return ResultInterface
     */
    public function initProcess(
        $mpTransactionId,
        $mpStatus,
        $notificationId,
        $paymentsDetails,
        $respData = null
    ) {
        $searchCriteria = $this->searchCriteria->addFilter('txn_id', $mpTransactionId)
            ->create();

        try {
            /** @var TransactionRepositoryInterface $transactions */
            $transactions = $this->transaction->getList($searchCriteria)->getItems();

            if($transactions === null || sizeof($transactions) === 0)
            {
                /** @var ResultInterface $result */
                return $this->createResult(
                    422,
                    [
                        'error'   => 422,
                        'message' => 'Nothing to proccess',
                    ]
                );
            }

        } catch (Exception $exc) {
            /** @var ResultInterface $result */
            return $this->createResult(
                500,
                [
                    'error'   => 500,
                    'message' => $exc->getMessage(),
                ]
            );
        }

        $origin = '';
        $mpAmountRefund = null;
        $process = [];
        $resultData = [];
        $refundId = null;

        foreach ($transactions as $transaction) {
            $order = $this->getOrderData($transaction->getOrderId());

            if ($mpStatus === 'refunded') {
                $refund = new CheckoutCustomRefund(
                    $this->config,
                    $this->notifierPool,
                    $this->invoice,
                    $this->creditMemoFactory,
                    $this->creditMemoService,
                    $order,
                    $this->logger,
                    $this->updatePayment,
                    $respData
                );

                $res = $refund->process();
                return $this->createResult($res['code'], $res['msg'] ?? '');
            } else {
                $process = $this->processNotification($mpStatus, $order, $notificationId, $refundId, $mpAmountRefund, $origin);

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
     * Process Notification.
     *
     * @param string          $mpStatus
     * @param OrderRepository $order
     * @param string|null     $mpAmountRefund
     * @param string $notificationId
     *
     * @return array
     */
    public function processNotification(
        $mpStatus,
        $order,
        $notificationId,
        $refundId,
        $mpAmountRefund = null,
        $origin = null
    ) {
        $result = [];

        $isNotApplicable = $this->filterInvalidNotification($mpStatus, $order, $refundId, $mpAmountRefund, $origin);

        if ($isNotApplicable['isInvalid']) {
            if (
                strcmp($isNotApplicable['msg'], 'Refund notification for order refunded directly in Mercado Pago.') !== 0
                && strcmp($isNotApplicable['msg'], 'Refund notification for order already closed.') !== 0
                && strcmp($isNotApplicable['msg'], 'Notification response for online refund created in magento') !== 0
                && strcmp($isNotApplicable['msg'], 'An error occured with refund.') !== 0
            ) {
                return $isNotApplicable;
            }
        }

        $order = $this->fetchStatus->fetch($order->getEntityId(), $notificationId);

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
}
