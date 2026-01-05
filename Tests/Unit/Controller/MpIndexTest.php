<?php
/**
 * Copyright © MercadoPago. All rights reserved.
 *
 * @author      Mercado Pago
 * @license     See LICENSE for license details.
 */

namespace MercadoPago\AdbPayment\Tests\Unit\Controller;

use PHPUnit\Framework\TestCase;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Serialize\Serializer\Json as JsonSerializer;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Payment\Model\Method\Logger;
use Magento\Sales\Api\TransactionRepositoryInterface;
use Magento\Sales\Model\OrderRepository;
use Magento\Sales\Model\Order;
use MercadoPago\AdbPayment\Controller\MpIndex;
use MercadoPago\AdbPayment\Gateway\Config\Config;
use MercadoPago\AdbPayment\Model\Console\Command\Notification\CheckoutProAddChildPayment;

/**
 * Test for MpIndex abstract controller
 */
class MpIndexTest extends TestCase
{
    /**
     * @var MpIndex
     */
    private $mpIndex;

    /**
     * @var Logger|\PHPUnit\Framework\MockObject\MockObject
     */
    private $loggerMock;

    /**
     * @var OrderRepository|\PHPUnit\Framework\MockObject\MockObject
     */
    private $orderRepositoryMock;

    /**
     * @var JsonFactory|\PHPUnit\Framework\MockObject\MockObject
     */
    private $resultJsonFactoryMock;

    /**
     * @var CheckoutProAddChildPayment|\PHPUnit\Framework\MockObject\MockObject
     */
    private $addChildPaymentMock;

    /**
     * Setup test environment
     */
    protected function setUp(): void
    {
        $this->loggerMock = $this->createMock(Logger::class);
        $this->orderRepositoryMock = $this->createMock(OrderRepository::class);
        $this->resultJsonFactoryMock = $this->createMock(JsonFactory::class);
        $this->addChildPaymentMock = $this->createMock(CheckoutProAddChildPayment::class);

        $contextMock = $this->createMock(Context::class);
        $contextMock->method('getRequest')->willReturn($this->createMock(RequestInterface::class));

        // Create a concrete implementation of the abstract class for testing
        $this->mpIndex = $this->getMockForAbstractClass(
            MpIndex::class,
            [
                $this->createMock(Config::class),
                $contextMock,
                $this->createMock(JsonSerializer::class),
                $this->createMock(SearchCriteriaBuilder::class),
                $this->createMock(TransactionRepositoryInterface::class),
                $this->orderRepositoryMock,
                $this->createMock(\Magento\Framework\View\Result\PageFactory::class),
                $this->resultJsonFactoryMock,
                $this->loggerMock,
                $this->createMock(\MercadoPago\AdbPayment\Model\Console\Command\Notification\FetchStatus::class),
                $this->createMock(\Magento\Framework\Notification\NotifierInterface::class),
                $this->createMock(\Magento\Sales\Model\Order\CreditmemoFactory::class),
                $this->createMock(\Magento\Sales\Model\Service\CreditmemoService::class),
                $this->createMock(\Magento\Sales\Model\Order\Invoice::class),
                $this->addChildPaymentMock,
                $this->createMock(\MercadoPago\AdbPayment\Model\MPApi\Notification::class),
                $this->createMock(\MercadoPago\AdbPayment\Model\MPApi\Order\OrderNotificationGet::class),
                $this->createMock(\MercadoPago\AdbPayment\Model\Order\UpdatePayment::class),
                $this->createMock(\MercadoPago\AdbPayment\Model\Metrics\MetricsClient::class),
            ]
        );
    }

    /**
     * Test createResult method returns JSON response with correct status code
     */
    public function testCreateResultReturnsJsonWithStatusCode()
    {
        $statusCode = 200;
        $data = ['success' => true, 'message' => 'Payment processed'];

        $resultJsonMock = $this->createMock(Json::class);
        $resultJsonMock->expects($this->once())
            ->method('setHttpResponseCode')
            ->with($statusCode)
            ->willReturnSelf();
        $resultJsonMock->expects($this->once())
            ->method('setData')
            ->with($data)
            ->willReturnSelf();

        $this->resultJsonFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($resultJsonMock);

        $result = $this->mpIndex->createResult($statusCode, $data);

        $this->assertInstanceOf(Json::class, $result);
    }

    /**
     * Test createResult with error status code
     */
    public function testCreateResultWithErrorStatusCode()
    {
        $statusCode = 500;
        $data = ['error' => 'Internal Server Error'];

        $resultJsonMock = $this->createMock(Json::class);
        $resultJsonMock->expects($this->once())
            ->method('setHttpResponseCode')
            ->with($statusCode)
            ->willReturnSelf();
        $resultJsonMock->expects($this->once())
            ->method('setData')
            ->with($data)
            ->willReturnSelf();

        $this->resultJsonFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($resultJsonMock);

        $result = $this->mpIndex->createResult($statusCode, $data);

        $this->assertInstanceOf(Json::class, $result);
    }

    /**
     * Test getOrderData returns order successfully
     */
    public function testGetOrderDataReturnsOrderSuccessfully()
    {
        $orderId = 100;
        $orderMock = $this->createMock(Order::class);

        $this->orderRepositoryMock->expects($this->once())
            ->method('get')
            ->with($orderId)
            ->willReturn($orderMock);

        $result = $this->mpIndex->getOrderData($orderId);

        $this->assertInstanceOf(Order::class, $result);
    }

    /**
     * Test getOrderData handles exception and returns error result
     */
    public function testGetOrderDataHandlesExceptionAndReturnsErrorResult()
    {
        $orderId = 999;
        $exceptionMessage = 'Order not found in database';

        $this->orderRepositoryMock->expects($this->once())
            ->method('get')
            ->with($orderId)
            ->willThrowException(new \Exception($exceptionMessage));

        $resultJsonMock = $this->createMock(Json::class);
        $resultJsonMock->expects($this->once())
            ->method('setHttpResponseCode')
            ->with(500)
            ->willReturnSelf();
        $resultJsonMock->expects($this->once())
            ->method('setData')
            ->with($this->callback(function ($data) use ($exceptionMessage) {
                return $data['error'] === 500 && $data['message'] === $exceptionMessage;
            }))
            ->willReturnSelf();

        $this->resultJsonFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($resultJsonMock);

        $result = $this->mpIndex->getOrderData($orderId);

        $this->assertInstanceOf(Json::class, $result);
    }

    /**
     * Test filterInvalidNotification returns invalid when order not found
     */
    public function testFilterInvalidNotificationReturnsInvalidWhenOrderNotFound()
    {
        $orderMock = $this->createMock(Order::class);
        $orderMock->expects($this->once())
            ->method('getEntityId')
            ->willReturn(null);

        $result = $this->mpIndex->filterInvalidNotification(
            'approved',
            $orderMock,
            null,
            null,
            null
        );

        $this->assertTrue($result['isInvalid']);
        $this->assertEquals(406, $result['code']);
    }

    /**
     * Test filterInvalidNotification returns valid for order with Order API status
     */
    public function testFilterInvalidNotificationWithOrderApiStatus()
    {
        $orderMock = $this->createMock(Order::class);
        $orderMock->expects($this->once())
            ->method('getEntityId')
            ->willReturn(100);
        $orderMock->expects($this->once())
            ->method('getStatus')
            ->willReturn('pending_payment');

        $this->loggerMock->expects($this->atLeast(1))
            ->method('debug');

        $result = $this->mpIndex->filterInvalidNotification(
            'processing', // Order API status
            $orderMock,
            null,
            null,
            'pp_order'
        );

        $this->assertFalse($result['isInvalid']);
    }

    /**
     * Test filterInvalidNotification with Payment API status
     */
    public function testFilterInvalidNotificationWithPaymentApiStatus()
    {
        $orderMock = $this->createMock(Order::class);
        $orderMock->expects($this->once())
            ->method('getEntityId')
            ->willReturn(100);
        $orderMock->expects($this->once())
            ->method('getStatus')
            ->willReturn('pending_payment');

        $this->loggerMock->expects($this->once())
            ->method('debug');

        $result = $this->mpIndex->filterInvalidNotification(
            'approved', // Payment API status
            $orderMock,
            null,
            null,
            'payment'
        );

        $this->assertFalse($result['isInvalid']);
    }

    /**
     * Test checkoutProAddChildInformation delegates to addChildPayment
     */
    public function testCheckoutProAddChildInformationDelegatesToAddChildPayment()
    {
        $orderId = 100;
        $childId = 'child-payment-123';

        $this->addChildPaymentMock->expects($this->once())
            ->method('add')
            ->with($orderId, $childId);

        $this->mpIndex->checkoutProAddChildInformation($orderId, $childId);
    }

    /**
     * Test that NOTIFICATION_TYPE_ORDER constant is defined
     */
    public function testNotificationTypeOrderConstantIsDefined()
    {
        $this->assertEquals('pp_order', MpIndex::NOTIFICATION_TYPE_ORDER);
    }
}
