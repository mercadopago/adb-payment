<?php
/**
 * Copyright © MercadoPago. All rights reserved.
 *
 * @author      Mercado Pago
 * @license     See LICENSE for license details.
 */

namespace MercadoPago\AdbPayment\Api\Data;

use Magento\Framework\Api\ExtensibleDataInterface;

/**
 * Interface Finance Cost - Data to calculate the cost of financing.
 *
 * @api
 *
 * @since 100.0.0
 */
interface FinanceCostInterface extends ExtensibleDataInterface
{
    /**
     * Finance Cost Amount.
     *
     * @var string
     */
    public const FINANCE_COST_AMOUNT = 'finance_cost_amount';

    /**
     * Base Finance Cost Amount.
     *
     * @var string
     */
    public const BASE_FINANCE_COST_AMOUNT = 'base_finance_cost_amount';

    /**
     * First card value.
     *
     * @var string
     */
    public const FIRST_CARD_AMOUNT = 'first_card_amount';

    /**
     * Second card value.
     *
     * @var string
     */
    public const SECOND_CARD_AMOUNT = 'second_card_amount';

    /**
     * Get selected installment.
     *
     * @return int
     */
    public function getSelectedInstallment();

    /**
     * Set selected installment.
     *
     * @param int $selectedInstallment
     *
     * @return void
     */
    public function setSelectedInstallment($selectedInstallment);
}
