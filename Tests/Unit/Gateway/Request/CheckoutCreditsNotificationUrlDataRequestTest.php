<?php

namespace MercadoPago\AdbPayment\Tests\Unit\Gateway\Request;

use Magento\Framework\UrlInterface;
use MercadoPago\AdbPayment\Gateway\Config\Config;
use MercadoPago\AdbPayment\Gateway\Request\CheckoutCreditsNotificationUrlDataRequest;


use PHPUnit\Framework\TestCase;
use ReflectionProperty;

/**
 * Gateway Requests for Checkout Credits Notification Url.
 */
class CheckoutCreditsNotificationUrlDataRequestTest extends TestCase
{

  /**
   * @var checkoutCreditsNotificationUrlDataRequestMock
   */
  protected $checkoutCreditsNotificationUrlDataRequestMock;

  /**
   * @var UrlInterface
   */
  protected $urlInterfaceMock;

  /**
   * @var Config
   */
  protected $configMock;

  /**
   * @var CheckoutCreditsNotificationUrlDataRequest
   */
  protected $checkoutCreditsNotificationUrlDataRequest;

  public function setUp(): void
  {
    $this->urlInterfaceMock = $this->getMockBuilder(UrlInterface::class)
      ->disableOriginalConstructor()
      ->getMock();

    $this->configMock = $this->getMockBuilder(Config::class)
      ->disableOriginalConstructor()
      ->getMock();
  }

  public function testConstruct()
  {
    $pathNotication = CheckoutCreditsNotificationUrlDataRequest::PATH_TO_NOTIFICATION;

    $this->checkoutCreditsNotificationUrlDataRequest = new CheckoutCreditsNotificationUrlDataRequest(
      $this->urlInterfaceMock,
      $this->configMock,
      $pathNotication
    );

    $reflectionProperty = new ReflectionProperty($this->checkoutCreditsNotificationUrlDataRequest, 'pathNotication');
    $reflectionProperty->setAccessible(true);
    $pathNoticationValue = $reflectionProperty->getValue($this->checkoutCreditsNotificationUrlDataRequest);

    $this->assertNotNull($this->checkoutCreditsNotificationUrlDataRequest);
    $this->assertEquals($pathNotication, $pathNoticationValue);
    $this->assertInstanceOf(CheckoutCreditsNotificationUrlDataRequest::class, $this->checkoutCreditsNotificationUrlDataRequest);
  }
}
