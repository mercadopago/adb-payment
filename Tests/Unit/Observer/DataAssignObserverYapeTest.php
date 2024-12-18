<?php

namespace MercadoPago\AdbPayment\Test\Observer;

use Magento\Framework\Event\Observer;
use \PHPUnit\Framework\MockObject\MockObject;
use \Magento\Framework\DataObject;
use Magento\Quote\Api\Data\PaymentInterface;
use MercadoPago\AdbPayment\Observer\DataAssignObserverYape;
use PHPUnit\Framework\TestCase;

class DataAssignObserverYapeTest extends TestCase
{
    /**
     * @var DataAssignObserverYape
     */
    private $dataAssignObserverYape;

    /**
     * @var Observer|MockObject
     */
    private $observerMock;

    /**
     * @var PaymentInterface|MockObject
     */
    private $paymentInfoMock;

    protected function setUp(): void
    {
        $this->dataAssignObserverYape = $this->getMockBuilder(DataAssignObserverYape::class)
            ->setMethods(['readDataArgument', 'readPaymentModelArgument'])
            ->getMock();
        $this->observerMock = $this->createMock(Observer::class);
        $this->paymentInfoMock = $this->getMockBuilder(PaymentInterface::class)
            ->setMethods(['setAdditionalInformation'])
            ->getMockForAbstractClass();
    }

    public function testExecuteWithValidAdditionalData()
    {
        $additionalData = [
            DataAssignObserverYape::YAPE_TOKEN_ID => 'test_yape_token_id',
            'mp_device_session_id' => 'test_device_session_id'
        ];

        $dataObjectMock = $this->createMock(DataObject::class);

        $this->dataAssignObserverYape->expects($this->once())
            ->method('readDataArgument')
            ->with($this->observerMock)
            ->willReturn($dataObjectMock);

        $this->dataAssignObserverYape->expects($this->once())
            ->method('readPaymentModelArgument')
            ->with($this->observerMock)
            ->willReturn($this->paymentInfoMock);

        $dataObjectMock->expects($this->once())
            ->method('getData')
            ->with(PaymentInterface::KEY_ADDITIONAL_DATA)
            ->willReturn($additionalData);

        $this->paymentInfoMock->expects($this->exactly(2))
            ->method('setAdditionalInformation')
            ->withConsecutive(
                [DataAssignObserverYape::YAPE_TOKEN_ID, 'test_yape_token_id'],
                ['mp_device_session_id', 'test_device_session_id']
            );

        $this->dataAssignObserverYape->execute($this->observerMock);
    }

    public function testExecuteWithInvalidAdditionalData()
    {
        $dataObjectMock = $this->createMock(DataObject::class);

        $this->dataAssignObserverYape->expects($this->any())
            ->method('readDataArgument')
            ->with($this->observerMock)
            ->willReturn($dataObjectMock);

        $this->dataAssignObserverYape->expects($this->any())
            ->method('readPaymentModelArgument')
            ->with($this->observerMock)
            ->willReturn($this->paymentInfoMock);

        $dataObjectMock->expects($this->once())
            ->method('getData')
            ->with(PaymentInterface::KEY_ADDITIONAL_DATA)
            ->willReturn(null);

        $this->paymentInfoMock->expects($this->never())
            ->method('setAdditionalInformation');

        $this->dataAssignObserverYape->execute($this->observerMock);
    }
}
