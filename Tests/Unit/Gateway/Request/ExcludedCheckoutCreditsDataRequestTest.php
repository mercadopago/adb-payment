<?php


namespace MercadoPago\AdbPayment\Tests\Unit\Gateway\Request;

use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use MercadoPago\AdbPayment\Gateway\Config\ConfigCheckoutCredits;
use MercadoPago\AdbPayment\Gateway\Request\ExcludedCheckoutCreditsDataRequest;
use Magento\Sales\Model\Order;

use PHPUnit\Framework\TestCase;

/**
 * Gateway Requests for Excluded Checkout Credits Data.
 */
class ExcludedCheckoutCreditsDataRequestTest extends TestCase
{

  /**
   * @var ConfigCheckoutCredits
   */
  protected $configCheckoutCreditsMock;

  /**
   * @var ExcludedCheckoutCreditsDataRequest
   */
  protected $excludedCheckoutCreditsDataRequest;


  public function setUp(): void
  {
    $this->configCheckoutCreditsMock = $this->getMockBuilder(ConfigCheckoutCredits::class)->disableOriginalConstructor()->getMock();
  }

  /**
   * @covers \MercadoPago\AdbPayment\Gateway\Request\ExcludedCheckoutCreditsDataRequest::build
   */
  public function testBuild()
  {
    $storeId = 1;

    $paymentDOMock = $this->getMockBuilder(PaymentDataObjectInterface::class)
      ->disableOriginalConstructor()
      ->getMock();

    $orderMock = $this->getMockBuilder(Order::class)
      ->disableOriginalConstructor()
      ->getMock();

    $paymentDOMock->expects($this->any())
      ->method('getOrder')
      ->willReturn($orderMock);

    $orderMock->expects($this->any())
      ->method('getStoreId')
      ->willReturn($storeId);

    $buildSubjectMock = ['payment' => $paymentDOMock];

    $excludeds = ['visa', 'master'];

    $this->configCheckoutCreditsMock->expects($this->any())
      ->method('getExcluded')
      ->with($storeId)
      ->willReturn($excludeds);

    $excludedCheckoutCreditsDataRequest = new ExcludedCheckoutCreditsDataRequest(
      $this->configCheckoutCreditsMock
    );

    $result = $excludedCheckoutCreditsDataRequest->build($buildSubjectMock);

    $this->assertIsArray($result);
    $this->assertEquals(sizeof($result[ExcludedCheckoutCreditsDataRequest::PAYMENT_METHODS][ExcludedCheckoutCreditsDataRequest::EXCLUDED_PAYMENT_METHODS]), sizeof($excludeds));
  }

  public function testBuildInvalidArgument()
  {
    $this->expectException(\InvalidArgumentException::class);

    $buildSubjectMock = [];

    $excludedCheckoutCreditsDataRequest = new ExcludedCheckoutCreditsDataRequest(
      $this->configCheckoutCreditsMock
    );

    $excludedCheckoutCreditsDataRequest->build($buildSubjectMock);
  }
}
