<?php

/**
 * Copyright © MercadoPago. All rights reserved.
 *
 * @author      Mercado Pago
 * @license     See LICENSE for license details.
 */

namespace MercadoPago\AdbPayment\Model\Api;

use MercadoPago\AdbPayment\Api\QuoteMpPaymentRepositoryInterface;
use MercadoPago\AdbPayment\Api\QuoteMpPaymentManagementInterface;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Quote\Model\MaskedQuoteIdToQuoteIdInterface;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Model for quote MPPayment details.
 */
class QuoteMpPaymentManagement implements QuoteMpPaymentManagementInterface
{

    /**
     * @var QuoteMpPaymentRepositoryInterface
     */
    protected $quoteMpPaymentRepository;

    /**
     * @var CustomerSession
     */
    protected $customerSession;

    /**
     * @var \Magento\Quote\Model\MaskedQuoteIdToQuoteIdInterface
     */
    private $maskedQuoteIdToQuoteId;

    /**
     * QuoteMpPayment manager constructor.
     *
     * @param QuoteMpPaymentRepositoryInterface $quoteMpPaymentRepository
     * @param MaskedQuoteIdToQuoteIdInterface $maskedQuoteIdToQuoteId
     * @param CustomerSession $customerSession
     *
     */
    public function __construct(
        QuoteMpPaymentRepositoryInterface $quoteMpPaymentRepository,
        MaskedQuoteIdToQuoteIdInterface $maskedQuoteIdToQuoteId,
        CustomerSession $customerSession
    ) {
        $this->quoteMpPaymentRepository = $quoteMpPaymentRepository;
        $this->maskedQuoteIdToQuoteId = $maskedQuoteIdToQuoteId;
        $this->customerSession = $customerSession;
    }

    /**
     * Quote Mp Payment Information.
     *
     * @param string $quoteId
     *
     * @return array
     */
    public function getQuoteMpPayment(
        $quoteId
    ) {
        if (!$this->customerSession->isLoggedIn()) {
            $quoteId = $this->maskedQuoteIdToQuoteId->execute($quoteId);
        }

        $info = [];
        $quote = $this->quoteMpPaymentRepository->getByQuoteId($quoteId);

        if ($quote === null) {
            throw new NoSuchEntityException(
                __('The payment record with quote ID %1 does not exist.', $quoteId)
            );
        }

        $info['data'] = [
            'payment_id'                        => $quote->getPaymentId(),
            'three_ds_external_resource_url'    => $quote->getThreeDsExternalResourceUrl(),
            'three_ds_creq'                     => $quote->getThreeDsCreq(),
            'quote_id'                          => $quote->getQuoteId()
        ];

        return $info;
    }
}
