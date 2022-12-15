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
                    'message' => __('It\'s not for you to be here...'),
                ]
            );
        }

        $response = $this->getRequest()->getContent();

        $mercadopagoData = $this->json->unserialize($response);

        $status = $mercadopagoData['status'];

        if ($status !== 'approved') {
            /** @var ResultInterface $result */
            $result = $this->createResult(200, ['empty' => null]);

            return $result;
        }

        $mpTransactionId = $mercadopagoData['preference_id'];
        $childTransactionId = $mercadopagoData['payments_details'][0]['id'];

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

        foreach ($transactions as $transaction) {
            $order = $this->getOrderData($transaction->getOrderId());

            if (!$order->getEntityId()) {
                return $this->createResult(
                    406,
                    [
                        'error'   => 406,
                        'message' => __('Order not found.'),
                    ]
                );
            }

            if ($order->getState() !== \Magento\Sales\Model\Order::STATE_NEW) {
                return $this->createResult(
                    412,
                    [
                        'error'   => 412,
                        'message' => __('Not available.'),
                        'state'   => $order->getState(),
                    ]
                );
            }

            $payment = $order->getPayment();
            $payment->setTransactionId($childTransactionId);
            $payment->setIsTransactionPending(1);
            $payment->setIsTransactionClosed(false);
            $payment->setAuthorizationTransaction($childTransactionId);
            $payment->addTransaction(Transaction::TYPE_AUTH);
            $order->save();

            $this->fetchStatus->fetch($order->getEntityId());

            return $this->createResult(
                200,
                [
                    'order'     => $order->getIncrementId(),
                    'state'     => $order->getState(),
                    'status'    => $order->getStatus(),
                ]
            );
        }

        /** @var ResultInterface $result */
        $result = $this->createResult(200, ['empty' => null]);

        return $result;
    }
}
