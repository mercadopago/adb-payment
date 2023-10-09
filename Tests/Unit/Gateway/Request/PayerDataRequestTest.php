<?php

namespace MercadoPago\Test\Unit\Gateway\Request;

use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use MercadoPago\AdbPayment\Gateway\Data\Order\OrderAdapterFactory;
use MercadoPago\AdbPayment\Gateway\SubjectReader;
use MercadoPago\AdbPayment\Gateway\Request\PayerDataRequest;
use Magento\Sales\Model\Order\Payment;
use Magento\Sales\Api\Data\OrderAddressInterface;
use Magento\Payment\Gateway\Data\OrderAdapterInterface;
use MercadoPago\AdbPayment\Gateway\Data\Order\OrderAdapter;


use PHPUnit\Framework\TestCase;

class PayerDataRequestTest extends TestCase
{

  /**
   * @var SubjectReader
   */
  protected $subjectReaderMock;

  /**
   * @var OrderAdapterFactory
   */
  protected $orderAdapterFactoryMock;

  /**
   * @var payerDataRequestMock
   */
  protected $payerDataRequestMock;

  public function setUp(): void
  {
    $this->subjectReaderMock = $this->getMockBuilder(SubjectReader::class)
      ->disableOriginalConstructor()
      ->getMock();

    $this->orderAdapterFactoryMock = $this->getMockBuilder(OrderAdapterFactory::class)
      ->disableOriginalConstructor()
      ->getMock();

    $this->payerDataRequestMock = $this->getMockBuilder(PayerDataRequest::class)
      ->setConstructorArgs([
        $this->subjectReaderMock,
        $this->orderAdapterFactoryMock
      ])->getMock();
  }

  public function testBuild()
  {
    $paymentDoMock = $this->getMockBuilder(PaymentDataObjectInterface::class)
      ->disableOriginalConstructor()
      ->getMock();

    $buildSubjectMock = ['payment' => $paymentDoMock];

    $this->subjectReaderMock->expects($this->any())
      ->method('readPayment')
      ->with($buildSubjectMock)
      ->willReturn($paymentDoMock);

    $paymentMock = $this->getMockBuilder(Payment::class)
      ->disableOriginalConstructor()
      ->getMock();

    $paymentDoMock->expects($this->any())
      ->method('getPayment')
      ->willReturn($paymentMock);

    $orderAdapterInterfaceMock = $this->getMockBuilder(OrderAdapterInterface::class)
      ->disableOriginalConstructor()
      ->getMock();

    $paymentDoMock->expects($this->any())
      ->method('getOrder')
      ->willReturn($orderAdapterInterfaceMock);

    $orderAdapterMock = $this->getMockBuilder(OrderAdapter::class)
      ->disableOriginalConstructor()
      ->getMock();

    $this->orderAdapterFactoryMock->expects($this->any())
      ->method('create')
      ->withAnyParameters()
      ->willReturn($orderAdapterMock);

    $paymentMock->expects($this->any())
      ->method('getAdditionalInformation')
      ->willReturnMap([
        ['mp_user_id', '12345'],
        ['payer_entity_type', 'customer'],
      ]);

    $billingAddressMock = $this->getMockBuilder(OrderAddressInterface::class)
      ->disableOriginalConstructor()
      ->getMock();

    $orderAdapterMock->expects($this->any())
      ->method('getBillingAddress')
      ->willReturn($billingAddressMock);

    $billingAddressMock->expects($this->any())
      ->method('getTelephone')
      ->willReturn('+1234567890');
    $billingAddressMock->expects($this->any())
      ->method('getEmail')
      ->willReturn('test@email.com');
    $billingAddressMock->expects($this->any())
      ->method('getFirstname')
      ->willReturn('John');
    $billingAddressMock->expects($this->any())
      ->method('getLastname')
      ->willReturn('Doe');

    $payerDataRequest = new PayerDataRequest(
      $this->subjectReaderMock,
      $this->orderAdapterFactoryMock
    );

    $result = $payerDataRequest->build($buildSubjectMock);

    $this->assertIsArray($result);
    $this->assertArrayHasKey('payer', $result);
    $this->assertArrayHasKey('phone', $result['payer']);
    $this->assertArrayHasKey('area_code', $result['payer']['phone']);
    $this->assertArrayHasKey('number', $result['payer']['phone']);
  }

  public function testBuildInvalidArgument()
  {
    $this->expectException(\InvalidArgumentException::class);

    $buildSubjectMock = [];

    $payerDataRequest = new PayerDataRequest(
      $this->subjectReaderMock,
      $this->orderAdapterFactoryMock
    );

    $payerDataRequest->build($buildSubjectMock);
  }
}
