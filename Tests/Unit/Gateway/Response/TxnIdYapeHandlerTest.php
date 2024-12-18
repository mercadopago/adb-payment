<?php

namespace MercadoPago\AdbPayment\Tests\Unit\Gateway\Response;

use InvalidArgumentException;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use MercadoPago\AdbPayment\Gateway\Response\TxnIdYapeHandler;
use Magento\Sales\Model\Order\Payment\Transaction;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Payment;

use PHPUnit\Framework\TestCase;

class TxnIdYapeHandlerTest extends TestCase
{
    /**
     * @var TxnIdYapeHandler
     */
    private $handler;

    protected function setUp(): void
    {
        $this->handler = new TxnIdYapeHandler();
    }

    public function testHandle()
    {
        $paymentMock = $this->getMockBuilder(Payment::class)
            ->addMethods([
                'setAuthorizationTransaction',
            ])
            ->onlyMethods(
                [
                    'setIsTransactionPending',
                    'setIsTransactionClosed',
                    'setTransactionId',
                    'setAdditionalInformation',
                    'addTransaction',
                    'getOrder',
                ]
            )
            ->disableOriginalConstructor()
            ->getMock();

        $orderMock = $this->createMock(Order::class);

        $orderMock->expects($this->any())
            ->method('getPayment')
            ->willReturn($paymentMock);

        $paymentMock->expects($this->exactly(3))
            ->method('setAdditionalInformation')
            ->withConsecutive(
                [TxnIdYapeHandler::MP_PAYMENT_ID, '123'],
                [TxnIdYapeHandler::MP_STATUS, 'approved'],
                [TxnIdYapeHandler::MP_STATUS_DETAIL, 'detail']
            );

        $paymentMock->expects($this->any())
            ->method('setTransactionId')
            ->willReturn('123');

        $paymentMock->expects($this->any())
            ->method('setIsTransactionPending')
            ->with(1);

        $paymentMock->expects($this->any())
            ->method('setIsTransactionClosed')
            ->with(false);

        $paymentMock->expects($this->any())
            ->method('setAuthorizationTransaction')
            ->with('123');

        $paymentMock->expects($this->any())
            ->method('addTransaction')
            ->with(Transaction::TYPE_AUTH);

        $orderMock->expects($this->any())
            ->method('setState')
            ->with(Order::STATE_NEW);

        $orderMock->expects($this->any())
            ->method('setStatus')
            ->with('pending');

        $orderMock->expects($this->any())
            ->method('addStatusHistoryComment')
            ->willReturn(__('Awaiting payment through Yape.'), 'pending');

        $paymentMock->method('getOrder')->willReturn($orderMock);

        $paymentDO = $this->createMock(PaymentDataObjectInterface::class);
        $paymentDO->method('getPayment')->willReturn($paymentMock);

        $handlingSubject = ['payment' => $paymentDO];
        $response = [
            TxnIdYapeHandler::PAYMENT_ID => '123',
            TxnIdYapeHandler::STATUS => 'approved',
            TxnIdYapeHandler::STATUS_DETAIL => 'detail'
        ];

        $this->handler->handle($handlingSubject, $response);
    }

    public function testHandleThrowsExceptionWhenPaymentNotProvided()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Payment data object should be provided');

        $handlingSubject = [];
        $response = [];

        $this->handler->handle($handlingSubject, $response);
    }

    public function testHandleThrowsExceptionWhenPaymentIsNotInstanceOfPaymentDataObjectInterface()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Payment data object should be provided');

        $handlingSubject = ['payment' => new \stdClass()];
        $response = [];

        $this->handler->handle($handlingSubject, $response);
    }
}
