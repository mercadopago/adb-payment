<?php

namespace MercadoPago\AdbPayment\Tests\Unit\Gateway\Request;

use InvalidArgumentException;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Gateway\Data\OrderAdapterInterface;
use MercadoPago\AdbPayment\Gateway\Request\MetadataPaymentYapeDataRequest;
use MercadoPago\AdbPayment\Gateway\Request\MetadataPaymentDataRequest;
use MercadoPago\AdbPayment\Gateway\SubjectReader;
use MercadoPago\AdbPayment\Gateway\Config\Config;
use Magento\Payment\Gateway\Data\PaymentDataObject;
use Magento\Sales\Model\Order\Payment;
use Magento\Sales\Model\Order\Payment\Proxy;

use PHPUnit\Framework\TestCase;


class MetadataPaymentYapeDataRequestTest extends TestCase
{
    /**
     * @var MetadataPaymentYapeDataRequest
     */
    private $metadataPaymentYapeDataRequest;

    protected function setUp(): void
    {
        $this->metadataPaymentYapeDataRequest = new MetadataPaymentYapeDataRequest();
    }

    public function testBuildWithValidPaymentDataObject()
    {
        $paymentDataObjectMock = $this->createMock(PaymentDataObjectInterface::class);

        $buildSubject = ['payment' => $paymentDataObjectMock];

        $expectedResult = [
            'metadata' => [
                'checkout' => 'custom',
                'checkout_type' => 'yape',
                'cpp_extra' => [
                    'checkout' => 'custom',
                    'checkout_type' => 'yape',
                ],
            ],
        ];

        $result = $this->metadataPaymentYapeDataRequest->build($buildSubject);

        $this->assertEquals($expectedResult, $result);
    }

    public function testBuildWithInvalidPaymentDataObject()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Payment data object should be provided');

        $buildSubject = ['payment' => null];

        $this->metadataPaymentYapeDataRequest->build($buildSubject);
    }

    public function testBuildWithMissingPaymentDataObject()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Payment data object should be provided');

        $buildSubject = [];

        $this->metadataPaymentYapeDataRequest->build($buildSubject);
    }

    public function testBuildWithValidPaymentDataObjectAndFlowId()
    {
        $configMock = $this->createMock(Config::class);
        $subjectReaderMock = $this->createMock(SubjectReader::class);
        $paymentMock = $this->createMock(Payment::class);
        $orderMock = $this->createMock(OrderAdapterInterface::class);
        $paymentDataObjectMock = $this->createMock(PaymentDataObjectInterface::class);

        $paymentDataObjectMock->expects($this->once())
            ->method('getOrder')
            ->willReturn($orderMock);
            
        $paymentDataObjectMock->expects($this->once())
            ->method('getPayment')
            ->willReturn($paymentMock);

        $orderMock->expects($this->once())
            ->method('getStoreId')
            ->willReturn(1);

        $paymentMock->expects($this->once())
            ->method('getAdditionalInformation')
            ->with('mp_flow_id')
            ->willReturn('test-flow-id');

        $configMock->expects($this->any())
            ->method('getMpSiteId')
            ->willReturn('MLB');

        $metadataRequest = new MetadataPaymentDataRequest($subjectReaderMock, $configMock);
        
        $buildSubject = ['payment' => $paymentDataObjectMock];
        $result = $metadataRequest->build($buildSubject);

        $this->assertArrayHasKey('metadata', $result);
        $this->assertArrayHasKey('mp_flow_id', $result['metadata']);
        $this->assertEquals('test-flow-id', $result['metadata']['mp_flow_id']);
    }
}
