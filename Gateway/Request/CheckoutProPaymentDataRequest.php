<?php
/**
 * Copyright © MercadoPago. All rights reserved.
 *
 * @author      Mercado Pago
 * @license     See LICENSE for license details.
 */

namespace MercadoPago\AdbPayment\Gateway\Request;

use InvalidArgumentException;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Gateway\Request\BuilderInterface;
use MercadoPago\AdbPayment\Gateway\Config\ConfigCheckoutPro;

/**
 * Gateway Requests Payment by Checkout Pro Data.
 */
class CheckoutProPaymentDataRequest implements BuilderInterface
{
    /**
     * Date of Expiration block name.
     */
    public const DATE_OF_EXPIRATION = 'date_of_expiration';

    /**
     * Auto Return block name.
     */
    public const AUTO_RETURN = 'auto_return';

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var ConfigCheckoutPro
     */
    protected $configCheckoutPro;

    /**
     * @param ConfigCheckoutPro $configCheckoutPro
     */
    public function __construct(
        ConfigCheckoutPro $configCheckoutPro
    ) {
        $this->configCheckoutPro = $configCheckoutPro;
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

        $result = [
            self::DATE_OF_EXPIRATION => $this->configCheckoutPro->getExpirationFormatted(),
            self::AUTO_RETURN        => 'all',
        ];

        return $result;
    }
}
