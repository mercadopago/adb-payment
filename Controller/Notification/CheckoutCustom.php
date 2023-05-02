<?php
/**
 * Copyright © MercadoPago. All rights reserved.
 *
 * @author      Bruno Elisei <brunoelisei@o2ti.com>
 * @license     See LICENSE for license details.
 */

namespace MercadoPago\PaymentMagento\Controller\Notification;

use Exception;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\HTTP\ZendClient;
use MercadoPago\PaymentMagento\Controller\MpIndex;

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

        $mpAmountRefund = null;
        $response = $this->getRequest()->getContent();
        $mercadopagoData = $this->json->unserialize($response);
        $mpTransactionId = $mercadopagoData['transaction_id'];
        $mpStatus = $mercadopagoData['status'];
        $notificationId = $mercadopagoData['notification_id'];
        $paymentsDetails = $mercadopagoData['payments_details'];

        if ($mpStatus === 'refunded') {
            $mpAmountRefund = $mercadopagoData['total_refunded'];
        }

        $this->logger->debug([
            'action'    => 'checkout_custom',
            'payload'   => $response,
            'mpstatus'  => $mpStatus,
            'transac'   => $mpTransactionId,
            'notif'     => $notificationId,
            'refund'    => $mpAmountRefund,
            'details'    => $this->json->serialize($paymentsDetails)
        ]);

        return $this->initProcess($mpTransactionId, $mpStatus, $mpAmountRefund, $notificationId, $paymentsDetails);
    }

    /**
     * Init Process.
     *
     * @param string $mpTransactionId
     * @param string $mpStatus
     * @param string $mpAmountRefund
     * @param string $notificationId
     *
     * @return ResultInterface
     */
    public function initProcess(
        $mpTransactionId,
        $mpStatus,
        $mpAmountRefund,
        $notificationId,
        $paymentsDetails
    ) {
        $searchCriteria = $this->searchCriteria->addFilter('txn_id', $mpTransactionId)
            ->create();

        try {
            /** @var TransactionRepositoryInterface $transactions */
            $transactions = $this->transaction->getList($searchCriteria)->getItems();
        } catch (Exception $exc) {

            /** @var ResultInterface $result */
            $result = $this->createResult(
                500,
                [
                    'error'   => 500,
                    'message' => $exc->getMessage(),
                ]
            );

            return $result;
        }

        foreach ($transactions as $transaction) {
            $order = $this->getOrderData($transaction->getOrderId());

            $origin = '';
            if ($mpStatus === 'refunded') {
                $payment = $order->getPayment();
                $transacId = $payment->getLastTransId();
                if (isset($paymentsDetails['0']['refunds'][$transacId])){
                    $origin = $paymentsDetails['0']['refunds'][$transacId]['metadata']['origem'];
                }
            }

            $process = $this->processNotification($mpStatus, $order, $notificationId, $mpAmountRefund, $origin);

            /** @var ResultInterface $result */
            $result = $this->createResult(
                $process['code'],
                $process['msg'],
            );

            return $result;
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
        $mpAmountRefund = null,
        $origin = null
    ) {
        $result = [];

        $isNotApplicable = $this->filterInvalidNotification($mpStatus, $order, $mpAmountRefund, $origin);

        if ($isNotApplicable['isInvalid']) {
            if (
                strcmp($isNotApplicable['msg'], 'Refund notification for order refunded directly in Mercado Pago.') !== 0
                && strcmp($isNotApplicable['msg'], 'Refund notification for order already closed.') !== 0
                && strcmp($isNotApplicable['msg'], 'Notification response for online refund created in magento') !== 0
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
