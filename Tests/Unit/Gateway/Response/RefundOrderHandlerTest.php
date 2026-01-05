<?php
/**
 * Copyright © MercadoPago. All rights reserved.
 *
 * @author      Mercado Pago
 * @license     See LICENSE for license details.
 */

namespace MercadoPago\AdbPayment\Tests\Unit\Gateway\Response;

use InvalidArgumentException;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Sales\Model\Order\Creditmemo;
use Magento\Sales\Model\Order\Payment;
use MercadoPago\AdbPayment\Gateway\Response\RefundOrderHandler;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for RefundOrderHandler.
 */
class RefundOrderHandlerTest extends TestCase
{
    /**
     * @var RefundOrderHandler
     */
    private $handler;

    /**
     * @var Payment|MockObject
     */
    private $paymentMock;

    /**
     * @var PaymentDataObjectInterface|MockObject
     */
    private $paymentDataObjectMock;

    /**
     * @var Creditmemo|MockObject
     */
    private $creditmemoMock;

    protected function setUp(): void
    {
        $this->handler = new RefundOrderHandler();
        $this->paymentMock = $this->createMock(Payment::class);
        $this->paymentDataObjectMock = $this->createMock(PaymentDataObjectInterface::class);
        $this->creditmemoMock = $this->createMock(Creditmemo::class);

        $this->paymentDataObjectMock->method('getPayment')->willReturn($this->paymentMock);
        $this->paymentMock->method('getCreditmemo')->willReturn($this->creditmemoMock);
    }

    /**
     * @dataProvider invalidHandlingSubjectProvider
     */
    public function testHandleThrowsExceptionForInvalidPayment(array $handlingSubject): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Payment data object should be provided');

        $this->handler->handle($handlingSubject, []);
    }

    /**
     * Data provider for invalid handling subject.
     */
    public function invalidHandlingSubjectProvider(): array
    {
        return [
            'empty subject' => [[]],
            'null payment' => [['payment' => null]],
            'invalid type' => [['payment' => 'invalid']],
            'array payment' => [['payment' => []]],
        ];
    }

    /**
     * @dataProvider statusMappingProvider
     */
    public function testHandleSetsCorrectCreditmemoState(string $apiStatus, int $expectedState): void
    {
        $refundId = 'REFUND123';
        $response = $this->buildResponse($apiStatus, $refundId);

        $this->paymentMock->expects($this->once())
            ->method('setTransactionId')
            ->with($refundId);

        $this->paymentMock->expects($this->once())
            ->method('setAdditionalInformation')
            ->with('mp_refund_id', $refundId);

        $this->creditmemoMock->expects($this->once())
            ->method('setTransactionId')
            ->with($refundId);

        $this->creditmemoMock->expects($this->once())
            ->method('setState')
            ->with($expectedState);

        $this->handler->handle(
            ['payment' => $this->paymentDataObjectMock],
            $response
        );
    }

    /**
     * Data provider for status mapping tests.
     */
    public function statusMappingProvider(): array
    {
        return [
            'processing status' => ['processing', Creditmemo::STATE_OPEN],
            'processed status' => ['processed', Creditmemo::STATE_REFUNDED],
            'failed status' => ['failed', Creditmemo::STATE_CANCELED],
        ];
    }

    /**
     * @dataProvider refundIdProvider
     */
    public function testHandleSetsRefundIdFromReference(array $reference, ?string $expectedRefundId): void
    {
        $response = [
            'payments' => [[
                'reference' => $reference,
                'status' => 'processed',
            ]],
        ];

        $this->paymentMock->expects($this->once())
            ->method('setTransactionId')
            ->with($expectedRefundId);

        $this->paymentMock->expects($this->once())
            ->method('setAdditionalInformation')
            ->with('mp_refund_id', $expectedRefundId);

        $this->creditmemoMock->expects($this->once())
            ->method('setTransactionId')
            ->with($expectedRefundId);

        $this->handler->handle(
            ['payment' => $this->paymentDataObjectMock],
            $response
        );
    }

    /**
     * Data provider for refund id tests.
     */
    public function refundIdProvider(): array
    {
        return [
            'both ids present - uses refund_payment_id' => [
                ['refund_payment_id' => 'PAY123', 'refund_order_id' => 'ORD456'],
                'PAY123',
            ],
            'only refund_payment_id' => [
                ['refund_payment_id' => 'PAY123'],
                'PAY123',
            ],
            'only refund_order_id' => [
                ['refund_order_id' => 'ORD456'],
                'ORD456',
            ],
            'empty reference' => [
                [],
                null,
            ],
        ];
    }

    public function testHandleWithEmptyPaymentsArray(): void
    {
        $response = ['payments' => []];

        $this->paymentMock->expects($this->once())
            ->method('setTransactionId')
            ->with(null);

        $this->paymentMock->expects($this->once())
            ->method('setAdditionalInformation')
            ->with('mp_refund_id', null);

        $this->creditmemoMock->expects($this->once())
            ->method('setTransactionId')
            ->with(null);

        $this->creditmemoMock->expects($this->never())
            ->method('setState');

        $this->handler->handle(
            ['payment' => $this->paymentDataObjectMock],
            $response
        );
    }

    public function testHandleWithUnknownStatus(): void
    {
        $response = $this->buildResponse('unknown_status', 'REFUND123');

        $this->paymentMock->expects($this->once())
            ->method('setTransactionId')
            ->with('REFUND123');

        $this->creditmemoMock->expects($this->once())
            ->method('setTransactionId')
            ->with('REFUND123');

        $this->creditmemoMock->expects($this->never())
            ->method('setState');

        $this->handler->handle(
            ['payment' => $this->paymentDataObjectMock],
            $response
        );
    }

    public function testHandleWithEmptyResponse(): void
    {
        $this->paymentMock->expects($this->once())
            ->method('setTransactionId')
            ->with(null);

        $this->creditmemoMock->expects($this->once())
            ->method('setTransactionId')
            ->with(null);

        $this->creditmemoMock->expects($this->never())
            ->method('setState');

        $this->handler->handle(
            ['payment' => $this->paymentDataObjectMock],
            []
        );
    }

    /**
     * @dataProvider constantsProvider
     */
    public function testConstantsHaveExpectedValues(string $constant, string $expectedValue): void
    {
        $this->assertEquals(
            $expectedValue,
            constant(RefundOrderHandler::class . '::' . $constant)
        );
    }

    /**
     * Data provider for constants tests.
     */
    public function constantsProvider(): array
    {
        return [
            'RESPONSE_STATUS' => ['RESPONSE_STATUS', 'status'],
            'RESPONSE_STATUS_PROCESSING' => ['RESPONSE_STATUS_PROCESSING', 'processing'],
            'RESPONSE_STATUS_PROCESSED' => ['RESPONSE_STATUS_PROCESSED', 'processed'],
            'RESPONSE_STATUS_FAILED' => ['RESPONSE_STATUS_FAILED', 'failed'],
            'RESPONSE_REFUND_PAYMENT_ID' => ['RESPONSE_REFUND_PAYMENT_ID', 'refund_payment_id'],
            'RESPONSE_REFUND_ORDER_ID' => ['RESPONSE_REFUND_ORDER_ID', 'refund_order_id'],
            'RESPONSE_REFERENCE' => ['RESPONSE_REFERENCE', 'reference'],
            'RESPONSE_PAYMENTS' => ['RESPONSE_PAYMENTS', 'payments'],
        ];
    }

    /**
     * Build response array for tests.
     */
    private function buildResponse(string $status, string $refundId): array
    {
        return [
            'payments' => [[
                'reference' => [
                    'refund_payment_id' => $refundId,
                ],
                'status' => $status,
            ]],
        ];
    }
}

