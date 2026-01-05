<?php

namespace MercadoPago\AdbPayment\Tests\Unit\Gateway\Request;

use InvalidArgumentException;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Gateway\Data\OrderAdapterInterface;
use MercadoPago\AdbPayment\Gateway\Request\MetadataPaymentOrderDataRequest;
use MercadoPago\AdbPayment\Gateway\SubjectReader;
use MercadoPago\AdbPayment\Gateway\Config\Config;
use Magento\Sales\Model\Order\Payment;
use PHPUnit\Framework\TestCase;

class MetadataPaymentOrderDataRequestTest extends TestCase
{
    /**
     * @var MetadataPaymentOrderDataRequest
     */
    private $metadataPaymentOrderDataRequest;

    /**
     * @var SubjectReader|\PHPUnit\Framework\MockObject\MockObject
     */
    private $subjectReaderMock;

    /**
     * @var Config|\PHPUnit\Framework\MockObject\MockObject
     */
    private $configMock;

    protected function setUp(): void
    {
        $this->subjectReaderMock = $this->createMock(SubjectReader::class);
        $this->configMock = $this->createMock(Config::class);
        
        $this->metadataPaymentOrderDataRequest = new MetadataPaymentOrderDataRequest(
            $this->subjectReaderMock,
            $this->configMock
        );
    }

    public function testBuildWithValidPaymentDataObject()
    {
        $paymentDataObjectMock = $this->createMock(PaymentDataObjectInterface::class);
        $orderMock = $this->createMock(OrderAdapterInterface::class);
        $paymentMock = $this->createMock(Payment::class);

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
            ->willReturn(null);

        $this->configMock->expects($this->once())
            ->method('getMpSiteId')
            ->with(1)
            ->willReturn('MLB');

        $this->configMock->expects($this->once())
            ->method('getMagentoVersion')
            ->willReturn('2.4.0');

        $this->configMock->expects($this->once())
            ->method('getModuleVersion')
            ->willReturn('1.0.0');

        $this->configMock->expects($this->once())
            ->method('isTestMode')
            ->willReturn(false);

        $this->configMock->expects($this->once())
            ->method('getMpSponsorId')
            ->with('MLB')
            ->willReturn('123456');

        $buildSubject = ['payment' => $paymentDataObjectMock];

        $result = $this->metadataPaymentOrderDataRequest->build($buildSubject);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('metadata', $result);
        $this->assertArrayHasKey('platform', $result['metadata']);
        $this->assertArrayHasKey('platform_version', $result['metadata']);
        $this->assertArrayHasKey('module_version', $result['metadata']);
        $this->assertArrayHasKey('test_mode', $result['metadata']);
        $this->assertArrayHasKey('sponsor_id', $result['metadata']);
        $this->assertArrayHasKey('site_id', $result['metadata']);
        $this->assertArrayHasKey('store_id', $result['metadata']);
        
        // Verifica que cpp_extra NÃO está presente
        $this->assertArrayNotHasKey('cpp_extra', $result['metadata']);
    }

    public function testBuildWithFlowId()
    {
        $paymentDataObjectMock = $this->createMock(PaymentDataObjectInterface::class);
        $orderMock = $this->createMock(OrderAdapterInterface::class);
        $paymentMock = $this->createMock(Payment::class);

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

        $this->configMock->expects($this->once())
            ->method('getMpSiteId')
            ->with(1)
            ->willReturn('MLB');

        $this->configMock->expects($this->once())
            ->method('getMagentoVersion')
            ->willReturn('2.4.0');

        $this->configMock->expects($this->once())
            ->method('getModuleVersion')
            ->willReturn('1.0.0');

        $this->configMock->expects($this->once())
            ->method('isTestMode')
            ->willReturn(false);

        $this->configMock->expects($this->once())
            ->method('getMpSponsorId')
            ->with('MLB')
            ->willReturn('123456');

        $buildSubject = ['payment' => $paymentDataObjectMock];

        $result = $this->metadataPaymentOrderDataRequest->build($buildSubject);

        $this->assertArrayHasKey('mp_flow_id', $result['metadata']);
        $this->assertEquals('test-flow-id', $result['metadata']['mp_flow_id']);
        
        // Verifica que cpp_extra NÃO está presente mesmo com flowId
        $this->assertArrayNotHasKey('cpp_extra', $result['metadata']);
    }

    public function testBuildWithInvalidPaymentDataObject()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Payment data object should be provided');

        $buildSubject = ['payment' => null];

        $this->metadataPaymentOrderDataRequest->build($buildSubject);
    }

    public function testBuildWithMissingPaymentDataObject()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Payment data object should be provided');

        $buildSubject = [];

        $this->metadataPaymentOrderDataRequest->build($buildSubject);
    }
}

