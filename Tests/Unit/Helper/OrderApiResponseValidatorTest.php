<?php
/**
 * Copyright © MercadoPago. All rights reserved.
 *
 * @author      Mercado Pago
 * @license     See LICENSE for license details.
 */

namespace MercadoPago\AdbPayment\Tests\Unit\Helper;

use MercadoPago\AdbPayment\Helper\OrderApiResponseValidator;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for OrderApiResponseValidator helper.
 */
class OrderApiResponseValidatorTest extends TestCase
{
    /**
     * @dataProvider isErrorProvider
     */
    public function testIsError(array $response, bool $expectedIsError): void
    {
        $this->assertEquals($expectedIsError, OrderApiResponseValidator::isError($response));
    }

    /**
     * Data provider for isError tests.
     */
    public function isErrorProvider(): array
    {
        return [
            'RESULT_CODE 0 is error' => [
                ['RESULT_CODE' => 0],
                true,
            ],
            'RESULT_CODE 1 is success' => [
                ['RESULT_CODE' => 1],
                false,
            ],
            'error field present is error' => [
                ['RESULT_CODE' => 1, 'error' => 'not_found'],
                true,
            ],
            'status 400 is error' => [
                ['RESULT_CODE' => 1, 'status' => 400],
                true,
            ],
            'status 404 is error' => [
                ['RESULT_CODE' => 1, 'status' => 404],
                true,
            ],
            'status 500 is error' => [
                ['RESULT_CODE' => 1, 'status' => 500],
                true,
            ],
            'status 200 is success' => [
                ['RESULT_CODE' => 1, 'status' => 200],
                false,
            ],
            'empty response is success' => [
                [],
                false,
            ],
            'combined error response' => [
                ['RESULT_CODE' => 0, 'status' => 404, 'error' => 'not_found', 'message' => 'Resource not found'],
                true,
            ],
            'only error field without RESULT_CODE' => [
                ['error' => 'invalid_request'],
                true,
            ],
            'only status 500 without RESULT_CODE' => [
                ['status' => 500],
                true,
            ],
        ];
    }

    /**
     * @dataProvider getErrorCodeProvider
     */
    public function testGetErrorCode(array $response, string $expectedCode): void
    {
        $this->assertEquals($expectedCode, OrderApiResponseValidator::getErrorCode($response));
    }

    /**
     * Data provider for getErrorCode tests.
     */
    public function getErrorCodeProvider(): array
    {
        return [
            'status 404' => [
                ['status' => 404],
                '404',
            ],
            'status 500' => [
                ['status' => 500],
                '500',
            ],
            'status as string' => [
                ['status' => '403'],
                '403',
            ],
            'no status defaults to 0' => [
                ['error' => 'some_error'],
                '0',
            ],
            'empty response defaults to 0' => [
                [],
                '0',
            ],
        ];
    }

    /**
     * @dataProvider getErrorMessageProvider
     */
    public function testGetErrorMessage(array $response, string $expectedMessage): void
    {
        $this->assertEquals($expectedMessage, OrderApiResponseValidator::getErrorMessage($response));
    }

    /**
     * Data provider for getErrorMessage tests.
     */
    public function getErrorMessageProvider(): array
    {
        return [
            'original_message takes priority' => [
                ['original_message' => 'Original error', 'message' => 'Resource not found', 'error' => 'not_found'],
                'Original error',
            ],
            'message field present' => [
                ['message' => 'Resource not found', 'error' => 'not_found'],
                'Resource not found',
            ],
            'only error field' => [
                ['error' => 'not_found'],
                'not_found',
            ],
            'original_message takes priority over message' => [
                ['original_message' => 'Original message', 'message' => 'Custom message'],
                'Original message',
            ],
            'message takes priority over error' => [
                ['message' => 'Custom message', 'error' => 'error_code'],
                'Custom message',
            ],
            'empty response returns empty string' => [
                [],
                '',
            ],
            'no message or error returns empty string' => [
                ['status' => 500],
                '',
            ],
        ];
    }

    /**
     * Test RESULT_CODE constant value.
     */
    public function testResultCodeConstant(): void
    {
        $this->assertEquals('RESULT_CODE', OrderApiResponseValidator::RESULT_CODE);
    }
}

