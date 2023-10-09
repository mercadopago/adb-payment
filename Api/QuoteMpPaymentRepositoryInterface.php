<?php

/**
 * Copyright © MercadoPago. All rights reserved.
 *
 * @author      Mercado Pago
 * @license     See LICENSE for license details.
 */

namespace MercadoPago\AdbPayment\Api;

use Magento\Framework\Exception\CouldNotSaveException;
use MercadoPago\AdbPayment\Api\Data\QuoteMpPaymentInterface;

interface QuoteMpPaymentRepositoryInterface
{
    /**
     * Save Quote Mp Payment
     * @param QuoteMpPaymentInterface $quoteMpPayment
     * @return QuoteMpPaymentInterface
     * @throws CouldNotSaveException
     */
    public function save(QuoteMpPaymentInterface $quoteMpPayment);

    /**
     * Get Quote Mp Payment by Quote ID
     * @param int $quoteId
     * @return QuoteMpPaymentInterface
     */
    public function getByQuoteId($quoteId);

    /**
     * Delete Quote Mp Payment
     * @param int $quoteId
     */
    public function deleteByQuoteId($quoteId);
}
