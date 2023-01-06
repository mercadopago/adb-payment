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

            $process = $this->processNotification($status, $childTransactionId, $order);

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
     * Create Child.
     *
     * @param string          $childTransactionId
     * @param OrderRepository $order
     *
     * @return void
     */
    public function createChild(
        $childTransactionId,
        $order
    ) {
        $payment = $order->getPayment();
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
     * @param string          $mpStatus
     * @param string          $childTransactionId
     * @param OrderRepository $order
     *
     * @return array
     */
    public function processNotification(
        $mpStatus,
        $childTransactionId,
        $order
    ) {
        $result = [];

        $isNotApplicable = $this->filterInvalidNotification($mpStatus, $order);

        if ($isNotApplicable['isInvalid']) {
            return $isNotApplicable;
        }

        $this->createChild($childTransactionId, $order);
        $this->fetchStatus->fetch($order->getEntityId());

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
