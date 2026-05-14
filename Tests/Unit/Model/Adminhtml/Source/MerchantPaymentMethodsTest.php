<?php
/**
 * Copyright © MercadoPago. All rights reserved.
 *
 * @author      Mercado Pago
 * @license     See LICENSE for license details.
 */

namespace MercadoPago\AdbPayment\Tests\Unit\Model\Adminhtml\Source;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Payment\Model\Method\Logger;
use MercadoPago\AdbPayment\Gateway\Config\Config;
use MercadoPago\AdbPayment\Model\Adminhtml\Source\MerchantPaymentMethods;
use MercadoPago\AdbPayment\Tests\Unit\Gateway\Http\Client\Order\FakeHttpClient;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for MerchantPaymentMethods.
 */
class MerchantPaymentMethodsTest extends TestCase
{
    /**
     * @var Logger|\PHPUnit\Framework\MockObject\MockObject
     */
    private $logger;

    /**
     * @var Config|\PHPUnit\Framework\MockObject\MockObject
     */
    private $mercadopagoConfig;

    /**
     * @var Json|\PHPUnit\Framework\MockObject\MockObject
     */
    private $json;

    /**
     * @var RequestInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $request;

    /**
     * @var MerchantPaymentMethods
     */
    private $merchantPaymentMethods;

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
        $this->mercadopagoConfig = $this->createMock(Config::class);
        $this->json = $this->createMock(Json::class);
        $this->request = $this->createMock(RequestInterface::class);

        $this->request->method('getParam')->with('store', 0)->willReturn(0);
        $this->mercadopagoConfig->method('getApiUrl')->willReturn('https://api.mercadopago.com');
        $this->mercadopagoConfig->method('getMerchantGatewayClientId')->willReturn('TEST-public-key');
        $this->mercadopagoConfig->method('getClientHeadersNoAuthMpPluginsPhpSdk')->willReturn([
            'x-product-id: BC32CANTRPP001U8NHO0',
            'x-platform-id: BP1EF6QIC4P001KBGQ10',
            'x-integrator-id: ',
        ]);

        $this->merchantPaymentMethods = new MerchantPaymentMethods(
            $this->logger,
            $this->mercadopagoConfig,
            $this->json,
            $this->request
        );
    }

    /**
     * @return void
     */
    public function testGetAllPaymentMethodsUsesBetaUriInSandbox(): void
    {
        $this->mercadopagoConfig->method('getEnvironmentMode')->willReturn(Config::ENVIRONMENT_SANDBOX);
        FakeHttpClient::$mockResponse = [['id' => 'pix', 'name' => 'Pix']];

        $result = $this->merchantPaymentMethods->getAllPaymentMethods();

        $this->assertTrue($result['success']);
        $this->assertStringContainsString('/ppcore/beta/', FakeHttpClient::$captured['uri']);
    }

    /**
     * @return void
     */
    public function testGetAllPaymentMethodsUsesProdUriInProduction(): void
    {
        $this->mercadopagoConfig->method('getEnvironmentMode')->willReturn(Config::ENVIRONMENT_PRODUCTION);
        FakeHttpClient::$mockResponse = [['id' => 'visa', 'name' => 'Visa']];

        $result = $this->merchantPaymentMethods->getAllPaymentMethods();

        $this->assertTrue($result['success']);
        $this->assertStringContainsString('/ppcore/prod/', FakeHttpClient::$captured['uri']);
    }

    /**
     * @return void
     */
    public function testToOptionArrayReturnsFourOptionsForThreeMethods(): void
    {
        $this->mercadopagoConfig->method('getEnvironmentMode')->willReturn(Config::ENVIRONMENT_PRODUCTION);
        FakeHttpClient::$mockResponse = [
            ['id' => 'credit_card', 'name' => 'Credit Card'],
            ['id' => 'pix', 'name' => 'Pix'],
            ['id' => 'bolbradesco', 'name' => 'Boleto'],
        ];

        $options = $this->merchantPaymentMethods->toOptionArray();

        $this->assertCount(4, $options);
        $this->assertNull($options[0]['value']);
        $this->assertSame('credit_card', $options[1]['value']);
        $this->assertSame('pix', $options[2]['value']);
        $this->assertSame('bolbradesco', $options[3]['value']);
    }

    /**
     * @return void
     */
    public function testGetAllPaymentMethodsReturnsFailureOnException(): void
    {
        $this->mercadopagoConfig->method('getEnvironmentMode')->willReturn(Config::ENVIRONMENT_PRODUCTION);
        FakeHttpClient::$throwException = true;

        $this->logger->expects($this->once())->method('debug')
            ->with(['error' => 'Connection error']);

        $result = $this->merchantPaymentMethods->getAllPaymentMethods();

        $this->assertFalse($result['success']);
    }

    /**
     * @return void
     */
    public function testGetAllPaymentMethodsReturnsFailureWhenResponseHasError(): void
    {
        $this->mercadopagoConfig->method('getEnvironmentMode')->willReturn(Config::ENVIRONMENT_PRODUCTION);
        FakeHttpClient::$mockResponse = ['error' => 'unauthorized'];

        $result = $this->merchantPaymentMethods->getAllPaymentMethods();

        $this->assertFalse($result['success']);
    }

    /**
     * @return void
     */
    public function testGetAllPaymentMethodsReturnsFailureOnHttpErrorStatus(): void
    {
        $this->mercadopagoConfig->method('getEnvironmentMode')->willReturn(Config::ENVIRONMENT_PRODUCTION);
        FakeHttpClient::$mockResponse = ['message' => 'Unauthorized'];
        FakeHttpClient::$mockStatus = 401;

        $this->logger->expects($this->once())->method('debug');

        $result = $this->merchantPaymentMethods->getAllPaymentMethods();

        $this->assertFalse($result['success']);
    }
}
