<?php

namespace MercadoPago\AdbPayment\Tests\Unit\Gateway\Http\Client\Order;

/**
 * Fake HTTP result to emulate SDK response object.
 */
class FakeHttpResult
{
    /** @var array */
    private $data;

    /** @var int */
    private $status;

    public function __construct(array $data, int $status = 200)
    {
        $this->data = $data;
        $this->status = $status;
    }

    public function getData()
    {
        return $this->data;
    }

    public function getStatus(): int
    {
        return $this->status;
    }
}
