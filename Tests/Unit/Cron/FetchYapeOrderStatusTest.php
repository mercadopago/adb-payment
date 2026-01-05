<?php

namespace MercadoPago\AdbPayment\Tests\Unit\Cron;

use Magento\Framework\App\ResourceConnection;
use Magento\Payment\Model\Method\Logger;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\ResourceModel\Order\Collection;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;
use MercadoPago\AdbPayment\Cron\FetchYapeOrderStatus;
use MercadoPago\AdbPayment\Gateway\Config\ConfigYape;
use MercadoPago\AdbPayment\Model\Console\Command\Notification\FetchStatus;
use Magento\Framework\DB\Select;
use PHPUnit\Framework\MockObject\MockObject;

use PHPUnit\Framework\TestCase;

class FetchYapeOrderStatusTest extends TestCase
{
    /**
     * @var FetchYapeOrderStatus
     */
    protected $fetchYapeOrderStatus;

    /**
     * @var Logger|MockObject
     */
    protected $loggerMock;

    /**
     * @var FetchStatus|MockObject
     */
    protected $fetchStatusMock;

    /**
     * @var CollectionFactory|MockObject
     */
    protected $collectionFactoryMock;

    /**
     * @var ResourceConnection|MockObject
     */
    protected $resourceMock;

    /**
     * @var Collection|MockObject
     */
    protected $orderCollectionMock;

    /**
     * @var Select|MockObject
     */
    protected $selectMock;

    protected function setUp(): void
    {
        $this->loggerMock = $this->createMock(Logger::class);
        $this->fetchStatusMock = $this->createMock(FetchStatus::class);
        $this->collectionFactoryMock = $this->createMock(CollectionFactory::class);
        $this->resourceMock = $this->createMock(ResourceConnection::class);
        $this->orderCollectionMock = $this->createMock(Collection::class);
        $this->selectMock = $this->createMock(Select::class);

        $this->collectionFactoryMock->method('create')
            ->willReturn($this->orderCollectionMock);

        $this->orderCollectionMock->method('getSelect')
            ->willReturn($this->selectMock);

        $this->resourceMock->method('getTableName')
            ->with('sales_order_payment')
            ->willReturn('sales_order_payment');

        $this->fetchYapeOrderStatus = new FetchYapeOrderStatus(
            $this->loggerMock,
            $this->fetchStatusMock,
            $this->collectionFactoryMock,
            $this->resourceMock
        );
    }

    public function testGetSalesOrderPaymentTableName()
    {
        $tableName = 'sales_order_payment';
        $this->resourceMock->method('getTableName')
            ->with('sales_order_payment')
            ->willReturn($tableName);

        $this->assertEquals($tableName, $this->fetchYapeOrderStatus->getSalesOrderPaymentTableName());
    }

    public function testExecute()
    {
        $orderMock = $this->createMock(Order::class);
        $orderMock->method('getEntityId')
            ->willReturn(1);

        $this->orderCollectionMock->expects($this->any())
            ->method('addFieldToFilter')
            ->with('state', Order::STATE_NEW)
            ->willReturnSelf();

        $this->selectMock->expects($this->any())
            ->method('join')
            ->with(
                $this->equalTo(['sop' => 'sales_order_payment']),
                $this->equalTo('main_table.entity_id = sop.parent_id'),
                $this->equalTo(['method'])
            )
            ->willReturnSelf();

        $this->selectMock->expects($this->any())
            ->method('where')
            ->with('sop.method = ?', ConfigYape::METHOD)
            ->willReturnSelf();

        $this->orderCollectionMock->expects($this->any())
            ->method('getItems')
            ->willReturn([$orderMock]);

        $this->orderCollectionMock->expects($this->any())
            ->method('getIterator')
            ->willReturn(new \ArrayIterator([$orderMock]));

        $this->loggerMock->expects($this->once())
            ->method('debug')
            ->with(['fetch' => 'Fetch Status Yape for Order Id 1']);

        $this->fetchStatusMock->expects($this->once())
            ->method('fetch')
            ->with(1);

        $this->fetchYapeOrderStatus->execute();
    }
}