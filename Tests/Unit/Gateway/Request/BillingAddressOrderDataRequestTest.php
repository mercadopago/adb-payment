<?php

namespace MercadoPago\AdbPayment\Tests\Unit\Gateway\Request;

use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use MercadoPago\AdbPayment\Gateway\Data\Order\OrderAdapterFactory;
use MercadoPago\AdbPayment\Gateway\SubjectReader;
use MercadoPago\AdbPayment\Gateway\Request\BillingAddressOrderDataRequest;
use Magento\Sales\Model\Order\Payment;
use Magento\Sales\Api\Data\OrderAddressInterface;
use Magento\Payment\Gateway\Data\OrderAdapterInterface;
use MercadoPago\AdbPayment\Gateway\Data\Order\OrderAdapter;
use MercadoPago\AdbPayment\Gateway\Config\Config;
use PHPUnit\Framework\TestCase;

class BillingAddressOrderDataRequestTest extends TestCase
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

    public function testBuild()
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

        $paymentDoMock->expects($this->any())
            ->method('getOrder')
            ->willReturn($orderAdapterInterfaceMock);

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
            ->method('getCountryId')
            ->willReturn('BR');
        $billingAddressMock->expects($this->any())
            ->method('getRegionCode')
            ->willReturn('SP');
        $billingAddressMock->expects($this->any())
            ->method('getCity')
            ->willReturn('São Paulo');
        $billingAddressMock->expects($this->any())
            ->method('getPostcode')
            ->willReturn('01234-567');

        $this->configMock->expects($this->any())
            ->method('getValueForAddress')
            ->willReturnMap([
                [$billingAddressMock, BillingAddressOrderDataRequest::STREET_NAME, 'Rua Teste'],
                [$billingAddressMock, BillingAddressOrderDataRequest::STREET_NUMBER, '123'],
                [$billingAddressMock, BillingAddressOrderDataRequest::STREET_NEIGHBORHOOD, 'Centro'],
            ]);

        $billingAddressOrderDataRequest = new BillingAddressOrderDataRequest(
            $this->subjectReaderMock,
            $this->configMock,
            $this->orderAdapterFactoryMock
        );

        $result = $billingAddressOrderDataRequest->build($buildSubjectMock);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('payer', $result);
        $this->assertArrayHasKey('address', $result['payer']);
        $this->assertArrayHasKey('street_name', $result['payer']['address']);
        $this->assertArrayHasKey('street_number', $result['payer']['address']);
        $this->assertArrayHasKey('neighborhood', $result['payer']['address']);
        $this->assertArrayHasKey('city', $result['payer']['address']);
        $this->assertArrayHasKey('federal_unit', $result['payer']['address']);
        $this->assertArrayHasKey('zip_code', $result['payer']['address']);
        $this->assertEquals('Rua Teste', $result['payer']['address']['street_name']);
        $this->assertEquals('123', $result['payer']['address']['street_number']);
        $this->assertEquals('Centro', $result['payer']['address']['neighborhood']);
        $this->assertEquals('São Paulo', $result['payer']['address']['city']);
        $this->assertEquals('SP', $result['payer']['address']['federal_unit']);
        $this->assertEquals('01234567', $result['payer']['address']['zip_code']);
    }

    public function testBuildInvalidArgument()
    {
        $this->expectException(\InvalidArgumentException::class);

        $buildSubjectMock = [];

        $billingAddressOrderDataRequest = new BillingAddressOrderDataRequest(
            $this->subjectReaderMock,
            $this->configMock,
            $this->orderAdapterFactoryMock
        );

        $billingAddressOrderDataRequest->build($buildSubjectMock);
    }
}

