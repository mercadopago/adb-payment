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
     * @dataProvider orderApiStatusMappingProvider
     */
    public function testHandleSetsCorrectCreditmemoStateForOrderApi(string $apiStatus, int $expectedState): void
    {
        $refundId = 'REFUND123';
        $response = $this->buildOrderApiResponse($apiStatus, $refundId);

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

    public function orderApiStatusMappingProvider(): array
    {
        return [
            'processing status' => ['processing', Creditmemo::STATE_OPEN],
            'processed status' => ['processed', Creditmemo::STATE_REFUNDED],
            'failed status' => ['failed', Creditmemo::STATE_CANCELED],
        ];
    }

    /**
     * @dataProvider paymentApiStatusMappingProvider
     */
    public function testHandleSetsCorrectCreditmemoStateForPaymentApi(string $apiStatus, int $expectedState): void
    {
        $refundId = 12345678;
        $response = $this->buildPaymentApiResponse($apiStatus, $refundId);

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

    public function paymentApiStatusMappingProvider(): array
    {
        return [
            'approved status' => ['approved', Creditmemo::STATE_REFUNDED],
        ];
    }

    /**
     * @dataProvider refundIdProvider
     */
    public function testHandleSetsRefundIdFromReference(array $reference, ?string $expectedRefundId): void
    {
        if ($expectedRefundId === null) {
            $this->paymentMock->expects($this->never())->method('setTransactionId');
            $this->paymentMock->expects($this->never())->method('setAdditionalInformation');
            $this->creditmemoMock->expects($this->never())->method('setTransactionId');
            $this->creditmemoMock->expects($this->never())->method('setState');
        } else {
            $this->paymentMock->expects($this->once())
                ->method('setTransactionId')
                ->with($expectedRefundId);

            $this->paymentMock->expects($this->once())
                ->method('setAdditionalInformation')
                ->with('mp_refund_id', $expectedRefundId);

            $this->creditmemoMock->expects($this->once())
                ->method('setTransactionId')
                ->with($expectedRefundId);
        }

        $response = [
            'payments' => [[
                'reference' => $reference,
                'status' => 'processed',
            ]],
        ];

        $this->handler->handle(
            ['payment' => $this->paymentDataObjectMock],
            $response
        );
    }

    public function refundIdProvider(): array
    {
        return [
            'both ids - uses refund_payment_id' => [
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
            'empty reference - early return' => [
                [],
                null,
            ],
        ];
    }

    /**
     * @dataProvider earlyReturnProvider
     */
    public function testHandleReturnsEarlyWhenNoRefundId(array $response): void
    {
        $this->paymentMock->expects($this->never())->method('setTransactionId');
        $this->paymentMock->expects($this->never())->method('setAdditionalInformation');
        $this->creditmemoMock->expects($this->never())->method('setTransactionId');
        $this->creditmemoMock->expects($this->never())->method('setState');

        $this->handler->handle(
            ['payment' => $this->paymentDataObjectMock],
            $response
        );
    }

    public function earlyReturnProvider(): array
    {
        return [
            'empty response' => [[]],
            'empty payments array' => [['payments' => []]],
            'payments without reference' => [['payments' => [['status' => 'processed']]]],
        ];
    }

    /**
     * @dataProvider unknownStatusProvider
     */
    public function testHandleDoesNotSetStateForUnknownStatus(array $response, $expectedRefundId): void
    {
        $this->paymentMock->expects($this->once())
            ->method('setTransactionId')
            ->with($expectedRefundId);

        $this->creditmemoMock->expects($this->once())
            ->method('setTransactionId')
            ->with($expectedRefundId);

        $this->creditmemoMock->expects($this->never())
            ->method('setState');

        $this->handler->handle(
            ['payment' => $this->paymentDataObjectMock],
            $response
        );
    }

    public function unknownStatusProvider(): array
    {
        return [
            'Order API unknown status' => [
                ['payments' => [['reference' => ['refund_payment_id' => 'REFUND123'], 'status' => 'unknown']]],
                'REFUND123',
            ],
            'Payment API unknown status' => [
                ['id' => 98765432, 'status' => 'pending'],
                98765432,
            ],
        ];
    }

    public function testHandlePrefersOrderApiWhenBothFormatsPresent(): void
    {
        $response = [
            'id' => 'SHOULD_NOT_USE',
            'status' => 'approved',
            'payments' => [[
                'reference' => ['refund_payment_id' => 'ORDER_API_ID'],
                'status' => 'processed',
            ]],
        ];

        $this->paymentMock->expects($this->once())
            ->method('setTransactionId')
            ->with('ORDER_API_ID');

        $this->creditmemoMock->expects($this->once())
            ->method('setState')
            ->with(Creditmemo::STATE_REFUNDED);

        $this->handler->handle(
            ['payment' => $this->paymentDataObjectMock],
            $response
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

    public function constantsProvider(): array
    {
        return [
            'RESPONSE_STATUS' => ['RESPONSE_STATUS', 'status'],
            'RESPONSE_STATUS_PROCESSING' => ['RESPONSE_STATUS_PROCESSING', 'processing'],
            'RESPONSE_STATUS_PROCESSED' => ['RESPONSE_STATUS_PROCESSED', 'processed'],
            'RESPONSE_STATUS_FAILED' => ['RESPONSE_STATUS_FAILED', 'failed'],
            'RESPONSE_STATUS_APPROVED' => ['RESPONSE_STATUS_APPROVED', 'approved'],
            'RESPONSE_REFUND_PAYMENT_ID' => ['RESPONSE_REFUND_PAYMENT_ID', 'refund_payment_id'],
            'RESPONSE_REFUND_ORDER_ID' => ['RESPONSE_REFUND_ORDER_ID', 'refund_order_id'],
            'RESPONSE_REFERENCE' => ['RESPONSE_REFERENCE', 'reference'],
            'RESPONSE_PAYMENTS' => ['RESPONSE_PAYMENTS', 'payments'],
            'RESPONSE_ID' => ['RESPONSE_ID', 'id'],
        ];
    }

    private function buildOrderApiResponse(string $status, string $refundId): array
    {
        return [
            'payments' => [[
                'reference' => ['refund_payment_id' => $refundId],
                'status' => $status,
            ]],
        ];
    }

    private function buildPaymentApiResponse(string $status, $refundId): array
    {
        return [
            'id' => $refundId,
            'status' => $status,
        ];
    }
}
