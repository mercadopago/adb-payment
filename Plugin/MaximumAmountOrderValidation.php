<?php
/**
 * Copyright © MercadoPago. All rights reserved.
 *
 * @author      Mercado Pago
 * @license     See LICENSE for license details.
 */
 declare (strict_types=1);

namespace MercadoPago\AdbPayment\Plugin;

use Magento\Payment\Model\Checks\Composite;
use Magento\Quote\Model\Quote;
use Magento\Payment\Model\MethodInterface;
use MercadoPago\AdbPayment\Gateway\Config\ConfigCc;
use MercadoPago\AdbPayment\Gateway\Config\ConfigTwoCc;
use MercadoPago\AdbPayment\Gateway\Config\ConfigCheckoutCredits;

/**
 * Plugin to change place order max order amount validation
 */
class MaximumAmountOrderValidation
{
    /** @var ConfigCc $configCc */
    protected ConfigCc $configCc;

    /** @var ConfigTwoCc $configTwoCc */
    protected ConfigTwoCc $configTwoCc;

    /** @var ConfigCheckoutCredits $configCheckoutCredits */
    protected ConfigCheckoutCredits $configCheckoutCredits;

    /**
     * Class constructor
     *
     * @param ConfigCc $configCc
     * @param ConfigTwoCc $configTwoCc
     * @param ConfigCheckoutCredits $configCheckoutCredits
     */
    public function __construct(
        ConfigCc $configCc,
        ConfigTwoCc $configTwoCc,
        ConfigCheckoutCredits $configCheckoutCredits
    ) {
        $this->configCc = $configCc;
        $this->configTwoCc = $configTwoCc;
        $this->configCheckoutCredits = $configCheckoutCredits;
    }

    /**
     * After is applicable method
     *
     * @param Composite $subject
     * @param boolean $result
     * @param MethodInterface $method
     * @param Quote $quote
     * @return boolean
     */
    public function afterIsApplicable(
        Composite $subject,
        bool $result,
        MethodInterface $method,
        Quote $quote
    ): bool {
        if (!$result && $financeCost = $quote->getFinanceCostAmount()) {
            $paymentCode = $method->getCode();
            $maxOrderLimit = null;

            switch($paymentCode) {
                case ConfigCc::METHOD:
                    $maxOrderLimit = $this->configCc->getMaximumOrderTotal();
                    break;
                case ConfigTwoCc::METHOD:
                    $maxOrderLimit = $this->configTwoCc->getMaximumOrderTotal();
                    break;
                case ConfigCheckoutCredits::METHOD:
                    $maxOrderLimit = $this->configCheckoutCredits->getMaximumOrderTotal();
                    break;
                default:
                    break;
            }

            $totalWithoutFinanceCost = $quote->getGrandTotal() - $financeCost;
            if ($maxOrderLimit && $totalWithoutFinanceCost <= $maxOrderLimit) {
                $result = true;
            }
        }

        return $result;
    }
}
