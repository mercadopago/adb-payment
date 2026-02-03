<?php
/**
 * Copyright © MercadoPago. All rights reserved.
 *
 * @author      Mercado Pago
 * @license     See LICENSE for license details.
 */

namespace MercadoPago\AdbPayment\Tests\Unit\Gateway\Response;

use MercadoPago\AdbPayment\Gateway\Http\Client\Order\FetchOrderClient;
use MercadoPago\AdbPayment\Gateway\Response\FetchOrderCompositeHandler;
use MercadoPago\AdbPayment\Gateway\Response\FetchOrderHandler;
use MercadoPago\AdbPayment\Gateway\Response\FetchPaymentHandler;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for FetchOrderCompositeHandler.
 */
class FetchOrderCompositeHandlerTest extends TestCase
{
    /**
     * @var FetchPaymentHandler|\PHPUnit\Framework\MockObject\MockObject
     */
    private $fetchPaymentHandlerMock;

    /**
     * @var FetchOrderHandler|\PHPUnit\Framework\MockObject\MockObject
     */
    private $fetchOrderHandlerMock;

    /**
     * @var FetchOrderCompositeHandler
     */
    private $compositeHandler;

    /**
     * Setup test dependencies
     */
    protected function setUp(): void
    {
        $this->fetchPaymentHandlerMock = $this->createMock(FetchPaymentHandler::class);
        $this->fetchOrderHandlerMock = $this->createMock(FetchOrderHandler::class);

        $this->compositeHandler = new FetchOrderCompositeHandler(
            $this->fetchPaymentHandlerMock,
            $this->fetchOrderHandlerMock
        );
    }

    /**
     * Test handle routes to FetchPaymentHandler when api_source is payment_api
     */
    public function testHandleRoutesToPaymentHandlerWhenApiSourceIsPaymentApi()
    {
        $handlingSubject = ['payment' => 'mock_payment_data'];
        $response = [
            FetchOrderClient::API_SOURCE => FetchOrderClient::API_SOURCE_PAYMENT,
            'id' => 144005057552,
            'status' => 'approved',
        ];

        // FetchPaymentHandler should be called
        $this->fetchPaymentHandlerMock->expects($this->once())
            ->method('handle')
            ->with($handlingSubject, $response);

        // FetchOrderHandler should NOT be called
        $this->fetchOrderHandlerMock->expects($this->never())
            ->method('handle');

        $this->compositeHandler->handle($handlingSubject, $response);
    }

    /**
     * Test handle routes to FetchOrderHandler when api_source is order_api
     */
    public function testHandleRoutesToOrderHandlerWhenApiSourceIsOrderApi()
    {
        $handlingSubject = ['payment' => 'mock_payment_data'];
        $response = [
            FetchOrderClient::API_SOURCE => FetchOrderClient::API_SOURCE_ORDER,
            'id' => 'PPORDO32YD7YI5KX2A9C0MZ1QZ33TJW',
            'status' => 'processed',
        ];

        // FetchOrderHandler should be called
        $this->fetchOrderHandlerMock->expects($this->once())
            ->method('handle')
            ->with($handlingSubject, $response);

        // FetchPaymentHandler should NOT be called
        $this->fetchPaymentHandlerMock->expects($this->never())
            ->method('handle');

        $this->compositeHandler->handle($handlingSubject, $response);
    }

    /**
     * Test handle defaults to FetchOrderHandler when api_source is not present
     */
    public function testHandleDefaultsToOrderHandlerWhenApiSourceNotPresent()
    {
        $handlingSubject = ['payment' => 'mock_payment_data'];
        $response = [
            'id' => 'PPORDO32YD7YI5KX2A9C0MZ1QZ33ABC',
            'status' => 'processed',
            // api_source is not present
        ];

        // FetchOrderHandler should be called (default)
        $this->fetchOrderHandlerMock->expects($this->once())
            ->method('handle')
            ->with($handlingSubject, $response);

        // FetchPaymentHandler should NOT be called
        $this->fetchPaymentHandlerMock->expects($this->never())
            ->method('handle');

        $this->compositeHandler->handle($handlingSubject, $response);
    }

    /**
     * Test handle defaults to FetchOrderHandler when api_source is null
     */
    public function testHandleDefaultsToOrderHandlerWhenApiSourceIsNull()
    {
        $handlingSubject = ['payment' => 'mock_payment_data'];
        $response = [
            FetchOrderClient::API_SOURCE => null,
            'id' => 'PPORDO32YD7YI5KX2A9C0MZ1QZ33DEF',
            'status' => 'processed',
        ];

        // FetchOrderHandler should be called (default)
        $this->fetchOrderHandlerMock->expects($this->once())
            ->method('handle')
            ->with($handlingSubject, $response);

        // FetchPaymentHandler should NOT be called
        $this->fetchPaymentHandlerMock->expects($this->never())
            ->method('handle');

        $this->compositeHandler->handle($handlingSubject, $response);
    }

    /**
     * Test handle defaults to FetchOrderHandler when api_source has unknown value
     */
    public function testHandleDefaultsToOrderHandlerWhenApiSourceIsUnknown()
    {
        $handlingSubject = ['payment' => 'mock_payment_data'];
        $response = [
            FetchOrderClient::API_SOURCE => 'unknown_api',
            'id' => 'some-id',
            'status' => 'pending',
        ];

        // FetchOrderHandler should be called (default for unknown)
        $this->fetchOrderHandlerMock->expects($this->once())
            ->method('handle')
            ->with($handlingSubject, $response);

        // FetchPaymentHandler should NOT be called
        $this->fetchPaymentHandlerMock->expects($this->never())
            ->method('handle');

        $this->compositeHandler->handle($handlingSubject, $response);
    }

    /**
     * Test handle passes complete response to Payment handler
     */
    public function testHandlePassesCompleteResponseToPaymentHandler()
    {
        $handlingSubject = [
            'payment' => 'mock_payment_data',
            'amount' => 100.50,
        ];
        $response = [
            FetchOrderClient::API_SOURCE => FetchOrderClient::API_SOURCE_PAYMENT,
            'id' => 144005057552,
            'status' => 'approved',
            'status_detail' => 'accredited',
            'transaction_amount' => 100.50,
            'payment_method_id' => 'pix',
            'payment_type_id' => 'bank_transfer',
        ];

        $this->fetchPaymentHandlerMock->expects($this->once())
            ->method('handle')
            ->with(
                $this->equalTo($handlingSubject),
                $this->equalTo($response)
            );

        $this->compositeHandler->handle($handlingSubject, $response);
    }

    /**
     * Test handle passes complete response to Order handler
     */
    public function testHandlePassesCompleteResponseToOrderHandler()
    {
        $handlingSubject = [
            'payment' => 'mock_payment_data',
            'amount' => 250.75,
        ];
        $response = [
            FetchOrderClient::API_SOURCE => FetchOrderClient::API_SOURCE_ORDER,
            'id' => 'PPORDO32YD7YI5KX2A9C0MZ1QZ33GHI',
            'status' => 'processed',
            'status_detail' => 'approved',
            'total_paid_amount' => 250.75,
            'payments' => [
                ['id' => 'payment-1', 'status' => 'approved'],
            ],
        ];

        $this->fetchOrderHandlerMock->expects($this->once())
            ->method('handle')
            ->with(
                $this->equalTo($handlingSubject),
                $this->equalTo($response)
            );

        $this->compositeHandler->handle($handlingSubject, $response);
    }

    /**
     * Test handle with empty response defaults to Order handler
     */
    public function testHandleWithEmptyResponseDefaultsToOrderHandler()
    {
        $handlingSubject = ['payment' => 'mock_payment_data'];
        $response = [];

        // FetchOrderHandler should be called (default for empty)
        $this->fetchOrderHandlerMock->expects($this->once())
            ->method('handle')
            ->with($handlingSubject, $response);

        // FetchPaymentHandler should NOT be called
        $this->fetchPaymentHandlerMock->expects($this->never())
            ->method('handle');

        $this->compositeHandler->handle($handlingSubject, $response);
    }

    /**
     * Data provider for api_source routing scenarios
     */
    public function apiSourceRoutingDataProvider(): array
    {
        return [
            'payment_api routes to payment handler' => [
                FetchOrderClient::API_SOURCE_PAYMENT,
                'payment',
            ],
            'order_api routes to order handler' => [
                FetchOrderClient::API_SOURCE_ORDER,
                'order',
            ],
        ];
    }

    /**
     * Test routing based on api_source value
     *
     * @dataProvider apiSourceRoutingDataProvider
     */
    public function testRoutingBasedOnApiSource(string $apiSource, string $expectedHandler)
    {
        $handlingSubject = ['payment' => 'mock_payment_data'];
        $response = [
            FetchOrderClient::API_SOURCE => $apiSource,
            'id' => 'test-id',
            'status' => 'approved',
        ];

        if ($expectedHandler === 'payment') {
            $this->fetchPaymentHandlerMock->expects($this->once())
                ->method('handle');
            $this->fetchOrderHandlerMock->expects($this->never())
                ->method('handle');
        } else {
            $this->fetchOrderHandlerMock->expects($this->once())
                ->method('handle');
            $this->fetchPaymentHandlerMock->expects($this->never())
                ->method('handle');
        }

        $this->compositeHandler->handle($handlingSubject, $response);
    }

    /**
     * Test that handler returns void (no return value)
     */
    public function testHandleReturnsVoid()
    {
        $handlingSubject = ['payment' => 'mock_payment_data'];
        $response = [
            FetchOrderClient::API_SOURCE => FetchOrderClient::API_SOURCE_ORDER,
            'id' => 'test-id',
        ];

        $result = $this->compositeHandler->handle($handlingSubject, $response);

        $this->assertNull($result);
    }

    /**
     * Test Payment API response with all typical fields
     */
    public function testPaymentApiResponseWithTypicalFields()
    {
        $handlingSubject = ['payment' => 'mock_payment_data'];
        $response = [
            FetchOrderClient::API_SOURCE => FetchOrderClient::API_SOURCE_PAYMENT,
            'id' => 144005057552,
            'status' => 'approved',
            'status_detail' => 'accredited',
            'transaction_amount' => 100.00,
            'currency_id' => 'BRL',
            'payment_method_id' => 'pix',
            'payment_type_id' => 'bank_transfer',
            'date_created' => '2024-01-15T10:30:00.000-03:00',
            'date_approved' => '2024-01-15T10:31:00.000-03:00',
        ];

        $this->fetchPaymentHandlerMock->expects($this->once())
            ->method('handle')
            ->with($handlingSubject, $response);

        $this->compositeHandler->handle($handlingSubject, $response);
    }

    /**
     * Test Order API response with all typical fields
     */
    public function testOrderApiResponseWithTypicalFields()
    {
        $handlingSubject = ['payment' => 'mock_payment_data'];
        $response = [
            FetchOrderClient::API_SOURCE => FetchOrderClient::API_SOURCE_ORDER,
            'id' => 'PPORDO32YD7YI5KX2A9C0MZ1QZ33JKL',
            'status' => 'processed',
            'status_detail' => 'approved',
            'external_reference' => 'order-12345',
            'total_paid_amount' => 100.00,
            'payments' => [
                [
                    'id' => 'payment-001',
                    'status' => 'approved',
                    'amount' => 100.00,
                    'payment_method' => ['id' => 'pix'],
                ],
            ],
        ];

        $this->fetchOrderHandlerMock->expects($this->once())
            ->method('handle')
            ->with($handlingSubject, $response);

        $this->compositeHandler->handle($handlingSubject, $response);
    }
}
