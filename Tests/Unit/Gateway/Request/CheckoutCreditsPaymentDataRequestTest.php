<?php


namespace MercadoPago\AdbPayment\Tests\Unit\Gateway\Request;

use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use MercadoPago\AdbPayment\Gateway\Config\ConfigCheckoutCredits;
use MercadoPago\AdbPayment\Gateway\Request\CheckoutCreditsPaymentDataRequest;

use PHPUnit\Framework\TestCase;

/**
 * Gateway Requests Payment by Checkout Credits Data.
 */
class CheckoutCreditsPaymentDataRequestTest extends TestCase
{

  /**
   * @var ConfigCheckoutCredits
   */
  protected $configCheckoutCreditsMock;

  /**
   * @var checkoutCreditsPaymentDataRequestMock
   */
  protected $checkoutCreditsPaymentDataRequestMock;

  public function setUp(): void
  {
    $this->configCheckoutCreditsMock = $this->getMockBuilder(ConfigCheckoutCredits::class)->disableOriginalConstructor()->getMock();

    $this->checkoutCreditsPaymentDataRequestMock = $this->getMockBuilder(CheckoutCreditsPaymentDataRequest::class)
      ->setConstructorArgs([
        $this->configCheckoutCreditsMock
      ])->getMock();
  }

  /**
   * @covers \MercadoPago\AdbPayment\Gateway\Request\CheckoutCreditsPaymentDataRequest::build
   */
  public function testBuild()
  {
    $expiration = '2021-12-31';
    $buildSubjectMock = ['payment' => $this->getMockBuilder(PaymentDataObjectInterface::class)
      ->disableOriginalConstructor()
      ->getMock()];

    $this->configCheckoutCreditsMock->expects($this->any())
      ->method('getExpirationFormatted')
      ->willReturn($expiration);

    $expectedResult = [
      'date_of_expiration' => $expiration,
      'auto_return' => 'all',
    ];

    $checkoutCreditsPaymentDataRequest = new CheckoutCreditsPaymentDataRequest(
      $this->configCheckoutCreditsMock
    );

    $result = $checkoutCreditsPaymentDataRequest->build($buildSubjectMock);

    $this->assertEquals($expectedResult, $result);
  }

  public function testBuildInvalidArgument()
  {
    $this->expectException(\InvalidArgumentException::class);

    $buildSubjectMock = [];

    $checkoutCreditsPaymentDataRequest = new CheckoutCreditsPaymentDataRequest(
      $this->configCheckoutCreditsMock
    );

    $checkoutCreditsPaymentDataRequest->build($buildSubjectMock);
  }
}
