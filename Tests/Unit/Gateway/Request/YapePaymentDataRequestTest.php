<?php

namespace MercadoPago\AdbPayment\Tests\Unit\Gateway\Request;

use InvalidArgumentException;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use MercadoPago\AdbPayment\Gateway\Request\YapePaymentDataRequest;

use PHPUnit\Framework\TestCase;
use Magento\Payment\Model\InfoInterface;


class YapePaymentDataRequestTest extends TestCase
{
    /**
     * @var YapePaymentDataRequest
     */
    private $yapePaymentDataRequest;

    protected function setUp(): void
    {
        $this->yapePaymentDataRequest = new YapePaymentDataRequest();
    }

    public function testBuildWithValidPaymentData()
    {
        $paymentDataObjectMock = $this->createMock(PaymentDataObjectInterface::class);
        $paymentMock = $this->createMock(InfoInterface::class);

        $paymentMock->expects($this->once())
            ->method('getAdditionalInformation')
            ->with(YapePaymentDataRequest::YAPE_TOKEN)
            ->willReturn('test_token');

        $paymentDataObjectMock->expects($this->once())
            ->method('getPayment')
            ->willReturn($paymentMock);

        $buildSubject = ['payment' => $paymentDataObjectMock];

        $result = $this->yapePaymentDataRequest->build($buildSubject);

        $this->assertIsArray($result);
        $this->assertArrayHasKey(YapePaymentDataRequest::PAYMENT_METHOD_ID, $result);
        $this->assertEquals('yape', $result[YapePaymentDataRequest::PAYMENT_METHOD_ID]);
        $this->assertArrayHasKey(YapePaymentDataRequest::TOKEN, $result);
        $this->assertEquals('test_token', $result[YapePaymentDataRequest::TOKEN]);
        $this->assertArrayHasKey(YapePaymentDataRequest::INSTALLMENTS, $result);
        $this->assertEquals(1, $result[YapePaymentDataRequest::INSTALLMENTS]);
    }

    public function testBuildWithInvalidPaymentData()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Payment data object should be provided');

        $buildSubject = ['payment' => null];

        $this->yapePaymentDataRequest->build($buildSubject);
    }

    public function testBuildWithMissingPaymentData()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Payment data object should be provided');

        $buildSubject = [];

        $this->yapePaymentDataRequest->build($buildSubject);
    }
}
