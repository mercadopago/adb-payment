<?php
/**
 * Copyright © MercadoPago. All rights reserved.
 *
 * @author      Bruno Elisei <brunoelisei@o2ti.com>
 * @license     See LICENSE for license details.
 */

namespace MercadoPago\PaymentMagento\Gateway\Request;

use InvalidArgumentException;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Gateway\Request\BuilderInterface;
use MercadoPago\PaymentMagento\Gateway\Config\ConfigCheckoutPro;

/**
 * Gateway requests for binary mode definition.
 */
class BinaryModeDataRequest implements BuilderInterface
{
    /**
     * Payment Methods block name.
     */
    public const PAYMENT_METHODS = 'payment_methods';

    /**
     * Binary Mode block name.
     */
    public const BINARY_MODE = 'binary_mode';

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

        $result[self::BINARY_MODE] = $this->config->isBinaryMode();

        return $result;
    }
}
