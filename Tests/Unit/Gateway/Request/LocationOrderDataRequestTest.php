<?php

namespace MercadoPago\AdbPayment\Tests\Unit\Gateway\Request;

use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use MercadoPago\AdbPayment\Gateway\Data\Order\OrderAdapterFactory;
use MercadoPago\AdbPayment\Gateway\SubjectReader;
use MercadoPago\AdbPayment\Gateway\Request\LocationOrderDataRequest;
use Magento\Sales\Model\Order\Payment;
use Magento\Sales\Api\Data\OrderAddressInterface;
use Magento\Payment\Gateway\Data\OrderAdapterInterface;
use MercadoPago\AdbPayment\Gateway\Data\Order\OrderAdapter;
use MercadoPago\AdbPayment\Gateway\Config\Config;
use PHPUnit\Framework\TestCase;

class LocationOrderDataRequestTest extends TestCase
{
    /**
     * @var SubjectReader
     */
    protected $subjectReaderMock;

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
        $this->subjectReaderMock = $this->getMockBuilder(SubjectReader::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->configMock = $this->getMockBuilder(Config::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->orderAdapterFactoryMock = $this->getMockBuilder(OrderAdapterFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    public function testBuildWithShippingAddress()
    {
        $paymentDoMock = $this->getMockBuilder(PaymentDataObjectInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $buildSubjectMock = ['payment' => $paymentDoMock];

        $this->subjectReaderMock->expects($this->any())
            ->method('readPayment')
            ->with($buildSubjectMock)
            ->willReturn($paymentDoMock);

        $paymentMock = $this->getMockBuilder(Payment::class)
            ->disableOriginalConstructor()
            ->getMock();

        $paymentDoMock->expects($this->any())
            ->method('getPayment')
            ->willReturn($paymentMock);

        $orderAdapterInterfaceMock = $this->getMockBuilder(OrderAdapterInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $paymentMock->expects($this->any())
            ->method('getOrder')
            ->willReturn($orderAdapterInterfaceMock);

        $orderAdapterMock = $this->getMockBuilder(OrderAdapter::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->orderAdapterFactoryMock->expects($this->any())
            ->method('create')
            ->with(['order' => $orderAdapterInterfaceMock])
            ->willReturn($orderAdapterMock);

        $shippingAddressMock = $this->getMockBuilder(OrderAddressInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $orderAdapterMock->expects($this->any())
            ->method('getShippingAddress')
            ->willReturn($shippingAddressMock);

        $shippingAddressMock->expects($this->any())
            ->method('getCountryId')
            ->willReturn('BR');
        $shippingAddressMock->expects($this->any())
            ->method('getRegionCode')
            ->willReturn('SP');

        $locationOrderDataRequest = new LocationOrderDataRequest(
            $this->subjectReaderMock,
            $this->configMock,
            $this->orderAdapterFactoryMock
        );

        $result = $locationOrderDataRequest->build($buildSubjectMock);

        $this->assertIsArray($result);
        $this->assertArrayHasKey(LocationOrderDataRequest::LOCATION, $result);
        $this->assertArrayHasKey(LocationOrderDataRequest::STATE_ID, $result[LocationOrderDataRequest::LOCATION]);
        $this->assertArrayHasKey(LocationOrderDataRequest::SOURCE, $result[LocationOrderDataRequest::LOCATION]);
        $this->assertEquals('BR-SP', $result[LocationOrderDataRequest::LOCATION][LocationOrderDataRequest::STATE_ID]);
        $this->assertEquals(LocationOrderDataRequest::SOURCE_SHIPMENT, $result[LocationOrderDataRequest::LOCATION][LocationOrderDataRequest::SOURCE]);
    }

    public function testBuildWithoutShippingAddress()
    {
        $paymentDoMock = $this->getMockBuilder(PaymentDataObjectInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $buildSubjectMock = ['payment' => $paymentDoMock];

        $this->subjectReaderMock->expects($this->any())
            ->method('readPayment')
            ->with($buildSubjectMock)
            ->willReturn($paymentDoMock);

        $paymentMock = $this->getMockBuilder(Payment::class)
            ->disableOriginalConstructor()
            ->getMock();

        $paymentDoMock->expects($this->any())
            ->method('getPayment')
            ->willReturn($paymentMock);

        $orderAdapterInterfaceMock = $this->getMockBuilder(OrderAdapterInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $paymentMock->expects($this->any())
            ->method('getOrder')
            ->willReturn($orderAdapterInterfaceMock);

        $orderAdapterMock = $this->getMockBuilder(OrderAdapter::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->orderAdapterFactoryMock->expects($this->any())
            ->method('create')
            ->with(['order' => $orderAdapterInterfaceMock])
            ->willReturn($orderAdapterMock);

        $orderAdapterMock->expects($this->any())
            ->method('getShippingAddress')
            ->willReturn(null);

        $locationOrderDataRequest = new LocationOrderDataRequest(
            $this->subjectReaderMock,
            $this->configMock,
            $this->orderAdapterFactoryMock
        );

        $result = $locationOrderDataRequest->build($buildSubjectMock);

        $this->assertIsArray($result);
        $this->assertEmpty($result);
        $this->assertArrayNotHasKey(LocationOrderDataRequest::LOCATION, $result);
    }

    public function testBuildInvalidArgument()
    {
        $this->expectException(\InvalidArgumentException::class);

        $buildSubjectMock = [];

        $locationOrderDataRequest = new LocationOrderDataRequest(
            $this->subjectReaderMock,
            $this->configMock,
            $this->orderAdapterFactoryMock
        );

        $locationOrderDataRequest->build($buildSubjectMock);
    }
}

