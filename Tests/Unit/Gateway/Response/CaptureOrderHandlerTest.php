<?php

namespace MercadoPago\AdbPayment\Tests\Unit\Gateway\Response;

use InvalidArgumentException;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Sales\Model\Order\Payment;
use MercadoPago\AdbPayment\Gateway\Response\CaptureOrderHandler;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CaptureOrderHandlerTest extends TestCase
{
    /**
     * @var CaptureOrderHandler
     */
    private $handler;

    /**
     * @var PaymentDataObjectInterface|MockObject
     */
    private $paymentDataObjectMock;

    /**
     * @var Payment|MockObject
     */
    private $paymentMock;

    protected function setUp(): void
    {
        $this->handler = new CaptureOrderHandler();

        $this->paymentDataObjectMock = $this->createMock(PaymentDataObjectInterface::class);
        
        $this->paymentMock = $this->getMockBuilder(Payment::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['setAdditionalInformation', 'setIsTransactionPending', 'setTransactionId'])
            ->getMock();

        $this->paymentDataObjectMock->method('getPayment')
            ->willReturn($this->paymentMock);
    }

    public function testHandleThrowsExceptionWhenPaymentMissing(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Payment data object should be provided');

        $this->handler->handle([], []);
    }

    public function testHandleThrowsExceptionWhenPaymentInvalid(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Payment data object should be provided');

        $this->handler->handle(['payment' => 'invalid'], []);
    }

    public function testHandleDoesNothingWhenResultCodeIsZero(): void
    {
        $handlingSubject = ['payment' => $this->paymentDataObjectMock];
        $response = [
            CaptureOrderHandler::RESULT_CODE => 0,
            CaptureOrderHandler::RESPONSE_STATUS => 'processed'
        ];

        $this->paymentMock->expects($this->never())
            ->method('setAdditionalInformation');

        $this->paymentMock->expects($this->never())
            ->method('setIsTransactionPending');

        $this->paymentMock->expects($this->never())
            ->method('setTransactionId');

        $this->handler->handle($handlingSubject, $response);
    }

    public function testHandleDoesNothingWhenResultCodeIsMissing(): void
    {
        $handlingSubject = ['payment' => $this->paymentDataObjectMock];
        $response = [
            CaptureOrderHandler::RESPONSE_STATUS => 'processed'
        ];

        $this->paymentMock->expects($this->never())
            ->method('setAdditionalInformation');

        $this->paymentMock->expects($this->never())
            ->method('setIsTransactionPending');

        $this->paymentMock->expects($this->never())
            ->method('setTransactionId');

        $this->handler->handle($handlingSubject, $response);
    }

    public function testHandleSetsTransactionIdWhenOrderIdPresent(): void
    {
        $mpOrderId = 'PPORD123456789';
        $handlingSubject = ['payment' => $this->paymentDataObjectMock];
        $response = [
            CaptureOrderHandler::RESULT_CODE => 1,
            CaptureOrderHandler::ORDER_ID => $mpOrderId,
            CaptureOrderHandler::RESPONSE_STATUS => 'processed',
            CaptureOrderHandler::STATUS_DETAIL => 'accredited'
        ];

        $this->paymentMock->expects($this->once())
            ->method('setTransactionId')
            ->with($mpOrderId);

        $this->paymentMock->method('setAdditionalInformation')->willReturnSelf();
        $this->paymentMock->method('setIsTransactionPending')->willReturnSelf();

        $this->handler->handle($handlingSubject, $response);
    }

    public function testHandleDoesNotSetTransactionIdWhenOrderIdMissing(): void
    {
        $handlingSubject = ['payment' => $this->paymentDataObjectMock];
        $response = [
            CaptureOrderHandler::RESULT_CODE => 1,
            CaptureOrderHandler::RESPONSE_STATUS => 'processed',
            CaptureOrderHandler::STATUS_DETAIL => 'accredited'
            // No ORDER_ID
        ];

        $this->paymentMock->expects($this->never())
            ->method('setTransactionId');

        $this->paymentMock->method('setAdditionalInformation')->willReturnSelf();
        $this->paymentMock->method('setIsTransactionPending')->willReturnSelf();

        $this->handler->handle($handlingSubject, $response);
    }

    public function testHandleSetsTransactionPendingWhenNotProcessed(): void
    {
        $handlingSubject = ['payment' => $this->paymentDataObjectMock];
        $response = [
            CaptureOrderHandler::RESULT_CODE => 1,
            CaptureOrderHandler::RESPONSE_STATUS => 'created',
            CaptureOrderHandler::STATUS_DETAIL => 'waiting_payment'
        ];

        $this->paymentMock->expects($this->exactly(2))
            ->method('setAdditionalInformation')
            ->withConsecutive(
                [CaptureOrderHandler::MP_STATUS, 'created'],
                [CaptureOrderHandler::MP_STATUS_DETAIL, 'waiting_payment']
            );

        // Payment is pending, so setIsTransactionPending(true)
        // This will create invoice with STATE_OPEN
        $this->paymentMock->expects($this->once())
            ->method('setIsTransactionPending')
            ->with(true);

        $this->handler->handle($handlingSubject, $response);
    }

    public function testHandleSetsTransactionNotPendingWhenProcessed(): void
    {
        $handlingSubject = ['payment' => $this->paymentDataObjectMock];
        $response = [
            CaptureOrderHandler::RESULT_CODE => 1,
            CaptureOrderHandler::RESPONSE_STATUS => 'processed',
            CaptureOrderHandler::STATUS_DETAIL => 'accredited'
        ];

        $this->paymentMock->expects($this->exactly(2))
            ->method('setAdditionalInformation')
            ->withConsecutive(
                [CaptureOrderHandler::MP_STATUS, 'processed'],
                [CaptureOrderHandler::MP_STATUS_DETAIL, 'accredited']
            );

        // Payment is processed, so setIsTransactionPending(false)
        // This will create invoice with STATE_PAID
        $this->paymentMock->expects($this->once())
            ->method('setIsTransactionPending')
            ->with(false);

        $this->handler->handle($handlingSubject, $response);
    }

    public function testConstantValues(): void
    {
        $this->assertEquals('RESULT_CODE', CaptureOrderHandler::RESULT_CODE);
        $this->assertEquals('id', CaptureOrderHandler::ORDER_ID);
        $this->assertEquals('status', CaptureOrderHandler::RESPONSE_STATUS);
        $this->assertEquals('status_detail', CaptureOrderHandler::STATUS_DETAIL);
        $this->assertEquals('processed', CaptureOrderHandler::RESPONSE_STATUS_PROCESSED);
        $this->assertEquals('mp_status', CaptureOrderHandler::MP_STATUS);
        $this->assertEquals('mp_status_detail', CaptureOrderHandler::MP_STATUS_DETAIL);
    }

    /**
     * @dataProvider orderStatusProvider
     */
    public function testHandleWithVariousOrderStatuses(
        string $orderStatus,
        string $statusDetail,
        bool $expectedPending
    ): void {
        $handlingSubject = ['payment' => $this->paymentDataObjectMock];
        $response = [
            CaptureOrderHandler::RESULT_CODE => 1,
            CaptureOrderHandler::RESPONSE_STATUS => $orderStatus,
            CaptureOrderHandler::STATUS_DETAIL => $statusDetail
        ];

        $this->paymentMock->expects($this->exactly(2))
            ->method('setAdditionalInformation')
            ->withConsecutive(
                [CaptureOrderHandler::MP_STATUS, $orderStatus],
                [CaptureOrderHandler::MP_STATUS_DETAIL, $statusDetail]
            );

        $this->paymentMock->expects($this->once())
            ->method('setIsTransactionPending')
            ->with($expectedPending);

        $this->handler->handle($handlingSubject, $response);
    }

    public function orderStatusProvider(): array
    {
        return [
            'processed - not pending' => ['processed', 'accredited', false],
            'created - pending' => ['created', 'waiting_payment', true],
            'canceled - pending' => ['canceled', 'by_user', true],
            'expired - pending' => ['expired', 'timeout', true],
            'failed - pending' => ['failed', 'rejected', true],
        ];
    }

    public function testHandleWithNullStatusValues(): void
    {
        $handlingSubject = ['payment' => $this->paymentDataObjectMock];
        $response = [
            CaptureOrderHandler::RESULT_CODE => 1
            // No status or status_detail
        ];

        $this->paymentMock->expects($this->exactly(2))
            ->method('setAdditionalInformation')
            ->withConsecutive(
                [CaptureOrderHandler::MP_STATUS, null],
                [CaptureOrderHandler::MP_STATUS_DETAIL, null]
            );

        // Null status means not processed, so pending = true
        $this->paymentMock->expects($this->once())
            ->method('setIsTransactionPending')
            ->with(true);

        $this->handler->handle($handlingSubject, $response);
    }
}
