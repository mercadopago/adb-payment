<?php

namespace MercadoPago\AdbPayment\Tests\Unit\Model\Metrics;

use PHPUnit\Framework\TestCase;
use MercadoPago\AdbPayment\Model\Metrics\Config;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\Module\ModuleListInterface;
use Magento\Framework\Module\ResourceInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Store\Api\Data\StoreInterface;

class ConfigTest extends TestCase
{
    private $productMetadata;
    private $resourceModule;
    private $storeManager;
    private $store;
    private $moduleList;

    protected function setUp(): void
    {
        $this->productMetadata = $this->createMock(ProductMetadataInterface::class);
        $this->resourceModule = $this->createMock(ResourceInterface::class);
        $this->storeManager = $this->createMock(StoreManagerInterface::class);
        $this->store = $this->createMock(StoreInterface::class);
        $this->moduleList = $this->createMock(ModuleListInterface::class);
    }

    public function testGetModuleVersionReturnsVersion()
    {
        $this->resourceModule->expects($this->once())
            ->method('getDbVersion')
            ->with('MercadoPago_AdbPayment')
            ->willReturn('1.13.0');

        $config = new Config(
            $this->productMetadata,
            $this->resourceModule,
            $this->storeManager,
            $this->moduleList
        );

        $this->assertEquals('1.13.0', $config->getModuleVersion());
    }

    public function testGetModuleVersionReturnsVersionFromModuleListWhenDbVersionIsNull()
    {
        $this->resourceModule->expects($this->once())
            ->method('getDbVersion')
            ->with('MercadoPago_AdbPayment')
            ->willReturn(null);

        $this->moduleList->expects($this->once())
            ->method('getOne')
            ->with('MercadoPago_AdbPayment')
            ->willReturn(['setup_version' => '1.13.0']);

        $config = new Config(
            $this->productMetadata,
            $this->resourceModule,
            $this->storeManager,
            $this->moduleList
        );

        $this->assertEquals('1.13.0', $config->getModuleVersion());
    }

    public function testGetModuleVersionReturnsNullWhenBothAreNull()
    {
        $this->resourceModule->expects($this->once())
            ->method('getDbVersion')
            ->with('MercadoPago_AdbPayment')
            ->willReturn(null);

        $this->moduleList->expects($this->once())
            ->method('getOne')
            ->with('MercadoPago_AdbPayment')
            ->willReturn(null);

        $config = new Config(
            $this->productMetadata,
            $this->resourceModule,
            $this->storeManager,
            $this->moduleList
        );

        $this->assertNull($config->getModuleVersion());
    }

    public function testGetModuleVersionReturnsNullWhenModuleInfoHasNoSetupVersion()
    {
        $this->resourceModule->expects($this->once())
            ->method('getDbVersion')
            ->with('MercadoPago_AdbPayment')
            ->willReturn(null);

        $this->moduleList->expects($this->once())
            ->method('getOne')
            ->with('MercadoPago_AdbPayment')
            ->willReturn(['name' => 'MercadoPago_AdbPayment']);

        $config = new Config(
            $this->productMetadata,
            $this->resourceModule,
            $this->storeManager,
            $this->moduleList
        );

        $this->assertNull($config->getModuleVersion());
    }

    public function testGetModuleVersionReturnsNullWhenModuleInfoIsEmptyArray()
    {
        $this->resourceModule->expects($this->once())
            ->method('getDbVersion')
            ->with('MercadoPago_AdbPayment')
            ->willReturn(null);

        $this->moduleList->expects($this->once())
            ->method('getOne')
            ->with('MercadoPago_AdbPayment')
            ->willReturn([]);

        $config = new Config(
            $this->productMetadata,
            $this->resourceModule,
            $this->storeManager,
            $this->moduleList
        );

        $this->assertNull($config->getModuleVersion());
    }

    public function testGetMagentoVersionReturnsVersion()
    {
        $this->productMetadata->expects($this->once())
            ->method('getVersion')
            ->willReturn('2.4.6');

        $config = new Config(
            $this->productMetadata,
            $this->resourceModule,
            $this->storeManager,
            $this->moduleList
        );

        $this->assertEquals('2.4.6', $config->getMagentoVersion());
    }

    public function testGetBaseUrlReturnsUrl()
    {
        $store = $this->getMockBuilder(\Magento\Store\Model\Store::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getBaseUrl'])
            ->getMock();

        $store->expects($this->once())
            ->method('getBaseUrl')
            ->willReturn('https://example.com/');

        $this->storeManager->expects($this->once())
            ->method('getStore')
            ->willReturn($store);

        $config = new Config(
            $this->productMetadata,
            $this->resourceModule,
            $this->storeManager,
            $this->moduleList
        );

        $this->assertEquals('https://example.com/', $config->getBaseUrl());
    }

    public function testGetBaseUrlReturnsEmptyStringOnException()
    {
        $this->storeManager->expects($this->once())
            ->method('getStore')
            ->willThrowException(new \Exception('Store not found'));

        $config = new Config(
            $this->productMetadata,
            $this->resourceModule,
            $this->storeManager,
            $this->moduleList
        );

        $this->assertEquals('', $config->getBaseUrl());
    }

    public function testGetLocationReturnsFormattedString()
    {
        // Set environment variable
        putenv('DD_REGION=us');

        $store = $this->getMockBuilder(\Magento\Store\Model\Store::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getCode', 'getCurrentCurrencyCode'])
            ->getMock();

        $store->expects($this->once())
            ->method('getCode')
            ->willReturn('default');

        $store->expects($this->once())
            ->method('getCurrentCurrencyCode')
            ->willReturn('BRL');

        $this->storeManager->expects($this->once())
            ->method('getStore')
            ->willReturn($store);

        $config = new Config(
            $this->productMetadata,
            $this->resourceModule,
            $this->storeManager,
            $this->moduleList
        );

        $location = $config->getLocation();

        $this->assertEquals('us_default_BRL', $location);

        // Clean up
        putenv('DD_REGION');
    }

    public function testGetLocationReturnsUnknownWhenRegionNotSet()
    {
        putenv('DD_REGION');

        $store = $this->getMockBuilder(\Magento\Store\Model\Store::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getCode', 'getCurrentCurrencyCode'])
            ->getMock();

        $store->expects($this->once())
            ->method('getCode')
            ->willReturn('default');

        $store->expects($this->once())
            ->method('getCurrentCurrencyCode')
            ->willReturn('BRL');

        $this->storeManager->expects($this->once())
            ->method('getStore')
            ->willReturn($store);

        $config = new Config(
            $this->productMetadata,
            $this->resourceModule,
            $this->storeManager,
            $this->moduleList
        );

        $location = $config->getLocation();

        $this->assertEquals('unknown_default_BRL', $location);
    }

    public function testGetLocationReturnsNullOnException()
    {
        $this->storeManager->expects($this->once())
            ->method('getStore')
            ->willThrowException(new \Exception('Store not found'));

        $config = new Config(
            $this->productMetadata,
            $this->resourceModule,
            $this->storeManager,
            $this->moduleList
        );

        $this->assertNull($config->getLocation());
    }
}

