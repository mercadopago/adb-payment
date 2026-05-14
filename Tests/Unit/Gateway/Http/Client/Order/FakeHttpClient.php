<?php

namespace MercadoPago\AdbPayment\Tests\Unit\Gateway\Http\Client\Order;

/**
 * Fake HTTP client replacing MercadoPago\PP\Sdk\HttpClient\HttpClient
 * Captures the request to allow assertions.
 */
class FakeHttpClient
{
    public static $captured = [
        'baseUrl'  => null,
        'uri'      => null,
        'headers'  => null,
        'payload'  => null,
        'requester' => null,
    ];

    /**
     * Configurable response for testing different scenarios.
     * @var array|null
     */
    public static $mockResponse = null;

    /** @var int */
    public static $mockStatus = 200;

    /** @var bool */
    public static $throwException = false;

    public function __construct($baseUrl, $requester)
    {
        self::$captured['baseUrl'] = $baseUrl;
        self::$captured['requester'] = $requester;
    }

    public function post($uri, array $headers, $payload)
    {
        self::$captured['uri'] = $uri;
        self::$captured['headers'] = $headers;
        self::$captured['payload'] = $payload;

        // Return configured response or default success response
        $response = self::$mockResponse ?? [
            'id'     => 'abc123',
            'status' => 'created',
        ];

        return new FakeHttpResult($response);
    }

    public function get($uri, $headers)
    {
        self::$captured['uri'] = $uri;
        self::$captured['headers'] = $headers;

        if (self::$throwException) {
            throw new \Exception('Connection error');
        }

        $response = self::$mockResponse ?? [];
        return new FakeHttpResult($response, self::$mockStatus);
    }

    public static function reset(): void
    {
        self::$captured = [
            'baseUrl'  => null,
            'uri'      => null,
            'headers'  => null,
            'payload'  => null,
            'requester' => null,
        ];
        self::$mockResponse = null;
        self::$mockStatus = 200;
        self::$throwException = false;
    }
}
