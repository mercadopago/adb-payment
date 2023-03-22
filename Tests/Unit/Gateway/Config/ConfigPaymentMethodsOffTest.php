<?php

namespace Tests\Unit\Gateway\Config;

use MercadoPago\PaymentMagento\Gateway\Config\ConfigPaymentMethodsOff;

class ConfigPaymentMethodsOffTest {

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