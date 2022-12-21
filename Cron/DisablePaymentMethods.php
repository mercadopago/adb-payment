<?php
/**
 * Copyright © MercadoPago. All rights reserved.
 *
 * @author      Bruno Elisei <brunoelisei@o2ti.com>
 * @license     See LICENSE for license details.
 */

namespace MercadoPago\PaymentMagento\Cron;

use MercadoPago\PaymentMagento\Model\Console\Command\Adminstrative\FetchPaymentMethods;

/**
 * CronTab to disable payment methods that are not allowed.
 */
class DisablePaymentMethods
{
    /**
     * @var FetchPaymentMethods
     */
    protected $fetchPayment;

    /**
     * Constructor.
     *
     * @param FetchPaymentMethods $fetchPayment
     */
    public function __construct(
        FetchPaymentMethods $fetchPayment
    ) {
        $this->fetchPayment = $fetchPayment;
    }

    /**
     * Execute the cron.
     *
     * @return void
     */
    public function execute()
    {
        $this->fetchPayment->fetch();
    }
}
