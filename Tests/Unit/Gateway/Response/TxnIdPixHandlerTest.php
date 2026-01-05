<?php

namespace MercadoPago\AdbPayment\Tests\Unit\Gateway\Response;

use InvalidArgumentException;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Payment;
use Magento\Sales\Model\Order\Payment\Transaction;
use MercadoPago\AdbPayment\Gateway\Response\TxnIdPixHandler;
use PHPUnit\Framework\TestCase;

class TxnIdPixHandlerTest extends TestCase
{
	/** @var TxnIdPixHandler */
	private $handler;

	protected function setUp(): void
	{
		$this->handler = new TxnIdPixHandler();
	}

    public function testHandleSetsAdditionalInformationAndTransaction()
    {
        $paymentMock = $this->getMockBuilder(Payment::class)
            ->addMethods([
                'setAuthorizationTransaction',
            ])
            ->onlyMethods([
                'setTransactionId',
                'setIsTransactionPending',
                'setIsTransactionClosed',
                'addTransaction',
                'setAdditionalInformation',
                'getOrder',
            ])
            ->disableOriginalConstructor()
            ->getMock();

		$orderMock = $this->createMock(Order::class);

		// Transaction changes
		$paymentMock->expects($this->once())
			->method('setTransactionId')
			->with('order-123');
		$paymentMock->expects($this->once())
			->method('setIsTransactionPending')
			->with(1);
		$paymentMock->expects($this->once())
			->method('setIsTransactionClosed')
			->with(false);
		$paymentMock->expects($this->once())
			->method('setAuthorizationTransaction')
			->with('order-123');
		$paymentMock->expects($this->once())
			->method('addTransaction')
			->with(Transaction::TYPE_AUTH);

		// Order updates
		$paymentMock->method('getOrder')->willReturn($orderMock);
		$orderMock->expects($this->once())->method('setState')->with(Order::STATE_NEW);
		$orderMock->expects($this->once())->method('setStatus')->with('pending');
		$orderMock->method('getStatus')->willReturn('pending');
		$orderMock->expects($this->once())->method('addStatusHistoryComment')
			->with($this->stringContains('Awaiting payment through Pix.'), 'pending');

		// Additional information (9 calls, in order)
		$paymentMock->expects($this->exactly(9))
			->method('setAdditionalInformation')
			->withConsecutive(
				[TxnIdPixHandler::MP_STATUS, 'created'],
				[TxnIdPixHandler::MP_STATUS_DETAIL, 'pending_waiting_transfer'],
				[TxnIdPixHandler::MP_PAYMENT_ID, 'old-789'],
				[TxnIdPixHandler::MP_ORDER_ID, 'order-123'],
				[TxnIdPixHandler::MP_PAYMENT_ID_ORDER, 'payment-123'],
				[TxnIdPixHandler::DATE_OF_EXPIRATION, '2025-12-01T23:59:59Z'],
				[TxnIdPixHandler::QR_CODE, 'qr-code-data'],
				[TxnIdPixHandler::QR_CODE_ENCODE, 'cXItBmFzZTY0'],
				[TxnIdPixHandler::EXTERNAL_TICKET_URL, 'https://mp/qr']
			);

		$paymentDO = $this->createMock(PaymentDataObjectInterface::class);
		$paymentDO->method('getPayment')->willReturn($paymentMock);

		$handlingSubject = ['payment' => $paymentDO];
		$response = [
			TxnIdPixHandler::PAYMENT_ID => 'order-123',
			TxnIdPixHandler::STATUS => 'created',
			TxnIdPixHandler::STATUS_DETAIL => 'pending_waiting_transfer',
			TxnIdPixHandler::PAYMENTS => [
				[
					TxnIdPixHandler::PAYMENT_ID => 'payment-123',
					TxnIdPixHandler::PAYMENT_METHOD => [
						TxnIdPixHandler::QR_CODE => 'qr-code-data',
						TxnIdPixHandler::QR_CODE_ENCODE => 'cXItBmFzZTY0',
						TxnIdPixHandler::PAYMENT_URL => 'https://mp/qr',
					],
					TxnIdPixHandler::DATE_OF_EXPIRATION => '2025-12-01T23:59:59Z',
					TxnIdPixHandler::REFERENCES => [
						TxnIdPixHandler::REFERENCE_SOURCE => TxnIdPixHandler::MP_PAYMENTS,
						TxnIdPixHandler::REFERENCE_PAYMENT_ID => 'old-789',
					],
				],
			],
		];

		$this->handler->handle($handlingSubject, $response);
	}

	public function testHandleThrowsWhenPaymentNotProvided()
	{
		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage('Payment data object should be provided');

		$this->handler->handle([], []);
	}

	public function testHandleThrowsWhenPaymentIsNotDataObject()
	{
		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage('Payment data object should be provided');

		$this->handler->handle(['payment' => new \stdClass()], []);
	}
}


