<?php

namespace MercadoPago\AdbPayment\Tests\Unit\Model\Ui;

use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Framework\View\Asset\Source;
use Magento\Payment\Model\CcConfig;
use Magento\Quote\Api\Data\CartInterface;
use MercadoPago\AdbPayment\Gateway\Config\ConfigYape;
use MercadoPago\AdbPayment\Model\Ui\ConfigProviderYape;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;


class ConfigProviderYapeTest extends TestCase
{
    /**
     * @var ConfigProviderYape
     */
    private $configProviderYape;

    /**
     * @var ConfigYape|MockObject
     */
    private $configYapeMock;

    /**
     * @var CartInterface|MockObject
     */
    private $cartMock;

    /**
     * @var CcConfig|MockObject
     */
    private $ccConfigMock;

    /**
     * @var Source|MockObject
     */
    private $assetSourceMock;

    /**
     * @var ConfigProviderYape|MockObject
     */
    private $provider;

    protected function setUp(): void
    {
        $this->configYapeMock = $this->createMock(ConfigYape::class);
        $this->cartMock = $this->createMock(CartInterface::class);
        $this->ccConfigMock = $this->createMock(CcConfig::class);
        $this->assetSourceMock = $this->createMock(Source::class);
        $this->provider = $this->createMock(ConfigProviderYape::class);

        $this->configProviderYape = new ConfigProviderYape(
            $this->configYapeMock,
            $this->cartMock,
            $this->ccConfigMock,
            $this->assetSourceMock
        );
    }

    public function testGetConfigReturnsEmptyArrayWhenInactive()
    {
        $storeId = 1;
        $this->cartMock->method('getStoreId')->willReturn($storeId);
        $this->configYapeMock->method('isActive')->with($storeId)->willReturn(false);

        $result = $this->configProviderYape->getConfig();

        $this->assertEquals([], $result);
    }

    public function testGetConfigReturnsConfigArrayWhenActive()
    {
        $storeId = 1;
        $this->cartMock->method('getStoreId')->willReturn($storeId);
        $this->configYapeMock->method('isActive')->with($storeId)->willReturn(true);
        $this->configYapeMock->method('getTitle')->with($storeId)->willReturn('Yape Payment');
        $this->provider->method('getIcons')->with($storeId)->willReturn([]);
        $this->provider->method('getLogo')->with($storeId)->willReturn([]);

        $asset = $this->createMock(\Magento\Framework\View\Asset\LocalInterface::class);
        $this->ccConfigMock->method('createAsset')->willReturn($asset);

        $this->assetSourceMock->method('findSource')
            ->willReturn(false);

        $this->configYapeMock->method('getFingerPrintLink')->with($storeId)->willReturn('https://example.com/fingerprint');

        $expectedResult = [
            'payment' => [
                ConfigYape::METHOD => [
                    'isActive'    => true,
                    'title'       => 'Yape Payment',
                    'logo'        => [],
                    'fingerprint' => 'https://example.com/fingerprint',
                    'yapeIcons'   => []
                ],
            ],
        ];

        $result = $this->configProviderYape->getConfig();
        $this->assertEquals($expectedResult, $result);
    }

    public function testGetLogoReturnsEmptyArrayWhenNoPlaceholder()
    {
        $this->ccConfigMock->method('createAsset')
            ->with('MercadoPago_AdbPayment::images/yape/logo.svg')
            ->willReturn($this->createMock(\Magento\Framework\View\Asset\File::class));

        $this->assetSourceMock->method('findSource')
            ->willReturn(false);

        $result = $this->configProviderYape->getLogo();

        $this->assertEquals([], $result);
    }

    public function testGetIconsReturnsIconsArrayWhenIconsConfigured()
    {
        $storeId = 1;
        $this->cartMock->method('getStoreId')->willReturn($storeId);
        $this->configYapeMock->method('getIcons')->with($storeId)->willReturn('attention,info');
        $visaAssetMock = $this->createMock(\Magento\Framework\View\Asset\File::class);
        $visaAssetMock->method('getUrl')->willReturn('http://example.com/icon.svg');
        $this->ccConfigMock->method('createAsset')
            ->withConsecutive(
                ['MercadoPago_AdbPayment::images/yape/attention.svg'],
                ['MercadoPago_AdbPayment::images/yape/info.svg']
            )
            ->willReturnOnConsecutiveCalls(
                $visaAssetMock,
                $visaAssetMock
            );
        $this->assetSourceMock->method('findSource')
            ->willReturn(true);
        $expectedResult = [
            'attention' => [
                'url'    => 'http://example.com/icon.svg',
                'code'   => 'attention',
                'title'  => 'attention',
            ],
            'info' => [
                'url'    => 'http://example.com/icon.svg',
                'code'   => 'info',
                'title'  => 'info',
            ],
        ];
        $result = $this->configProviderYape->getIcons();
        $this->assertEquals($expectedResult, $result);
    }
}
