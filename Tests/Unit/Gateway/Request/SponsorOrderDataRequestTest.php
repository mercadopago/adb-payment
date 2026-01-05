<?php

namespace MercadoPago\AdbPayment\Tests\Unit\Gateway\Request;

use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use MercadoPago\AdbPayment\Gateway\Data\Order\OrderAdapterFactory;
use MercadoPago\AdbPayment\Gateway\Request\SponsorOrderDataRequest;
use Magento\Sales\Model\Order\Payment;
use Magento\Sales\Api\Data\OrderAddressInterface;
use Magento\Payment\Gateway\Data\OrderAdapterInterface;
use MercadoPago\AdbPayment\Gateway\Data\Order\OrderAdapter;
use MercadoPago\AdbPayment\Gateway\Config\Config;
use PHPUnit\Framework\TestCase;

class SponsorOrderDataRequestTest extends TestCase
{
    /**
     * @var Config
     */
    protected $configMock;

    /**
     * @var OrderAdapterFactory
     */
    protected $orderAdapterFactoryMock;

    public function setUp(): void
    {
        $this->configMock = $this->getMockBuilder(Config::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->orderAdapterFactoryMock = $this->getMockBuilder(OrderAdapterFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    public function testBuildWithSponsorIdAsString()
    {
        $paymentDoMock = $this->getMockBuilder(PaymentDataObjectInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $buildSubjectMock = ['payment' => $paymentDoMock];

        $paymentMock = $this->getMockBuilder(Payment::class)
            ->disableOriginalConstructor()
            ->getMock();

        $paymentDoMock->expects($this->any())
            ->method('getPayment')
            ->willReturn($paymentMock);

        $orderAdapterInterfaceMock = $this->getMockBuilder(OrderAdapterInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $paymentDoMock->expects($this->any())
            ->method('getOrder')
            ->willReturn($orderAdapterInterfaceMock);

        $orderAdapterInterfaceMock->expects($this->any())
            ->method('getStoreId')
            ->willReturn(1);

        $orderAdapterMock = $this->getMockBuilder(OrderAdapter::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->orderAdapterFactoryMock->expects($this->any())
            ->method('create')
            ->withAnyParameters()
            ->willReturn($orderAdapterMock);

        $billingAddressMock = $this->getMockBuilder(OrderAddressInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $orderAdapterMock->expects($this->any())
            ->method('getBillingAddress')
            ->willReturn($billingAddressMock);

        $billingAddressMock->expects($this->any())
            ->method('getEmail')
            ->willReturn('customer@example.com');

        $this->configMock->expects($this->any())
            ->method('getMpSiteId')
            ->with(1)
            ->willReturn('MLB');

        $this->configMock->expects($this->any())
            ->method('getMpSponsorId')
            ->with('MLB')
            ->willReturn('222567845');

        $sponsorOrderDataRequest = new SponsorOrderDataRequest(
            $this->configMock,
            $this->orderAdapterFactoryMock
        );

        $result = $sponsorOrderDataRequest->build($buildSubjectMock);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('sponsor_id', $result);
        $this->assertIsString($result['sponsor_id']);
        $this->assertEquals('222567845', $result['sponsor_id']);
    }

    public function testBuildWithTestUserEmailReturnsNull()
    {
        $paymentDoMock = $this->getMockBuilder(PaymentDataObjectInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $buildSubjectMock = ['payment' => $paymentDoMock];

        $paymentMock = $this->getMockBuilder(Payment::class)
            ->disableOriginalConstructor()
            ->getMock();

        $paymentDoMock->expects($this->any())
            ->method('getPayment')
            ->willReturn($paymentMock);

        $orderAdapterInterfaceMock = $this->getMockBuilder(OrderAdapterInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $paymentDoMock->expects($this->any())
            ->method('getOrder')
            ->willReturn($orderAdapterInterfaceMock);

        $orderAdapterInterfaceMock->expects($this->any())
            ->method('getStoreId')
            ->willReturn(1);

        $orderAdapterMock = $this->getMockBuilder(OrderAdapter::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->orderAdapterFactoryMock->expects($this->any())
            ->method('create')
            ->withAnyParameters()
            ->willReturn($orderAdapterMock);

        $billingAddressMock = $this->getMockBuilder(OrderAddressInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $orderAdapterMock->expects($this->any())
            ->method('getBillingAddress')
            ->willReturn($billingAddressMock);

        $billingAddressMock->expects($this->any())
            ->method('getEmail')
            ->willReturn('test@testuser.com');

        $this->configMock->expects($this->any())
            ->method('getMpSiteId')
            ->with(1)
            ->willReturn('MLB');

        $this->configMock->expects($this->any())
            ->method('getMpSponsorId')
            ->with('MLB')
            ->willReturn('222567845');

        $sponsorOrderDataRequest = new SponsorOrderDataRequest(
            $this->configMock,
            $this->orderAdapterFactoryMock
        );

        $result = $sponsorOrderDataRequest->build($buildSubjectMock);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('sponsor_id', $result);
        $this->assertNull($result['sponsor_id']);
    }

    public function testBuildInvalidArgument()
    {
        $this->expectException(\InvalidArgumentException::class);

        $buildSubjectMock = [];

        $sponsorOrderDataRequest = new SponsorOrderDataRequest(
            $this->configMock,
            $this->orderAdapterFactoryMock
        );

        $sponsorOrderDataRequest->build($buildSubjectMock);
    }
}

