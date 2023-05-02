<?php
/**
 * Copyright © MercadoPago. All rights reserved.
 *
 * @author      Mercado Pago
 * @license     See LICENSE for license details.
 */

namespace MercadoPago\AdbPayment\Cron;

use Magento\Payment\Model\Method\Logger;
use MercadoPago\AdbPayment\Model\Console\Command\Adminstrative\FetchMerchant;

/**
 * CronTab for fetch merchant data.
 */
class FetchMerchantInfo
{
    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @var FetchMerchant
     */
    protected $fetchMerchant;

    /**
     * Constructor.
     *
     * @param Logger        $logger
     * @param FetchMerchant $fetchMerchant
     */
    public function __construct(
        Logger $logger,
        FetchMerchant $fetchMerchant
    ) {
        $this->logger = $logger;
        $this->fetchMerchant = $fetchMerchant;
    }

    /**
     * Execute the cron.
     *
     * @return void
     */
    public function execute()
    {
        $this->fetchMerchant->fetch();
    }
}
