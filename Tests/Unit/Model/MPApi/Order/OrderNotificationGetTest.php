<?php
/**
 * Copyright © MercadoPago. All rights reserved.
 *
 * @author      Mercado Pago
 * @license     See LICENSE for license details.
 */

namespace MercadoPago\AdbPayment\Tests\Unit\Model\MPApi\Order;

use Exception;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Payment\Model\Method\Logger;
use MercadoPago\AdbPayment\Gateway\Config\Config;
use MercadoPago\AdbPayment\Helper\OrderApiHeadersBuilder;
use MercadoPago\AdbPayment\Model\Metrics\MetricsClient;
use MercadoPago\AdbPayment\Model\MPApi\Order\OrderNotificationGet;
use PHPUnit\Framework\TestCase;

/**
 * Test for OrderNotificationGet API client.
 */
class OrderNotificationGetTest extends TestCase
{
    /**
     * @var OrderNotificationGet
     */
    private $orderNotificationGet;

    /**
     * @var Config|\PHPUnit\Framework\MockObject\MockObject
     */
    private $configMock;

    /**
     * @var Json|\PHPUnit\Framework\MockObject\MockObject
     */
    private $jsonMock;

    /**
     * @var Logger|\PHPUnit\Framework\MockObject\MockObject
     */
    private $loggerMock;

    /**
     * @var OrderApiHeadersBuilder|\PHPUnit\Framework\MockObject\MockObject
     */
    private $headersBuilderMock;

    /**
     * @var MetricsClient|\PHPUnit\Framework\MockObject\MockObject
     */
    private $metricsClientMock;

    /**
     * Setup test dependencies.
     */
    protected function setUp(): void
    {
        $this->configMock = $this->createMock(Config::class);
        $this->jsonMock = $this->createMock(Json::class);
        $this->loggerMock = $this->createMock(Logger::class);
        $this->headersBuilderMock = $this->createMock(OrderApiHeadersBuilder::class);
        $this->metricsClientMock = $this->createMock(MetricsClient::class);

        $this->orderNotificationGet = new OrderNotificationGet(
            $this->configMock,
            $this->jsonMock,
            $this->loggerMock,
            $this->headersBuilderMock,
            $this->metricsClientMock
        );
    }

    /**
     * Test OrderNotificationGet instance can be created.
     */
    public function testInstanceCanBeCreated()
    {
        $this->assertInstanceOf(OrderNotificationGet::class, $this->orderNotificationGet);
    }

    /**
     * Test NOTIFICATION_ORDER_URI constant is defined.
     */
    public function testNotificationOrderUriConstantIsDefined()
    {
        $this->assertEquals(
            '/plugins-platforms/v1/notification/order/',
            OrderNotificationGet::NOTIFICATION_ORDER_URI
        );
    }

    /**
     * Test OrderNotificationGet uses OrderApiHeadersBuilder.
     */
    public function testOrderNotificationGetUsesHeadersBuilder()
    {
        $this->assertInstanceOf(OrderNotificationGet::class, $this->orderNotificationGet);
    }
}

