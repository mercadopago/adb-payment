<?php
/**
 * Copyright © MercadoPago. All rights reserved.
 *
 * @author      Mercado Pago
 * @license     See LICENSE for license details.
 */

namespace MercadoPago\AdbPayment\Model\Checks;

use Magento\Payment\Model\Checks\SpecificationInterface;
use Magento\Payment\Model\MethodInterface;
use Magento\Quote\Model\Quote;
use MercadoPago\AdbPayment\Gateway\Config\Config;

/**
 * Plugin for \Magento\Payment\Model\Checks\Composite.
 */
class SpecificationPlugin
{
    /**
     * @var Config
     */
    protected $config;

    /**
     * @param Config $config
     */
    public function __construct(
        Config $config
    ) {
        $this->config = $config;
    }

    /**
     * Override check for Mp Site Id Available.
     *
     * @param SpecificationInterface $specification
     * @param bool                   $result
     * @param MethodInterface        $paymentMethod
     * @param Quote                  $quote
     *
     * @return bool
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterIsApplicable(
        SpecificationInterface $specification,
        $result,
        MethodInterface $paymentMethod,
        Quote $quote
    ) {
        if (!$result) {
            return false;
        }

        $storeId = $quote->getStoreId();
        $mpSiteId = $this->config->getMpSiteId($storeId);
        $restrict = $this->config->getRestrictPaymentOnMpSiteId($storeId);

        foreach ($restrict as $methodName => $siteId) {
            if ($methodName === $paymentMethod->getCode()) {
                if (!in_array($mpSiteId, $siteId)) {
                    return false;
                }
            }
        }

        return true;
    }
}
