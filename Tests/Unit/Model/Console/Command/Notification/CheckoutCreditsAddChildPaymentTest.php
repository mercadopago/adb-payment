<?php

namespace Tests\Unit\Model\Console\Command\Notification;

use Exception;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Payment;
use MercadoPago\AdbPayment\Model\Console\Command\Notification\CheckoutCreditsAddChildPayment;
use PHPUnit\Framework\TestCase;

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
     * @var Payment|\PHPUnit\Framework\MockObject\MockObject
     */
    private $paymentMock;

    protected function setUp(): void
    {
        $objectManager = new ObjectManager($this);

        $this->orderMock = $this->getMockBuilder(Order::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->paymentMock = $this->getMockBuilder(Payment::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->model = $objectManager->getObject(CheckoutCreditsAddChildPayment::class, [
            'order' => $this->orderMock,
        ]);
    }

    public function testAddMethodWithException()
    {
        $orderId = 1;
        $childTransaction = '6jzrbpyca8gpnhqaclw74dz163h50c';

        $this->orderMock->expects($this->once())
            ->method('load')
            ->with($orderId)
            ->willThrowException(new Exception('Order not found.'));

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
}
