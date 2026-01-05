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
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Payment;
use MercadoPago\AdbPayment\Gateway\Response\VoidOrderHandler;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for VoidOrderHandler.
 */
class VoidOrderHandlerTest extends TestCase
{
    /**
     * @var VoidOrderHandler
     */
    private $handler;

    /**
     * @var PaymentDataObjectInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $paymentDOMock;

    /**
     * @var Payment|\PHPUnit\Framework\MockObject\MockObject
     */
    private $paymentMock;

    /**
     * @var Order|\PHPUnit\Framework\MockObject\MockObject
     */
    private $orderMock;

    protected function setUp(): void
    {
        $this->handler = new VoidOrderHandler();

        $this->paymentDOMock = $this->getMockForAbstractClass(PaymentDataObjectInterface::class);

        // Use getMock() without specifying methods - PHPUnit will auto-mock all
        $this->paymentMock = $this->getMockBuilder(Payment::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->orderMock = $this->createMock(Order::class);

        $this->paymentDOMock->method('getPayment')->willReturn($this->paymentMock);
        $this->paymentMock->method('getOrder')->willReturn($this->orderMock);
    }

    /**
     * Test handle throws exception when payment data object is missing.
     */
    public function testHandleThrowsExceptionWhenPaymentDataObjectIsMissing(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Payment data object should be provided');

        $this->handler->handle([], []);
    }

    /**
     * Test handle throws exception when payment is not instance of PaymentDataObjectInterface.
     */
    public function testHandleThrowsExceptionWhenPaymentIsNotPaymentDataObjectInterface(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Payment data object should be provided');

        $this->handler->handle(['payment' => new \stdClass()], []);
    }

    /**
     * @dataProvider invalidPaymentProvider
     */
    public function testHandleThrowsExceptionForInvalidPayment(array $handlingSubject): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Payment data object should be provided');

        $this->handler->handle($handlingSubject, []);
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
     * Test handle does nothing when RESULT_CODE is 0.
     */
    public function testHandleDoesNothingWhenResultCodeIsZero(): void
    {
        $handlingSubject = ['payment' => $this->paymentDOMock];
        $response = [VoidOrderHandler::RESULT_CODE => 0];

        $this->paymentMock->expects($this->never())
            ->method('setAdditionalInformation');

        $this->handler->handle($handlingSubject, $response);
    }

    /**
     * Test handle processes payment when RESULT_CODE is 1.
     */
    public function testHandleProcessesPaymentWhenResultCodeIsOne(): void
    {
        $amount = 100.00;

        $this->orderMock->method('getBaseGrandTotal')->willReturn($amount);

        $handlingSubject = ['payment' => $this->paymentDOMock];
        $response = [
            VoidOrderHandler::RESULT_CODE => 1,
            VoidOrderHandler::ID => 'PPORDO32YD7YI5KX2A9C0MZ1QZ33TJW',
            VoidOrderHandler::RESPONSE_STATUS => 'canceled',
            VoidOrderHandler::STATUS_DETAIL => 'by_collector',
            VoidOrderHandler::PAYMENTS => [],
        ];

        // Verify setAdditionalInformation is called (5 times for void)
        $this->paymentMock->expects($this->exactly(5))
            ->method('setAdditionalInformation');

        $this->handler->handle($handlingSubject, $response);
    }

    /**
     * Test handle sets additional information correctly.
     */
    public function testHandleSetsAdditionalInformationCorrectly(): void
    {
        $amount = 150.00;

        $this->orderMock->method('getBaseGrandTotal')->willReturn($amount);

        $handlingSubject = ['payment' => $this->paymentDOMock];
        $response = [
            VoidOrderHandler::RESULT_CODE => 1,
            VoidOrderHandler::ID => 'PPORDO32YD7YI5KX2A9C0MZ1QZ33TJW',
            VoidOrderHandler::RESPONSE_STATUS => 'canceled',
            VoidOrderHandler::STATUS_DETAIL => 'by_collector',
            VoidOrderHandler::PAYMENTS => [
                [
                    VoidOrderHandler::ID => 'PAYMENT123',
                    VoidOrderHandler::REFERENCES => [
                        VoidOrderHandler::REFERENCE_SOURCE => VoidOrderHandler::MP_PAYMENTS,
                        VoidOrderHandler::REFERENCE_PAYMENT_ID => 'PAY789',
                    ],
                ],
            ],
        ];

        $this->paymentMock->expects($this->exactly(5))
            ->method('setAdditionalInformation')
            ->withConsecutive(
                [VoidOrderHandler::MP_PAYMENT_ID, 'PAY789'],
                [VoidOrderHandler::MP_ORDER_ID, 'PPORDO32YD7YI5KX2A9C0MZ1QZ33TJW'],
                [VoidOrderHandler::MP_PAYMENT_ID_ORDER, 'PAYMENT123'],
                [VoidOrderHandler::MP_STATUS, 'canceled'],
                [VoidOrderHandler::MP_STATUS_DETAIL, 'by_collector']
            );

        $this->handler->handle($handlingSubject, $response);
    }

    /**
     * Test handle with mp_order reference source.
     */
    public function testHandleWithMpOrderReferenceSource(): void
    {
        $amount = 100.00;

        $this->orderMock->method('getBaseGrandTotal')->willReturn($amount);

        $handlingSubject = ['payment' => $this->paymentDOMock];
        $response = [
            VoidOrderHandler::RESULT_CODE => 1,
            VoidOrderHandler::ID => 'PPORDO32YD7YI5KX2A9C0MZ1QZ33TJW',
            VoidOrderHandler::RESPONSE_STATUS => 'canceled',
            VoidOrderHandler::STATUS_DETAIL => 'by_collector',
            VoidOrderHandler::PAYMENTS => [
                [
                    VoidOrderHandler::ID => 'PAYMENT123',
                    VoidOrderHandler::REFERENCES => [
                        VoidOrderHandler::REFERENCE_SOURCE => VoidOrderHandler::MP_ORDER,
                        VoidOrderHandler::REFERENCE_ORDER_ID => 'ORDER456',
                    ],
                ],
            ],
        ];

        $this->paymentMock->expects($this->exactly(5))
            ->method('setAdditionalInformation')
            ->withConsecutive(
                [VoidOrderHandler::MP_PAYMENT_ID, 'ORDER456'],
                [VoidOrderHandler::MP_ORDER_ID, 'PPORDO32YD7YI5KX2A9C0MZ1QZ33TJW'],
                [VoidOrderHandler::MP_PAYMENT_ID_ORDER, 'PAYMENT123'],
                [VoidOrderHandler::MP_STATUS, 'canceled'],
                [VoidOrderHandler::MP_STATUS_DETAIL, 'by_collector']
            );

        $this->handler->handle($handlingSubject, $response);
    }

    /**
     * Test handle with empty payments array.
     */
    public function testHandleWithEmptyPaymentsArray(): void
    {
        $amount = 100.00;

        $this->orderMock->method('getBaseGrandTotal')->willReturn($amount);

        $handlingSubject = ['payment' => $this->paymentDOMock];
        $response = [
            VoidOrderHandler::RESULT_CODE => 1,
            VoidOrderHandler::ID => 'PPORDO32YD7YI5KX2A9C0MZ1QZ33TJW',
            VoidOrderHandler::RESPONSE_STATUS => 'canceled',
            VoidOrderHandler::STATUS_DETAIL => 'by_collector',
            VoidOrderHandler::PAYMENTS => [],
        ];

        $this->paymentMock->expects($this->exactly(5))
            ->method('setAdditionalInformation')
            ->withConsecutive(
                [VoidOrderHandler::MP_PAYMENT_ID, null],
                [VoidOrderHandler::MP_ORDER_ID, 'PPORDO32YD7YI5KX2A9C0MZ1QZ33TJW'],
                [VoidOrderHandler::MP_PAYMENT_ID_ORDER, null],
                [VoidOrderHandler::MP_STATUS, 'canceled'],
                [VoidOrderHandler::MP_STATUS_DETAIL, 'by_collector']
            );

        $this->handler->handle($handlingSubject, $response);
    }

    /**
     * Test handle with missing payments key.
     */
    public function testHandleWithMissingPaymentsKey(): void
    {
        $amount = 100.00;

        $this->orderMock->method('getBaseGrandTotal')->willReturn($amount);

        $handlingSubject = ['payment' => $this->paymentDOMock];
        $response = [
            VoidOrderHandler::RESULT_CODE => 1,
            VoidOrderHandler::ID => 'PPORDO32YD7YI5KX2A9C0MZ1QZ33TJW',
            VoidOrderHandler::RESPONSE_STATUS => 'canceled',
            VoidOrderHandler::STATUS_DETAIL => 'by_collector',
        ];

        $this->paymentMock->expects($this->exactly(5))
            ->method('setAdditionalInformation')
            ->withConsecutive(
                [VoidOrderHandler::MP_PAYMENT_ID, null],
                [VoidOrderHandler::MP_ORDER_ID, 'PPORDO32YD7YI5KX2A9C0MZ1QZ33TJW'],
                [VoidOrderHandler::MP_PAYMENT_ID_ORDER, null],
                [VoidOrderHandler::MP_STATUS, 'canceled'],
                [VoidOrderHandler::MP_STATUS_DETAIL, 'by_collector']
            );

        $this->handler->handle($handlingSubject, $response);
    }

    /**
     * Test handle with unknown reference source.
     */
    public function testHandleWithUnknownReferenceSource(): void
    {
        $amount = 100.00;

        $this->orderMock->method('getBaseGrandTotal')->willReturn($amount);

        $handlingSubject = ['payment' => $this->paymentDOMock];
        $response = [
            VoidOrderHandler::RESULT_CODE => 1,
            VoidOrderHandler::ID => 'PPORDO32YD7YI5KX2A9C0MZ1QZ33TJW',
            VoidOrderHandler::RESPONSE_STATUS => 'canceled',
            VoidOrderHandler::STATUS_DETAIL => 'by_collector',
            VoidOrderHandler::PAYMENTS => [
                [
                    VoidOrderHandler::ID => 'PAYMENT123',
                    VoidOrderHandler::REFERENCES => [
                        VoidOrderHandler::REFERENCE_SOURCE => 'unknown_source',
                        VoidOrderHandler::REFERENCE_PAYMENT_ID => 'PAY789',
                    ],
                ],
            ],
        ];

        $this->paymentMock->expects($this->exactly(5))
            ->method('setAdditionalInformation')
            ->withConsecutive(
                [VoidOrderHandler::MP_PAYMENT_ID, null],
                [VoidOrderHandler::MP_ORDER_ID, 'PPORDO32YD7YI5KX2A9C0MZ1QZ33TJW'],
                [VoidOrderHandler::MP_PAYMENT_ID_ORDER, 'PAYMENT123'],
                [VoidOrderHandler::MP_STATUS, 'canceled'],
                [VoidOrderHandler::MP_STATUS_DETAIL, 'by_collector']
            );

        $this->handler->handle($handlingSubject, $response);
    }

    /**
     * @dataProvider constantsProvider
     */
    public function testConstantsHaveExpectedValues(string $constant, string $expectedValue): void
    {
        $this->assertEquals($expectedValue, constant(VoidOrderHandler::class . '::' . $constant));
    }

    /**
     * Data provider for constants tests.
     */
    public function constantsProvider(): array
    {
        return [
            'RESULT_CODE' => ['RESULT_CODE', 'RESULT_CODE'],
            'RESPONSE_STATUS' => ['RESPONSE_STATUS', 'status'],
            'STATUS_DETAIL' => ['STATUS_DETAIL', 'status_detail'],
            'ID' => ['ID', 'id'],
            'MP_PAYMENT_ID' => ['MP_PAYMENT_ID', 'mp_payment_id'],
            'MP_ORDER_ID' => ['MP_ORDER_ID', 'mp_order_id'],
            'MP_PAYMENT_ID_ORDER' => ['MP_PAYMENT_ID_ORDER', 'mp_payment_id_order'],
            'MP_STATUS' => ['MP_STATUS', 'mp_status'],
            'MP_STATUS_DETAIL' => ['MP_STATUS_DETAIL', 'mp_status_detail'],
            'REFERENCES' => ['REFERENCES', 'references'],
            'REFERENCE_SOURCE' => ['REFERENCE_SOURCE', 'source'],
            'REFERENCE_PAYMENT_ID' => ['REFERENCE_PAYMENT_ID', 'payment_id'],
            'REFERENCE_ORDER_ID' => ['REFERENCE_ORDER_ID', 'order_id'],
            'MP_PAYMENTS' => ['MP_PAYMENTS', 'mp_payments'],
            'MP_ORDER' => ['MP_ORDER', 'mp_order'],
            'PAYMENTS' => ['PAYMENTS', 'payments'],
        ];
    }

    /**
     * Test handler can be instantiated.
     */
    public function testCanBeInstantiated(): void
    {
        $handler = new VoidOrderHandler();
        
        $this->assertInstanceOf(VoidOrderHandler::class, $handler);
    }

    /**
     * Test handle with missing status and status_detail keys (null coalescing).
     */
    public function testHandleWithMissingStatusKeys(): void
    {
        $amount = 100.00;

        $this->orderMock->method('getBaseGrandTotal')->willReturn($amount);

        $handlingSubject = ['payment' => $this->paymentDOMock];
        $response = [
            VoidOrderHandler::RESULT_CODE => 1,
            VoidOrderHandler::ID => 'PPORDO32YD7YI5KX2A9C0MZ1QZ33TJW',
            // Missing RESPONSE_STATUS and STATUS_DETAIL
        ];

        $this->paymentMock->expects($this->exactly(5))
            ->method('setAdditionalInformation')
            ->withConsecutive(
                [VoidOrderHandler::MP_PAYMENT_ID, null],
                [VoidOrderHandler::MP_ORDER_ID, 'PPORDO32YD7YI5KX2A9C0MZ1QZ33TJW'],
                [VoidOrderHandler::MP_PAYMENT_ID_ORDER, null],
                [VoidOrderHandler::MP_STATUS, null],
                [VoidOrderHandler::MP_STATUS_DETAIL, null]
            );

        $this->handler->handle($handlingSubject, $response);
    }
}

