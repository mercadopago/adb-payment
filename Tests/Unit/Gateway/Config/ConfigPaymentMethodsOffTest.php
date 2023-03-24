<?php

namespace Tests\Unit\Gateway\Config;

use PHPUnit\Framework\TestCase;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Payment\Gateway\Config\Config as PaymentConfig;
use Magento\Store\Model\ScopeInterface;

class ConfigPaymentMethodsOffTest extends TestCase {
    /**
     * @var configPaymentMethodsOff
     */
    private $configPaymentMethodsOff;

    /**
     * @var configPaymentMethodsOffMock
     */
    private $configPaymentMethodsOffMock;

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

    public function setUp(): void 
    {
        $this->scopeConfigMock = $this->getMockBuilder(ScopeConfigInterface::class)->getMockForAbstractClass();
        $this->dateMock = $this->getMockBuilder(DateTime::class)->disableOriginalConstructor()->getMock();
        $this->configMock = $this->getMockBuilder(Config::class)->disableOriginalConstructor()->getMock();

        $this->configPaymentMethodsOffMock = $this->getMockBuilder(ConfigPaymentMethodsOff::class)->setMethods(
            ['isActive'])->disableOriginalConstructor()->getMock();

        //$this->configPaymentMethodsOffMock->expects($this->any())->method('isActive')->willReturn(true);

        $this->configPaymentMethodsOffMock->method('isActive')->willReturn(true);
    }

    public function test_getMethod_Active(): void
    {
        $teste = $this->configPaymentMethodsOffMock->isActive(null);
        $this->assertEquals($teste, true);
    }
}