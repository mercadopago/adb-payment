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

    public function testConstantsAreDefined(): void
    {
        $this->assertEquals('mp_order_id', ExtOrderIdDataRequest::ORDER_API_ID_KEY);
        $this->assertEquals('mp_payment_id_order', ExtOrderIdDataRequest::ORDER_API_PAYMENT_ID_KEY);
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
     * @dataProvider buildResultProvider
     */
    public function testBuildReturnsExpectedResult(
        array $additionalInfo,
        ?string $lastTransId,
        $expectedOrderId,
        $expectedPaymentId
    ): void {
        $paymentMock = $this->getMockBuilder(Payment::class)
            ->disableOriginalConstructor()
            ->getMock();

        $paymentMock->method('getAdditionalInformation')->willReturn($additionalInfo);
        $paymentMock->method('getLastTransId')->willReturn($lastTransId);

        $paymentDOMock = $this->createMock(PaymentDataObjectInterface::class);
        $paymentDOMock->method('getPayment')->willReturn($paymentMock);

        $result = $this->request->build(['payment' => $paymentDOMock]);

        $this->assertCount(2, $result);
        $this->assertArrayHasKey(ExtOrderIdDataRequest::ORDER_API_ID_KEY, $result);
        $this->assertArrayHasKey(ExtOrderIdDataRequest::ORDER_API_PAYMENT_ID_KEY, $result);
        $this->assertSame($expectedOrderId, $result[ExtOrderIdDataRequest::ORDER_API_ID_KEY]);
        $this->assertSame($expectedPaymentId, $result[ExtOrderIdDataRequest::ORDER_API_PAYMENT_ID_KEY]);
    }

    public function buildResultProvider(): array
    {
        return [
            'Order API - both fields present' => [
                [
                    ExtOrderIdDataRequest::ORDER_API_ID_KEY => 'PPORDO32YD7YI5KX2A9C0MZ1QZ33TJW',
                    ExtOrderIdDataRequest::ORDER_API_PAYMENT_ID_KEY => 'PPPAY71WFLIEBP0O7H4Q7QM0BQMF6I4',
                ],
                null,
                'PPORDO32YD7YI5KX2A9C0MZ1QZ33TJW',
                'PPPAY71WFLIEBP0O7H4Q7QM0BQMF6I4',
            ],
            'Payment API - only mp_order_id (numeric)' => [
                [ExtOrderIdDataRequest::ORDER_API_ID_KEY => '143625890728'],
                null,
                '143625890728',
                null,
            ],
            'fallback to lastTransId' => [
                [],
                'PPORD123456789',
                'PPORD123456789',
                null,
            ],
            'prefers mp_order_id over lastTransId' => [
                [ExtOrderIdDataRequest::ORDER_API_ID_KEY => 'PPORDO32YD7YI5KX2A9C0MZ1QZ33TJW'],
                'PPORD_OLD_TRANS_ID',
                'PPORDO32YD7YI5KX2A9C0MZ1QZ33TJW',
                null,
            ],
            'both sources empty - returns null' => [
                [],
                null,
                null,
                null,
            ],
            'empty string mp_payment_id_order' => [
                [
                    ExtOrderIdDataRequest::ORDER_API_ID_KEY => 'PPORDO32YD7YI5KX2A9C0MZ1QZ33TJW',
                    ExtOrderIdDataRequest::ORDER_API_PAYMENT_ID_KEY => '',
                ],
                null,
                'PPORDO32YD7YI5KX2A9C0MZ1QZ33TJW',
                '',
            ],
            'string zero mp_payment_id_order' => [
                [
                    ExtOrderIdDataRequest::ORDER_API_ID_KEY => 'PPORDO32YD7YI5KX2A9C0MZ1QZ33TJW',
                    ExtOrderIdDataRequest::ORDER_API_PAYMENT_ID_KEY => '0',
                ],
                null,
                'PPORDO32YD7YI5KX2A9C0MZ1QZ33TJW',
                '0',
            ],
        ];
    }

    /**
     * @dataProvider validMpOrderIdProvider
     */
    public function testBuildAcceptsVariousMpOrderIdFormats(string $mpOrderId): void
    {
        $paymentMock = $this->getMockBuilder(Payment::class)
            ->disableOriginalConstructor()
            ->getMock();

        $paymentMock->method('getAdditionalInformation')
            ->willReturn([ExtOrderIdDataRequest::ORDER_API_ID_KEY => $mpOrderId]);

        $paymentDOMock = $this->createMock(PaymentDataObjectInterface::class);
        $paymentDOMock->method('getPayment')->willReturn($paymentMock);

        $result = $this->request->build(['payment' => $paymentDOMock]);

        $this->assertEquals($mpOrderId, $result[ExtOrderIdDataRequest::ORDER_API_ID_KEY]);
    }

    public function validMpOrderIdProvider(): array
    {
        return [
            'standard Order API format' => ['PPORDO32YD7YI5KX2A9C0MZ1QZ33TJW'],
            'short format' => ['PPORD123'],
            'alphanumeric mixed' => ['ABC123DEF456'],
        ];
    }
}
