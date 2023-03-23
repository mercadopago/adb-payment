<?php

namespace Tests\Unit\Gateway\Config;

use PHPUnit\Framework\TestCase;

use MercadoPago\PaymentMagento\Model\Ui\Config;

class ConfigPaymentMethodsOffTest extends TestCase {

        /**
     * @var ConfigPaymentMethodsOff
     */
    protected $config;

    public function __construct(
        ConfigPaymentMethodsOff $config
    ) {
        $this->config = $config;
    }

}