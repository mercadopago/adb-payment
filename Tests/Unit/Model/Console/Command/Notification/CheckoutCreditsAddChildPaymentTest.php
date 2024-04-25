<?php

namespace Tests\Unit\Model\Console\Command\Notification;

use Exception;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Payment;
use MercadoPago\AdbPayment\Model\Console\Command\Notification\CheckoutCreditsAddChildPayment;
use PHPUnit\Framework\TestCase;
use Magento\Framework\Exception\NoSuchEntityException;

class CheckoutCreditsAddChildPaymentTest extends TestCase
{
    /**
     * @var CheckoutCreditsAddChildPayment
     */
    private $model;

    /**
     * @var Order|\PHPUnit\Framework\MockObject\MockObject
     */
    private $orderMock;

    /**
     * @var OrderRepositoryInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $orderRepositoryMock;

    /**
     * @var Payment|\PHPUnit\Framework\MockObject\MockObject
     */
    private $paymentMock;

    protected function setUp(): void
    {
        $objectManager = new ObjectManager($this);

        $this->orderRepositoryMock = $this->getMockBuilder(OrderRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->orderMock = $this->getMockBuilder(Order::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->paymentMock = $this->getMockBuilder(Payment::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->model = $objectManager->getObject(CheckoutCreditsAddChildPayment::class, [
            'order' => $this->orderMock,
            'orderRepository' => $this->orderRepositoryMock,
        ]);
    }
    
    public function testAddMethodWithException()
    {
        $orderId = 1;
        $childTransaction = '6jzrbpyca8gpnhqaclw74dz163h50c';

        $this->orderRepositoryMock->expects($this->once())
            ->method('get')
            ->with($orderId)
            ->willThrowException(new NoSuchEntityException(__("The entity that was requested doesn't exist. Verify the entity and try again.")));

        $this->orderMock->expects($this->never())
            ->method('getPayment');

        $this->paymentMock->expects($this->never())
            ->method('setTransactionId');

        $this->paymentMock->expects($this->never())
            ->method('setParentTransactionId');

        $this->paymentMock->expects($this->never())
            ->method('setIsTransactionClosed');

        $this->paymentMock->expects($this->never())
            ->method('setShouldCloseParentTransaction');

        $this->paymentMock->expects($this->never())
            ->method('addTransaction');

        $this->orderMock->expects($this->never())
            ->method('save');

        $this->paymentMock->expects($this->never())
            ->method('update');

        $this->orderMock->expects($this->never())
            ->method('getState');

        $this->orderMock->expects($this->never())
            ->method('getStatus');

        $this->orderMock->expects($this->never())
            ->method('setState');

        $this->orderMock->expects($this->never())
            ->method('save');

        $this->expectException(Exception::class);

        $this->model->add($orderId, $childTransaction);
    }

    public function testUpdatePaymentReviewOrderStatus()
    {
        $orderId = 1;
        $childTransaction = '6jzrbpyca8gpnhqaclw74dz163h50c';

        $this->orderRepositoryMock->expects($this->once())
            ->method('get')
            ->with($orderId)
            ->willReturn($this->orderMock);

        $this->orderMock->expects($this->once())
            ->method('getPayment')
            ->willReturn($this->paymentMock);
        
        $this->paymentMock->expects($this->once())
            ->method('setTransactionId')
            ->with($childTransaction);

        $this->orderMock->expects($this->any())
            ->method('getState')
            ->willReturn(Order::STATE_PAYMENT_REVIEW);

        $this->orderMock->expects($this->any())
            ->method('getStatus')
            ->willReturn(Order::STATE_CLOSED);

        $this->paymentMock->expects($this->any())
            ->method('getOrder')
            ->willReturn($this->orderMock);

        $this->orderMock->expects($this->once())
            ->method('setState')
            ->with(Order::STATE_CLOSED);

        $this->model->add($orderId, $childTransaction = '6jzrbpyca8gpnhqaclw74dz163h50c');
    }

    public function testUpdatePaymentReviewOrderStatusAndStateNew()
    {
        $orderId = 1;
        $childTransaction = '6jzrbpyca8gpnhqaclw74dz163h50c';

        $this->orderRepositoryMock->expects($this->once())
            ->method('get')
            ->with($orderId)
            ->willReturn($this->orderMock);

        $this->orderMock->expects($this->once())
            ->method('getPayment')
            ->willReturn($this->paymentMock);
        
        $this->paymentMock->expects($this->once())
            ->method('setTransactionId')
            ->with($childTransaction);

        $this->orderMock->expects($this->any())
            ->method('getState')
            ->willReturn(Order::STATE_PAYMENT_REVIEW);

        $this->orderMock->expects($this->any())
            ->method('getStatus')
            ->willReturn(Order::STATE_NEW);

        $this->paymentMock->expects($this->any())
            ->method('getOrder')
            ->willReturn($this->orderMock);

        $this->orderMock->expects($this->once())
            ->method('setState')
            ->with(Order::STATE_NEW);

            $this->orderMock->expects($this->once())
            ->method('setStatus')
            ->with('pending');

        $this->model->add($orderId, $childTransaction = '6jzrbpyca8gpnhqaclw74dz163h50c');
    }
}
