<?php

namespace MercadoPago\Test\Unit\Cron;

use Magento\Payment\Model\Method\Logger;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;
use Magento\Sales\Model\ResourceModel\Order\Collection;
use Magento\Framework\DB\Select;
use Magento\Sales\Model\Order\Payment;
use MercadoPago\AdbPayment\Gateway\Config\ConfigCheckoutCredits;
use MercadoPago\AdbPayment\Model\Console\Command\Notification\FetchStatus;
use MercadoPago\AdbPayment\Cron\CancelCheckoutCredits as CancelCheckoutCredits;

use PHPUnit\Framework\TestCase;

class CancelCheckoutCreditsTest extends TestCase
{

  /**
   * @var CancelCheckoutCredits
   */
  protected $cancelCheckoutCredits;

  /**
   * @var Logger
   */
  protected $loggerMock;

  /**
   * @var FetchStatus
   */
  protected $fetchStatusMock;

  /**
   * @var configCheckoutCreditsMock
   */
  protected $configCheckoutCreditsMock;

  /**
   * @var CollectionFactory
   */
  protected $collectionFactoryMock;


  public function setUp(): void
  {

    $this->loggerMock = $this->getMockBuilder(Logger::class)
      ->disableOriginalConstructor()
      ->getMock();
    $this->fetchStatusMock = $this->getMockBuilder(FetchStatus::class)
      ->disableOriginalConstructor()
      ->getMock();
    $this->configCheckoutCreditsMock = $this->getMockBuilder(ConfigCheckoutCredits::class)
      ->disableOriginalConstructor()
      ->getMock();
    $this->collectionFactoryMock = $this->getMockBuilder(CollectionFactory::class)
      ->disableOriginalConstructor()
      ->getMock();

    $this->cancelCheckoutCredits = new CancelCheckoutCredits(
      $this->loggerMock,
      $this->fetchStatusMock,
      $this->configCheckoutCreditsMock,
      $this->collectionFactoryMock
    );
  }

  public function testExecute()
  {

    $orderCollectionMock = $this->getMockBuilder(Collection::class)
      ->disableOriginalConstructor()
      ->getMock();

    $ordersMock = new \ArrayIterator([
      $this->getMockBuilder(Order::class)
        ->disableOriginalConstructor()
        ->getMock()
    ]);

    $selectMock = $this->getMockBuilder(Select::class)
      ->disableOriginalConstructor()
      ->getMock();

    $expiration = '2023-06-19 12:00:00';
    $orderId = 1;
    $amount = 105;
    $baseAmount = 105;

    $this->configCheckoutCreditsMock->expects($this->any())
      ->method('getExpiredPaymentDate')
      ->willReturn($expiration);

    $this->collectionFactoryMock->expects($this->any())
      ->method('create')
      ->willReturn($orderCollectionMock);

    $orderCollectionMock->expects($this->any())
      ->method('addFieldToFilter')
      ->with('state', Order::STATE_NEW)
      ->willReturnSelf();

    $orderCollectionMock->expects($this->any())
      ->method('addAttributeToFilter')
      ->with('created_at', ['lteq' => $expiration])
      ->willReturnSelf();

    $orderCollectionMock->expects($this->any())
      ->method('getSelect')
      ->willReturn($selectMock);

    $selectMock->expects($this->any())
      ->method('join')
      ->with(
        ['sop' => 'sales_order_payment'],
        'main_table.entity_id = sop.parent_id',
        ['method']
      )->willReturnSelf();

    $selectMock->expects($this->any())
      ->method('where')
      ->with('sop.method = ?', ConfigCheckoutCredits::METHOD)
      ->willReturnSelf();

    $orderCollectionMock->expects($this->any())
      ->method('getIterator')
      ->willReturn($ordersMock);

    $order = $ordersMock->current();

    $order->expects($this->any())
      ->method('getEntityId')
      ->willReturn($orderId);

    $order->expects($this->any())
      ->method('getTotalDue')
      ->willReturn($amount);

    $order->expects($this->any())
      ->method('getBaseTotalDue')
      ->willReturn($baseAmount);

    // Mock order->getPayment()
    $payment = $this->getMockBuilder(Payment::class)
      ->addMethods([
        'setPreparedMessage',
        'setIsTransactionApproved',
        'setIsTransactionDenied',
        'setIsInProcess'
      ])
      ->onlyMethods(
        [
          'registerVoidNotification',
          'setIsTransactionPending',
          'setIsTransactionClosed',
          'setShouldCloseParentTransaction',
          'setAmountCanceled',
          'setBaseAmountCanceled'
        ]
      )
      ->disableOriginalConstructor()
      ->getMock();

    $order->expects($this->any())
      ->method('getPayment')
      ->willReturn($payment);

    $paymentMethods = [
      'setPreparedMessage',
      'registerVoidNotification',
      'setIsTransactionApproved',
      'setIsTransactionDenied',
      'setIsTransactionPending',
      'setIsInProcess',
      'setIsTransactionClosed',
      'setShouldCloseParentTransaction',
      'setAmountCanceled',
      'setBaseAmountCanceled'
    ];

    foreach ($paymentMethods as $method) {
      $payment->expects($this->any())
        ->method($method)
        ->willReturnSelf();
    }

    $payment->expects($this->any())
      ->method($method)
      ->willReturnSelf();

    $order->expects($this->any())
      ->method('cancel');

    $order->expects($this->any())
      ->method('save');

    $this->loggerMock->expects($this->any())
      ->method('debug')
      ->with([
        'fetch'   => 'Cancel Order Id ' . $orderId,
      ]);

    $this->cancelCheckoutCredits->execute();

    $this->assertEquals($orderId, $order->getEntityId());
    $this->assertEquals($amount, $order->getTotalDue());
    $this->assertEquals($baseAmount, $order->getBaseTotalDue());
    $this->assertEquals($payment, $order->getPayment());
    $this->assertEquals($expiration, $this->configCheckoutCreditsMock->getExpiredPaymentDate());
  }
}
