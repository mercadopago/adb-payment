<?php

namespace Tests\Unit\Model\Ui;

use PHPUnit\Framework\TestCase;
use MercadoPago\PaymentMagento\Tests\Unit\Mocks\PaymentsMethodsActiveMock;

use Magento\Framework\Escaper;
use Magento\Framework\View\Asset\Source;
use Magento\Payment\Model\CcConfig;
use Magento\Quote\Api\Data\CartInterface;
use MercadoPago\PaymentMagento\Gateway\Config\ConfigPaymentMethodsOff;
use MercadoPago\PaymentMagento\Gateway\Config\Config as MercadoPagoConfig;

use MercadoPago\PaymentMagento\Model\Ui\ConfigProviderPaymentMethodsOff;

class ConfigProviderPaymentMethodsOffTest extends TestCase {
    
    /**
     * @var configProviderPaymentMethodsOffMock
     */
    private $configProviderPaymentMethodsOffMock;

        /**
     * @var ConfigPaymentMethodsOff
     */
    private $configMock;

    /**
     * @var CartInterface
     */
    private $cartMock;

    /**
     * @var CcConfig
     */
    private $ccConfigMock;

     /**
     * @var MercadoPagoConfig
     */
    private $mercadopagoConfigMock;

    /**
     * @var Escaper
     */
    private $escaperMock;

    /**
     * @var Source
     */
    private $assetSourceMock;

    public function setUp(): void
    {
        $this->configMock = $this->getMockBuilder(ConfigPaymentMethodsOff::class)->disableOriginalConstructor()->getMock();
        $this->cartMock = $this->getMockBuilder(CartInterface::class)->disableOriginalConstructor()->getMock();
        $this->escaperMock = $this->getMockBuilder(Escaper::class)->disableOriginalConstructor()->getMock();
        $this->ccConfigMock = $this->getMockBuilder(CcConfig::class)->disableOriginalConstructor()->getMock();
        $this->assetSourceMock = $this->getMockBuilder(Source::class)->disableOriginalConstructor()->getMock();
        $this->mercadopagoConfigMock = $this->getMockBuilder(MercadoPagoConfig::class)->disableOriginalConstructor()->getMock();

        $this->configProviderPaymentMethodsOffMock = $this->getMockBuilder(ConfigProviderPaymentMethodsOff::class)->setConstructorArgs([
            'config' => $this->configMock,
            'cart' => $this->cartMock,
            'ccConfig' => $this->ccConfigMock,
            'escaper' => $this->escaperMock,
            'assetSource' => $this->assetSourceMock,
            'mercadopagoConfig' => $this->mercadopagoConfigMock
        ])->getMock();
    }

    public function test_filterPaymentMethods_ticket_return_success(): void
    {
        $configProviderPaymentMethodsOff = new ConfigProviderPaymentMethodsOff(
            $this->configMock, 
            $this->cartMock, 
            $this->ccConfigMock, 
            $this->escaperMock, 
            $this->assetSourceMock, 
            $this->mercadopagoConfigMock
        );

        $result_filter = $configProviderPaymentMethodsOff->filterPaymentMethods(PaymentsMethodsActiveMock::PAYMENT_METHODS_OFF_TICKET);

        $this->assertEquals(PaymentsMethodsActiveMock::EXPECTED_TICKET, $result_filter);
    }
}