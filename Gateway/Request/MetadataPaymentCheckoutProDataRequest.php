<?php
/**
 * Copyright © MercadoPago. All rights reserved.
 *
 * @license     See LICENSE for license details.
 */

namespace MercadoPago\AdbPayment\Gateway\Request;

use InvalidArgumentException;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Gateway\Request\BuilderInterface;
use MercadoPago\AdbPayment\Gateway\Config\ConfigCheckoutPro;

/**
 * Gateway requests for Payment Metadata by Checkout Pro.
 */
class MetadataPaymentCheckoutProDataRequest implements BuilderInterface
{
    /**
     * Checkout block name.
     */
    public const CHECKOUT = 'checkout';

    /**
     * Checkout Type block name.
     */
    public const CHECKOUT_TYPE = 'checkout_type';

    /**
     * @var ConfigCheckoutPro
     */
    protected $config;

    /**
     * @param ConfigCheckoutPro $config
     */
    public function __construct(
        ConfigCheckoutPro $config
    ) {
        $this->config = $config;
    }

    /**
     * Build.
     *
     * @param array $buildSubject
     */
    public function build(array $buildSubject)
    {
        if (!isset($buildSubject['payment'])
            || !$buildSubject['payment'] instanceof PaymentDataObjectInterface
        ) {
            throw new InvalidArgumentException('Payment data object should be provided');
        }
        $result = [];

        $result[MetadataPaymentDataRequest::METADATA] = [
            self::CHECKOUT      => 'pro',
            self::CHECKOUT_TYPE => $this->config->getTypeRedirect(),
        ];

        return $result;
    }
}
