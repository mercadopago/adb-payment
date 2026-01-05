<?php
/**
 * Copyright © MercadoPago. All rights reserved.
 *
 * @author      Mercado Pago
 * @license     See LICENSE for license details.
 */

namespace MercadoPago\AdbPayment\Tests\Unit\Gateway\Response;

use PHPUnit\Framework\TestCase;
use MercadoPago\AdbPayment\Gateway\Response\FetchOrderHandler;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Sales\Model\Order\Payment;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Invoice;
use InvalidArgumentException;

/**
 * Test for FetchOrderHandler
 */
class FetchOrderHandlerTest extends TestCase
{
    /**
     * @var FetchOrderHandler
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

    /**
     * Setup test environment
     */
    protected function setUp(): void
    {
        $this->handler = new FetchOrderHandler();
        
        $this->paymentDOMock = $this->getMockForAbstractClass(PaymentDataObjectInterface::class);
        
        // Create payment mock without specifying methods - PHPUnit will handle them
        $this->paymentMock = $this->getMockBuilder(Payment::class)
            ->disableOriginalConstructor()
            ->getMock();
            
        $this->orderMock = $this->createMock(Order::class);

        $this->paymentDOMock->method('getPayment')->willReturn($this->paymentMock);
        $this->paymentMock->method('getOrder')->willReturn($this->orderMock);
    }

    /**
     * Test handle throws exception when payment data object is missing
     */
    public function testHandleThrowsExceptionWhenPaymentDataObjectIsMissing()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Payment data object should be provided');

        $this->handler->handle([], []);
    }

    /**
     * Test handle throws exception when payment is not instance of PaymentDataObjectInterface
     */
    public function testHandleThrowsExceptionWhenPaymentIsNotPaymentDataObjectInterface()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Payment data object should be provided');

        $this->handler->handle(['payment' => new \stdClass()], []);
    }

    /**
     * Test handle returns early when status is not present in response
     */
    public function testHandleReturnsEarlyWhenStatusIsNotPresent()
    {
        $handlingSubject = ['payment' => $this->paymentDOMock];
        $response = [];

        // Should not throw exception and should return early
        $this->handler->handle($handlingSubject, $response);
        
        // No assertions needed - test passes if no exception is thrown
        $this->assertTrue(true);
    }

    /**
     * Data provider for approved payment scenarios
     * 
     * @return array
     */
    public function approvedPaymentDataProvider(): array
    {
        return [
            'payment_review status with processed' => [
                'orderStatus' => 'payment_review',
                'orderApiStatus' => 'processed',
                'shouldProcess' => true
            ],
            'pending status with processed' => [
                'orderStatus' => 'pending',
                'orderApiStatus' => 'processed',
                'shouldProcess' => true
            ],
            'processing status with processed' => [
                'orderStatus' => 'processing',
                'orderApiStatus' => 'processed',
                'shouldProcess' => false
            ],
            'payment_review with failed' => [
                'orderStatus' => 'payment_review',
                'orderApiStatus' => 'failed',
                'shouldProcess' => false
            ],
        ];
    }

    /**
     * Test approved payment processing
     * 
     * @dataProvider approvedPaymentDataProvider
     */
    public function testHandleProcessesApprovedPayment(
        string $orderStatus,
        string $orderApiStatus,
        bool $shouldProcess
    ) {
        $baseAmount = '100.00';
        $paidAmount = '100.00';
        
        $this->orderMock->method('getStatus')->willReturn($orderStatus);
        $this->orderMock->method('getGrandTotal')->willReturn($baseAmount);
        $this->orderMock->method('getBaseGrandTotal')->willReturn($baseAmount);

        $handlingSubject = ['payment' => $this->paymentDOMock];
        $response = [
            'status' => $orderApiStatus,
            'total_paid_amount' => $paidAmount,
            'payments' => []
        ];

        if ($shouldProcess) {
            // Expect payment to be approved
            $this->paymentMock->expects($this->once())
                ->method('registerCaptureNotification')
                ->with($paidAmount, true);
        } else {
            // Should not process as approved
            $this->paymentMock->expects($this->never())
                ->method('registerCaptureNotification');
        }

        $this->handler->handle($handlingSubject, $response);
    }

    /**
     * Test approved payment with partial amount creates invoice
     */
    public function testHandleCreatesInvoiceWhenPaidAmountDiffersFromBaseAmount()
    {
        $baseAmount = '100.00';
        $paidAmount = '50.00';
        
        $this->orderMock->method('getStatus')->willReturn('payment_review');
        $this->orderMock->method('getGrandTotal')->willReturn($baseAmount);
        $this->orderMock->method('getBaseGrandTotal')->willReturn($baseAmount);

        $invoiceMock = $this->createMock(Invoice::class);
        $invoiceMock->expects($this->once())->method('register')->willReturnSelf();
        $invoiceMock->expects($this->once())->method('setOrder')->with($this->orderMock);
        $invoiceMock->expects($this->once())->method('setBaseGrandTotal')->with($paidAmount);
        $invoiceMock->expects($this->once())->method('setGrandTotal')->with($paidAmount);
        $invoiceMock->expects($this->once())->method('setSubtotal')->with($paidAmount);
        $invoiceMock->expects($this->once())->method('setBaseSubtotal')->with($paidAmount);
        $invoiceMock->expects($this->once())->method('addComment');

        $this->orderMock->expects($this->once())
            ->method('prepareInvoice')
            ->willReturn($invoiceMock);
            
        $this->orderMock->expects($this->once())
            ->method('addRelatedObject')
            ->with($invoiceMock);

        $handlingSubject = ['payment' => $this->paymentDOMock];
        $response = [
            'status' => 'processed',
            'total_paid_amount' => $paidAmount,
            'payments' => []
        ];

        $this->handler->handle($handlingSubject, $response);
    }

    /**
     * Data provider for canceled payment scenarios
     * 
     * @return array
     */
    public function canceledPaymentDataProvider(): array
    {
        return [
            'failed status' => ['failed', true],
            'canceled status' => ['canceled', true],
            'expired status' => ['expired', true],
            'processed status' => ['processed', false],
            'in_mediation status' => ['in_mediation', false],
        ];
    }

    /**
     * Test canceled payment processing
     * 
     * @dataProvider canceledPaymentDataProvider
     */
    public function testHandleProcessesCanceledPayment(string $orderApiStatus, bool $shouldCancel)
    {
        $amount = '100.00';
        $baseAmount = '100.00';
        
        $this->orderMock->method('getStatus')->willReturn('pending');
        $this->orderMock->method('getGrandTotal')->willReturn($amount);
        $this->orderMock->method('getBaseGrandTotal')->willReturn($baseAmount);

        $handlingSubject = ['payment' => $this->paymentDOMock];
        $response = [
            'status' => $orderApiStatus,
            'payments' => []
        ];

        if ($shouldCancel) {
            $this->paymentMock->expects($this->once())
                ->method('registerVoidNotification')
                ->with($amount);
        } else {
            $this->paymentMock->expects($this->never())
                ->method('registerVoidNotification');
        }

        $this->handler->handle($handlingSubject, $response);
    }

    /**
     * Test payment info update when payments array exists
     */
    public function testHandleUpdatesPaymentInfoWhenPaymentsArrayExists()
    {
        $this->orderMock->method('getStatus')->willReturn('pending');
        $this->orderMock->method('getGrandTotal')->willReturn('100.00');
        $this->orderMock->method('getBaseGrandTotal')->willReturn('100.00');

        $paymentData = [
            'id' => 'PAYMENT123',
            'payment_method' => [
                'id' => 'pix'
            ],
            'amount' => '100.00',
            'paid_amount' => '100.00',
            'status' => 'approved',
            'status_detail' => 'accredited'
        ];

        $handlingSubject = ['payment' => $this->paymentDOMock];
        $response = [
            'status' => 'in_review',
            'payments' => [$paymentData]
        ];

        // Expect 8 calls to setAdditionalInformation (new fields added)
        $this->paymentMock->expects($this->exactly(8))
            ->method('setAdditionalInformation');

        $this->handler->handle($handlingSubject, $response);
    }

    /**
     * Test payment info update with missing payment data uses defaults
     */
    public function testHandleUpdatesPaymentInfoWithDefaults()
    {
        $this->orderMock->method('getStatus')->willReturn('pending');
        $this->orderMock->method('getGrandTotal')->willReturn('100.00');
        $this->orderMock->method('getBaseGrandTotal')->willReturn('100.00');

        $paymentData = []; // Empty payment data

        $handlingSubject = ['payment' => $this->paymentDOMock];
        $response = [
            'status' => 'processing',
            'payments' => [$paymentData]
        ];

        // Expect 8 calls with null/0 defaults (new fields added)
        $this->paymentMock->expects($this->exactly(8))
            ->method('setAdditionalInformation');

        $this->handler->handle($handlingSubject, $response);
    }

    /**
     * Test payment info is not updated when payments array is missing
     */
    public function testHandleDoesNotUpdatePaymentInfoWhenPaymentsArrayIsMissing()
    {
        $this->orderMock->method('getStatus')->willReturn('pending');
        $this->orderMock->method('getGrandTotal')->willReturn('100.00');
        $this->orderMock->method('getBaseGrandTotal')->willReturn('100.00');

        $handlingSubject = ['payment' => $this->paymentDOMock];
        $response = [
            'status' => 'processing'
            // No 'payments' key
        ];

        $this->paymentMock->expects($this->never())
            ->method('setAdditionalInformation');

        $this->handler->handle($handlingSubject, $response);
    }

    /**
     * Test full flow: process approved and update payment info
     */
    public function testHandleFullFlowProcessedStatus()
    {
        $baseAmount = '100.00';
        
        $this->orderMock->method('getStatus')->willReturn('payment_review');
        $this->orderMock->method('getGrandTotal')->willReturn($baseAmount);
        $this->orderMock->method('getBaseGrandTotal')->willReturn($baseAmount);

        $handlingSubject = ['payment' => $this->paymentDOMock];
        $response = [
            'status' => 'processed',
            'total_paid_amount' => $baseAmount,
            'payments' => [
                [
                    'id' => 'PAYMENT123',
                    'payment_method' => ['id' => 'pix'],
                    'amount' => '100.00',
                    'paid_amount' => '100.00',
                    'status' => 'approved',
                    'status_detail' => 'accredited'
                ]
            ]
        ];

        // Expect both approval and payment info update
        $this->paymentMock->expects($this->once())
            ->method('registerCaptureNotification');
            
        $this->paymentMock->expects($this->exactly(8))
            ->method('setAdditionalInformation');

        $this->handler->handle($handlingSubject, $response);
    }

    /**
     * Test full flow: process canceled and update payment info
     */
    public function testHandleFullFlowFailedStatus()
    {
        $amount = '100.00';
        
        $this->orderMock->method('getStatus')->willReturn('pending');
        $this->orderMock->method('getGrandTotal')->willReturn($amount);
        $this->orderMock->method('getBaseGrandTotal')->willReturn($amount);

        $handlingSubject = ['payment' => $this->paymentDOMock];
        $response = [
            'status' => 'failed',
            'payments' => [
                [
                    'id' => 'PAYMENT123',
                    'payment_method' => ['id' => 'pix'],
                    'amount' => '100.00',
                    'paid_amount' => '0.00',
                    'status' => 'rejected',
                    'status_detail' => 'cc_rejected_insufficient_amount'
                ]
            ]
        ];

        // Expect both cancellation and payment info update
        $this->paymentMock->expects($this->once())
            ->method('registerVoidNotification');
            
        $this->paymentMock->expects($this->exactly(8))
            ->method('setAdditionalInformation');

        $this->handler->handle($handlingSubject, $response);
    }
}

