<?php
/**
 * Copyright © MercadoPago. All rights reserved.
 *
 * @author      Mercado Pago
 * @license     See LICENSE for license details.
 */

namespace MercadoPago\AdbPayment\Tests\Unit\Model\Notification\Refund\Order;

use Exception;
use MercadoPago\AdbPayment\Gateway\Config\Config;
use MercadoPago\AdbPayment\Model\Notification\Refund\Order\RefundOrder;
use MercadoPago\AdbPayment\Model\Order\UpdatePayment;
use MercadoPago\AdbPayment\Model\Metrics\MetricsClient;
use Magento\Framework\Notification\NotifierInterface;
use Magento\Payment\Model\Method\Logger;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Creditmemo;
use Magento\Sales\Model\Order\CreditmemoFactory;
use Magento\Sales\Model\Order\Payment;
use Magento\Sales\Model\ResourceModel\Order\Creditmemo\Collection as CreditmemoCollection;
use Magento\Sales\Model\Service\CreditmemoService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class RefundOrderTest extends TestCase
{
    /** @var Config|MockObject */
    private $configMock;

    /** @var NotifierInterface|MockObject */
    private $notifierMock;

    /** @var CreditmemoFactory|MockObject */
    private $creditmemoFactoryMock;

    /** @var CreditmemoService|MockObject */
    private $creditmemoServiceMock;

    /** @var Order|MockObject */
    private $orderMock;

    /** @var Logger|MockObject */
    private $loggerMock;

    /** @var UpdatePayment|MockObject */
    private $updatePaymentMock;

    /** @var Payment|MockObject */
    private $paymentMock;

    /** @var CreditmemoCollection|MockObject */
    private $creditmemoCollectionMock;

    /** @var MetricsClient|MockObject */
    private $metricsClientMock;

    protected function setUp(): void
    {
        $this->configMock = $this->createMock(Config::class);
        $this->notifierMock = $this->createMock(NotifierInterface::class);
        $this->creditmemoFactoryMock = $this->createMock(CreditmemoFactory::class);
        $this->creditmemoServiceMock = $this->createMock(CreditmemoService::class);
        $this->orderMock = $this->createMock(Order::class);
        $this->loggerMock = $this->createMock(Logger::class);
        $this->updatePaymentMock = $this->createMock(UpdatePayment::class);
        $this->paymentMock = $this->createMock(Payment::class);
        $this->creditmemoCollectionMock = $this->createMock(CreditmemoCollection::class);
        $this->metricsClientMock = $this->createMock(MetricsClient::class);

        $this->orderMock->method('getPayment')->willReturn($this->paymentMock);
        $this->orderMock->method('getCreditmemosCollection')->willReturn($this->creditmemoCollectionMock);
        
        $this->creditmemoCollectionMock->method('clear')->willReturnSelf();
        $this->creditmemoCollectionMock->method('load')->willReturnSelf();
    }

    private function createRefundOrder(array $notification): RefundOrder
    {
        return new RefundOrder(
            $this->configMock,
            $this->notifierMock,
            $this->creditmemoFactoryMock,
            $this->creditmemoServiceMock,
            $this->orderMock,
            $this->loggerMock,
            $this->updatePaymentMock,
            $notification,
            $this->metricsClientMock
        );
    }

    private function buildNotification(
        string $refundId,
        float $amount,
        string $status,
        string $source = '',
        string $notificationId = 'NOTIF123'
    ): array {
        return [
            'notification_id' => $notificationId,
            'payments_details' => [
                [
                    'refunds' => [
                        [
                            'notifying' => true,
                            'status' => $status,
                            'amount' => $amount,
                            'source' => $source,
                            'references' => ['refund_order_id' => $refundId],
                        ],
                    ],
                ],
            ],
        ];
    }

    private function setupOrderForRefund(): void
    {
        $this->orderMock->method('getInvoiceCollection')->willReturn(['invoice']);
        $this->orderMock->method('getState')->willReturn(Order::STATE_PROCESSING);
        $this->orderMock->method('getStoreId')->willReturn(1);
        $this->orderMock->method('getIncrementId')->willReturn('100000001');
        $this->orderMock->method('save')->willReturnSelf();
        $this->orderMock->method('addCommentToStatusHistory')->willReturnSelf();

        $this->configMock->method('isApplyRefund')->with(1)->willReturn(true);
    }

    public function testProcessReturnsErrorWhenNoInvoices(): void
    {
        $this->orderMock->method('getInvoiceCollection')->willReturn([]);
        $this->orderMock->method('getIncrementId')->willReturn('100000001');

        $result = $this->createRefundOrder([])->process();

        $this->assertEquals(400, $result['code']);
        $this->assertStringContainsString('has no invoice to refund', $result['msg']);
    }

    public function testProcessReturnsSuccessWhenOrderAlreadyClosed(): void
    {
        $this->orderMock->method('getInvoiceCollection')->willReturn(['invoice']);
        $this->orderMock->method('getState')->willReturn(Order::STATE_CLOSED);
        $this->orderMock->method('getIncrementId')->willReturn('100000001');

        $this->updatePaymentMock->expects($this->once())
            ->method('updateInformation')
            ->with($this->orderMock, $this->isType('array'));

        $notification = $this->buildNotification('REF123', 50.00, 'processed');
        $result = $this->createRefundOrder($notification)->process();

        $this->assertEquals(200, $result['code']);
        $this->assertStringContainsString('already closed', $result['msg']);
    }

    public function testProcessReturnsSuccessWhenRefundDisabledInConfig(): void
    {
        $this->orderMock->method('getInvoiceCollection')->willReturn(['invoice']);
        $this->orderMock->method('getState')->willReturn(Order::STATE_PROCESSING);
        $this->orderMock->method('getStoreId')->willReturn(1);

        $this->configMock->method('isApplyRefund')->with(1)->willReturn(false);

        $notification = $this->buildNotification('REF123', 50.00, 'processed');
        $result = $this->createRefundOrder($notification)->process();

        $this->assertEquals(200, $result['code']);
        $this->assertStringContainsString('disabled in config', $result['msg']);
    }

    public function existingCreditmemoStateProvider(): array
    {
        return [
            'creditmemo already refunded' => [
                Creditmemo::STATE_REFUNDED, 'processed', 'Refund already processed',
            ],
            'creditmemo canceled' => [
                Creditmemo::STATE_CANCELED, 'processed', 'CreditMemo was canceled',
            ],
            'creditmemo open and refund processed' => [
                Creditmemo::STATE_OPEN, 'processed', 'CreditMemo updated to REFUNDED',
            ],
            'creditmemo open and refund processing' => [
                Creditmemo::STATE_OPEN, 'processing', 'CreditMemo is OPEN, refund still processing',
            ],
        ];
    }

    /**
     * @dataProvider existingCreditmemoStateProvider
     */
    public function testHandlesExistingCreditmemoState(int $state, string $refundStatus, string $expected): void
    {
        $this->setupOrderForRefund();

        $creditmemoMock = $this->createMock(Creditmemo::class);
        $creditmemoMock->method('getTransactionId')->willReturn('REF123');
        $creditmemoMock->method('getState')->willReturn($state);
        $creditmemoMock->method('setState')->willReturnSelf();
        $creditmemoMock->method('addComment')->willReturnSelf();
        $creditmemoMock->method('save')->willReturnSelf();

        $this->creditmemoCollectionMock->method('getIterator')
            ->willReturn(new \ArrayIterator([$creditmemoMock]));

        $notification = $this->buildNotification('REF123', 50.00, $refundStatus);
        $result = $this->createRefundOrder($notification)->process();

        $this->assertEquals(200, $result['code']);
        $this->assertStringContainsString($expected, $result['msg']);
    }

    public function testProcessCreatesRefundSuccessfully(): void
    {
        $this->setupOrderForRefund();
        $this->creditmemoCollectionMock->method('getIterator')->willReturn(new \ArrayIterator([]));

        $creditmemoMock = $this->createMock(Creditmemo::class);
        $creditmemoMock->method('getBaseGrandTotal')->willReturn(100.00);
        $creditmemoMock->method('setState')->willReturnSelf();
        $creditmemoMock->method('setBaseGrandTotal')->willReturnSelf();
        $creditmemoMock->method('setGrandTotal')->willReturnSelf();
        $creditmemoMock->method('setItems')->willReturnSelf();
        $creditmemoMock->method('addComment')->willReturnSelf();

        $this->creditmemoFactoryMock->method('createByOrder')
            ->with($this->orderMock)->willReturn($creditmemoMock);

        $this->paymentMock->method('setTransactionId')->willReturnSelf();
        $this->paymentMock->method('setIsTransactionClosed')->willReturnSelf();
        $this->paymentMock->method('setAdditionalInformation')->willReturnSelf();
        $this->paymentMock->method('setParentTransactionId')->willReturnSelf();
        $this->paymentMock->method('getAdditionalInformation')->willReturn(['mp_order_id' => 'PPORD123']);
        $this->paymentMock->method('addTransaction')->willReturn(null);

        $this->orderMock->method('getTotalPaid')->willReturn(100.00);
        $this->orderMock->method('getTotalRefunded')->willReturn(0.00);

        $this->creditmemoServiceMock->expects($this->once())->method('refund')->with($creditmemoMock, false);
        $this->notifierMock->expects($this->once())->method('add');

        $notification = $this->buildNotification('REF123', 100.00, 'processed');
        $result = $this->createRefundOrder($notification)->process();

        $this->assertEquals(200, $result['code']);
        $this->assertStringContainsString('Refunded successfully', $result['msg']);
    }

    public function testProcessCreatesPartialRefundWithEmptyItems(): void
    {
        $this->setupOrderForRefund();
        $this->creditmemoCollectionMock->method('getIterator')->willReturn(new \ArrayIterator([]));

        $creditmemoMock = $this->createMock(Creditmemo::class);
        $creditmemoMock->method('getBaseGrandTotal')->willReturn(100.00);
        $creditmemoMock->method('setState')->willReturnSelf();
        $creditmemoMock->method('setBaseGrandTotal')->willReturnSelf();
        $creditmemoMock->method('setGrandTotal')->willReturnSelf();
        $creditmemoMock->method('addComment')->willReturnSelf();

        $creditmemoMock->expects($this->once())->method('setItems')->with([])->willReturnSelf();

        $this->creditmemoFactoryMock->method('createByOrder')->willReturn($creditmemoMock);

        $this->paymentMock->method('setTransactionId')->willReturnSelf();
        $this->paymentMock->method('setIsTransactionClosed')->willReturnSelf();
        $this->paymentMock->method('setAdditionalInformation')->willReturnSelf();
        $this->paymentMock->method('setParentTransactionId')->willReturnSelf();
        $this->paymentMock->method('getAdditionalInformation')->willReturn(['mp_order_id' => 'PPORD123']);
        $this->paymentMock->method('addTransaction')->willReturn(null);

        $this->orderMock->method('getTotalPaid')->willReturn(100.00);
        $this->orderMock->method('getTotalRefunded')->willReturn(0.00);

        $notification = $this->buildNotification('REF123', 30.00, 'processed');
        $result = $this->createRefundOrder($notification)->process();

        $this->assertEquals(200, $result['code']);
    }

    public function testProcessHandlesRefundException(): void
    {
        $this->setupOrderForRefund();
        $this->creditmemoCollectionMock->method('getIterator')->willReturn(new \ArrayIterator([]));

        $this->creditmemoFactoryMock->method('createByOrder')
            ->willThrowException(new Exception('Test exception'));

        $this->loggerMock->expects($this->once())->method('debug');

        $notification = $this->buildNotification('REF123', 50.00, 'processed');
        $result = $this->createRefundOrder($notification)->process();

        $this->assertEquals(400, $result['code']);
        $this->assertStringContainsString('Failed to process refund', $result['msg']);
    }

    public function testProcessHandlesFailedRefundCancelsOpenCreditmemo(): void
    {
        $this->setupOrderForRefund();

        $creditmemoMock = $this->createMock(Creditmemo::class);
        $creditmemoMock->method('getTransactionId')->willReturn('REF123');
        $creditmemoMock->method('getState')->willReturn(Creditmemo::STATE_OPEN);
        
        $creditmemoMock->expects($this->once())
            ->method('setState')->with(Creditmemo::STATE_CANCELED)->willReturnSelf();
        
        $creditmemoMock->method('addComment')->willReturnSelf();
        $creditmemoMock->method('save')->willReturnSelf();

        $this->creditmemoCollectionMock->method('getIterator')
            ->willReturn(new \ArrayIterator([$creditmemoMock]));

        $notification = $this->buildNotification('REF123', 50.00, 'failed');
        $result = $this->createRefundOrder($notification)->process();

        $this->assertEquals(200, $result['code']);
        $this->assertStringContainsString('Refund failed. CreditMemo canceled', $result['msg']);
    }

    public function testProcessSkipsNonNotifyingRefunds(): void
    {
        $this->setupOrderForRefund();
        $this->creditmemoCollectionMock->method('getIterator')->willReturn(new \ArrayIterator([]));

        $notification = [
            'notification_id' => 'NOTIF123',
            'payments_details' => [[
                'refunds' => [['notifying' => false, 'status' => 'processed', 'amount' => 50.00]],
            ]],
        ];

        $result = $this->createRefundOrder($notification)->process();

        $this->assertEquals(200, $result['code']);
        $this->assertStringContainsString('No refunds to process', $result['msg']);
    }

    public function refundIdExtractionProvider(): array
    {
        return [
            'refund_order_id present' => [
                ['refund_order_id' => 'REFORDER123'], 'REFORDER123',
            ],
            'refund_payment_id only' => [
                ['refund_payment_id' => 'REFPAY456'], 'REFPAY456',
            ],
            'both ids - order takes priority' => [
                ['refund_order_id' => 'REFORDER789', 'refund_payment_id' => 'REFPAY789'], 'REFORDER789',
            ],
            'empty order_id uses payment_id' => [
                ['refund_order_id' => '', 'refund_payment_id' => 'REFPAY999'], 'REFPAY999',
            ],
        ];
    }

    /**
     * @dataProvider refundIdExtractionProvider
     */
    public function testRefundIdExtraction(array $references, string $expectedId): void
    {
        $this->setupOrderForRefund();
        
        $creditmemoMock = $this->createMock(Creditmemo::class);
        $creditmemoMock->method('getTransactionId')->willReturn($expectedId);
        $creditmemoMock->method('getState')->willReturn(Creditmemo::STATE_REFUNDED);

        $this->creditmemoCollectionMock->method('getIterator')
            ->willReturn(new \ArrayIterator([$creditmemoMock]));

        $notification = [
            'notification_id' => 'NOTIF123',
            'payments_details' => [[
                'refunds' => [[
                    'notifying' => true,
                    'status' => 'processed',
                    'amount' => 50.00,
                    'source' => '',
                    'references' => $references,
                ]],
            ]],
        ];

        $result = $this->createRefundOrder($notification)->process();

        $this->assertStringContainsString('Refund already processed', $result['msg']);
    }

    public function testProcessHandlesMpOpPpOrderApiSourceWithExistingCreditmemo(): void
    {
        $this->setupOrderForRefund();

        $creditmemoMock = $this->createMock(Creditmemo::class);
        $creditmemoMock->method('getTransactionId')->willReturn('REF123');
        $creditmemoMock->method('getState')->willReturn(Creditmemo::STATE_REFUNDED);

        $this->creditmemoCollectionMock->method('getIterator')
            ->willReturn(new \ArrayIterator([$creditmemoMock]));

        $notification = $this->buildNotification('REF123', 50.00, 'processed', 'mp-op-pp-order-api');
        $result = $this->createRefundOrder($notification)->process();

        $this->assertEquals(200, $result['code']);
        $this->assertStringContainsString('Refund already processed', $result['msg']);
    }

    public function testProcessHandlesMpOpPpOrderApiSourceWithoutCreditmemo(): void
    {
        $this->setupOrderForRefund();
        $this->creditmemoCollectionMock->method('getIterator')->willReturn(new \ArrayIterator([]));

        $creditmemoMock = $this->createMock(Creditmemo::class);
        $creditmemoMock->method('getBaseGrandTotal')->willReturn(100.00);
        $creditmemoMock->method('setState')->willReturnSelf();
        $creditmemoMock->method('setBaseGrandTotal')->willReturnSelf();
        $creditmemoMock->method('setGrandTotal')->willReturnSelf();
        $creditmemoMock->method('setItems')->willReturnSelf();
        $creditmemoMock->method('addComment')->willReturnSelf();

        $this->creditmemoFactoryMock->method('createByOrder')->willReturn($creditmemoMock);

        $this->paymentMock->method('setTransactionId')->willReturnSelf();
        $this->paymentMock->method('setIsTransactionClosed')->willReturnSelf();
        $this->paymentMock->method('setAdditionalInformation')->willReturnSelf();
        $this->paymentMock->method('setParentTransactionId')->willReturnSelf();
        $this->paymentMock->method('getAdditionalInformation')->willReturn(['mp_order_id' => 'PPORD123']);
        $this->paymentMock->method('addTransaction')->willReturn(null);

        $this->orderMock->method('getTotalPaid')->willReturn(100.00);
        $this->orderMock->method('getTotalRefunded')->willReturn(0.00);

        $notification = $this->buildNotification('REF123', 50.00, 'processed', 'mp-op-pp-order-api');
        $result = $this->createRefundOrder($notification)->process();

        $this->assertEquals(200, $result['code']);
        $this->assertStringContainsString('Refunded successfully', $result['msg']);
    }

    public function testProcessHandlesEmptyRefundsGracefully(): void
    {
        $this->setupOrderForRefund();
        $this->creditmemoCollectionMock->method('getIterator')->willReturn(new \ArrayIterator([]));

        $notification = [
            'notification_id' => 'NOTIF123',
            'payments_details' => [['refunds' => []]],
        ];

        $result = $this->createRefundOrder($notification)->process();

        $this->assertEquals(200, $result['code']);
        $this->assertStringContainsString('No refunds to process', $result['msg']);
    }

    public function testProcessSetsParentTransactionId(): void
    {
        $this->setupOrderForRefund();
        $this->creditmemoCollectionMock->method('getIterator')->willReturn(new \ArrayIterator([]));

        $creditmemoMock = $this->createMock(Creditmemo::class);
        $creditmemoMock->method('getBaseGrandTotal')->willReturn(100.00);
        $creditmemoMock->method('setState')->willReturnSelf();
        $creditmemoMock->method('setBaseGrandTotal')->willReturnSelf();
        $creditmemoMock->method('setGrandTotal')->willReturnSelf();
        $creditmemoMock->method('setItems')->willReturnSelf();
        $creditmemoMock->method('addComment')->willReturnSelf();

        $this->creditmemoFactoryMock->method('createByOrder')->willReturn($creditmemoMock);

        $this->paymentMock->method('setTransactionId')->willReturnSelf();
        $this->paymentMock->method('setIsTransactionClosed')->willReturnSelf();
        $this->paymentMock->method('setAdditionalInformation')->willReturnSelf();
        $this->paymentMock->method('getAdditionalInformation')->willReturn(['mp_order_id' => 'PPORD123']);
        $this->paymentMock->method('addTransaction')->willReturn(null);

        // Expect setParentTransactionId to be called with the mp_order_id
        $this->paymentMock->expects($this->once())
            ->method('setParentTransactionId')
            ->with('PPORD123')
            ->willReturnSelf();

        $this->orderMock->method('getTotalPaid')->willReturn(100.00);
        $this->orderMock->method('getTotalRefunded')->willReturn(0.00);

        $notification = $this->buildNotification('REF123', 100.00, 'processed');
        $result = $this->createRefundOrder($notification)->process();

        $this->assertEquals(200, $result['code']);
    }

    public function testProcessUsesNotificationIdAsFallbackForParentTransactionId(): void
    {
        $this->setupOrderForRefund();
        $this->creditmemoCollectionMock->method('getIterator')->willReturn(new \ArrayIterator([]));

        $creditmemoMock = $this->createMock(Creditmemo::class);
        $creditmemoMock->method('getBaseGrandTotal')->willReturn(100.00);
        $creditmemoMock->method('setState')->willReturnSelf();
        $creditmemoMock->method('setBaseGrandTotal')->willReturnSelf();
        $creditmemoMock->method('setGrandTotal')->willReturnSelf();
        $creditmemoMock->method('setItems')->willReturnSelf();
        $creditmemoMock->method('addComment')->willReturnSelf();

        $this->creditmemoFactoryMock->method('createByOrder')->willReturn($creditmemoMock);

        $this->paymentMock->method('setTransactionId')->willReturnSelf();
        $this->paymentMock->method('setIsTransactionClosed')->willReturnSelf();
        $this->paymentMock->method('setAdditionalInformation')->willReturnSelf();
        // mp_order_id is not set, so it should fallback to notification_id
        $this->paymentMock->method('getAdditionalInformation')->willReturn(['notification_id' => 'NOTIF456']);
        $this->paymentMock->method('addTransaction')->willReturn(null);

        $this->paymentMock->expects($this->once())
            ->method('setParentTransactionId')
            ->with('NOTIF456')
            ->willReturnSelf();

        $this->orderMock->method('getTotalPaid')->willReturn(100.00);
        $this->orderMock->method('getTotalRefunded')->willReturn(0.00);

        $notification = $this->buildNotification('REF123', 100.00, 'processed');
        $result = $this->createRefundOrder($notification)->process();

        $this->assertEquals(200, $result['code']);
    }

    public function testProcessDoesNotSetParentTransactionIdWhenNotAvailable(): void
    {
        $this->setupOrderForRefund();
        $this->creditmemoCollectionMock->method('getIterator')->willReturn(new \ArrayIterator([]));

        $creditmemoMock = $this->createMock(Creditmemo::class);
        $creditmemoMock->method('getBaseGrandTotal')->willReturn(100.00);
        $creditmemoMock->method('setState')->willReturnSelf();
        $creditmemoMock->method('setBaseGrandTotal')->willReturnSelf();
        $creditmemoMock->method('setGrandTotal')->willReturnSelf();
        $creditmemoMock->method('setItems')->willReturnSelf();
        $creditmemoMock->method('addComment')->willReturnSelf();

        $this->creditmemoFactoryMock->method('createByOrder')->willReturn($creditmemoMock);

        $this->paymentMock->method('setTransactionId')->willReturnSelf();
        $this->paymentMock->method('setIsTransactionClosed')->willReturnSelf();
        $this->paymentMock->method('setAdditionalInformation')->willReturnSelf();
        // No mp_order_id or notification_id available
        $this->paymentMock->method('getAdditionalInformation')->willReturn([]);
        $this->paymentMock->method('addTransaction')->willReturn(null);

        // setParentTransactionId should NOT be called when no parent ID is available
        $this->paymentMock->expects($this->never())
            ->method('setParentTransactionId');

        $this->orderMock->method('getTotalPaid')->willReturn(100.00);
        $this->orderMock->method('getTotalRefunded')->willReturn(0.00);

        $notification = $this->buildNotification('REF123', 100.00, 'processed');
        $result = $this->createRefundOrder($notification)->process();

        $this->assertEquals(200, $result['code']);
    }

    public function testRefundIdExtractionFallsBackToArrayKey(): void
    {
        $this->setupOrderForRefund();

        // The array key "2769645889" should be used as fallback when references are empty
        $creditmemoMock = $this->createMock(Creditmemo::class);
        $creditmemoMock->method('getTransactionId')->willReturn('2769645889');
        $creditmemoMock->method('getState')->willReturn(Creditmemo::STATE_REFUNDED);

        $this->creditmemoCollectionMock->method('getIterator')
            ->willReturn(new \ArrayIterator([$creditmemoMock]));

        // Notification with associative array key as refund ID (like MP payload)
        $notification = [
            'notification_id' => 'NOTIF123',
            'payments_details' => [[
                'refunds' => [
                    '2769645889' => [  // Key is the refund ID
                        'notifying' => true,
                        'status' => 'processed',
                        'amount' => 100.00,
                        'source' => '',
                        'references' => [
                            'refund_order_id' => '',
                            'refund_payment_id' => '',
                        ],
                    ],
                ],
            ]],
        ];

        $result = $this->createRefundOrder($notification)->process();

        $this->assertEquals(200, $result['code']);
        $this->assertStringContainsString('Refund already processed', $result['msg']);
    }
}

