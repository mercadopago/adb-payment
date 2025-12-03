<?php

namespace MercadoPago\AdbPayment\Tests\Unit\Gateway\Response;

use InvalidArgumentException;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use MercadoPago\AdbPayment\Gateway\Response\FetchPaymentHandler;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Payment;
use Magento\Sales\Model\Order\Invoice;
use PHPUnit\Framework\TestCase;

class FetchPaymentHandlerTest extends TestCase
{
    /**
     * @var FetchPaymentHandler
     */
    private $handler;

    protected function setUp(): void
    {
        $this->handler = new FetchPaymentHandler();
    }

    /**
     * Test handle throws exception when payment not provided
     */
    public function testHandleThrowsExceptionWhenPaymentNotProvided()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Payment data object should be provided');

        $handlingSubject = [];
        $response = [];

        $this->handler->handle($handlingSubject, $response);
    }

    /**
     * Test handle throws exception when payment is not instance of PaymentDataObjectInterface
     */
    public function testHandleThrowsExceptionWhenPaymentIsNotInstanceOfPaymentDataObjectInterface()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Payment data object should be provided');

        $handlingSubject = ['payment' => new \stdClass()];
        $response = [];

        $this->handler->handle($handlingSubject, $response);
    }

    /**
     * Test handle does nothing when response status is not set
     */
    public function testHandleDoesNothingWhenResponseStatusNotSet()
    {
        $paymentMock = $this->createPaymentMock();
        $paymentDO = $this->createPaymentDataObject($paymentMock);

        $handlingSubject = ['payment' => $paymentDO];
        $response = [];

        // Should not throw any exception
        $this->handler->handle($handlingSubject, $response);
        $this->assertTrue(true);
    }

    /**
     * Test handle with approved status
     */
    public function testHandleWithApprovedStatus()
    {
        $paymentMock = $this->createPaymentMock();
        $orderMock = $this->createOrderMock('payment_review', 100.00, 100.00);

        $paymentMock->expects($this->any())
            ->method('getOrder')
            ->willReturn($orderMock);

        $paymentMock->expects($this->once())
            ->method('registerCaptureNotification')
            ->with(100.00, true);

        $paymentDO = $this->createPaymentDataObject($paymentMock);

        $handlingSubject = ['payment' => $paymentDO];
        $response = [
            FetchPaymentHandler::RESPONSE_STATUS => FetchPaymentHandler::RESPONSE_STATUS_APPROVED,
            'total_paid' => 100.00,
            FetchPaymentHandler::PAYMENT_DETAILS => [
                [
                    FetchPaymentHandler::ID => '12345',
                    FetchPaymentHandler::STATUS => 'approved',
                    FetchPaymentHandler::STATUS_DETAIL => 'accredited',
                    FetchPaymentHandler::PAYMENT_METHOD_ID => 'visa',
                    FetchPaymentHandler::TOTAL_AMOUNT => 100.00,
                    FetchPaymentHandler::PAID_AMOUNT => 100.00,
                    FetchPaymentHandler::PAYMENT_METHOD_INFO => [
                        FetchPaymentHandler::LAST_FOUR_DIGITS => '1234',
                        FetchPaymentHandler::INSTALLMENTS => 1,
                    ],
                ],
            ],
        ];

        $this->handler->handle($handlingSubject, $response);
    }

    /**
     * Test handle with approved status and pending order status
     */
    public function testHandleWithApprovedStatusAndPendingOrderStatus()
    {
        $paymentMock = $this->createPaymentMock();
        $orderMock = $this->createOrderMock('pending', 100.00, 100.00);

        $paymentMock->expects($this->any())
            ->method('getOrder')
            ->willReturn($orderMock);

        $paymentMock->expects($this->once())
            ->method('registerCaptureNotification')
            ->with(100.00, true);

        $paymentDO = $this->createPaymentDataObject($paymentMock);

        $handlingSubject = ['payment' => $paymentDO];
        $response = [
            FetchPaymentHandler::RESPONSE_STATUS => FetchPaymentHandler::RESPONSE_STATUS_APPROVED,
            'total_paid' => 100.00,
            FetchPaymentHandler::PAYMENT_DETAILS => [
                [
                    FetchPaymentHandler::ID => '12345',
                    FetchPaymentHandler::STATUS => 'approved',
                    FetchPaymentHandler::STATUS_DETAIL => 'accredited',
                    FetchPaymentHandler::PAYMENT_METHOD_ID => 'visa',
                    FetchPaymentHandler::TOTAL_AMOUNT => 100.00,
                    FetchPaymentHandler::PAID_AMOUNT => 100.00,
                    FetchPaymentHandler::PAYMENT_METHOD_INFO => [
                        FetchPaymentHandler::LAST_FOUR_DIGITS => '1234',
                        FetchPaymentHandler::INSTALLMENTS => 1,
                    ],
                ],
            ],
        ];

        $this->handler->handle($handlingSubject, $response);
    }

    /**
     * Test handle with approved status and partial payment (creates invoice)
     */
    public function testHandleWithApprovedStatusAndPartialPayment()
    {
        $paymentMock = $this->createPaymentMock();
        $orderMock = $this->createOrderMock('payment_review', 100.00, 100.00);

        $invoiceMock = $this->createMock(Invoice::class);
        $invoiceMock->expects($this->once())
            ->method('register')
            ->willReturnSelf();
        $invoiceMock->expects($this->once())
            ->method('setOrder')
            ->with($orderMock);
        $invoiceMock->expects($this->once())
            ->method('setBaseGrandTotal');
        $invoiceMock->expects($this->once())
            ->method('setBaseSubtotal');
        $invoiceMock->expects($this->once())
            ->method('setGrandTotal');
        $invoiceMock->expects($this->once())
            ->method('setSubtotal');
        $invoiceMock->expects($this->once())
            ->method('addComment');

        $orderMock->expects($this->once())
            ->method('prepareInvoice')
            ->willReturn($invoiceMock);
        $orderMock->expects($this->once())
            ->method('addRelatedObject')
            ->with($invoiceMock);
        $orderMock->expects($this->once())
            ->method('getBaseToOrderRate')
            ->willReturn(1.0);

        $paymentMock->expects($this->any())
            ->method('getOrder')
            ->willReturn($orderMock);

        $paymentDO = $this->createPaymentDataObject($paymentMock);

        $handlingSubject = ['payment' => $paymentDO];
        $response = [
            FetchPaymentHandler::RESPONSE_STATUS => FetchPaymentHandler::RESPONSE_STATUS_APPROVED,
            'total_paid' => 50.00, // Partial payment
            FetchPaymentHandler::PAYMENT_DETAILS => [
                [
                    FetchPaymentHandler::ID => '12345',
                    FetchPaymentHandler::STATUS => 'approved',
                    FetchPaymentHandler::STATUS_DETAIL => 'accredited',
                    FetchPaymentHandler::PAYMENT_METHOD_ID => 'visa',
                    FetchPaymentHandler::TOTAL_AMOUNT => 50.00,
                    FetchPaymentHandler::PAID_AMOUNT => 50.00,
                    FetchPaymentHandler::PAYMENT_METHOD_INFO => [
                        FetchPaymentHandler::LAST_FOUR_DIGITS => '1234',
                        FetchPaymentHandler::INSTALLMENTS => 1,
                    ],
                ],
            ],
        ];

        $this->handler->handle($handlingSubject, $response);
    }

    /**
     * Test handle with approved status with coupon amount
     */
    public function testHandleWithApprovedStatusWithCouponAmount()
    {
        $paymentMock = $this->createPaymentMock();
        $orderMock = $this->createOrderMock('payment_review', 100.00, 100.00);

        $invoiceMock = $this->createMock(Invoice::class);
        $invoiceMock->expects($this->once())
            ->method('register')
            ->willReturnSelf();
        $invoiceMock->expects($this->once())
            ->method('setOrder')
            ->with($orderMock);
        $invoiceMock->expects($this->once())
            ->method('setBaseGrandTotal');
        $invoiceMock->expects($this->once())
            ->method('setBaseSubtotal');
        $invoiceMock->expects($this->once())
            ->method('setGrandTotal');
        $invoiceMock->expects($this->once())
            ->method('setSubtotal');
        $invoiceMock->expects($this->once())
            ->method('addComment');

        $orderMock->expects($this->once())
            ->method('prepareInvoice')
            ->willReturn($invoiceMock);
        $orderMock->expects($this->once())
            ->method('addRelatedObject')
            ->with($invoiceMock);
        $orderMock->expects($this->once())
            ->method('getBaseToOrderRate')
            ->willReturn(1.0);

        $paymentMock->expects($this->any())
            ->method('getOrder')
            ->willReturn($orderMock);

        $paymentDO = $this->createPaymentDataObject($paymentMock);

        $handlingSubject = ['payment' => $paymentDO];
        $response = [
            FetchPaymentHandler::RESPONSE_STATUS => FetchPaymentHandler::RESPONSE_STATUS_APPROVED,
            'total_paid' => 90.00,
            FetchPaymentHandler::PAYMENT_DETAILS => [
                [
                    FetchPaymentHandler::ID => '12345',
                    FetchPaymentHandler::STATUS => 'approved',
                    FetchPaymentHandler::STATUS_DETAIL => 'accredited',
                    FetchPaymentHandler::PAYMENT_METHOD_ID => 'visa',
                    FetchPaymentHandler::TOTAL_AMOUNT => 90.00,
                    FetchPaymentHandler::PAID_AMOUNT => 90.00,
                    'coupon_amount' => 10.00,
                    FetchPaymentHandler::PAYMENT_METHOD_INFO => [
                        FetchPaymentHandler::LAST_FOUR_DIGITS => '1234',
                        FetchPaymentHandler::INSTALLMENTS => 1,
                    ],
                ],
            ],
        ];

        $this->handler->handle($handlingSubject, $response);
    }

    /**
     * Test handle with approved status with currency conversion rate greater than 1
     */
    public function testHandleWithApprovedStatusWithCurrencyConversionRateGreaterThanOne()
    {
        $paymentMock = $this->createPaymentMock();
        $orderMock = $this->createOrderMock('payment_review', 100.00, 100.00);

        $invoiceMock = $this->createMock(Invoice::class);
        $invoiceMock->expects($this->once())
            ->method('register')
            ->willReturnSelf();
        $invoiceMock->expects($this->once())
            ->method('setOrder')
            ->with($orderMock);
        $invoiceMock->expects($this->once())
            ->method('setBaseGrandTotal');
        $invoiceMock->expects($this->once())
            ->method('setBaseSubtotal');
        $invoiceMock->expects($this->once())
            ->method('setGrandTotal');
        $invoiceMock->expects($this->once())
            ->method('setSubtotal');
        $invoiceMock->expects($this->once())
            ->method('addComment');

        $orderMock->expects($this->once())
            ->method('prepareInvoice')
            ->willReturn($invoiceMock);
        $orderMock->expects($this->once())
            ->method('addRelatedObject')
            ->with($invoiceMock);
        $orderMock->expects($this->once())
            ->method('getBaseToOrderRate')
            ->willReturn(3.5);

        $paymentMock->expects($this->any())
            ->method('getOrder')
            ->willReturn($orderMock);

        $paymentDO = $this->createPaymentDataObject($paymentMock);

        $handlingSubject = ['payment' => $paymentDO];
        $response = [
            FetchPaymentHandler::RESPONSE_STATUS => FetchPaymentHandler::RESPONSE_STATUS_APPROVED,
            'total_paid' => 90.00,
            FetchPaymentHandler::PAYMENT_DETAILS => [
                [
                    FetchPaymentHandler::ID => '12345',
                    FetchPaymentHandler::STATUS => 'approved',
                    FetchPaymentHandler::STATUS_DETAIL => 'accredited',
                    FetchPaymentHandler::PAYMENT_METHOD_ID => 'visa',
                    FetchPaymentHandler::TOTAL_AMOUNT => 90.00,
                    FetchPaymentHandler::PAID_AMOUNT => 90.00,
                    FetchPaymentHandler::PAYMENT_METHOD_INFO => [
                        FetchPaymentHandler::LAST_FOUR_DIGITS => '1234',
                        FetchPaymentHandler::INSTALLMENTS => 1,
                    ],
                ],
            ],
        ];

        $this->handler->handle($handlingSubject, $response);
    }

    /**
     * Test handle with approved status with currency conversion rate less than 1
     */
    public function testHandleWithApprovedStatusWithCurrencyConversionRateLessThanOne()
    {
        $paymentMock = $this->createPaymentMock();
        $orderMock = $this->createOrderMock('payment_review', 100.00, 100.00);

        $invoiceMock = $this->createMock(Invoice::class);
        $invoiceMock->expects($this->once())
            ->method('register')
            ->willReturnSelf();
        $invoiceMock->expects($this->once())
            ->method('setOrder')
            ->with($orderMock);
        $invoiceMock->expects($this->once())
            ->method('setBaseGrandTotal');
        $invoiceMock->expects($this->once())
            ->method('setBaseSubtotal');
        $invoiceMock->expects($this->once())
            ->method('setGrandTotal');
        $invoiceMock->expects($this->once())
            ->method('setSubtotal');
        $invoiceMock->expects($this->once())
            ->method('addComment');

        $orderMock->expects($this->once())
            ->method('prepareInvoice')
            ->willReturn($invoiceMock);
        $orderMock->expects($this->once())
            ->method('addRelatedObject')
            ->with($invoiceMock);
        $orderMock->expects($this->once())
            ->method('getBaseToOrderRate')
            ->willReturn(0.5);

        $paymentMock->expects($this->any())
            ->method('getOrder')
            ->willReturn($orderMock);

        $paymentDO = $this->createPaymentDataObject($paymentMock);

        $handlingSubject = ['payment' => $paymentDO];
        $response = [
            FetchPaymentHandler::RESPONSE_STATUS => FetchPaymentHandler::RESPONSE_STATUS_APPROVED,
            'total_paid' => 90.00,
            FetchPaymentHandler::PAYMENT_DETAILS => [
                [
                    FetchPaymentHandler::ID => '12345',
                    FetchPaymentHandler::STATUS => 'approved',
                    FetchPaymentHandler::STATUS_DETAIL => 'accredited',
                    FetchPaymentHandler::PAYMENT_METHOD_ID => 'visa',
                    FetchPaymentHandler::TOTAL_AMOUNT => 90.00,
                    FetchPaymentHandler::PAID_AMOUNT => 90.00,
                    FetchPaymentHandler::PAYMENT_METHOD_INFO => [
                        FetchPaymentHandler::LAST_FOUR_DIGITS => '1234',
                        FetchPaymentHandler::INSTALLMENTS => 1,
                    ],
                ],
            ],
        ];

        $this->handler->handle($handlingSubject, $response);
    }

    /**
     * Test handle with cancelled status
     */
    public function testHandleWithCancelledStatus()
    {
        $paymentMock = $this->createPaymentMock();
        $orderMock = $this->createOrderMock('pending', 100.00, 100.00);

        $paymentMock->expects($this->any())
            ->method('getOrder')
            ->willReturn($orderMock);

        $paymentMock->expects($this->once())
            ->method('registerVoidNotification')
            ->with(100.00);

        $paymentDO = $this->createPaymentDataObject($paymentMock);

        $handlingSubject = ['payment' => $paymentDO];
        $response = [
            FetchPaymentHandler::RESPONSE_STATUS => FetchPaymentHandler::RESPONSE_STATUS_CANCELLED,
            FetchPaymentHandler::PAYMENT_DETAILS => [
                [
                    FetchPaymentHandler::ID => '12345',
                    FetchPaymentHandler::STATUS => 'cancelled',
                    FetchPaymentHandler::STATUS_DETAIL => 'by_collector',
                    FetchPaymentHandler::PAYMENT_METHOD_ID => 'visa',
                    FetchPaymentHandler::TOTAL_AMOUNT => 100.00,
                    FetchPaymentHandler::PAID_AMOUNT => 0.00,
                    FetchPaymentHandler::PAYMENT_METHOD_INFO => [
                        FetchPaymentHandler::LAST_FOUR_DIGITS => '1234',
                        FetchPaymentHandler::INSTALLMENTS => 1,
                    ],
                ],
            ],
        ];

        $this->handler->handle($handlingSubject, $response);
    }

    /**
     * Test handle with rejected status
     */
    public function testHandleWithRejectedStatus()
    {
        $paymentMock = $this->createPaymentMock();
        $orderMock = $this->createOrderMock('pending', 100.00, 100.00);

        $paymentMock->expects($this->any())
            ->method('getOrder')
            ->willReturn($orderMock);

        $paymentMock->expects($this->once())
            ->method('registerVoidNotification')
            ->with(100.00);

        $paymentDO = $this->createPaymentDataObject($paymentMock);

        $handlingSubject = ['payment' => $paymentDO];
        $response = [
            FetchPaymentHandler::RESPONSE_STATUS => FetchPaymentHandler::RESPONSE_STATUS_REJECTED,
            FetchPaymentHandler::PAYMENT_DETAILS => [
                [
                    FetchPaymentHandler::ID => '12345',
                    FetchPaymentHandler::STATUS => 'rejected',
                    FetchPaymentHandler::STATUS_DETAIL => 'cc_rejected_bad_filled_security_code',
                    FetchPaymentHandler::PAYMENT_METHOD_ID => 'visa',
                    FetchPaymentHandler::TOTAL_AMOUNT => 100.00,
                    FetchPaymentHandler::PAID_AMOUNT => 0.00,
                    FetchPaymentHandler::PAYMENT_METHOD_INFO => [
                        FetchPaymentHandler::LAST_FOUR_DIGITS => '1234',
                        FetchPaymentHandler::INSTALLMENTS => 1,
                    ],
                ],
            ],
        ];

        $this->handler->handle($handlingSubject, $response);
    }

    /**
     * Test handle with refunded status
     */
    public function testHandleWithRefundedStatus()
    {
        $paymentMock = $this->createPaymentMock();
        $orderMock = $this->createOrderMock('processing', 100.00, 100.00);

        $paymentMock->expects($this->any())
            ->method('getOrder')
            ->willReturn($orderMock);

        // Mock existing payment information
        $paymentMock->expects($this->any())
            ->method('getAdditionalInformation')
            ->willReturnCallback(function ($key) {
                if ($key === 'payment_0_id') {
                    return '12345';
                }
                if ($key === 'payment_0_refunded_amount') {
                    return 50.00;
                }
                return null;
            });

        $paymentDO = $this->createPaymentDataObject($paymentMock);

        $handlingSubject = ['payment' => $paymentDO];
        $response = [
            FetchPaymentHandler::RESPONSE_STATUS => FetchPaymentHandler::RESPONSE_STATUS_REFUNDED,
            FetchPaymentHandler::PAYMENT_DETAILS => [
                [
                    FetchPaymentHandler::ID => '12345',
                    FetchPaymentHandler::STATUS => 'refunded',
                    FetchPaymentHandler::STATUS_DETAIL => 'refunded',
                    FetchPaymentHandler::PAYMENT_METHOD_ID => 'visa',
                    FetchPaymentHandler::TOTAL_AMOUNT => 100.00,
                    FetchPaymentHandler::PAID_AMOUNT => 0.00,
                    FetchPaymentHandler::PAYMENT_METHOD_INFO => [
                        FetchPaymentHandler::LAST_FOUR_DIGITS => '1234',
                        FetchPaymentHandler::INSTALLMENTS => 1,
                    ],
                ],
            ],
        ];

        $this->handler->handle($handlingSubject, $response);
        $this->assertTrue(true); // Assert that no exception was thrown
    }

    /**
     * Test handle with pending status and multiple payments
     */
    public function testHandleWithPendingStatusAndMultiplePayments()
    {
        $paymentMock = $this->createPaymentMock();
        $orderMock = $this->createOrderMock('processing', 200.00, 200.00);

        $paymentMock->expects($this->any())
            ->method('getOrder')
            ->willReturn($orderMock);

        $paymentDO = $this->createPaymentDataObject($paymentMock);

        $handlingSubject = ['payment' => $paymentDO];
        $response = [
            FetchPaymentHandler::RESPONSE_STATUS => FetchPaymentHandler::RESPONSE_STATUS_PENDING,
            FetchPaymentHandler::PAYMENT_DETAILS => [
                [
                    FetchPaymentHandler::ID => '12345',
                    FetchPaymentHandler::STATUS => 'approved',
                    FetchPaymentHandler::STATUS_DETAIL => 'accredited',
                    FetchPaymentHandler::PAYMENT_METHOD_ID => 'visa',
                    FetchPaymentHandler::TOTAL_AMOUNT => 100.00,
                    FetchPaymentHandler::PAID_AMOUNT => 100.00,
                    FetchPaymentHandler::PAYMENT_METHOD_INFO => [
                        FetchPaymentHandler::LAST_FOUR_DIGITS => '1234',
                        FetchPaymentHandler::INSTALLMENTS => 1,
                    ],
                ],
                [
                    FetchPaymentHandler::ID => '67890',
                    FetchPaymentHandler::STATUS => 'pending',
                    FetchPaymentHandler::STATUS_DETAIL => 'pending_contingency',
                    FetchPaymentHandler::PAYMENT_METHOD_ID => 'master',
                    FetchPaymentHandler::TOTAL_AMOUNT => 100.00,
                    FetchPaymentHandler::PAID_AMOUNT => 0.00,
                    FetchPaymentHandler::PAYMENT_METHOD_INFO => [
                        FetchPaymentHandler::LAST_FOUR_DIGITS => '5678',
                        FetchPaymentHandler::INSTALLMENTS => 3,
                    ],
                ],
            ],
        ];

        $this->handler->handle($handlingSubject, $response);
        $this->assertTrue(true); // Assert that no exception was thrown
    }

    /**
     * Test handle with payment expiration date
     */
    public function testHandleWithPaymentExpirationDate()
    {
        $paymentMock = $this->createPaymentMock();
        $orderMock = $this->createOrderMock('pending', 100.00, 100.00);

        $paymentMock->expects($this->any())
            ->method('getOrder')
            ->willReturn($orderMock);

        $paymentDO = $this->createPaymentDataObject($paymentMock);

        $handlingSubject = ['payment' => $paymentDO];
        $response = [
            FetchPaymentHandler::RESPONSE_STATUS => FetchPaymentHandler::RESPONSE_STATUS_PENDING,
            FetchPaymentHandler::PAYMENT_DETAILS => [
                [
                    FetchPaymentHandler::ID => '12345',
                    FetchPaymentHandler::STATUS => 'pending',
                    FetchPaymentHandler::STATUS_DETAIL => 'pending_waiting_payment',
                    FetchPaymentHandler::PAYMENT_METHOD_ID => 'bolbradesco',
                    FetchPaymentHandler::TOTAL_AMOUNT => 100.00,
                    FetchPaymentHandler::PAID_AMOUNT => 0.00,
                    FetchPaymentHandler::PAYMENT_METHOD_INFO => [
                        FetchPaymentHandler::LAST_FOUR_DIGITS => '0000',
                        FetchPaymentHandler::INSTALLMENTS => 1,
                        FetchPaymentHandler::DATE_OF_EXPIRATION => '2025-12-31T23:59:59.000Z',
                    ],
                ],
            ],
        ];

        $this->handler->handle($handlingSubject, $response);
        $this->assertTrue(true); // Assert that no exception was thrown
    }

    /**
     * Test getIndexPayment returns correct index when payment found at index 0
     */
    public function testGetIndexPaymentReturnsZeroWhenPaymentFoundAtIndexZero()
    {
        $paymentMock = $this->createPaymentMock();

        $paymentMock->expects($this->once())
            ->method('getAdditionalInformation')
            ->with('payment_0_id')
            ->willReturn('12345');

        $result = $this->handler->getIndexPayment($paymentMock, '12345');
        $this->assertEquals(0, $result);
    }

    /**
     * Test getIndexPayment returns correct index when payment found at index 1
     */
    public function testGetIndexPaymentReturnsOneWhenPaymentFoundAtIndexOne()
    {
        $paymentMock = $this->createPaymentMock();

        $paymentMock->expects($this->exactly(2))
            ->method('getAdditionalInformation')
            ->willReturnCallback(function ($key) {
                if ($key === 'payment_0_id') {
                    return '99999';
                }
                if ($key === 'payment_1_id') {
                    return '12345';
                }
                return null;
            });

        $result = $this->handler->getIndexPayment($paymentMock, '12345');
        $this->assertEquals(1, $result);
    }

    /**
     * Test getIndexPayment returns null when payment not found
     */
    public function testGetIndexPaymentReturnsNullWhenPaymentNotFound()
    {
        $paymentMock = $this->createPaymentMock();

        $paymentMock->expects($this->exactly(2))
            ->method('getAdditionalInformation')
            ->willReturnCallback(function ($key) {
                return null;
            });

        $result = $this->handler->getIndexPayment($paymentMock, '12345');
        $this->assertNull($result);
    }

    /**
     * Test updatePaymentByIndex sets all payment information correctly
     */
    public function testUpdatePaymentByIndexSetsAllPaymentInformation()
    {
        $paymentMock = $this->createPaymentMock();

        $mpPayment = [
            FetchPaymentHandler::ID => '12345',
            FetchPaymentHandler::STATUS => 'approved',
            FetchPaymentHandler::STATUS_DETAIL => 'accredited',
            FetchPaymentHandler::PAYMENT_METHOD_ID => 'visa',
            FetchPaymentHandler::TOTAL_AMOUNT => 100.00,
            FetchPaymentHandler::PAID_AMOUNT => 100.00,
            FetchPaymentHandler::PAYMENT_METHOD_INFO => [
                FetchPaymentHandler::LAST_FOUR_DIGITS => '1234',
                FetchPaymentHandler::INSTALLMENTS => 1,
                FetchPaymentHandler::DATE_OF_EXPIRATION => '2025-12-31T23:59:59.000Z',
            ],
        ];

        $expectedCalls = [
            ['payment_0_id', '12345'],
            ['payment_0_type', 'visa'],
            ['payment_0_total_amount', 100.00],
            ['payment_0_paid_amount', 100.00],
            ['payment_0_card_number', '1234'],
            ['payment_0_installments', 1],
            ['mp_0_status', 'approved'],
            ['mp_0_status_detail', 'accredited'],
            ['payment_0_expiration', '2025-12-31T23:59:59.000Z'],
        ];

        $callCount = 0;
        $paymentMock->expects($this->exactly(count($expectedCalls) + 1))
            ->method('setAdditionalInformation')
            ->willReturnCallback(function ($key, $value) use ($expectedCalls, &$callCount) {
                if ($key === 'payment_0_refunded_amount') {
                    $this->assertEquals(0, $value);
                    return;
                }

                $this->assertEquals($expectedCalls[$callCount][0], $key);
                $this->assertEquals($expectedCalls[$callCount][1], $value);
                $callCount++;
            });

        $paymentMock->expects($this->once())
            ->method('getAdditionalInformation')
            ->with('payment_0_refunded_amount')
            ->willReturn(null);

        $this->handler->updatePaymentByIndex($paymentMock, 0, $mpPayment);
    }

    /**
     * Test updatePaymentByIndex without expiration date
     */
    public function testUpdatePaymentByIndexWithoutExpirationDate()
    {
        $paymentMock = $this->createPaymentMock();

        $mpPayment = [
            FetchPaymentHandler::ID => '67890',
            FetchPaymentHandler::STATUS => 'pending',
            FetchPaymentHandler::STATUS_DETAIL => 'pending_contingency',
            FetchPaymentHandler::PAYMENT_METHOD_ID => 'master',
            FetchPaymentHandler::TOTAL_AMOUNT => 50.00,
            FetchPaymentHandler::PAID_AMOUNT => 0.00,
            FetchPaymentHandler::PAYMENT_METHOD_INFO => [
                FetchPaymentHandler::LAST_FOUR_DIGITS => '5678',
                FetchPaymentHandler::INSTALLMENTS => 3,
            ],
        ];

        $paymentMock->expects($this->once())
            ->method('getAdditionalInformation')
            ->with('payment_1_refunded_amount')
            ->willReturn(25.00);

        $this->handler->updatePaymentByIndex($paymentMock, 1, $mpPayment);
    }

    /**
     * Helper method to create payment mock
     */
    private function createPaymentMock()
    {
        return $this->getMockBuilder(Payment::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * Helper method to create order mock
     */
    private function createOrderMock($status, $grandTotal, $baseGrandTotal)
    {
        $orderMock = $this->getMockBuilder(Order::class)
            ->onlyMethods([
                'getStatus',
                'getGrandTotal',
                'getBaseGrandTotal',
                'setState',
                'setStatus',
                'addStatusHistoryComment',
                'prepareInvoice',
                'addRelatedObject',
                'getBaseToOrderRate',
            ])
            ->disableOriginalConstructor()
            ->getMock();

        $orderMock->method('getStatus')->willReturn($status);
        $orderMock->method('getGrandTotal')->willReturn($grandTotal);
        $orderMock->method('getBaseGrandTotal')->willReturn($baseGrandTotal);

        return $orderMock;
    }

    /**
     * Helper method to create payment data object
     */
    private function createPaymentDataObject($paymentMock)
    {
        $paymentDO = $this->createMock(PaymentDataObjectInterface::class);
        $paymentDO->method('getPayment')->willReturn($paymentMock);
        return $paymentDO;
    }
}
