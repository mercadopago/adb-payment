<?php

namespace MercadoPago\AdbPayment\Tests\Unit\Model\Notification\Refund;

use PHPUnit\Framework\TestCase;

use MercadoPago\AdbPayment\Model\Notification\Refund\CheckoutCustom;
use MercadoPago\AdbPayment\Gateway\Config\Config;
use Magento\Framework\Notification\NotifierInterface;
use Magento\Sales\Model\Order\Creditmemo;
use Magento\Sales\Model\Order\CreditmemoFactory;
use Magento\Sales\Model\Service\CreditmemoService;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Invoice;
use Magento\Sales\Model\Order\Payment;
use Magento\Sales\Model\ResourceModel\Order\Creditmemo\Collection as CreditmemoCollection;
use Magento\Sales\Model\ResourceModel\Order\Invoice\Collection as InvoiceCollection;
use MercadoPago\AdbPayment\Tests\Unit\Mocks\Notification\Refund\SinglePayment;
use Magento\Payment\Model\Method\Logger;
use MercadoPago\AdbPayment\Model\Order\UpdatePayment;
use MercadoPago\AdbPayment\Tests\Unit\Mocks\Model\Order\Payment\AdditionalInformation;

class CheckoutCustomTest extends TestCase
{
    private $config;
    private $notifier;
    private $invoice;
    private $creditmemoFactory;
    private $creditmemoService;
    private $order;
    private $logger;
    private $updatePayment;

    public function setUp(): void
    {
        $this->config = $this->createMock(Config::class);
        $this->notifier = $this->createMock(NotifierInterface::class);
        $this->invoice = $this->createMock(Invoice::class);
        $this->creditmemoFactory = $this->createMock(CreditmemoFactory::class);
        $this->creditmemoService = $this->createMock(CreditmemoService::class);
        $this->logger = $this->createMock(Logger::class);
        $this->order = $this->createMock(Order::class);
        $this->updatePayment = $this->createMock(UpdatePayment::class);
    }

    private function getInstance(array $data): CheckoutCustom
    {
        return new CheckoutCustom(
            $this->config,
            $this->notifier,
            $this->invoice,
            $this->creditmemoFactory,
            $this->creditmemoService,
            $this->order,
            $this->logger,
            $this->updatePayment,
            $data
        );
    }

    private function prepareFullRefundFlow(array $data = []): array
    {
        $creditmemoCollection = isset($data['creditmemoCollection']) ? $data['creditmemoCollection'] : $this->mockCreditMemoCollection([]);

        $this->order->expects($this->once())
            ->method('getStoreId')
            ->willReturn(1);

        $this->config->expects($this->once())
            ->method('isApplyRefund')
            ->willReturn(true);

        $this->order->expects($this->once())
            ->method('getState')
            ->willReturn(isset($data['state']) ? $data['state'] : 'processing');

        $invoiceMock = $this->createMock(Invoice::class);

        $invoiceCollectionMock = $this->mockInvoiceCollection([$invoiceMock]);

        $this->order->expects($this->once())
            ->method('getInvoiceCollection')
            ->willReturn($invoiceCollectionMock);

        $creditMemo = $this->createMock(Creditmemo::class);

        if (!isset($data['creditmemoFactory'])) {
            $this->creditmemoFactory->expects($this->once())
                ->method('createByOrder')
                ->willReturn($creditMemo);
        }

        $payment = $this->createMock(Payment::class);

        $this->order->expects($this->any())
            ->method('getPayment')
            ->willReturn($payment);

        $this->creditmemoService->expects($this->once())
            ->method('refund');

        $this->order->expects($this->once())
            ->method('addCommentToStatusHistory')
            ->with('Order refunded.');

        $this->order->expects($this->any())
            ->method('setPayment')
            ->with($payment);

        $this->order->expects($this->once())
            ->method('save');

        return [
            'creditmemoCollection' => $creditmemoCollection,
            'invoiceCollection' => $invoiceCollectionMock,
            'invoice' => $invoiceMock,
            'creditMemo' => $creditMemo,
            'payment' => $payment
        ];
    }

    private function mockCreditmemoCollection(array $return)
    {
        $creditmemoCollectionMock = $this->createMock(CreditmemoCollection::class);
        $creditmemoCollectionMock->expects($this->any())
            ->method('getIterator')
            ->will($this->returnValue(new \ArrayIterator($return)));

        $this->order->expects($this->once())
            ->method('getCreditmemosCollection')
            ->willReturn($creditmemoCollectionMock);

        return $creditmemoCollectionMock;
    }

    private function mockInvoiceCollection(array $return)
    {
        $invoiceCollectionMock = $this->createMock(InvoiceCollection::class);
        $invoiceCollectionMock->expects($this->any())
            ->method('count')
            ->willReturn(1);
        $invoiceCollectionMock->expects($this->any())
            ->method('getIterator')
            ->will($this->returnValue(new \ArrayIterator([$return])));

        $this->order->expects($this->once())
            ->method('getInvoiceCollection')
            ->willReturn($invoiceCollectionMock);

        return $invoiceCollectionMock;
    }

    public function testProcessRefundStartedFromMagento()
    {
        $this->mockCreditmemoCollection([]);

        $this->config->expects($this->once())
            ->method('isApplyRefund')
            ->willReturn(true);

        $invoiceMock = $this->createMock(Invoice::class);
        $this->mockInvoiceCollection([$invoiceMock]);

        $refund = $this->getInstance(SinglePayment::refundFromMagento());
        $result = $refund->process();

        $this->assertEquals([
            'code'      => 200,
            'msg'       => 'Notification P-66817921925 - Refund 1538130155: Refund created from magento',
        ], $result);
    }

    public function testRefundAlreadyRefunded()
    {
        $this->config->expects($this->once())
            ->method('isApplyRefund')
            ->willReturn(true);

        $creditMemo = $this->createMock(Creditmemo::class);
        $creditMemo->expects($this->once())
            ->method('getTransactionId')
            ->willReturn(1538130155);

        $creditmemoCollectionMock = $this->mockCreditmemoCollection([$creditMemo]);

        $this->order->expects($this->once())
            ->method('getCreditmemosCollection')
            ->willReturn($creditmemoCollectionMock);

        $invoiceMock = $this->createMock(Invoice::class);
        $this->mockInvoiceCollection([$invoiceMock]);

        $refund = $this->getInstance(SinglePayment::SINGLE_PAYMENT_DATA);
        $result = $refund->process();

        $this->assertEquals([
            'code'      => 200,
            'msg'       => 'Notification P-66817921925 - Refund 1538130155: Refund already refunded. Ignoring it.'
        ], $result);
    }

    public function testRefundAlreadyRefundedAndValidRefund()
    {
        $creditMemo = $this->createMock(Creditmemo::class);
        $creditMemo->expects($this->exactly(2))
            ->method('getTransactionId')
            ->willReturn(1538130155);

        $creditmemoCollectionMock = $this->mockCreditmemoCollection([$creditMemo]);

        $this->prepareFullRefundFlow(['creditmemoCollection' => $creditmemoCollectionMock]);

        $data = SinglePayment::twoRefunds();
        $refund = $this->getInstance($data);
        $result = $refund->process();

        $this->assertEquals([
            'code'      => 200,
            'msg'       => 'Notification P-66817921925 - Refund 1538130155: Refund already refunded. Ignoring it., Notification P-66817921925 - Refund 1538130156: Refunded sucessfull'
        ], $result);
    }

    public function testRefundNotApprovedAndValidRefund()
    {
        $this->prepareFullRefundFlow();

        $data = SinglePayment::twoRefundsWithProcessingOne();
        $refund = $this->getInstance($data);
        $result = $refund->process();

        $this->assertEquals([
            'code'      => 200,
            'msg'       => 'Notification P-66817921925 - Refund 1538130155: Refund status not approved. Current status is processing, Notification P-66817921925 - Refund 1538130156: Refunded sucessfull'
        ], $result);
    }

    public function testFullRefundSingleCard()
    {
        $mocks = $this->prepareFullRefundFlow();

        $creditMemo = $mocks['creditMemo'];

        // VALIDATE CREDITMEMO DURING REFUND
        $creditMemo->expects($this->any())
            ->method('getBaseGrandTotal')
            ->willReturn(112.00);

        $creditMemo->expects($this->exactly(0))
            ->method('setItems');

        $creditMemo->expects($this->once())
            ->method('setState')
            ->with(1);

        $creditMemo->expects($this->once())
            ->method('setBaseGrandTotal')
            ->with(112.00);

        $creditMemo->expects($this->once())
            ->method('addComment')
            ->with('Order refunded in Mercado Pago, refunded offline in the store.');

        $payment = $mocks['payment'];

        // VALIDATE PAYMENT METHODS DURING REFUND
        $payment->expects($this->once())
            ->method('setTransactionId')
            ->with(1538130155);

        $payment->expects($this->once())
            ->method('setIsTransactionClosed')
            ->with(true);

        $refund = $this->getInstance(SinglePayment::SINGLE_PAYMENT_DATA);
        $result = $refund->process();

        $this->assertEquals([
            'code' => 200,
            'msg' => 'Notification P-66817921925 - Refund 1538130155: Refunded sucessfull',
        ], $result);
    }

    public function testPartialRefundWithSingleCard()
    {
        $mocks = $this->prepareFullRefundFlow();

        $creditMemo = $mocks['creditMemo'];

        // VALIDATE CREDITMEMO DURING REFUND
        $creditMemo->expects($this->any())
            ->method('getBaseGrandTotal')
            ->willReturn(130.00);

        $creditMemo->expects($this->exactly(1))
            ->method('setItems')
            ->with([]);

        $creditMemo->expects($this->once())
            ->method('setState')
            ->with(1);

        $creditMemo->expects($this->once())
            ->method('setBaseGrandTotal')
            ->with(112.00);

        $creditMemo->expects($this->once())
            ->method('addComment')
            ->with('Order refunded in Mercado Pago, refunded offline in the store.');

        $payment = $mocks['payment'];

        // VALIDATE PAYMENT METHODS DURING REFUND
        $payment->expects($this->once())
            ->method('setTransactionId')
            ->with(1538130155);

        $payment->expects($this->once())
            ->method('setIsTransactionClosed')
            ->with(true);

        $invoiceMock = $this->createMock(Invoice::class);
        $this->mockInvoiceCollection([$invoiceMock]);

        $refund = $this->getInstance(SinglePayment::SINGLE_PAYMENT_DATA);
        $result = $refund->process();

        $this->assertEquals([
            'code' => 200,
            'msg' => 'Notification P-66817921925 - Refund 1538130155: Refunded sucessfull',
        ], $result);
    }

    public function testRefundWithConfigDisabled()
    {
        $this->mockCreditmemoCollection([]);

        $this->order->expects($this->once())
            ->method('getStoreId')
            ->willReturn(1);

        $this->config->expects($this->once())
            ->method('isApplyRefund')
            ->willReturn(false);

        $invoiceMock = $this->createMock(Invoice::class);
        $this->mockInvoiceCollection([$invoiceMock]);

        $refund = $this->getInstance(SinglePayment::SINGLE_PAYMENT_DATA);
        $result = $refund->process();

        $this->assertEquals([
            'code' => 200,
            'msg' => 'Refund notification process disabled in config'
        ], $result);
    }

    public function testErroredRefundWithValidRefund()
    {
        $this->prepareFullRefundFlow(['creditmemoFactory' => 'ignore']);

        $creditMemo = $this->createMock(Creditmemo::class);
        $matcher = $this->exactly(2);
        $this->creditmemoFactory->expects($matcher)
                ->method('createByOrder')
                ->willReturnCallback(function () use ($matcher, $creditMemo) {
                    if ($matcher->getInvocationCount() === 1) {
                        return $creditMemo;
                    }

                    throw new \Exception();
                });

        $data = SinglePayment::twoRefunds();
        $refund = $this->getInstance($data);
        $result = $refund->process();

        $this->assertEquals([
            'code'      => 400,
            'msg'       => 'Notification P-66817921925 - Refund 1538130155: Refunded sucessfull, Notification P-66817921925 - Refund 1538130156: Failed to process refund. '
        ], $result);
    }

    public function testRefundClosedOrder()
    {
        $this->mockCreditMemoCollection([]);

        $this->config->expects($this->exactly(0))
            ->method('isApplyRefund');

        $this->order->expects($this->once())
            ->method('getState')
            ->willReturn('closed');

        $this->order->expects($this->once())
            ->method('getIncrementId')
            ->willReturn('1234');

        $invoiceMock = $this->createMock(Invoice::class);

        $invoiceCollectionMock = $this->mockInvoiceCollection([$invoiceMock]);

        $this->order->expects($this->once())
            ->method('getInvoiceCollection')
            ->willReturn($invoiceCollectionMock);

        $refund = $this->getInstance(SinglePayment::SINGLE_PAYMENT_DATA);
        $result = $refund->process();

        $this->assertEquals([
            'code' => 200,
            'msg' => 'Refund notification for order 1234 already closed.',
        ], $result);
    }
}
