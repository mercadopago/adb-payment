<?php

namespace MercadoPago\AdbPayment\Tests\Unit\Gateway\Http\Client\Order;

/**
 * Fake HTTP result to emulate SDK response object.
 */
class FakeHttpResult
{
    /** @var array */
    private $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function getData()
    {
        return $this->data;
    }
}

