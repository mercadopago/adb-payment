<?php

namespace MercadoPago\AdbPayment\Tests\Unit\Gateway\Config;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Store\Model\ScopeInterface;
use MercadoPago\AdbPayment\Gateway\Config\ConfigYape;
use MercadoPago\AdbPayment\Gateway\Data\Checkout\Fingerprint;
use MercadoPago\AdbPayment\Gateway\Config\Config;

use PHPUnit\Framework\TestCase;


class ConfigYapeTest extends TestCase
{
    /**
     * config method.
     */
    public const CONFIG_METHOD = 'getValue';

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfigMock;

    /**
     * @var Json
     */
    private $jsonMock;

    /**
     * @var Config
     */
    private $configMock;

    /**
     * @var Fingerprint
     */
    private $fingerprintMock;

    /**
     * @var ConfigYape
     */
    private $configYape;

    protected function setUp(): void
    {
        $this->scopeConfigMock = $this->getMockBuilder(ScopeConfigInterface::class)
        ->disableOriginalConstructor()
        ->getMock();

        $this->jsonMock = $this->getMockBuilder(Json::class)
        ->disableOriginalConstructor()
        ->getMock();

        $this->configMock = $this->getMockBuilder(Config::class)
        ->disableOriginalConstructor()
        ->getMock();

        $this->fingerprintMock = $this->getMockBuilder(Fingerprint::class)
        ->disableOriginalConstructor()
        ->getMock();

        $this->configYape = new ConfigYape(
            $this->scopeConfigMock,
            $this->jsonMock,
            $this->configMock,
            $this->fingerprintMock
        );
    }

    public function testIsActive()
    {
        $storeId = 1;
        $this->scopeConfigMock->method(self::CONFIG_METHOD)
            ->with('payment/mercadopago_adbpayment_yape/active', ScopeInterface::SCOPE_STORE, $storeId)
            ->willReturn('1');

        $this->assertTrue($this->configYape->isActive($storeId));
    }

    public function testGetTitle()
    {
        $storeId = 1;
        $title = 'Yape Payment';
        $this->scopeConfigMock->method(self::CONFIG_METHOD)
            ->with('payment/mercadopago_adbpayment_yape/title', ScopeInterface::SCOPE_STORE, $storeId)
            ->willReturn($title);

        $this->assertEquals($title, $this->configYape->getTitle($storeId));
    }

    public function testGetFingerPrintLink()
    {
        $storeId = 1;
        $mpSiteId = 'MLA';
        $fingerPrintLink = 'https://example.com/fingerprint';

        $this->configMock->method('getMpSiteId')
            ->with($storeId)
            ->willReturn($mpSiteId);

        $this->fingerprintMock->method('getFingerPrintLink')
            ->with($mpSiteId)
            ->willReturn($fingerPrintLink);

        $this->assertEquals($fingerPrintLink, $this->configYape->getFingerPrintLink($storeId));
    }

    public function testGetMaximumOrderTotal()
    {
        $storeId = 1;
        $maxOrderTotal = 1000.00;
        $this->scopeConfigMock->method(self::CONFIG_METHOD)
            ->with('payment/mercadopago_adbpayment_yape/max_order_total', ScopeInterface::SCOPE_STORE, $storeId)
            ->willReturn($maxOrderTotal);

        $this->assertEquals($maxOrderTotal, $this->configYape->getMaximumOrderTotal($storeId));
    }
}
