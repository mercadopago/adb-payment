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
use Magento\Sales\Model\Order\Payment\Transaction;
use MercadoPago\PaymentMagento\Controller\MpIndex;

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

        $response = $this->getRequest()->getContent();

        $this->logger->debug([
            'action'    => 'checkout_pro',
            'payload'   => $response,
        ]);

        $mercadopagoData = $this->json->unserialize($response);

        $mpAmountRefund = null;

        $status = $mercadopagoData['status'];

        if ($status !== 'approved'
            && $status !== 'refunded'
            && $status !== 'pending'
            && $status !== 'cancelled'
        ) {
            /** @var ResultInterface $result */
            $result = $this->createResult(200, ['empty' => null]);

            return $result;
        }

        if ($status === 'refunded') {
            $mpAmountRefund = $mercadopagoData['total_refunded'];
        }

        $mpStatus = $mercadopagoData['status'];
        $mpTransactionId = $mercadopagoData['preference_id'];
        $childTransactionId = $mercadopagoData['payments_details'][0]['id'];

        $this->logger->debug([
            'Checkout type'    => 'Pro',
            'Mp payment status' => $mpStatus,
            'Mp transaction id - preference' => $mpTransactionId,
            'Child transaction id - payment' => $childTransactionId,
        ]);

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

        $this->logger->debug([
            'Class'    => 'CheckoutPro',
            'Action'    => 'for each transaction',
        ]);

        foreach ($transactions as $transaction) {
            $order = $this->getOrderData($transaction->getOrderId());
            
            $this->logger->debug([
                'Class'    => 'CheckoutPro',
                'Order status'    => $order->getStatus(),
                'Order id' => $transaction->getOrderId(),
            ]);
            
            
            if ($mpStatus === 'pending') {

                $this->logger->debug([
                    'Class'    => 'CheckoutPro',
                    'action'    => 'Updating payment detail to pending',
                ]);

                $this->updateDetails($mercadopagoData, $order);

                $this->logger->debug([
                    'Class'    => 'CheckoutPro',
                    'action'    => 'Updated details',
                ]);

                /** @var ResultInterface $result */
                $result = $this->createResult(
                    200,
                    'Update Details.',
                );

                return $result;
            }

            $process = $this->processNotification(
                $mpTransactionId,
                $status,
                $childTransactionId,
                $order,
                $mpAmountRefund,
                $mercadopagoData
            );

            $this->logger->debug([
                'Class'    => 'CheckoutPro',
                'Action'    => 'After process notification',
                'code' => $process['code'],
                'msg' => $process['msg'],
            ]);

            /** @var ResultInterface $result */
            $result = $this->createResult(
                $process['code'],
                $process['msg'],
            );

            return $result;
        }

        $this->logger->debug([
            'Class'    => 'CheckoutPro',
            'action'    => 'no transactions',
        ]);

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
        $this->logger->debug([
            'Class'    => 'CheckoutPro',
            'action'    => 'details',
            'data' => $this->json->serialize($mercadopagoData),
            'status' => $order->getStatus(),
            'state' => $order->getState(),
        ]);
        $orderId = $order->getId();
        $childTransctions = $mercadopagoData['payments_details'];

        foreach ($childTransctions as $child) {
            $this->checkoutProAddChildInformation($orderId, $child['id'], 'CheckoutPro');
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
        $this->logger->debug([
            'Class'    => 'CheckoutPro',
            'action'    => 'create child',
            'order status' => $order->getStatus(),
        ]);
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
        $mpAmountRefund = null,
        $mercadopagoData = null
    ) {
        $this->logger->debug([
            'Class'    => 'CheckoutPro',
            'Action'    => 'Process notification',
            'Order status' => $order->getStatus(),
        ]);

        $result = [];

        $isNotApplicable = $this->filterInvalidNotification($mpStatus, $order, $mpAmountRefund, 'CheckoutPro');

        if ($isNotApplicable['isInvalid']) {
            if ($isNotApplicable['code'] === 999) {
                $this->logger->debug([
                    'Class'    => 'CheckoutPro',
                    'action'    => 'Updating payment details status for order not closed',
                ]);
                $this->updateDetails($mercadopagoData, $order);

                $this->logger->debug([
                    'Class'    => 'CheckoutPro',
                    'action'    => 'After updating details status for order not closed',
                ]);

                $result = [
                    'isInvalid' => true,
                    'code'      => 200,
                    'msg'       => [
                        'error'   => 200,
                        'message' => __('Order not yet closed in Magento.'),
                        'state'   => $order->getState(),
                        'tatus'   => $order->getStatus(),
                    ],
                ];

                return $result;
            }
            if ($isNotApplicable['code'] === 888) {
                $this->logger->debug([
                    'Class'    => 'CheckoutPro',
                    'action'    => 'Updating payment details status for order already closed',
                ]);
                
                $this->updateDetails($mercadopagoData, $order);

                $this->logger->debug([
                    'Class'    => 'CheckoutPro',
                    'action'    => 'After updating details status for order already closed',
                ]);

                $result = [
                    'isInvalid' => true,
                    'code'      => 200,
                    'msg'       => [
                        'error'   => 200,
                        'message' => __('Order already closed in Magento.'),
                        'state'   => $order->getState(),
                        'tatus'   => $order->getStatus(),
                    ],
                ];

                return $result;
            }

            return $isNotApplicable;
        }

        $this->logger->debug([
            'Class'    => 'CheckoutPro',
            'Notification validity'    => 'valid',
            'Action' => 'Before creating child',
        ]);

        $this->createChild($mpTransactionId, $childTransactionId, $order);

        $this->logger->debug([
            'Class'     => 'CheckoutPro',
            'Action'    => 'After creating child',
            'Action'    => 'Before fetch',
            'order'     => $order->getIncrementId(),
            'state'     => $order->getState(),
            'status'    => $order->getStatus(),
            'entity id' => $order->getEntityId(),
        ]);

        $order = $this->fetchStatus->fetch($order->getEntityId(), 'checkout pro');
       
        $this->logger->debug([
            'Class'     => 'CheckoutPro',
            'Action'    => 'After fetch',
            'order'     => $order->getIncrementId(),
            'state'     => $order->getState(),
            'status'    => $order->getStatus(),
            'entity id' => $order->getEntityId(),
        ]);

        $result = [
            'code'  => 200,
            'msg'   => [
                'order'     => $order->getIncrementId(),
                'state'     => $order->getState(),
                'status'    => $order->getStatus(),
            ],
        ];

        $this->logger->debug([
            'Class'    => 'CheckoutPro',
            'Action'    => 'Notification processed',
            'msg'   => $result,
        ]);

        return $result;
    }
}
