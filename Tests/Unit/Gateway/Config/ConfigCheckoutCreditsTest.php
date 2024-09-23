<?php

namespace Tests\Unit\Gateway\Config;

use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Framework\App\Config\ScopeConfigInterface;
use MercadoPago\AdbPayment\Gateway\Config\Config;
use Magento\Store\Model\ScopeInterface;
use MercadoPago\AdbPayment\Gateway\Config\ConfigCheckoutCredits;
use MercadoPago\AdbPayment\Gateway\Data\Checkout\Fingerprint;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;

use PHPUnit\Framework\TestCase;

class ConfigCheckoutCreditsTest extends TestCase
{

  /**
   * path pattern.
   */
  public const PATH_PATTERN = 'payment/%s/%s';

  /**
   * config method.
   */
  public const CONFIG_METHOD = 'getValue';

  /**
   * config method.
   */
  public const BANNER_TEXT_USE = 'Compre em até 12x sem cartão de crédito';

  /**
   * @var ScopeConfigInterface
   */
  private $scopeConfigMock;

  /**
   * @var DateTime
   */
  private $dateMock;

  /**
   * @var Config
   */
  private $configMock;

  /**
   * @var Fingerprint
   */
  protected $fingerprintMock;

  /**
   * @var ConfigCheckoutCredits
   */
  protected $methodCode;

  /**
   * @var ObjectManager
   */
  protected $objectManager;

  public function setUp(): void
  {
    $this->objectManager = new ObjectManager($this);

    $this->scopeConfigMock = $this->getMockBuilder(ScopeConfigInterface::class)
      ->setMethods([self::CONFIG_METHOD])
      ->getMockForAbstractClass();

    $this->configMock = $this->getMockBuilder(Config::class)
      ->disableOriginalConstructor()
      ->getMock();

    $this->dateMock = $this->getMockBuilder(DateTime::class)
      ->disableOriginalConstructor()
      ->getMock();

    $this->fingerprintMock = $this->getMockBuilder(Fingerprint::class)
      ->disableOriginalConstructor()
      ->getMock();
  }

  /**
   * Tests functions get configs()
   */
  public function testIsActiveGetTrue()
  {
    $this->scopeConfigMock->expects($this->any())
      ->method(self::CONFIG_METHOD)
      ->with(sprintf(self::PATH_PATTERN, ConfigCheckoutCredits::METHOD, ConfigCheckoutCredits::ACTIVE), ScopeInterface::SCOPE_STORE)
      ->willReturn(true);

    $configCheckoutCredits = new ConfigCheckoutCredits(
      $this->scopeConfigMock,
      $this->dateMock,
      $this->configMock,
      $this->fingerprintMock
    );

    $result = $configCheckoutCredits->isActive(null);

    $this->assertTrue($result);
  }

  public function testGetTitleGetWithValue()
  {
    $this->scopeConfigMock->expects($this->any())
      ->method(self::CONFIG_METHOD)
      ->with(sprintf(self::PATH_PATTERN, ConfigCheckoutCredits::METHOD, ConfigCheckoutCredits::TITLE), ScopeInterface::SCOPE_STORE)
      ->willReturn('CheckoutCredits');

    $configCheckoutCredits = new ConfigCheckoutCredits(
      $this->scopeConfigMock,
      $this->dateMock,
      $this->configMock,
      $this->fingerprintMock
    );

    $result = $configCheckoutCredits->getTitle(null);

    $this->assertEquals('CheckoutCredits', $result);
  }

  public function testGetExcludedCheckoutCreditsWithNullValue()
  {
    $this->scopeConfigMock->expects($this->any())
      ->method(self::CONFIG_METHOD)
      ->with(sprintf(self::PATH_PATTERN, ConfigCheckoutCredits::METHOD, ConfigCheckoutCredits::EXCLUDED), ScopeInterface::SCOPE_STORE)
      ->willReturn(null);

    $configCheckoutCredits = new ConfigCheckoutCredits(
      $this->scopeConfigMock,
      $this->dateMock,
      $this->configMock,
      $this->fingerprintMock
    );

    $result = $configCheckoutCredits->getExcluded(null);

    $this->assertEquals(null, $result);
  }

  public function testGetExcludedCheckoutCreditsWithArrayValue()
  {
    $excluded = 'Pix, Boleto';

    $this->scopeConfigMock->expects($this->any())
      ->method(self::CONFIG_METHOD)
      ->with(sprintf(self::PATH_PATTERN, ConfigCheckoutCredits::METHOD, ConfigCheckoutCredits::EXCLUDED), ScopeInterface::SCOPE_STORE)
      ->willReturn($excluded);

    $configCheckoutCredits = new ConfigCheckoutCredits(
      $this->scopeConfigMock,
      $this->dateMock,
      $this->configMock,
      $this->fingerprintMock
    );

    $result = $configCheckoutCredits->getExcluded(null);

    $this->assertIsArray($result);
  }

  public function testGetFingerPrintLink()
  {
    $link = 'https://www.mercadopago.com.br/ajuda/termos-e-politicas_194';
    $siteId = 'MLB';
    $storeId = 1;

    $this->configMock->expects($this->any())
      ->method('getMpSiteId')
      ->with($storeId)
      ->willReturn($siteId);

    $configCheckoutCredits = new ConfigCheckoutCredits(
      $this->scopeConfigMock,
      $this->dateMock,
      $this->configMock,
      $this->objectManager->getObject(Fingerprint::class)
    );

    $result = $configCheckoutCredits->getFingerPrintLink($storeId);

    $this->assertEquals($link, $result);
  }

  public function testGetFingerPrintLinkDefault()
  {
    $link = 'https://www.mercadopago.com.ar/ayuda/terminos-y-politicas_194';
    $siteId = 'MXX';
    $storeId = 1;

    $this->configMock->expects($this->any())
      ->method('getMpSiteId')
      ->with($storeId)
      ->willReturn($siteId);

    $configCheckoutCredits = new ConfigCheckoutCredits(
      $this->scopeConfigMock,
      $this->dateMock,
      $this->configMock,
      $this->objectManager->getObject(Fingerprint::class)
    );

    $result = $configCheckoutCredits->getFingerPrintLink($storeId);

    $this->assertEquals($link, $result);
  }

  public function testGetBannerTextHowToUse()
  {
    $textId = 'credits-use';
    $storeId = 1;

    $this->scopeConfigMock->expects($this->any())
    ->method(self::CONFIG_METHOD)
    ->with(sprintf(self::PATH_PATTERN, ConfigCheckoutCredits::METHOD, $textId), ScopeInterface::SCOPE_STORE, $storeId)
    ->willReturn('Compre em até 12x sem cartão de crédito');

    $configCheckoutCredits = new ConfigCheckoutCredits(
      $this->scopeConfigMock,
      $this->dateMock,
      $this->configMock,
      $this->fingerprintMock
    );

    $result = $configCheckoutCredits->getBannerText($textId, $storeId);

    $this->assertEquals('Compre em até 12x sem cartão de crédito', $result);
  }
}
