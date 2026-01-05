<?php

namespace MercadoPago\AdbPayment\Tests\Unit\Gateway\Request;

use InvalidArgumentException;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use MercadoPago\AdbPayment\Gateway\Request\MetadataPaymentPixDataRequest;
use MercadoPago\AdbPayment\Gateway\Request\MetadataPaymentDataRequest;
use PHPUnit\Framework\TestCase;

class MetadataPaymentPixDataRequestTest extends TestCase
{
    /**
     * @var MetadataPaymentPixDataRequest
     */
    private $metadataPaymentPixDataRequest;

    protected function setUp(): void
    {
        $this->metadataPaymentPixDataRequest = new MetadataPaymentPixDataRequest();
    }

    public function testBuildWithValidPaymentDataObject()
    {
        $paymentDataObjectMock = $this->createMock(PaymentDataObjectInterface::class);

        $buildSubject = ['payment' => $paymentDataObjectMock];

        $expectedResult = [
            'metadata' => [
                'checkout' => 'custom',
                'checkout_type' => 'pix',
            ],
        ];

        $result = $this->metadataPaymentPixDataRequest->build($buildSubject);

        $this->assertEquals($expectedResult, $result);
        
        // Verifica que cpp_extra NÃO está presente
        $this->assertArrayNotHasKey('cpp_extra', $result['metadata']);
        
        // Verifica que os campos corretos estão presentes
        $this->assertArrayHasKey('checkout', $result['metadata']);
        $this->assertEquals('custom', $result['metadata']['checkout']);
        $this->assertArrayHasKey('checkout_type', $result['metadata']);
        $this->assertEquals('pix', $result['metadata']['checkout_type']);
    }

    public function testBuildWithInvalidPaymentDataObject()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Payment data object should be provided');

        $buildSubject = ['payment' => null];

        $this->metadataPaymentPixDataRequest->build($buildSubject);
    }

    public function testBuildWithMissingPaymentDataObject()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Payment data object should be provided');

        $buildSubject = [];

        $this->metadataPaymentPixDataRequest->build($buildSubject);
    }
}

