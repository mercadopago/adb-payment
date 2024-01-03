<?php
/**
 * Copyright © MercadoPago. All rights reserved.
 *
 * @author      Mercado Pago
 * @license     See LICENSE for license details.
 */

namespace MercadoPago\AdbPayment\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Store\Model\ScopeInterface;
use MercadoPago\AdbPayment\Model\Console\Command\Adminstrative\FetchMerchant;

/**
 * Excecute Fetch Merchant Data after save config.
 */
class ChangeConfigModule implements ObserverInterface
{
    /**
     * @var FetchMerchant
     */
    protected $fetchMerhant;

    /**
     * Construct.
     *
     * @param FetchMerchant $fetchMerhant
     */
    public function __construct(
        FetchMerchant $fetchMerhant
    ) {
        $this->fetchMerhant = $fetchMerhant;
    }

    /**
     * Excecute fetch merchant after save config.
     *
     * @param Observer $observer
     *
     * @return void
     */
    public function execute(Observer $observer)
    {
        $storeId = $observer->getStore();
        $websiteId = $observer->getWebsiteId();

        if ($storeId) {
            $this->fetchMerhant->fetch($storeId, ScopeInterface::SCOPE_STORES);
        } elseif ($websiteId) {
            $this->fetchMerhant->fetch($websiteId, ScopeInterface::SCOPE_WEBSITES);
        } else {
            $this->fetchMerhant->fetch();
        }

    }
}
