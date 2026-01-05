<?php

namespace MercadoPago\AdbPayment\Tests\Unit\Block\Sales\Form;

use Magento\Backend\Model\Session\Quote;
use Magento\Quote\Model\Quote as QuoteModel;
use Magento\Framework\View\Element\Template\Context;
use MercadoPago\AdbPayment\Gateway\Config\ConfigCheckoutCredits;
use MercadoPago\AdbPayment\Block\Sales\Form\CheckoutCredits;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;

use PHPUnit\Framework\TestCase;
use PSpell\Config;

class CheckoutCreditsTest extends TestCase
{

  /**
   * @var CheckoutCredits
   */
  protected $checkoutCreditsMock;

  /**
   * @var Context
   */
  protected $contextMock;

  /**
   * @var ConfigCheckoutCredits
   */
  protected $configCheckoutCreditsMock;

  /**
   * @var Quote
   */
  protected $sessionQuoteMock;

  /**
   * @var QuoteModel
   */
  protected $backendSessionQuoteMock;

  /**
   * @var ObjectManager
   */
  protected $objectManager;


  public function setUp(): void
  {
    $this->contextMock = $this->getMockBuilder(Context::class)
      ->disableOriginalConstructor()
      ->getMock();
    $this->configCheckoutCreditsMock = $this->getMockBuilder(ConfigCheckoutCredits::class)
      ->disableOriginalConstructor()
      ->getMock();
    $this->sessionQuoteMock = $this->getMockBuilder(Quote::class)
      ->disableOriginalConstructor()
      ->getMock();
    $this->backendSessionQuoteMock = $this->getMockBuilder(QuoteModel::class)
      ->disableOriginalConstructor()
      ->getMock();

    $this->checkoutCreditsMock = $this->getMockBuilder(CheckoutCredits::class)
      ->setConstructorArgs([
        $this->contextMock,
        $this->configCheckoutCreditsMock,
        $this->sessionQuoteMock
      ])->getMock();
  }

  public function testGetTitle()
  {
    $storeId = 1;
    $expectedTitle = 'CheckoutCredits';

    $this->configCheckoutCreditsMock->expects($this->any())
      ->method('getTitle')
      ->with($storeId)
      ->willReturn($expectedTitle);

    $this->sessionQuoteMock->expects($this->any())
      ->method('getQuote')
      ->willReturn($this->backendSessionQuoteMock);

    $this->backendSessionQuoteMock->expects($this->any())
      ->method('getStoreId')
      ->willReturn($storeId);

    $checkoutCreditsBlock = new CheckoutCredits(
      $this->contextMock,
      $this->configCheckoutCreditsMock,
      $this->sessionQuoteMock
    );

    $result = $checkoutCreditsBlock->getTitle();

    $this->assertEquals($expectedTitle, $result);
  }

  public function testGetExpiration()
  {
    $storeId = 1;
    $expectedExpiration = 'dd/mm/YYYY';

    $this->configCheckoutCreditsMock->expects($this->any())
      ->method('getExpirationFormat')
      ->with($storeId)
      ->willReturn($expectedExpiration);

    $this->sessionQuoteMock->expects($this->any())
      ->method('getQuote')
      ->willReturn($this->backendSessionQuoteMock);

    $this->backendSessionQuoteMock->expects($this->any())
      ->method('getStoreId')
      ->willReturn($storeId);

    $checkoutCreditsBlock = new CheckoutCredits(
      $this->contextMock,
      $this->configCheckoutCreditsMock,
      $this->sessionQuoteMock
    );

    $result = $checkoutCreditsBlock->getExpiration();

    $this->assertEquals($expectedExpiration, $result);
  }
}
