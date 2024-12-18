<?php

namespace MercadoPago\AdbPayment\Tests\Unit\Gateway\Request;

use InvalidArgumentException;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use MercadoPago\AdbPayment\Gateway\Request\MetadataPaymentYapeDataRequest;

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
}
