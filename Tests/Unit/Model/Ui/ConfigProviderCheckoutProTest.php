<?php

namespace Tests\Unit\Model\Ui;

use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Framework\Escaper;
use Magento\Framework\View\Asset\Source;
use Magento\Payment\Model\CcConfig;
use Magento\Quote\Api\Data\CartInterface;
use MercadoPago\AdbPayment\Gateway\Config\ConfigCheckoutPro;
use MercadoPago\AdbPayment\Model\Ui\ConfigProviderCheckoutPro;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ConfigProviderCheckoutProTest extends TestCase
{
    /**
     * storeId method.
     */
    public const STOREID_METHOD = 'getStoreId';

    /**
     * asset method.
     */
    public const ASSET_METHOD = 'createAsset';

    /**
     * url method.
     */
    public const URL_METHOD = 'getUrl';

    /**
     * source method.
     */
    public const SOURCE_METHOD = 'findSource';

    /**
     * base url.
     */
    public const BASE_URL = 'https://example.com/';

    /**
     * @var ConfigProviderCheckoutPro
     */
    private $configProvider;

    /**
     * @var ConfigCheckoutPro|MockObject
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

    protected function setUp(): void
    {
        $this->config = $this->createMock(ConfigCheckoutPro::class);
        $this->cart = $this->createMock(CartInterface::class);
        $this->ccConfig = $this->createMock(CcConfig::class);
        $this->escaper = $this->createMock(Escaper::class);
        $this->assetSource = $this->createMock(Source::class);

        $this->configProvider = new ConfigProviderCheckoutPro(
            $this->config,
            $this->cart,
            $this->ccConfig,
            $this->escaper,
            $this->assetSource,
        );
    }

    public function testGetIcons()
    {
        $storeId = 1;
        $choProTypes = 'visa,mastercard';
        $expectedIcons = [
            'visa' => [
                'url'    => 'https://example.com/visa.svg',
                'code'   => 'visa',
                'width'  => '32px',
                'height' => '24px',
                'title'  => 'visa',
                'alt'    => 'visa',
            ],
            'mastercard' => [
                'url'    => 'https://example.com/mastercard.svg',
                'code'   => 'mastercard',
                'width'  => '32px',
                'height' => '24px',
                'title'  => 'mastercard',
                'alt'    => 'mastercard',
            ],
        ];

        $this->cart->method(self::STOREID_METHOD)
            ->willReturn($storeId);

        $this->config->method('getChoProAvailableTypes')
            ->with($storeId)
            ->willReturn($choProTypes);

        $this->ccConfig->method(self::ASSET_METHOD)
            ->willReturnCallback(function ($path) {
                $assetMock = $this->createMock(\Magento\Framework\View\Asset\File::class);
                $assetMock->method(self::URL_METHOD)
                    ->willReturn(self::BASE_URL . basename($path));
                return $assetMock;
            });

        $this->assetSource->method(self::SOURCE_METHOD)
            ->willReturn(true);

        $result = $this->configProvider->getIcons();

        $this->assertEquals($expectedIcons, $result);
    }

    public function testGetChoProInfoIcons()
    {
        $storeId = 1;
        $choproIcons = 'visa,mastercard';
        $expectedIcons = [
            'visa' => [
                'url'    => 'https://example.com/visa.svg',
                'code'   => 'visa',
                'width'  => '16px',
                'height' => '16px',
                'title'  => 'visa',
            ],
            'mastercard' => [
                'url'    => 'https://example.com/mastercard.svg',
                'code'   => 'mastercard',
                'width'  => '16px',
                'height' => '16px',
                'title'  => 'mastercard',
            ],
        ];

        $this->cart->method(self::STOREID_METHOD)
            ->willReturn($storeId);

        $this->config->method('getChoProInfoIcons')
            ->with($storeId)
            ->willReturn($choproIcons);

        $this->ccConfig->method(self::ASSET_METHOD)
            ->willReturnCallback(function ($path) {
                $assetMock = $this->createMock(\Magento\Framework\View\Asset\File::class);
                $assetMock->method(self::URL_METHOD)
                    ->willReturn(self::BASE_URL . basename($path));
                return $assetMock;
            });

        $this->assetSource->method(self::SOURCE_METHOD)
            ->willReturn(true);

        $result = $this->configProvider->getChoProInfoIcons();

        $this->assertEquals($expectedIcons, $result);
    }

}
