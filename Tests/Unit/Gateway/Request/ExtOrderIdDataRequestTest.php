<?php
/**
 * Copyright © MercadoPago. All rights reserved.
 *
 * @author      Mercado Pago
 * @license     See LICENSE for license details.
 */

namespace MercadoPago\AdbPayment\Tests\Unit\Gateway\Request;

use InvalidArgumentException;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Sales\Model\Order\Payment;
use MercadoPago\AdbPayment\Gateway\Request\ExtOrderIdDataRequest;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for ExtOrderIdDataRequest.
 */
class ExtOrderIdDataRequestTest extends TestCase
{
    /**
     * @var ExtOrderIdDataRequest
     */
    private $request;

    protected function setUp(): void
    {
        $this->request = new ExtOrderIdDataRequest();
    }

    /**
     * Test build returns mp_order_id from additional information.
     */
    public function testBuildReturnsMpOrderIdFromAdditionalInformation(): void
    {
        $mpOrderId = 'PPORDO32YD7YI5KX2A9C0MZ1QZ33TJW';

        $paymentMock = $this->getMockBuilder(Payment::class)
            ->disableOriginalConstructor()
            ->getMock();

        $paymentMock->method('getAdditionalInformation')
            ->willReturn([ExtOrderIdDataRequest::MP_ORDER_ID => $mpOrderId]);

        $paymentDOMock = $this->createMock(PaymentDataObjectInterface::class);
        $paymentDOMock->method('getPayment')->willReturn($paymentMock);

        $result = $this->request->build(['payment' => $paymentDOMock]);

        $this->assertArrayHasKey(ExtOrderIdDataRequest::MP_ORDER_ID, $result);
        $this->assertEquals($mpOrderId, $result[ExtOrderIdDataRequest::MP_ORDER_ID]);
    }

    /**
     * Test build falls back to getLastTransId when mp_order_id is not in additional info.
     */
    public function testBuildFallsBackToLastTransId(): void
    {
        $lastTransId = 'PPORD123456789';

        $paymentMock = $this->getMockBuilder(Payment::class)
            ->disableOriginalConstructor()
            ->getMock();

        $paymentMock->method('getAdditionalInformation')
            ->willReturn([]);

        $paymentMock->method('getLastTransId')
            ->willReturn($lastTransId);

        $paymentDOMock = $this->createMock(PaymentDataObjectInterface::class);
        $paymentDOMock->method('getPayment')->willReturn($paymentMock);

        $result = $this->request->build(['payment' => $paymentDOMock]);

        $this->assertArrayHasKey(ExtOrderIdDataRequest::MP_ORDER_ID, $result);
        $this->assertEquals($lastTransId, $result[ExtOrderIdDataRequest::MP_ORDER_ID]);
    }

    /**
     * Test build prefers mp_order_id over lastTransId when both are available.
     */
    public function testBuildPrefersMpOrderIdOverLastTransId(): void
    {
        $mpOrderId = 'PPORDO32YD7YI5KX2A9C0MZ1QZ33TJW';
        $lastTransId = 'PPORD_OLD_TRANS_ID';

        $paymentMock = $this->getMockBuilder(Payment::class)
            ->disableOriginalConstructor()
            ->getMock();

        $paymentMock->method('getAdditionalInformation')
            ->willReturn([ExtOrderIdDataRequest::MP_ORDER_ID => $mpOrderId]);

        $paymentMock->method('getLastTransId')
            ->willReturn($lastTransId);

        $paymentDOMock = $this->createMock(PaymentDataObjectInterface::class);
        $paymentDOMock->method('getPayment')->willReturn($paymentMock);

        $result = $this->request->build(['payment' => $paymentDOMock]);

        $this->assertEquals($mpOrderId, $result[ExtOrderIdDataRequest::MP_ORDER_ID]);
        $this->assertNotEquals($lastTransId, $result[ExtOrderIdDataRequest::MP_ORDER_ID]);
    }

    /**
     * @dataProvider invalidPaymentProvider
     */
    public function testBuildThrowsExceptionForInvalidPayment(array $buildSubject): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Payment data object should be provided');

        $this->request->build($buildSubject);
    }

    /**
     * Data provider for invalid payment tests.
     */
    public function invalidPaymentProvider(): array
    {
        return [
            'missing payment' => [[]],
            'null payment' => [['payment' => null]],
            'invalid type string' => [['payment' => 'invalid']],
            'invalid type array' => [['payment' => []]],
            'invalid type object' => [['payment' => new \stdClass()]],
        ];
    }

    /**
     * Test build returns null mp_order_id when both sources are empty.
     */
    public function testBuildReturnsNullWhenBothSourcesAreEmpty(): void
    {
        $paymentMock = $this->getMockBuilder(Payment::class)
            ->disableOriginalConstructor()
            ->getMock();

        $paymentMock->method('getAdditionalInformation')
            ->willReturn([]);

        $paymentMock->method('getLastTransId')
            ->willReturn(null);

        $paymentDOMock = $this->createMock(PaymentDataObjectInterface::class);
        $paymentDOMock->method('getPayment')->willReturn($paymentMock);

        $result = $this->request->build(['payment' => $paymentDOMock]);

        $this->assertArrayHasKey(ExtOrderIdDataRequest::MP_ORDER_ID, $result);
        $this->assertNull($result[ExtOrderIdDataRequest::MP_ORDER_ID]);
    }

    /**
     * @dataProvider validMpOrderIdProvider
     */
    public function testBuildWithVariousMpOrderIdFormats(string $mpOrderId): void
    {
        $paymentMock = $this->getMockBuilder(Payment::class)
            ->disableOriginalConstructor()
            ->getMock();

        $paymentMock->method('getAdditionalInformation')
            ->willReturn([ExtOrderIdDataRequest::MP_ORDER_ID => $mpOrderId]);

        $paymentDOMock = $this->createMock(PaymentDataObjectInterface::class);
        $paymentDOMock->method('getPayment')->willReturn($paymentMock);

        $result = $this->request->build(['payment' => $paymentDOMock]);

        $this->assertEquals($mpOrderId, $result[ExtOrderIdDataRequest::MP_ORDER_ID]);
    }

    /**
     * Data provider for valid mp_order_id formats.
     */
    public function validMpOrderIdProvider(): array
    {
        return [
            'standard Order API format' => ['PPORDO32YD7YI5KX2A9C0MZ1QZ33TJW'],
            'short format' => ['PPORD123'],
            'numeric format' => ['12345678901234567890'],
            'alphanumeric mixed' => ['ABC123DEF456'],
        ];
    }

    /**
     * Test that result array only contains mp_order_id key.
     */
    public function testBuildReturnsOnlyMpOrderIdKey(): void
    {
        $mpOrderId = 'PPORDO32YD7YI5KX2A9C0MZ1QZ33TJW';

        $paymentMock = $this->getMockBuilder(Payment::class)
            ->disableOriginalConstructor()
            ->getMock();

        $paymentMock->method('getAdditionalInformation')
            ->willReturn([ExtOrderIdDataRequest::MP_ORDER_ID => $mpOrderId]);

        $paymentDOMock = $this->createMock(PaymentDataObjectInterface::class);
        $paymentDOMock->method('getPayment')->willReturn($paymentMock);

        $result = $this->request->build(['payment' => $paymentDOMock]);

        $this->assertCount(1, $result);
        $this->assertArrayHasKey(ExtOrderIdDataRequest::MP_ORDER_ID, $result);
    }
}

