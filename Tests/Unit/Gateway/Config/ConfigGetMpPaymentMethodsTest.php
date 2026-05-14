<?php
/**
 * Copyright © MercadoPago. All rights reserved.
 *
 * @author      Mercado Pago
 * @license     See LICENSE for license details.
 */

namespace MercadoPago\AdbPayment\Tests\Unit\Gateway\Config;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\Module\ModuleListInterface;
use Magento\Framework\Module\ResourceInterface;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Payment\Model\Method\Logger;
use MercadoPago\AdbPayment\Gateway\Config\Config;
use MercadoPago\AdbPayment\Tests\Unit\Gateway\Http\Client\Order\FakeHttpClient;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for Config::getMpPaymentMethods.
 */
class ConfigGetMpPaymentMethodsTest extends TestCase
{
    /**
     * @var Config|\PHPUnit\Framework\MockObject\MockObject
     */
    private $config;

    /**
     * @var Logger|\PHPUnit\Framework\MockObject\MockObject
     */
    private $logger;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        if (!class_exists(\MercadoPago\PP\Sdk\HttpClient\HttpClient::class, false)) {
            class_alias(FakeHttpClient::class, \MercadoPago\PP\Sdk\HttpClient\HttpClient::class);
        }

        FakeHttpClient::reset();

        $this->logger = $this->createMock(Logger::class);

        $this->config = $this->getMockBuilder(Config::class)
            ->setConstructorArgs([
                $this->createMock(ProductMetadataInterface::class),
                $this->createMock(ResourceInterface::class),
                $this->createMock(ScopeConfigInterface::class),
                $this->logger,
                $this->createMock(Json::class),
                $this->createMock(ModuleListInterface::class),
            ])
            ->onlyMethods([
                'getEnvironmentMode',
                'getApiUrl',
                'getMerchantGatewayClientId',
                'getClientHeadersNoAuthMpPluginsPhpSdk',
            ])
            ->getMock();

        $this->config->method('getApiUrl')->willReturn('https://api.mercadopago.com');
        $this->config->method('getMerchantGatewayClientId')->willReturn('TEST-public-key');
        $this->config->method('getClientHeadersNoAuthMpPluginsPhpSdk')->willReturn([
            'x-product-id: BC32CANTRPP001U8NHO0',
            'x-platform-id: BP1EF6QIC4P001KBGQ10',
            'x-integrator-id: ',
        ]);
    }

    /**
     * @return void
     */
    public function testGetMpPaymentMethodsUsesBetaUriInSandbox(): void
    {
        $this->config->method('getEnvironmentMode')->willReturn(Config::ENVIRONMENT_SANDBOX);
        FakeHttpClient::$mockResponse = [['id' => 'pix', 'name' => 'Pix']];

        $result = $this->config->getMpPaymentMethods();

        $this->assertTrue($result['success']);
        $this->assertStringContainsString('/ppcore/beta/', FakeHttpClient::$captured['uri']);
    }

    /**
     * @return void
     */
    public function testGetMpPaymentMethodsUsesProdUriInProduction(): void
    {
        $this->config->method('getEnvironmentMode')->willReturn(Config::ENVIRONMENT_PRODUCTION);
        FakeHttpClient::$mockResponse = [['id' => 'pix', 'name' => 'Pix']];

        $result = $this->config->getMpPaymentMethods();

        $this->assertTrue($result['success']);
        $this->assertStringContainsString('/ppcore/prod/', FakeHttpClient::$captured['uri']);
    }

    /**
     * @return void
     */
    public function testGetMpPaymentMethodsReturnsCoreEndpoint(): void
    {
        $this->config->method('getEnvironmentMode')->willReturn(Config::ENVIRONMENT_PRODUCTION);
        FakeHttpClient::$mockResponse = [['id' => 'visa', 'name' => 'Visa']];

        $this->config->getMpPaymentMethods();

        $this->assertSame(
            '/ppcore/prod/payment-methods/v1/payment-methods',
            FakeHttpClient::$captured['uri']
        );
    }

    /**
     * @return void
     */
    public function testGetMpPaymentMethodsReturnsFailureOnHttpError(): void
    {
        $this->config->method('getEnvironmentMode')->willReturn(Config::ENVIRONMENT_PRODUCTION);
        FakeHttpClient::$mockResponse = ['message' => 'Unauthorized'];
        FakeHttpClient::$mockStatus = 401;

        $this->logger->expects($this->once())->method('debug');

        $result = $this->config->getMpPaymentMethods();

        $this->assertFalse($result['success']);
    }

    /**
     * @return void
     */
    public function testGetMpPaymentMethodsReturnsFailureOnException(): void
    {
        $this->config->method('getEnvironmentMode')->willReturn(Config::ENVIRONMENT_PRODUCTION);
        FakeHttpClient::$throwException = true;

        $this->logger->expects($this->once())->method('debug');

        $result = $this->config->getMpPaymentMethods();

        $this->assertFalse($result['success']);
        $this->assertSame('Connection error', $result['error']);
    }
}
