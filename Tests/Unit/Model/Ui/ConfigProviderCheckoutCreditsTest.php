<?php

namespace MercadoPago\AdbPayment\Tests\Unit\Model\Ui;

use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Framework\Escaper;
use Magento\Framework\View\Asset\Source;
use Magento\Payment\Model\CcConfig;
use Magento\Quote\Api\Data\CartInterface;
use MercadoPago\AdbPayment\Gateway\Config\ConfigCheckoutCredits;
use MercadoPago\AdbPayment\Gateway\Config\Config as MercadoPagoConfig;
use MercadoPago\AdbPayment\Model\Ui\ConfigProviderCheckoutCredits;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ConfigProviderCheckoutCreditsTest extends TestCase
{
    /**
     * @var ConfigProviderCheckoutCredits
     */
    private $configProvider;

    /**
     * @var ConfigCheckoutCredits|MockObject
     */
    private $config;

    /**
     * @var CartInterface|MockObject
     */
    private $cart;

    /**
     * @var CcConfig|MockObject
     */
    private $ccConfig;

    /**
     * @var Escaper|MockObject
     */
    private $escaper;

    /**
     * @var Source|MockObject
     */
    private $assetSource;

    /**
     * @var MercadoPagoConfig|MockObject
     */
    private $mercadoPagoConfig;

    protected function setUp(): void
    {
        $this->config = $this->createMock(ConfigCheckoutCredits::class);
        $this->cart = $this->createMock(CartInterface::class);
        $this->ccConfig = $this->createMock(CcConfig::class);
        $this->escaper = $this->createMock(Escaper::class);
        $this->assetSource = $this->createMock(Source::class);
        $this->mercadoPagoConfig = $this->createMock(MercadoPagoConfig::class);

        $this->configProvider = new ConfigProviderCheckoutCredits(
            $this->config,
            $this->cart,
            $this->ccConfig,
            $this->escaper,
            $this->assetSource,
            $this->mercadoPagoConfig
        );
    }

    public function testImplementsConfigProviderInterface()
    {
        $this->assertInstanceOf(ConfigProviderInterface::class, $this->configProvider);
    }

    public function testGetConfigReturnsEmptyArrayWhenNotActive()
    {
        $storeId = 1;
        $paymentsResponse = [
            'success' => false,
            'response' => [
                [],
            ]
        ];
        $this->cart->method('getStoreId')->willReturn($storeId);
        $this->config->method('isActive')->with($storeId)->willReturn(false);
        $this->mercadoPagoConfig->method('getMpPaymentMethods')->willReturn($paymentsResponse);
        $config = $this->configProvider->getConfig();

        $this->assertEmpty($config);
        $this->assertFalse($paymentsResponse['success']);
    }


    public function testGetLogoReturnsEmptyArrayWhenLogoAssetNotFound()
    {
        $asset = $this->createMock(\Magento\Framework\View\Asset\LocalInterface::class);
        $this->ccConfig->method('createAsset')->willReturn($asset);
        $this->assetSource->method('findSource')->willReturn(null);

        $logo = $this->configProvider->getLogo();

        $this->assertEmpty($logo);
    }

    public function testIsActiveReturnsTrueWhenPaymentMethodIsFound()
    {
        $storeId = 1;
        $this->cart->method('getStoreId')->willReturn($storeId);
        $paymentsResponse = [
            'success' => true,
            'response' => [
                ['id' => 'pix'],
                ['id' => 'consumer_credits'],
            ]
        ];

        $this->mercadoPagoConfig->method('getMpPaymentMethods')->willReturn($paymentsResponse);

        $isActive = $this->configProvider->isActive();

        $this->assertTrue($isActive);
    }

    public function testIsActiveReturnsFalseWhenPaymentMethodIsNotFound()
    {
        $storeId = 1;
        $this->cart->method('getStoreId')->willReturn($storeId);
        $paymentsResponse = [
            'success' => false,
            'response' => [
                [],
            ]
        ];
        $this->mercadoPagoConfig->method('getMpPaymentMethods')->willReturn($paymentsResponse);

        $isActive = $this->configProvider->isActive();

        $this->assertFalse($isActive);
    }

    public function testGetImagesByNameReturnsEmptyArrayWhenSourceNotFound()
    {
        $this->ccConfig
            ->method('createAsset')
            ->with('MercadoPago_AdbPayment::images/credits/credits-1.svg')
            ->willReturn($this->createMock(\Magento\Framework\View\Asset\File::class));

        $this->assetSource
            ->method('findSource')
            ->willReturn(false);

        $image = $this->configProvider->getImagesByName('credits-1');

        $this->assertSame([], $image);
    }
}
