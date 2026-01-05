<?php

namespace MercadoPago\AdbPayment\Tests\Unit\Gateway\Config;

use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Framework\App\Config\ScopeConfigInterface;
use MercadoPago\AdbPayment\Gateway\Config\Config;
use Magento\Store\Model\ScopeInterface;
use MercadoPago\AdbPayment\Gateway\Config\ConfigCheckoutPro;
use MercadoPago\AdbPayment\Gateway\Data\Checkout\Fingerprint;

use PHPUnit\Framework\TestCase;

class ConfigCheckoutProTest extends TestCase
{

  /**
   * config method.
   */
  public const CONFIG_METHOD = 'getValue';

  /**
   * siteId method.
   */
  public const SITEID_METHOD = 'getMpSiteId';

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
   * @var ConfigCheckoutPro
   */
  protected $configCheckoutPro;

  public function setUp(): void
  {
    $this->scopeConfigMock = $this->createMock(ScopeConfigInterface::class);
    $this->configMock = $this->createMock(Config::class);
    $this->dateMock = $this->createMock(DateTime::class);
    $this->fingerprintMock = $this->createMock(Fingerprint::class);
    
    $this->configCheckoutPro = new ConfigCheckoutPro(
      $this->scopeConfigMock,
      $this->dateMock,
      $this->configMock,
      $this->fingerprintMock
      );  
  }

  public function testGetChoProAvailableTypes() {
        $storeId = 1;
        $siteId = 'MLB';
        $expectedResult = 'some_value';

        $this->configMock->expects($this->any())
        ->method(self::SITEID_METHOD)
        ->with($storeId)
        ->willReturn($siteId);

        $this->scopeConfigMock->expects($this->any())->method(self::CONFIG_METHOD)->willReturn($expectedResult);

        $result = $this->configCheckoutPro->getChoProAvailableTypes($storeId);

        $this->assertEquals($expectedResult, $result);
  }

  public function testGetChoProIcons() {
    $storeId = 1;
    $siteId = 'MLB';
    $expectedResult = 'some_value';

    $this->configMock->expects($this->any())
    ->method(self::SITEID_METHOD)
    ->with($storeId)
    ->willReturn($siteId);

    $this->scopeConfigMock->expects($this->any())->method(self::CONFIG_METHOD)->willReturn($expectedResult);

    $result = $this->configCheckoutPro->getChoProInfoIcons($storeId);

    $this->assertEquals($expectedResult, $result);
  }
}
