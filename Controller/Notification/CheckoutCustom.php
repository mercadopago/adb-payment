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

        $response = $this->getRequest()->getContent();

        $this->logger->debug([
            'action'    => 'checkout_custom',
            'payload'   => $response,
        ]);

        $mercadopagoData = $this->json->unserialize($response);

        $txnType = 'authorization';

        $mpTransactionId = $mercadopagoData['transaction_id'];

        $mpStatus = $mercadopagoData['status'];

        if ($mpStatus === 'refunded') {
            $txnType = 'capture';
        }

        $searchCriteria = $this->searchCriteria
            ->addFilter('txn_id', $mpTransactionId)
            ->addFilter('txn_type', $txnType)
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

            $process = $this->processNotification($mpStatus, $order);

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
     *
     * @return array
     */
    public function processNotification(
        $mpStatus,
        $order
    ) {
        $result = [];

        $isNotApplicable = $this->filterInvalidNotification($mpStatus, $order);

        if ($isNotApplicable['isInvalid']) {
            return $isNotApplicable;
        }

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
