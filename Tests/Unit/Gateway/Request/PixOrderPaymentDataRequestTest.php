<?php

namespace MercadoPago\AdbPayment\Tests\Unit\Gateway\Request;

use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use MercadoPago\AdbPayment\Gateway\Data\Order\OrderAdapterFactory;
use MercadoPago\AdbPayment\Gateway\SubjectReader;
use MercadoPago\AdbPayment\Gateway\Request\PixOrderPaymentDataRequest;
use Magento\Sales\Model\Order\Payment;
use Magento\Payment\Gateway\Data\OrderAdapterInterface;
use MercadoPago\AdbPayment\Gateway\Data\Order\OrderAdapter;
use MercadoPago\AdbPayment\Gateway\Config\Config;
use MercadoPago\AdbPayment\Gateway\Config\ConfigPix;
use PHPUnit\Framework\TestCase;

class PixOrderPaymentDataRequestTest extends TestCase
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
     * @var ConfigPix
     */
    protected $configPixMock;

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

        $this->configPixMock = $this->getMockBuilder(ConfigPix::class)
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

        $orderMock = $this->getMockBuilder(\Magento\Sales\Model\Order::class)
            ->disableOriginalConstructor()
            ->getMock();

        $paymentMock->expects($this->any())
            ->method('getOrder')
            ->willReturn($orderMock);

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
            ->with(['order' => $orderMock])
            ->willReturn($orderAdapterMock);

        $orderAdapterMock->expects($this->any())
            ->method('getGrandTotalAmount')
            ->willReturn(100.00);

        $this->configMock->expects($this->any())
            ->method('formatPrice')
            ->with(100.00, 1)
            ->willReturn(100.00);

        $this->configPixMock->expects($this->any())
            ->method('getExpirationDuration')
            ->with(1)
            ->willReturn('P1D');

        $pixOrderPaymentDataRequest = new PixOrderPaymentDataRequest(
            $this->subjectReaderMock,
            $this->configMock,
            $this->configPixMock,
            $this->orderAdapterFactoryMock
        );

        $result = $pixOrderPaymentDataRequest->build($buildSubjectMock);

        $this->assertIsArray($result);
        $this->assertArrayHasKey(PixOrderPaymentDataRequest::TYPE, $result);
        $this->assertEquals('online', $result[PixOrderPaymentDataRequest::TYPE]);
        $this->assertArrayHasKey(PixOrderPaymentDataRequest::PAYMENTS, $result);
        $this->assertIsArray($result[PixOrderPaymentDataRequest::PAYMENTS]);
        $this->assertCount(1, $result[PixOrderPaymentDataRequest::PAYMENTS]);
        $this->assertArrayHasKey(PixOrderPaymentDataRequest::AMOUNT, $result[PixOrderPaymentDataRequest::PAYMENTS][0]);
        $this->assertEquals(100.00, $result[PixOrderPaymentDataRequest::PAYMENTS][0][PixOrderPaymentDataRequest::AMOUNT]);
        $this->assertArrayHasKey(PixOrderPaymentDataRequest::PAYMENT_METHOD, $result[PixOrderPaymentDataRequest::PAYMENTS][0]);
        $this->assertArrayHasKey(PixOrderPaymentDataRequest::PAYMENT_METHOD_ID, $result[PixOrderPaymentDataRequest::PAYMENTS][0][PixOrderPaymentDataRequest::PAYMENT_METHOD]);
        $this->assertEquals('pix', $result[PixOrderPaymentDataRequest::PAYMENTS][0][PixOrderPaymentDataRequest::PAYMENT_METHOD][PixOrderPaymentDataRequest::PAYMENT_METHOD_ID]);
        $this->assertArrayHasKey(PixOrderPaymentDataRequest::PAYMENT_METHOD_TYPE, $result[PixOrderPaymentDataRequest::PAYMENTS][0][PixOrderPaymentDataRequest::PAYMENT_METHOD]);
        $this->assertEquals('bank_transfer', $result[PixOrderPaymentDataRequest::PAYMENTS][0][PixOrderPaymentDataRequest::PAYMENT_METHOD][PixOrderPaymentDataRequest::PAYMENT_METHOD_TYPE]);
        $this->assertArrayHasKey(PixOrderPaymentDataRequest::DATE_OF_EXPIRATION, $result[PixOrderPaymentDataRequest::PAYMENTS][0]);
        $this->assertEquals('P1D', $result[PixOrderPaymentDataRequest::PAYMENTS][0][PixOrderPaymentDataRequest::DATE_OF_EXPIRATION]);
    }

    public function testBuildInvalidArgument()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Payment data object should be provided');

        $buildSubjectMock = [];

        $pixOrderPaymentDataRequest = new PixOrderPaymentDataRequest(
            $this->subjectReaderMock,
            $this->configMock,
            $this->configPixMock,
            $this->orderAdapterFactoryMock
        );

        $pixOrderPaymentDataRequest->build($buildSubjectMock);
    }

    public function testBuildWithNullPayment()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Payment data object should be provided');

        $buildSubjectMock = ['payment' => null];

        $pixOrderPaymentDataRequest = new PixOrderPaymentDataRequest(
            $this->subjectReaderMock,
            $this->configMock,
            $this->configPixMock,
            $this->orderAdapterFactoryMock
        );

        $pixOrderPaymentDataRequest->build($buildSubjectMock);
    }
}

