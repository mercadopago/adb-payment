<?php

namespace MercadoPago\AdbPayment\Tests\Unit\Helper;

use PHPUnit\Framework\TestCase;
use MercadoPago\AdbPayment\Helper\HttpErrorCodeExtractor;

class HttpErrorCodeExtractorTest extends TestCase
{
    public function testExtractFromExceptionCodeProperty()
    {
        // Test with valid HTTP status code
        $exception = new \Exception('Error', 401);
        $this->assertEquals('401', HttpErrorCodeExtractor::extract($exception));

        $exception = new \Exception('Error', 404);
        $this->assertEquals('404', HttpErrorCodeExtractor::extract($exception));

        $exception = new \Exception('Error', 500);
        $this->assertEquals('500', HttpErrorCodeExtractor::extract($exception));
    }

    public function testExtractFromExceptionCodePropertyWithInvalidCode()
    {
        // Test with invalid HTTP status code (999 is not valid)
        $exception = new \Exception('Error', 999);
        $this->assertEquals('500', HttpErrorCodeExtractor::extract($exception));

        // Test with code 0
        $exception = new \Exception('Error', 0);
        $this->assertEquals('500', HttpErrorCodeExtractor::extract($exception));
    }

    public function testExtractFromExceptionMessage()
    {
        // Test with HTTP code in message
        $exception = new \Exception('HTTP 404: Not Found');
        $this->assertEquals('404', HttpErrorCodeExtractor::extract($exception));

        $exception = new \Exception('Error 401: Unauthorized');
        $this->assertEquals('401', HttpErrorCodeExtractor::extract($exception));

        $exception = new \Exception('Status code 500');
        $this->assertEquals('500', HttpErrorCodeExtractor::extract($exception));
    }

    public function testExtractReturnsDefaultWhenNoCodeFound()
    {
        // Test without code in message or property
        $exception = new \Exception('Generic error without code');
        $this->assertEquals('500', HttpErrorCodeExtractor::extract($exception));

        $exception = new \Exception('Some other error');
        $this->assertEquals('500', HttpErrorCodeExtractor::extract($exception));
    }

    public function testExtractPrioritizesCodePropertyOverMessage()
    {
        // When both code property and message have codes, property should win
        $exception = new \Exception('HTTP 404: Not Found', 401);
        $this->assertEquals('401', HttpErrorCodeExtractor::extract($exception));
    }
}

