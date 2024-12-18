<?php

namespace MercadoPago\Test\Unit\Cron;

use Magento\Framework\App\ResourceConnection;
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
   * @var ConfigCheckoutCredits
   */
  protected $configCheckoutCreditsMock;

  /**
   * @var CollectionFactory
   */
  protected $collectionFactoryMock;

  /**
   * @var ResourceConnection
   */
  protected $resourceConnectionMock;

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
    $this->resourceConnectionMock = $this->getMockBuilder(ResourceConnection::class)
      ->disableOriginalConstructor()
      ->getMock();

    $this->cancelCheckoutCredits = $this->getMockBuilder(CancelCheckoutCredits::class)
      ->setConstructorArgs([
          $this->loggerMock,
          $this->fetchStatusMock,
          $this->configCheckoutCreditsMock,
          $this->collectionFactoryMock,
          $this->resourceConnectionMock
      ])
      ->onlyMethods(['getSalesOrderPaymentTableName'])
      ->getMock();
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

    $orderId = 1;
    $amount = 105;
    $baseAmount = 105;

    $this->cancelCheckoutCredits->expects($this->any())
      ->method('getSalesOrderPaymentTableName')
      ->willReturn('sales_order_payment');

    $this->collectionFactoryMock->expects($this->any())
      ->method('create')
      ->willReturn($orderCollectionMock);

    $orderCollectionMock->expects($this->any())
      ->method('addFieldToFilter')
      ->with('state', Order::STATE_NEW)
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
      ->with($this->callback(function ($expr) {
          return $expr instanceof \Zend_Db_Expr &&
                 $expr->__toString() === "sop.method = ?
                        AND TIME_TO_SEC(
                            TIMEDIFF(CURRENT_TIMESTAMP(),
                                STR_TO_DATE(
                                    REPLACE(
                                        SUBSTRING_INDEX(
                                            JSON_UNQUOTE(JSON_EXTRACT(sop.additional_information, '$.date_of_expiration')),
                                            '.',
                                            1
                                        ),
                                        'T', ' '
                                    ),
                                    '%Y-%m-%d %H:%i:%s'
                                )
                            )
                        ) >= 0";
      }))
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
  }
}
