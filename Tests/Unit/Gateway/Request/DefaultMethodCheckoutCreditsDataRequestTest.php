<?php


namespace MercadoPago\AdbPayment\Tests\Unit\Gateway\Request;

use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use MercadoPago\AdbPayment\Gateway\Config\ConfigCheckoutCredits;
use MercadoPago\AdbPayment\Gateway\Request\DefaultMethodCheckoutCreditsDataRequest;

use PHPUnit\Framework\TestCase;

class DefaultMethodCheckoutCreditsDataRequestTest extends TestCase
{

  /**
   * @var ConfigCheckoutCredits
   */
  protected $configCheckoutCreditsMock;

  /**
   * @var defaultMethodCheckoutCreditsDataRequestMock
   */
  protected $defaultMethodCheckoutCreditsDataRequestMock;

  public function setUp(): void
  {
    $this->configCheckoutCreditsMock = $this->getMockBuilder(ConfigCheckoutCredits::class)
      ->disableOriginalConstructor()
      ->getMock();
  }

  public function testBuild()
  {

    $buildSubjectMock =
      ['payment' => $this->getMockBuilder(PaymentDataObjectInterface::class)->disableOriginalConstructor()->getMock()];

    $defaultMethodCheckoutCreditsDataRequest = new DefaultMethodCheckoutCreditsDataRequest(
      $this->configCheckoutCreditsMock
    );

    $result = $defaultMethodCheckoutCreditsDataRequest->build($buildSubjectMock);

    $this->assertIsArray($result);
    $this->assertArrayHasKey(DefaultMethodCheckoutCreditsDataRequest::PURPOSE, $result);
    $this->assertArrayHasKey(DefaultMethodCheckoutCreditsDataRequest::PAYMENT_METHODS, $result);
    $this->assertEquals(DefaultMethodCheckoutCreditsDataRequest::ONBOARDING_CREDITS, $result[DefaultMethodCheckoutCreditsDataRequest::PURPOSE]);
  }

  public function testBuildInvalidArgument()
  {
    $this->expectException(\InvalidArgumentException::class);

    $buildSubjectMock = [];

    $defaultMethodCheckoutCreditsDataRequest = new DefaultMethodCheckoutCreditsDataRequest(
      $this->configCheckoutCreditsMock
    );

    $defaultMethodCheckoutCreditsDataRequest->build($buildSubjectMock);
  }
}
