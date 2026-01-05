<?php
/**
 * Copyright © MercadoPago. All rights reserved.
 *
 * @author      Mercado Pago
 * @license     See LICENSE for license details.
 */

namespace MercadoPago\AdbPayment\Tests\Unit\Helper;

use MercadoPago\AdbPayment\Gateway\Config\Config;
use MercadoPago\AdbPayment\Helper\OrderApiHeadersBuilder;
use PHPUnit\Framework\TestCase;

/**
 * Test for OrderApiHeadersBuilder helper.
 */
class OrderApiHeadersBuilderTest extends TestCase
{
    /**
     * @var OrderApiHeadersBuilder
     */
    private $headersBuilder;

    /**
     * @var Config|\PHPUnit\Framework\MockObject\MockObject
     */
    private $configMock;

    /**
     * Setup test dependencies.
     */
    protected function setUp(): void
    {
        $this->configMock = $this->createMock(Config::class);
        $this->headersBuilder = new OrderApiHeadersBuilder($this->configMock);
    }

    /**
     * Test buildHeaders merges base headers with content type.
     */
    public function testBuildHeadersMergesBaseHeadersWithContentType()
    {
        $storeId = '1';
        $baseHeaders = [
            'Authorization: Bearer test-token',
            'x-integrator-id: test-integrator',
        ];

        $this->configMock->expects($this->once())
            ->method('getClientHeadersMpPluginsPhpSdk')
            ->with($storeId)
            ->willReturn($baseHeaders);

        $result = $this->headersBuilder->buildHeaders($storeId);

        $this->assertIsArray($result);
        $this->assertContains('Authorization: Bearer test-token', $result);
        $this->assertContains('x-integrator-id: test-integrator', $result);
        $this->assertContains(OrderApiHeadersBuilder::CONTENT_TYPE_JSON, $result);
    }

    /**
     * Test CONTENT_TYPE_JSON constant is defined.
     */
    public function testContentTypeJsonConstantIsDefined()
    {
        $this->assertEquals(
            'Content-Type: application/json',
            OrderApiHeadersBuilder::CONTENT_TYPE_JSON
        );
    }
}

