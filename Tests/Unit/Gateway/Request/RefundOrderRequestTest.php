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
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Creditmemo;
use Magento\Sales\Model\Order\Payment;
use MercadoPago\AdbPayment\Gateway\Config\Config;
use MercadoPago\AdbPayment\Gateway\Request\RefundOrderRequest;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for RefundOrderRequest.
 */
class RefundOrderRequestTest extends TestCase
{
    /**
     * @var RefundOrderRequest
     */
    private $request;

    /**
     * @var Config|MockObject
     */
    private $configMock;

    protected function setUp(): void
    {
        $this->configMock = $this->createMock(Config::class);
        $this->configMock->method('formatPrice')
            ->willReturnCallback(fn($price) => (float) $price);

        $this->request = new RefundOrderRequest($this->configMock);
    }

    /**
     * @dataProvider buildProvider
     */
    public function testBuild(
        float $orderTotal,
        float $creditmemoTotal,
        string $transactionId,
        string $orderIncrementId,
        float $amountRefunded,
        ?string $mpPaymentIdOrder,
        string $expectedOrderId,
        string $expectedRefundKey,
        bool $expectedIsPartial
    ): void {
        $result = $this->buildWithMocks(
            $orderTotal,
            $creditmemoTotal,
            $transactionId,
            $orderIncrementId,
            $amountRefunded,
            $mpPaymentIdOrder
        );

        $this->assertEquals($expectedOrderId, $result['mp_order_id']);
        $this->assertEquals($expectedRefundKey, $result['refund_key']);
        $this->assertEquals($creditmemoTotal, $result['amount']);
        $this->assertEquals($expectedIsPartial, $result['is_partial_refund']);

        if ($expectedIsPartial) {
            $this->assertEquals($mpPaymentIdOrder, $result['mp_payment_id_order']);
        }
    }

    /**
     * Data provider for build tests.
     */
    public function buildProvider(): array
    {
        return [
            'total refund' => [
                100.00,                    // orderTotal
                100.00,                    // creditmemoTotal
                'PPORD123-capture',        // transactionId
                '100000001',               // orderIncrementId
                0.00,                      // amountRefunded
                null,                      // mpPaymentIdOrder (not needed for total refund)
                'PPORD123',                // expectedOrderId
                '100000001-0',             // expectedRefundKey
                false,                     // expectedIsPartial
            ],
            'partial refund' => [
                100.00,
                50.00,
                'PPORD456-capture-refund',
                '100000002',
                0.00,
                'PPPAY456ABCDEFGHIJKLMNOPQR',
                'PPORD456',
                '100000002-0',
                true,
            ],
            'second partial refund' => [
                100.00,
                25.00,
                'PPORD789',
                '100000003',
                50.00,
                'PPPAY789ABCDEFGHIJKLMNOPQR',
                'PPORD789',
                '100000003-50',
                true,
            ],
        ];
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
        ];
    }

    /**
     * @dataProvider transactionIdRegexProvider
     */
    public function testTransactionIdRegex(string $input, string $expected): void
    {
        $result = $this->buildWithMocks(100.00, 100.00, $input, '100000001', 0.00, 'PPPAY123');

        $this->assertEquals($expected, $result['mp_order_id']);
    }

    /**
     * Data provider for transaction id regex tests.
     */
    public function transactionIdRegexProvider(): array
    {
        return [
            'with capture suffix' => ['PPORD123-capture', 'PPORD123'],
            'with refund suffix' => ['PPORD123-refund', 'PPORD123'],
            'with void suffix' => ['PPORD123-void', 'PPORD123'],
            'with capture-refund suffix' => ['PPORD123-capture-refund', 'PPORD123'],
            'with capture-refund-void suffix' => ['PPORD123-capture-refund-void', 'PPORD123'],
            'without suffix' => ['PPORD123', 'PPORD123'],
        ];
    }

    /**
     * @dataProvider refundKeyUniquenessProvider
     */
    public function testRefundKeyUniqueness(float $amountRefunded, string $expectedRefundKey): void
    {
        $result = $this->buildWithMocks(100.00, 25.00, 'PPORD123', '100000001', $amountRefunded, 'PPPAY123');

        $this->assertEquals($expectedRefundKey, $result['refund_key']);
    }

    /**
     * Data provider for refund key uniqueness tests.
     */
    public function refundKeyUniquenessProvider(): array
    {
        return [
            'first refund' => [0.00, '100000001-0'],
            'second refund after $25' => [25.00, '100000001-25'],
            'third refund after $50' => [50.00, '100000001-50'],
            'fourth refund after $75' => [75.00, '100000001-75'],
        ];
    }

    /**
     * Build request with fresh mocks.
     */
    private function buildWithMocks(
        float $orderTotal,
        float $creditmemoTotal,
        string $transactionId,
        string $orderIncrementId,
        float $amountRefunded,
        ?string $mpPaymentIdOrder
    ): array {
        $orderMock = $this->createMock(Order::class);
        $orderMock->method('getStoreId')->willReturn(1);
        $orderMock->method('getGrandTotal')->willReturn($orderTotal);
        $orderMock->method('getIncrementId')->willReturn($orderIncrementId);

        $creditmemoMock = $this->createMock(Creditmemo::class);
        $creditmemoMock->method('getGrandTotal')->willReturn($creditmemoTotal);

        $paymentMock = $this->createMock(Payment::class);
        $paymentMock->method('getOrder')->willReturn($orderMock);
        $paymentMock->method('getCreditMemo')->willReturn($creditmemoMock);
        $paymentMock->method('getTransactionId')->willReturn($transactionId);
        $paymentMock->method('getAmountRefunded')->willReturn($amountRefunded);
        $paymentMock->method('getAdditionalInformation')
            ->with('mp_payment_id_order')
            ->willReturn($mpPaymentIdOrder);

        $paymentDataObjectMock = $this->createMock(PaymentDataObjectInterface::class);
        $paymentDataObjectMock->method('getPayment')->willReturn($paymentMock);

        return $this->request->build(['payment' => $paymentDataObjectMock]);
    }
}
