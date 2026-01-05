<?php
/**
 * Copyright © MercadoPago. All rights reserved.
 *
 * @author      Mercado Pago
 * @license     See LICENSE for license details.
 */

namespace MercadoPago\AdbPayment\Tests\Unit\Gateway\Request;

use InvalidArgumentException;
use Magento\Framework\UrlInterface;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use MercadoPago\AdbPayment\Gateway\Config\Config;
use MercadoPago\AdbPayment\Gateway\Request\NotificationUrlOrderDataRequest;
use PHPUnit\Framework\TestCase;

/**
 * Test for NotificationUrlOrderDataRequest.
 */
class NotificationUrlOrderDataRequestTest extends TestCase
{
    /**
     * @var UrlInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $urlInterfaceMock;

    /**
     * @var Config|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $configMock;

    /**
     * @var NotificationUrlOrderDataRequest
     */
    protected $notificationUrlOrderDataRequest;

    /**
     * Set up test dependencies.
     */
    public function setUp(): void
    {
        $this->urlInterfaceMock = $this->getMockBuilder(UrlInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->configMock = $this->getMockBuilder(Config::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->notificationUrlOrderDataRequest = new NotificationUrlOrderDataRequest(
            $this->urlInterfaceMock,
            $this->configMock
        );
    }

    /**
     * Test build method returns notification URL.
     */
    public function testBuildReturnsNotificationUrl()
    {
        $expectedUrl = 'https://example.com/mp/notification/order';

        $paymentDoMock = $this->getMockBuilder(PaymentDataObjectInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $buildSubject = ['payment' => $paymentDoMock];

        $this->urlInterfaceMock->expects($this->once())
            ->method('getUrl')
            ->with(NotificationUrlOrderDataRequest::PATH_TO_NOTIFICATION)
            ->willReturn($expectedUrl);

        $this->configMock->expects($this->once())
            ->method('getRewriteNotificationUrl')
            ->willReturn(null);

        $result = $this->notificationUrlOrderDataRequest->build($buildSubject);

        $this->assertIsArray($result);
        $this->assertArrayHasKey(NotificationUrlOrderDataRequest::NOTIFICATION_URL, $result);
        $this->assertEquals($expectedUrl, $result[NotificationUrlOrderDataRequest::NOTIFICATION_URL]);
    }

    /**
     * Test build method with rewrite notification URL.
     */
    public function testBuildWithRewriteNotificationUrl()
    {
        $defaultUrl = 'https://example.com/mp/notification/order';
        $rewriteUrl = 'https://custom.example.com/webhook/mercadopago/order';

        $paymentDoMock = $this->getMockBuilder(PaymentDataObjectInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $buildSubject = ['payment' => $paymentDoMock];

        $this->urlInterfaceMock->expects($this->once())
            ->method('getUrl')
            ->with(NotificationUrlOrderDataRequest::PATH_TO_NOTIFICATION)
            ->willReturn($defaultUrl);

        $this->configMock->expects($this->once())
            ->method('getRewriteNotificationUrl')
            ->willReturn($rewriteUrl);

        $result = $this->notificationUrlOrderDataRequest->build($buildSubject);

        $this->assertIsArray($result);
        $this->assertArrayHasKey(NotificationUrlOrderDataRequest::NOTIFICATION_URL, $result);
        $this->assertEquals($rewriteUrl, $result[NotificationUrlOrderDataRequest::NOTIFICATION_URL]);
    }

    /**
     * Test build method throws exception when payment is missing.
     */
    public function testBuildThrowsExceptionWhenPaymentMissing()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Payment data object should be provided');

        $buildSubject = [];

        $this->notificationUrlOrderDataRequest->build($buildSubject);
    }

    /**
     * Test build method throws exception when payment is not PaymentDataObjectInterface.
     */
    public function testBuildThrowsExceptionWhenPaymentInvalid()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Payment data object should be provided');

        $buildSubject = ['payment' => 'invalid'];

        $this->notificationUrlOrderDataRequest->build($buildSubject);
    }

    /**
     * Test PATH_TO_NOTIFICATION constant value.
     */
    public function testPathToNotificationConstant()
    {
        $this->assertEquals(
            'mp/notification/order',
            NotificationUrlOrderDataRequest::PATH_TO_NOTIFICATION
        );
    }

    /**
     * Test NOTIFICATION_URL constant value.
     */
    public function testNotificationUrlConstant()
    {
        $this->assertEquals(
            'notification_url',
            NotificationUrlOrderDataRequest::NOTIFICATION_URL
        );
    }
}

